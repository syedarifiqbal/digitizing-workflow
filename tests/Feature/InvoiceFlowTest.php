<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Notifications\InvoicePaymentRecordedNotification;
use App\Notifications\InvoiceSentNotification;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_send_pay_and_download_invoice_pdf(): void
    {
        Notification::fake();
        [$tenant, $admin] = $this->createTenantAdmin();

        $client = Client::factory()->for($tenant)->create();
        $order = Order::factory()
            ->for($tenant)
            ->for($client)
            ->delivered()
            ->state([
                'price' => 175.50,
                'is_invoiced' => false,
                'created_by' => $admin->id,
                'is_quote' => false,
            ])
            ->create();

        $invoiceResponse = $this->actingAs($admin)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'payment_terms' => 'Net 15',
            'tax_rate' => 5,
            'discount_amount' => 10,
            'currency' => 'USD',
            'notes' => 'Thank you for your business.',
            'order_ids' => [$order->id],
            'order_notes' => [$order->id => 'Include final artwork'],
            'custom_items' => [
                [
                    'description' => 'Digitizing setup fee',
                    'quantity' => 1,
                    'unit_price' => 25,
                ],
            ],
        ]);

        $invoiceResponse->assertRedirect(route('invoices.index'));

        $invoice = Invoice::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
        $this->assertSame(2, $invoice->items()->count());
        $this->assertTrue($order->fresh()->is_invoiced);

        $pdfResponse = response('PDF DATA', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice.pdf"',
        ]);

        $fakePdf = new class($pdfResponse) {
            public function __construct(private $response)
            {
            }

            public function download(string $filename)
            {
                return $this->response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }
        };

        $pdfService = Mockery::mock(InvoicePdfService::class);
        $pdfService
            ->shouldReceive('make')
            ->once()
            ->with(Mockery::on(fn ($argument) => $argument->is($invoice)))
            ->andReturn($fakePdf);
        app()->instance(InvoicePdfService::class, $pdfService);

        $sendResponse = $this->actingAs($admin)->post(route('invoices.send', $invoice), [
            'message' => 'Please remit payment within 15 days.',
            'attach_pdf' => false,
        ]);

        $sendResponse->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::SENT, $invoice->status);
        Notification::assertSentOnDemand(InvoiceSentNotification::class, function ($notification, $channels, $notifiable) use ($client) {
            return $notifiable->routes['mail'] === $client->email;
        });

        $paymentResponse = $this->actingAs($admin)->post(route('invoices.payments.store', $invoice), [
            'amount' => $invoice->total_amount,
            'payment_method' => 'ACH',
            'payment_date' => now()->toDateString(),
            'reference' => 'ACH-123',
            'notes' => 'Paid in full.',
        ]);

        $paymentResponse->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertGreaterThan(0, $invoice->payments()->count());
        Notification::assertSentOnDemand(InvoicePaymentRecordedNotification::class, function ($notification, $channels, $notifiable) use ($client) {
            return $notifiable->routes['mail'] === $client->email;
        });

        $downloadResponse = $this->actingAs($admin)->get(route('invoices.pdf', $invoice));
        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('Content-Type', 'application/pdf');
    }
}
