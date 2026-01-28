<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $admin = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'Manager']))
            ->first();

        if (! $admin) {
            $this->command->warn('No admin or manager user found for AuditEventSeeder.');
            return;
        }

        $orders = Order::where('tenant_id', $tenant->id)
            ->limit(30)
            ->get();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders available for AuditEventSeeder.');
            return;
        }

        $statusFlow = [
            OrderStatus::RECEIVED,
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::APPROVED,
            OrderStatus::DELIVERED,
            OrderStatus::CLOSED,
        ];

        foreach ($orders as $order) {
            $currentStatus = $order->status;
            $targetIndex = array_search($currentStatus, $statusFlow, true);

            if ($targetIndex === false) {
                if ($currentStatus === OrderStatus::CANCELLED) {
                    OrderStatusHistory::create([
                        'tenant_id' => $tenant->id,
                        'order_id' => $order->id,
                        'from_status' => OrderStatus::RECEIVED,
                        'to_status' => OrderStatus::CANCELLED,
                        'changed_by_user_id' => $admin->id,
                        'changed_at' => now()->subDays(2),
                        'notes' => 'Cancelled due to client request.',
                    ]);
                }
                continue;
            }

            $changedAt = now()->subDays(6);

            for ($i = 0; $i < $targetIndex; $i++) {
                $from = $statusFlow[$i];
                $to = $statusFlow[$i + 1];

                OrderStatusHistory::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'from_status' => $from,
                    'to_status' => $to,
                    'changed_by_user_id' => $admin->id,
                    'changed_at' => $changedAt,
                    'notes' => $this->statusNote($to),
                ]);

                $changedAt = $changedAt->addDay();
            }
        }

        $this->command->info('Order timeline events generated.');
    }

    private function statusNote(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::ASSIGNED => 'Assigned to primary designer.',
            OrderStatus::IN_PROGRESS => 'Designer started stitching.',
            OrderStatus::SUBMITTED => 'Designer submitted deliverables.',
            OrderStatus::IN_REVIEW => 'Admin reviewing submission.',
            OrderStatus::APPROVED => 'Approved for delivery.',
            OrderStatus::DELIVERED => 'Delivered to client portal.',
            OrderStatus::CLOSED => 'Order closed after confirmation.',
            default => 'Status updated.',
        };
    }
}

