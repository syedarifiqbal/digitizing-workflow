<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $tenant = $request->user()->tenant;

        $settings = array_merge($this->defaultSettings(), $tenant->settings ?? []);

        return Inertia::render('Settings/General', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email_verification_required' => ['required', 'boolean'],
            'sales_commission_earned_on' => ['required', 'in:approved,delivered'],
            'designer_bonus_earned_on' => ['required', 'in:approved,delivered'],
            'enable_designer_tips' => ['required', 'boolean'],
            'auto_assign_on_designer' => ['required', 'boolean'],
            'auto_submit_on_upload' => ['required', 'boolean'],
            'auto_review_on_submit' => ['required', 'boolean'],
            'allowed_input_extensions' => ['required', 'string', 'max:255'],
            'allowed_output_extensions' => ['required', 'string', 'max:255'],
            'max_upload_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'order_number_prefix' => ['nullable', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD,DD-MM-YYYY,DD.MM.YYYY'],
            'show_order_cards' => ['required', 'boolean'],
            'notify_on_assignment' => ['required', 'boolean'],
            'api_enabled' => ['required', 'boolean'],
        ]);

        $tenant = $request->user()->tenant;

        $settings = array_merge($tenant->settings ?? $this->defaultSettings(), [
            'email_verification_required' => $validated['email_verification_required'],
            'date_format' => $validated['date_format'],
            'sales_commission_earned_on' => $validated['sales_commission_earned_on'],
            'designer_bonus_earned_on' => $validated['designer_bonus_earned_on'],
            'enable_designer_tips' => $validated['enable_designer_tips'],
            'auto_assign_on_designer' => $validated['auto_assign_on_designer'],
            'auto_submit_on_upload' => $validated['auto_submit_on_upload'],
            'auto_review_on_submit' => $validated['auto_review_on_submit'],
            'allowed_input_extensions' => $validated['allowed_input_extensions'],
            'allowed_output_extensions' => $validated['allowed_output_extensions'],
            'max_upload_mb' => $validated['max_upload_mb'],
            'currency' => $validated['currency'],
            'order_number_prefix' => $validated['order_number_prefix'] ?? '',
            'show_order_cards' => $validated['show_order_cards'],
            'notify_on_assignment' => $validated['notify_on_assignment'],
            'api_enabled' => $validated['api_enabled'],
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'settings' => $settings,
        ]);

        return back()->with('success', 'Settings saved.');
    }

    private function defaultSettings(): array
    {
        return [
            'email_verification_required' => true,
            'date_format' => 'MM/DD/YYYY',
            'sales_commission_earned_on' => 'delivered',
            'designer_bonus_earned_on' => 'delivered',
            'enable_designer_tips' => false,
            'auto_assign_on_designer' => true,
            'auto_submit_on_upload' => true,
            'auto_review_on_submit' => false,
            'allowed_input_extensions' => 'jpg,jpeg,png,pdf',
            'allowed_output_extensions' => 'dst,emb,pes,exp,pdf,ai,psd,png,jpg',
            'max_upload_mb' => 25,
            'currency' => 'USD',
            'order_number_prefix' => '',
            'show_order_cards' => false,
            'notify_on_assignment' => true,
            'api_enabled' => false,
            'api_key_hash' => null,
            'api_key_last_four' => null,
        ];
    }
}
