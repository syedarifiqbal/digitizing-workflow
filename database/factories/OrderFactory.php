<?php

namespace Database\Factories;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'order_number' => 'ORD-' . fake()->unique()->numberBetween(10000, 99999),
            'sequence' => fake()->unique()->numberBetween(1, 10000),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'quantity' => fake()->numberBetween(1, 100),
            'priority' => fake()->randomElement([OrderPriority::NORMAL, OrderPriority::RUSH]),
            'type' => fake()->randomElement(['digitizing', 'vector', 'patch']),
            'is_quote' => fake()->boolean(20),
            'status' => OrderStatus::RECEIVED,
            'price' => fake()->optional()->randomFloat(2, 10, 500),
            'designer_id' => null,
            'sales_user_id' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the order is assigned to a designer.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::ASSIGNED,
            'designer_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the order is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::IN_PROGRESS,
            'designer_id' => User::factory(),
            'sales_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the order is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::SUBMITTED,
            'designer_id' => User::factory(),
            'sales_user_id' => User::factory(),
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED,
            'designer_id' => User::factory(),
            'sales_user_id' => User::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CLOSED,
            'designer_id' => User::factory(),
            'sales_user_id' => User::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'submitted_at' => now()->subDays(5),
            'approved_at' => now()->subDays(3),
            'delivered_at' => now()->subDays(2),
        ]);
    }

    /**
     * Indicate that the order is rush priority.
     */
    public function rush(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => OrderPriority::RUSH,
        ]);
    }

    /**
     * Set specific order type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
