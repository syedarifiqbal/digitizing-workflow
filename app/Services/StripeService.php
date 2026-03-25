<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeService
{
    public function __construct(private Tenant $tenant) {}

    // ─── Feature flags ────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return (bool) $this->tenant->getSetting('stripe_enabled', false)
            && $this->getSecretKey() !== null
            && $this->getPublishableKey() !== null;
    }

    public function getCheckoutMode(): string
    {
        return $this->tenant->getSetting('stripe_checkout_mode', 'hosted');
    }

    public function allowAdminPayment(): bool
    {
        return (bool) $this->tenant->getSetting('stripe_allow_admin_payment', false);
    }

    // ─── Key accessors (decrypt on read) ─────────────────────────────────────

    public function getPublishableKey(): ?string
    {
        return $this->decrypt($this->tenant->getSetting('stripe_publishable_key_enc'));
    }

    public function getSecretKey(): ?string
    {
        return $this->decrypt($this->tenant->getSetting('stripe_secret_key_enc'));
    }

    public function getWebhookSecret(): ?string
    {
        return $this->decrypt($this->tenant->getSetting('stripe_webhook_secret_enc'));
    }

    public function hasPublishableKey(): bool
    {
        return ! empty($this->tenant->getSetting('stripe_publishable_key_enc'));
    }

    public function hasSecretKey(): bool
    {
        return ! empty($this->tenant->getSetting('stripe_secret_key_enc'));
    }

    public function hasWebhookSecret(): bool
    {
        return ! empty($this->tenant->getSetting('stripe_webhook_secret_enc'));
    }

    // ─── Encryption helpers (static so controller can use before instantiation)

    public static function encryptKey(string $value): string
    {
        return Crypt::encryptString($value);
    }

    private function decrypt(?string $encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception) {
            return null;
        }
    }

    // ─── Stripe client ────────────────────────────────────────────────────────

    public function client(): StripeClient
    {
        $key = $this->getSecretKey();

        if (! $key) {
            throw new \RuntimeException('Stripe secret key is not configured for this tenant.');
        }

        return new StripeClient($key);
    }

    // ─── Checkout session ─────────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session.
     *
     * @param  Invoice  $invoice
     * @param  string   $successUrl   URL after payment (hosted) or return_url (embedded)
     * @param  string   $cancelUrl    URL on cancel (hosted only)
     * @param  string   $mode         'hosted' or 'embedded'
     * @return \Stripe\Checkout\Session
     */
    public function createCheckoutSession(
        Invoice $invoice,
        string $successUrl,
        string $cancelUrl,
        string $mode = 'hosted'
    ): \Stripe\Checkout\Session {
        $stripe = $this->client();

        $lineItems = $this->buildLineItems($invoice);

        $params = [
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'currency'             => strtolower($invoice->currency ?? 'usd'),
            'metadata'             => [
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'tenant_id'      => $invoice->tenant_id,
            ],
            'customer_email' => $invoice->client?->email,
        ];

        if ($mode === 'embedded') {
            $params['ui_mode']   = 'embedded';
            $params['return_url'] = $successUrl;
        } else {
            $params['success_url'] = $successUrl;
            $params['cancel_url']  = $cancelUrl;
        }

        $session = $stripe->checkout->sessions->create($params);

        // Store session ID on invoice for webhook lookup
        $invoice->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    /**
     * Construct and verify a Stripe webhook event.
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        $secret = $this->getWebhookSecret();

        if (! $secret) {
            throw new \RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $sigHeader, $secret);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function buildLineItems(Invoice $invoice): array
    {
        $items = $invoice->relationLoaded('items') ? $invoice->items : $invoice->items()->get();

        if ($items->isEmpty()) {
            // Fallback: single line item for the invoice total
            return [[
                'price_data' => [
                    'currency'     => strtolower($invoice->currency ?? 'usd'),
                    'unit_amount'  => (int) round((float) $invoice->total_amount * 100),
                    'product_data' => [
                        'name' => "Invoice {$invoice->invoice_number}",
                    ],
                ],
                'quantity' => 1,
            ]];
        }

        return $items->map(fn ($item) => [
            'price_data' => [
                'currency'     => strtolower($invoice->currency ?? 'usd'),
                'unit_amount'  => (int) round((float) $item->unit_price * 100),
                'product_data' => [
                    'name' => $item->description,
                ],
            ],
            'quantity' => max(1, (int) $item->quantity),
        ])->values()->all();
    }
}
