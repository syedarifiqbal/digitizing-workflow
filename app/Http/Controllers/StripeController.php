<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeController extends Controller
{
    // ─── Admin Checkout ───────────────────────────────────────────────────────

    /**
     * Initiate Stripe Checkout for an invoice (admin/staff).
     * Redirects to Stripe hosted page, or returns JSON client_secret for embedded.
     */
    public function checkout(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $invoice);

        $stripe = $this->resolveStripeService($request);

        abort_if(! $stripe->isEnabled(), 403, 'Stripe payments are not enabled.');
        abort_if(! $stripe->allowAdminPayment(), 403, 'Admin payments are not enabled for this tenant.');
        abort_unless($this->invoiceIsPayable($invoice), 422, 'This invoice cannot be paid.');

        $invoice->load('client', 'items');

        return $this->initiateCheckout($stripe, $invoice, $request, isClient: false);
    }

    // ─── Client Portal Checkout ───────────────────────────────────────────────

    /**
     * Initiate Stripe Checkout for an invoice (client portal).
     */
    public function clientCheckout(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $user   = $request->user();
        $client = $user->client()->withTrashed()->first();

        abort_if(! $client, 403);
        abort_if($invoice->client_id !== $client->id, 403);
        abort_if($invoice->tenant_id !== $user->tenant_id, 403);
        abort_if($invoice->status === InvoiceStatus::DRAFT, 403);

        $stripe = $this->resolveStripeService($request);

        abort_if(! $stripe->isEnabled(), 403, 'Stripe payments are not enabled.');
        abort_unless($this->invoiceIsPayable($invoice), 422, 'This invoice cannot be paid.');

        $invoice->load('client', 'items');

        return $this->initiateCheckout($stripe, $invoice, $request, isClient: true);
    }

    // ─── Success / Cancel redirects ───────────────────────────────────────────

    public function success(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Payment completed successfully. The invoice will be marked paid shortly.');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('error', 'Payment was cancelled.');
    }

    public function clientSuccess(Request $request, Invoice $invoice): RedirectResponse
    {
        return redirect()->route('client.invoices.show', $invoice)
            ->with('success', 'Payment completed successfully. Your invoice will be marked paid shortly.');
    }

    public function clientCancel(Request $request, Invoice $invoice): RedirectResponse
    {
        return redirect()->route('client.invoices.show', $invoice)
            ->with('error', 'Payment was cancelled.');
    }

    // ─── Webhook (no auth, no CSRF) ───────────────────────────────────────────

    public function webhook(Request $request, string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $stripe = new StripeService($tenant);

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = $stripe->constructWebhookEvent($payload, $sigHeader);
        } catch (\Exception $e) {
            return response('Webhook signature verification failed.', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->handleCheckoutCompleted($tenant, $session);
        }

        return response('OK', 200);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveStripeService(Request $request): StripeService
    {
        return new StripeService($request->user()->tenant);
    }

    private function initiateCheckout(
        StripeService $stripe,
        Invoice $invoice,
        Request $request,
        bool $isClient
    ): RedirectResponse|JsonResponse {
        $mode = $stripe->getCheckoutMode();

        if ($isClient) {
            $successUrl = route('stripe.client.success', $invoice);
            $cancelUrl  = route('stripe.client.cancel', $invoice);
        } else {
            $successUrl = route('stripe.success', $invoice);
            $cancelUrl  = route('stripe.cancel', $invoice);
        }

        // For embedded mode, success_url is the return_url (Stripe appends ?session_id={CHECKOUT_SESSION_ID})
        $session = $stripe->createCheckoutSession(
            $invoice,
            $mode === 'embedded' ? $successUrl . '?session_id={CHECKOUT_SESSION_ID}' : $successUrl,
            $cancelUrl,
            $mode
        );

        if ($mode === 'embedded') {
            return response()->json([
                'client_secret'   => $session->client_secret,
                'publishable_key' => $stripe->getPublishableKey(),
            ]);
        }

        return redirect()->away($session->url);
    }

    private function invoiceIsPayable(Invoice $invoice): bool
    {
        return in_array($invoice->status, [
            InvoiceStatus::SENT,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::OVERDUE,
        ]);
    }

    private function handleCheckoutCompleted(Tenant $tenant, object $session): void
    {
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('stripe_session_id', $session->id)
            ->first();

        if (! $invoice) {
            return;
        }

        // Idempotency: skip if already paid via this session
        if ($invoice->status === InvoiceStatus::PAID) {
            return;
        }

        $amountPaid = ($session->amount_total ?? 0) / 100;

        // Record the payment
        $invoice->payments()->create([
            'tenant_id'      => $invoice->tenant_id,
            'amount'         => $amountPaid,
            'payment_method' => 'Stripe',
            'payment_date'   => now()->toDateString(),
            'reference'      => $session->payment_intent ?? $session->id,
            'notes'          => 'Paid via Stripe Checkout',
            'recorded_by'    => null,
        ]);

        // Mark invoice paid
        $invoice->update([
            'status'  => InvoiceStatus::PAID,
            'paid_at' => now(),
        ]);

        $invoice->logActivity(
            'paid',
            "Invoice paid via Stripe. Amount: {$amountPaid} {$invoice->currency}. Session: {$session->id}"
        );
    }
}
