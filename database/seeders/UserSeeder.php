<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Admin'],
            ['guard_name' => 'web']
        );

        $managerRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Manager'],
            ['guard_name' => 'web']
        );

        $designerRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Designer'],
            ['guard_name' => 'web']
        );

        $salesRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Sales'],
            ['guard_name' => 'web']
        );

        // Create Admin users
        $admin1 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'phone' => '555-1001',
            'is_active' => true,
        ]);
        $admin1->roles()->attach($adminRole);

        $admin2 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jane Admin',
            'email' => 'jane.admin@demo.com',
            'password' => Hash::make('password'),
            'phone' => '555-1002',
            'is_active' => true,
        ]);
        $admin2->roles()->attach($adminRole);

        // Create Manager users
        $manager1 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tom Manager',
            'email' => 'tom.manager@demo.com',
            'password' => Hash::make('password'),
            'phone' => '555-2001',
            'is_active' => true,
        ]);
        $manager1->roles()->attach($managerRole);

        $manager2 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lisa Manager',
            'email' => 'lisa.manager@demo.com',
            'password' => Hash::make('password'),
            'phone' => '555-2002',
            'is_active' => true,
        ]);
        $manager2->roles()->attach($managerRole);

        // Create Sales users
        $salesUsers = [
            ['name' => 'Bob Sales', 'email' => 'bob.sales@demo.com', 'phone' => '555-3001'],
            ['name' => 'Alice Sales', 'email' => 'alice.sales@demo.com', 'phone' => '555-3002'],
            ['name' => 'Charlie Sales', 'email' => 'charlie.sales@demo.com', 'phone' => '555-3003'],
            ['name' => 'Diana Sales', 'email' => 'diana.sales@demo.com', 'phone' => '555-3004'],
            ['name' => 'Edward Sales', 'email' => 'edward.sales@demo.com', 'phone' => '555-3005'],
        ];

        foreach ($salesUsers as $userData) {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'phone' => $userData['phone'],
                'is_active' => true,
            ]);
            $user->roles()->attach($salesRole);
        }

        // Create Designer users
        $designerUsers = [
            ['name' => 'Frank Designer', 'email' => 'frank.designer@demo.com', 'phone' => '555-4001'],
            ['name' => 'Grace Designer', 'email' => 'grace.designer@demo.com', 'phone' => '555-4002'],
            ['name' => 'Henry Designer', 'email' => 'henry.designer@demo.com', 'phone' => '555-4003'],
            ['name' => 'Iris Designer', 'email' => 'iris.designer@demo.com', 'phone' => '555-4004'],
            ['name' => 'Jack Designer', 'email' => 'jack.designer@demo.com', 'phone' => '555-4005'],
            ['name' => 'Kate Designer', 'email' => 'kate.designer@demo.com', 'phone' => '555-4006'],
            ['name' => 'Leo Designer', 'email' => 'leo.designer@demo.com', 'phone' => '555-4007'],
        ];

        foreach ($designerUsers as $userData) {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'phone' => $userData['phone'],
                'is_active' => true,
            ]);
            $user->roles()->attach($designerRole);
        }

        $this->command->info('Created users: 2 Admins, 2 Managers, 5 Sales, 7 Designers');
        $this->command->info('All passwords are: password');
    }
}
