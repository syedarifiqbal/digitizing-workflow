<?php

namespace Tests;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createTenantAdmin(?Tenant $tenant = null): array
    {
        $tenant ??= Tenant::factory()->create();
        $this->ensureRoleExists($tenant, 'Admin');

        $admin = User::factory()->for($tenant)->create();
        $admin->assignRole('Admin');

        return [$tenant, $admin];
    }

    protected function ensureRoleExists(Tenant $tenant, string $role): void
    {
        Role::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => $role,
            ],
            [
                'guard_name' => 'web',
            ]
        );
    }
}
