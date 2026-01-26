<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
            'settings' => [
                'order_number_prefix' => 'ORD-',
                'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
                'max_upload_mb' => 25,
                'sales_commission_earned_on' => 'delivered',
                'designer_bonus_earned_on' => 'delivered',
                'default_currency' => 'USD',
                'date_format' => 'M d, Y',
            ],
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
