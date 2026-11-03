<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings_without_logo(): void
    {
        Storage::fake('public');
        [$tenant, $admin] = $this->createTenantAdmin();

        $payload = $this->validSettingsPayload([
            'name' => 'Updated Stitch Co',
            'default_payment_terms' => 'Net 45',
        ]);

        $response = $this->actingAs($admin)->put(route('settings.update'), $payload);
        $response->assertRedirect();

        $tenant->refresh();
        $this->assertSame('Updated Stitch Co', $tenant->name);
        $this->assertSame('Net 45', $tenant->settings['default_payment_terms']);
        $this->assertNull($tenant->settings['company_logo_path'] ?? null);
    }

    public function test_admin_can_toggle_comment_features(): void
    {
        [$tenant, $admin] = $this->createTenantAdmin();

        $this->actingAs($admin)->put(route('settings.update'), $this->validSettingsPayload([
            'client_comments_enabled' => false,
            'staff_comments_enabled' => false,
        ]))->assertRedirect();

        $tenant->refresh();
        $this->assertFalse($tenant->getSetting('client_comments_enabled'));
        $this->assertFalse($tenant->getSetting('staff_comments_enabled'));

        $this->actingAs($admin)->put(route('settings.update'), $this->validSettingsPayload())->assertRedirect();

        $tenant->refresh();
        $this->assertTrue($tenant->getSetting('client_comments_enabled'));
        $this->assertTrue($tenant->getSetting('staff_comments_enabled'));
    }

    public function test_admin_can_upload_and_remove_logo(): void
    {
        Storage::fake('public');
        [$tenant, $admin] = $this->createTenantAdmin();

        $uploadResponse = $this->actingAs($admin)->post(route('settings.update'), $this->validSettingsPayload([
            '_method' => 'PUT',
            'company_logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ]));

        $uploadResponse->assertRedirect();
        $tenant->refresh();

        $logoPath = $tenant->settings['company_logo_path'] ?? null;
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $removeResponse = $this->actingAs($admin)->post(route('settings.update'), $this->validSettingsPayload([
            '_method' => 'PUT',
            'remove_logo' => true,
        ]));

        $removeResponse->assertRedirect();
        $tenant->refresh();

        Storage::disk('public')->assertMissing($logoPath);
        $this->assertNull($tenant->settings['company_logo_path'] ?? null);
    }

    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Stitch Co',
            'email_verification_required' => true,
            'sales_commission_earned_on' => 'delivered',
            'designer_bonus_earned_on' => 'delivered',
            'enable_designer_tips' => false,
            'auto_assign_on_designer' => true,
            'auto_submit_on_upload' => true,
            'auto_review_on_submit' => false,
            'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
            'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
            'max_upload_mb' => 50,
            'currency' => 'USD',
            'commission_currency' => 'USD',
            'order_number_prefix' => 'ORD-',
            'date_format' => 'MM/DD/YYYY',
            'time_format' => 'h:mm A',
            'show_order_cards' => false,
            'notify_on_assignment' => true,
            'notify_on_comment' => true,
            'client_comments_enabled' => true,
            'staff_comments_enabled' => true,
            'enable_invoice_bulk_action' => true,
            'api_enabled' => false,
            'stripe_enabled' => false,
            'stripe_checkout_mode' => 'hosted',
            'stripe_allow_admin_payment' => false,
            'invoice_number_prefix' => 'INV-',
            'default_payment_terms' => 'Net 30',
            'default_tax_rate' => 7.5,
            'company_details' => [
                'name' => 'Stitch Co',
                'address' => '123 Embroidery Ave',
                'phone' => '555-1234',
                'email' => 'billing@stitch.test',
            ],
            'bank_details' => "Bank: Sample Bank\nAccount: 123456",
            'company_logo' => null,
            'remove_logo' => false,
            'smtp_host' => '',
            'smtp_port' => null,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => '',
            'mail_from_address' => null,
            'mail_from_name' => '',
            'webhook_url' => '',
            'webhook_secret' => '',
            'webhook_events' => [],
        ], $overrides);
    }
}
