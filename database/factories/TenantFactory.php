<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
            'settings' => [
                'order_number_prefix' => 'ORD-',
                'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
                'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
                'max_upload_mb' => 25,
                'sales_commission_earned_on' => 'delivered',
                'designer_bonus_earned_on' => 'delivered',
                'enable_designer_tips' => false,
                'auto_assign_on_designer' => true,
                'auto_submit_on_upload' => true,
                'auto_review_on_submit' => false,
                'notify_on_assignment' => true,
                'currency' => 'USD',
                'date_format' => 'M d, Y',
                'show_order_cards' => false,
                'email_verification_required' => true,
                'api_enabled' => false,
                'api_key_hash' => null,
                'api_key_last_four' => null,
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
            ],
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
