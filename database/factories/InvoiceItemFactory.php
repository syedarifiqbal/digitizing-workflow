<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = 1;
        $unitPrice = fake()->randomFloat(2, 25, 500);

        return [
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'order_id' => null,
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => round($quantity * $unitPrice, 2),
            'note' => null,
        ];
    }

    public function forOrder(?Order $order = null): static
    {
        return $this->state(function () use ($order) {
            $order ??= Order::factory()->delivered()->create();
            $price = (float) ($order->price ?? fake()->randomFloat(2, 25, 500));

            return [
                'order_id' => $order->id,
                'description' => ($order->order_number ?? 'Order') . ' - ' . ($order->title ?? 'Digitizing'),
                'quantity' => 1,
                'unit_price' => $price,
                'amount' => $price,
            ];
        });
    }

    public function custom(): static
    {
        return $this->state(fn () => [
            'order_id' => null,
            'description' => fake()->randomElement([
                'Rush fee',
                'Setup charge',
                'Digitizing revision',
                'Color change fee',
                'Shipping & handling',
            ]),
            'quantity' => fake()->randomElement([1, 2]),
            'unit_price' => fake()->randomFloat(2, 10, 100),
        ])->afterMaking(function (InvoiceItem $item) {
            $item->amount = round((float) $item->quantity * (float) $item->unit_price, 2);
        })->afterCreating(function (InvoiceItem $item) {
            $item->update(['amount' => round((float) $item->quantity * (float) $item->unit_price, 2)]);
        });
    }
}
