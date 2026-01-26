<?php

namespace Database\Factories;

use App\Enums\RoleType;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commission>
 */
class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseAmount = fake()->randomFloat(2, 10, 100);
        $extraAmount = fake()->randomFloat(2, 0, 20);

        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'role_type' => fake()->randomElement([RoleType::SALES, RoleType::DESIGNER]),
            'base_amount' => $baseAmount,
            'extra_amount' => $extraAmount,
            'total_amount' => $baseAmount + $extraAmount,
            'currency' => 'USD',
            'earned_on_status' => 'delivered',
            'earned_at' => now(),
            'notes' => fake()->optional()->sentence(),
            'rule_snapshot' => [
                'type' => 'percent',
                'percent_rate' => 10,
                'currency' => 'USD',
            ],
        ];
    }

    /**
     * Indicate that the commission is for sales.
     */
    public function forSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => RoleType::SALES,
        ]);
    }

    /**
     * Indicate that the commission is for designer.
     */
    public function forDesigner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => RoleType::DESIGNER,
        ]);
    }

    /**
     * Add extra amount (tip/bonus).
     */
    public function withTip(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'extra_amount' => $amount,
            'total_amount' => $attributes['base_amount'] + $amount,
            'notes' => "Includes USD " . number_format($amount, 2) . " tip from admin",
        ]);
    }
}
