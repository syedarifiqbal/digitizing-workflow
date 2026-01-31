<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $admin = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Manager']))
            ->first();

        if (! $admin) {
            $this->command->warn('No admin user found for InvoiceSeeder.');
            return;
        }

        $clients = Client::where('tenant_id', $tenant->id)->limit(5)->get();

        if ($clients->isEmpty()) {
            $this->command->warn('No clients found. Please run ClientSeeder first.');
            return;
        }

        $prefix = $tenant->getSetting('invoice_number_prefix', 'INV-');
        $sequence = 0;

        foreach ($clients->take(3) as $client) {
            $deliveredOrders = Order::where('tenant_id', $tenant->id)
                ->where('client_id', $client->id)
                ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
                ->where('is_invoiced', false)
                ->where(fn ($q) => $q->where('is_quote', false)->orWhereNull('is_quote'))
                ->limit(3)
                ->get();

            if ($deliveredOrders->isEmpty()) {
                continue;
            }

            $sequence++;
            $subtotal = $deliveredOrders->sum(fn ($o) => (float) ($o->price ?? 0));
            $taxRate = (float) $tenant->getSetting('default_tax_rate', 0);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = $subtotal + $taxAmount;

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'created_by' => $admin->id,
                'sequence' => $sequence,
                'invoice_number' => sprintf('%s%05d', $prefix, $sequence),
                'status' => InvoiceStatus::DRAFT,
                'issue_date' => now()->subDays(rand(1, 30))->toDateString(),
                'due_date' => now()->addDays(rand(5, 30))->toDateString(),
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $total,
                'currency' => $tenant->getSetting('currency', 'USD'),
                'payment_terms' => $tenant->getSetting('default_payment_terms', 'Net 30'),
            ]);

            foreach ($deliveredOrders as $order) {
                $invoice->items()->create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'description' => ($order->order_number ?? 'Order') . ' - ' . ($order->title ?? ''),
                    'quantity' => 1,
                    'unit_price' => (float) ($order->price ?? 0),
                    'amount' => (float) ($order->price ?? 0),
                ]);

                $order->update(['is_invoiced' => true, 'invoiced_at' => now()]);
            }
        }

        // Create a sent invoice with a partial payment
        $client = $clients->skip(1)->first() ?? $clients->first();
        $sequence++;
        $subtotal = 350.00;
        $taxRate = (float) $tenant->getSetting('default_tax_rate', 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $sentInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'sequence' => $sequence,
            'invoice_number' => sprintf('%s%05d', $prefix, $sequence),
            'status' => InvoiceStatus::PARTIALLY_PAID,
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $total,
            'currency' => $tenant->getSetting('currency', 'USD'),
            'payment_terms' => 'Net 30',
            'sent_at' => now()->subDays(20),
        ]);

        $sentInvoice->items()->create([
            'tenant_id' => $tenant->id,
            'description' => 'Custom digitizing project',
            'quantity' => 1,
            'unit_price' => 350.00,
            'amount' => 350.00,
        ]);

        $sentInvoice->payments()->create([
            'tenant_id' => $tenant->id,
            'amount' => 150.00,
            'payment_method' => 'ACH',
            'payment_date' => now()->subDays(10)->toDateString(),
            'reference' => 'ACH-001',
            'recorded_by' => $admin->id,
        ]);

        // Create a paid invoice
        $sequence++;
        $paidInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'created_by' => $admin->id,
            'sequence' => $sequence,
            'invoice_number' => sprintf('%s%05d', $prefix, $sequence),
            'status' => InvoiceStatus::PAID,
            'issue_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(15)->toDateString(),
            'subtotal' => 200.00,
            'tax_rate' => $taxRate,
            'tax_amount' => round(200 * ($taxRate / 100), 2),
            'discount_amount' => 0,
            'total_amount' => 200.00 + round(200 * ($taxRate / 100), 2),
            'currency' => $tenant->getSetting('currency', 'USD'),
            'payment_terms' => 'Net 30',
            'sent_at' => now()->subDays(45),
            'paid_at' => now()->subDays(30),
        ]);

        $paidInvoice->items()->create([
            'tenant_id' => $tenant->id,
            'description' => 'Vector conversion - logo redesign',
            'quantity' => 1,
            'unit_price' => 200.00,
            'amount' => 200.00,
        ]);

        $paidInvoice->payments()->create([
            'tenant_id' => $tenant->id,
            'amount' => $paidInvoice->total_amount,
            'payment_method' => 'Wire',
            'payment_date' => now()->subDays(30)->toDateString(),
            'reference' => 'WIRE-001',
            'notes' => 'Paid in full.',
            'recorded_by' => $admin->id,
        ]);

        $this->command->info("Created {$sequence} sample invoices.");
    }
}
