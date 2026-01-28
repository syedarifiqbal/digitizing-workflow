<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderRevision;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RevisionSeeder extends Seeder
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
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        if (! $admin) {
            $this->command->warn('No admin user found for RevisionSeeder.');
            return;
        }

        $orders = Order::where('tenant_id', $tenant->id)
            ->whereNotNull('designer_id')
            ->inRandomOrder()
            ->limit(12)
            ->get();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders available for RevisionSeeder.');
            return;
        }

        foreach ($orders as $index => $order) {
            $resolved = $index % 2 === 1;

            OrderRevision::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'requested_by_user_id' => $admin->id,
                'notes' => 'Please adjust satin density and tidy border.',
                'status' => $resolved ? 'resolved' : 'open',
                'resolved_at' => $resolved ? now()->subDays(rand(1, 3)) : null,
            ]);
        }

        $this->command->info('Revision records created for sample orders.');
    }
}

