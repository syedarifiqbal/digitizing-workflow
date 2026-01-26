<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderComment>
 */
class OrderCommentFactory extends Factory
{
    protected $model = OrderComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'visibility' => 'client',
            'body' => fake()->paragraph(),
        ];
    }

    /**
     * Indicate that the comment is internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'internal',
        ]);
    }

    /**
     * Indicate that the comment is client-visible.
     */
    public function clientVisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'client',
        ]);
    }
}
