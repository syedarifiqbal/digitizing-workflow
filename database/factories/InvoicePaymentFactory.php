<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoicePaymentFactory extends Factory
{
    protected $model = InvoicePayment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'payment_method' => fake()->randomElement(['ACH', 'Wire', 'Check', 'Credit Card', 'PayPal', 'Zelle']),
            'payment_date' => now()->toDateString(),
            'reference' => fake()->optional()->bothify('PAY-####-??'),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    public function fullPayment(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'amount' => (float) $invoice->total_amount,
        ]);
    }

    public function method(string $method): static
    {
        return $this->state(fn () => [
            'payment_method' => $method,
        ]);
    }
}
