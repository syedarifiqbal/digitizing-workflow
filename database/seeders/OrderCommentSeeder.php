<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $orders = Order::where('tenant_id', $tenant->id)
            ->whereNotNull('designer_id')
            ->limit(20)
            ->get();

        $staffUsers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Manager', 'Designer', 'Sales']))
            ->get();

        if ($orders->isEmpty() || $staffUsers->isEmpty()) {
            $this->command->warn('Please ensure orders and staff users exist before seeding comments.');
            return;
        }

        foreach ($orders as $order) {
            $numComments = fake()->numberBetween(1, 5);

            for ($i = 0; $i < $numComments; $i++) {
                // Client comments
                if ($order->client->user) {
                    OrderComment::create([
                        'tenant_id' => $tenant->id,
                        'order_id' => $order->id,
                        'user_id' => $order->client->user->id,
                        'visibility' => 'client',
                        'body' => fake()->paragraph(),
                    ]);
                }

                // Staff comments (mix of client-visible and internal)
                OrderComment::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'user_id' => $staffUsers->random()->id,
                    'visibility' => fake()->randomElement(['client', 'internal']),
                    'body' => fake()->paragraph(),
                ]);
            }
        }

        $this->command->info('Created comments for orders.');
    }
}
