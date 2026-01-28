<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
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

        // Create demo clients
        $clients = [
            [
                'name' => 'John Smith',
                'company' => 'ABC Embroidery',
                'email' => 'john@abcembroidery.com',
                'phone' => '555-0101',
            ],
            [
                'name' => 'Sarah Johnson',
                'company' => 'Custom Stitches LLC',
                'email' => 'sarah@customstitches.com',
                'phone' => '555-0102',
            ],
            [
                'name' => 'Michael Brown',
                'company' => 'Pro Designs Inc',
                'email' => 'michael@prodesigns.com',
                'phone' => '555-0103',
            ],
        ];

        $clientRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Client'],
            ['guard_name' => 'web']
        );

        foreach ($clients as $clientData) {
            $client = Client::create([
                'tenant_id' => $tenant->id,
                'name' => $clientData['name'],
                'company' => $clientData['company'],
                'email' => $clientData['email'],
                'phone' => $clientData['phone'],
                'is_active' => true,
            ]);

            // Create a user account for each client
            $clientUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => Hash::make('password'),
                'is_active' => true,
                'client_id' => $client->id,
                'email_verified_at' => now(),
            ]);

            $clientUser->roles()->attach($clientRole);
        }

        // Create additional random clients
        Client::factory()
            ->count(10)
            ->for($tenant)
            ->create();
    }
}
