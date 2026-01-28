<?php

namespace Database\Factories;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\CommissionRule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role_type' => fake()->randomElement([RoleType::SALES, RoleType::DESIGNER]),
            'type' => CommissionType::PERCENT,
            'fixed_amount' => null,
            'percent_rate' => fake()->randomFloat(2, 5, 20),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the rule is for sales.
     */
    public function forSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => RoleType::SALES,
        ]);
    }

    /**
     * Indicate that the rule is for designer.
     */
    public function forDesigner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => RoleType::DESIGNER,
        ]);
    }

    /**
     * Indicate that the rule is fixed amount.
     */
    public function fixed(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CommissionType::FIXED,
            'fixed_amount' => $amount,
            'percent_rate' => null,
        ]);
    }

    /**
     * Indicate that the rule is percentage.
     */
    public function percent(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CommissionType::PERCENT,
            'fixed_amount' => null,
            'percent_rate' => $rate,
        ]);
    }

    /**
     * Indicate that the rule is hybrid.
     */
    public function hybrid(float $fixedAmount, float $percentRate): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CommissionType::HYBRID,
            'fixed_amount' => $fixedAmount,
            'percent_rate' => $percentRate,
        ]);
    }

    /**
     * Indicate that the rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific order types.
     */
    public function forOrderTypes(array $types): static
    {
        return $this->state(fn (array $attributes) => [
            'order_types' => $types,
        ]);
    }
}
