<?php

namespace App\Actions\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

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
            'sales_commission_earned_on' => 'delivered',
            'designer_bonus_earned_on' => 'delivered',
            'enable_designer_tips' => false,
            'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
            'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
            'max_upload_mb' => 25,
            'currency' => 'USD',
            'date_format' => 'DD/MM/YYYY',
            'order_number_prefix' => '',
            'show_order_cards' => false,
            'auto_assign_on_designer' => true,
            'auto_submit_on_upload' => true,
            'auto_review_on_submit' => false,
            'notify_on_assignment' => true,
            'notify_on_comment' => true,
            'api_enabled' => false,
            'api_key_hash' => null,
            'api_key_last_four' => null,
            'webhook_url' => '',
            'webhook_secret' => '',
            'webhook_events' => [],
            'invoice_number_prefix' => 'INV-',
            'default_payment_terms' => 'Net 30',
            'default_tax_rate' => 0,
            'company_details' => [
                'name' => '',
                'address' => '',
                'phone' => '',
                'email' => '',
            ],
            'bank_details' => '',
        ];
    }
}
