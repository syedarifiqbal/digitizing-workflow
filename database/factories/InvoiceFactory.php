<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 2000);
        $taxRate = fake()->randomElement([0, 5, 7.5, 10]);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $discount = fake()->boolean(30) ? round($subtotal * 0.05, 2) : 0;
        $total = max($subtotal + $taxAmount - $discount, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 99999),
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(10000, 99999),
            'status' => InvoiceStatus::DRAFT,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'currency' => 'USD',
            'notes' => fake()->optional()->sentence(),
            'payment_terms' => 'Net 30',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::DRAFT,
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::SENT,
            'sent_at' => now(),
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::PAID,
            'sent_at' => now()->subDays(7),
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::OVERDUE,
            'issue_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(15)->toDateString(),
            'sent_at' => now()->subDays(45),
            'paid_at' => null,
        ]);
    }

    public function partiallyPaid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::PARTIALLY_PAID,
            'sent_at' => now()->subDays(14),
            'paid_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::CANCELLED,
        ]);
    }

    public function void(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::VOID,
            'sent_at' => now()->subDays(7),
        ]);
    }
}
