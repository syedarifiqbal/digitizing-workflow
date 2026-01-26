<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\RoleType;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
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

        // Get delivered and closed orders
        $completedOrders = Order::where('tenant_id', $tenant->id)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->whereNotNull('price')
            ->whereNotNull('designer_id')
            ->whereNotNull('sales_user_id')
            ->get();

        foreach ($completedOrders as $order) {
            // Sales commission
            if ($order->sales_user_id && $order->price > 0) {
                $salesCommission = $order->price * 0.10; // 10% example rate

                Commission::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'user_id' => $order->sales_user_id,
                    'role_type' => RoleType::SALES,
                    'base_amount' => $salesCommission,
                    'extra_amount' => 0,
                    'total_amount' => $salesCommission,
                    'currency' => 'USD',
                    'earned_on_status' => 'delivered',
                    'earned_at' => $order->delivered_at ?? now(),
                    'rule_snapshot' => [
                        'type' => 'percent',
                        'percent_rate' => 10,
                        'currency' => 'USD',
                    ],
                ]);
            }

            // Designer bonus (some with tips)
            if ($order->designer_id && $order->price > 0) {
                $designerBonus = $order->price * 0.05; // 5% example rate
                $tip = fake()->boolean(30) ? fake()->randomFloat(2, 5, 20) : 0;

                Commission::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'user_id' => $order->designer_id,
                    'role_type' => RoleType::DESIGNER,
                    'base_amount' => $designerBonus,
                    'extra_amount' => $tip,
                    'total_amount' => $designerBonus + $tip,
                    'currency' => 'USD',
                    'earned_on_status' => 'delivered',
                    'earned_at' => $order->delivered_at ?? now(),
                    'notes' => $tip > 0 ? "Includes USD " . number_format($tip, 2) . " tip from admin" : null,
                    'rule_snapshot' => [
                        'type' => 'percent',
                        'percent_rate' => 5,
                        'currency' => 'USD',
                    ],
                ]);
            }
        }

        $this->command->info('Created commissions for completed orders.');
    }
}
