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
                'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
                'max_upload_mb' => 50,
                'sales_commission_earned_on' => 'delivered',
                'designer_bonus_earned_on' => 'delivered',
                'enable_designer_tips' => false,
                'auto_assign_on_designer' => true,
                'auto_submit_on_upload' => true,
                'auto_review_on_submit' => false,
                'notify_on_assignment' => true,
                'notify_on_comment' => true,
                'currency' => 'USD',
                'date_format' => 'M d, Y',
                'show_order_cards' => false,
                'email_verification_required' => true,
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
                    'name' => 'Demo Digitizing Company',
                    'address' => '123 Embroidery Lane, Austin, TX',
                    'phone' => '+1 (555) 123-4567',
                    'email' => 'billing@demo-digitizing.test',
                ],
                'bank_details' => "Bank: Demo Bank\nAccount: 123456789\nRouting: 987654321",
            ],
        ]);

        // Create additional demo tenants
        Tenant::factory()->count(2)->create();
    }
}
