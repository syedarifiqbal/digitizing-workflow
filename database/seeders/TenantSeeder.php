<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::create([
            'name' => 'Demo Digitizing Company',
            'slug' => 'demo-digitizing',
            'is_active' => true,
            'settings' => [
                'order_number_prefix' => 'DMO-',
                'allowed_input_extensions' => 'jpg,jpeg,png,pdf,ai,psd',
                'max_upload_mb' => 50,
                'sales_commission_earned_on' => 'delivered',
                'designer_bonus_earned_on' => 'delivered',
                'default_currency' => 'USD',
                'date_format' => 'M d, Y',
            ],
        ]);

        // Create additional demo tenants
        Tenant::factory()->count(2)->create();
    }
}
