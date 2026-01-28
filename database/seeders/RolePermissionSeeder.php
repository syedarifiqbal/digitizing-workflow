<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run TenantSeeder first.');
            return;
        }

        $roleNames = ['Admin', 'Manager', 'Designer', 'Sales', 'Client'];

        foreach ($tenants as $tenant) {
            foreach ($roleNames as $name) {
                Role::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $name,
                    ],
                    [
                        'guard_name' => 'web',
                    ]
                );
            }
        }

        $this->command->info('Default roles created for all tenants.');
    }
}

