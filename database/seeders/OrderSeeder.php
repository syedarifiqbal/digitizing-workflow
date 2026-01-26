<?php

namespace Database\Seeders;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
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

        $clients = Client::where('tenant_id', $tenant->id)->get();
        $designers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Designer'))
            ->get();
        $salesUsers = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Sales'))
            ->get();
        $admin = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
            ->first();

        if ($clients->isEmpty() || $designers->isEmpty() || $salesUsers->isEmpty()) {
            $this->command->warn('Please ensure clients, designers, and sales users exist before seeding orders.');
            return;
        }

        $orderTypes = ['digitizing', 'vector', 'patch'];
        $statuses = [
            OrderStatus::RECEIVED,
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::DELIVERED,
            OrderStatus::CLOSED,
        ];

        // Create diverse set of orders
        for ($i = 1; $i <= 50; $i++) {
            $status = fake()->randomElement($statuses);
            $designer = $designers->random();
            $sales = $salesUsers->random();
            $client = $clients->random();

            $orderData = [
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'order_number' => $tenant->getSetting('order_number_prefix', 'ORD-') . str_pad($i, 5, '0', STR_PAD_LEFT),
                'sequence' => $i,
                'title' => fake()->sentence(4),
                // 'description' => fake()->paragraph(),
                'quantity' => fake()->numberBetween(1, 50),
                'priority' => fake()->randomElement([OrderPriority::NORMAL, OrderPriority::RUSH]),
                'type' => fake()->randomElement($orderTypes),
                'is_quote' => fake()->boolean(15),
                'status' => $status,
                'created_by' => $admin?->id ?? $client->user?->id,
            ];

            // Add assignments and prices based on status
            if (in_array($status, [
                OrderStatus::ASSIGNED,
                OrderStatus::IN_PROGRESS,
                OrderStatus::SUBMITTED,
                OrderStatus::IN_REVIEW,
                OrderStatus::DELIVERED,
                OrderStatus::CLOSED,
            ])) {
                $orderData['designer_id'] = $designer->id;
                $orderData['sales_user_id'] = $sales->id;
                $orderData['price'] = fake()->randomFloat(2, 20, 500);
            }

            // Add timestamps based on status
            if (in_array($status, [OrderStatus::SUBMITTED, OrderStatus::IN_REVIEW, OrderStatus::DELIVERED, OrderStatus::CLOSED])) {
                $orderData['submitted_at'] = now()->subDays(rand(1, 10));
            }

            if (in_array($status, [OrderStatus::DELIVERED, OrderStatus::CLOSED])) {
                $orderData['approved_at'] = now()->subDays(rand(1, 5));
                $orderData['delivered_at'] = now()->subDays(rand(1, 3));
            }

            Order::create($orderData);
        }

        $this->command->info('Created 50 orders with various statuses.');
    }
}
