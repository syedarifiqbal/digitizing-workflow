<?php

namespace App\Actions\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterTenantAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['company'],
                'settings' => $this->defaultSettings(),
            ]);

            $this->createRoles($tenant);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('Admin');

            return $user;
        });
    }

    private function createRoles(Tenant $tenant): void
    {
        $roleNames = ['Admin', 'Manager', 'Designer', 'Client', 'Sales'];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'guard_name' => 'web',
                ]
            );
        }
    }

    private function defaultSettings(): array
    {
        return [
            'email_verification_required' => true,
            'commission_earned_on' => 'delivered',
            'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
            'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
            'max_upload_mb' => 25,
            'currency' => 'USD',
            'date_format' => 'DD/MM/YYYY',
            'order_number_prefix' => '',
            'show_order_cards' => false,
            'auto_assign_on_designer' => true,
            'auto_submit_on_upload' => true,
        ];
    }
}
