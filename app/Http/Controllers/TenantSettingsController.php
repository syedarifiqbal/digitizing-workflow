<?php

namespace App\Http\Controllers;

use App\Support\TenantMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $tenant = $request->user()->tenant;

        $settings = array_merge($this->defaultSettings(), $tenant->settings ?? []);
        $logoPath = $settings['company_logo_path'] ?? null;
        $settings['company_logo_url'] = $logoPath ? Storage::disk('public')->url($logoPath) : null;

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
            'notify_on_comment' => ['required', 'boolean'],
            'enable_invoice_bulk_action' => ['required', 'boolean'],
            'api_enabled' => ['required', 'boolean'],
            'invoice_number_prefix' => ['nullable', 'string', 'max:10'],
            'default_payment_terms' => ['nullable', 'string', 'max:255'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'company_details' => ['nullable', 'array'],
            'company_details.name' => ['nullable', 'string', 'max:255'],
            'company_details.address' => ['nullable', 'string', 'max:500'],
            'company_details.phone' => ['nullable', 'string', 'max:100'],
            'company_details.email' => ['nullable', 'email', 'max:255'],
            'bank_details' => ['nullable', 'string'],
            'company_logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'in:,tls,ssl'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = $request->user()->tenant;
        $currentSettings = $tenant->settings ?? $this->defaultSettings();

        $logoPath = $currentSettings['company_logo_path'] ?? null;

        if ($request->boolean('remove_logo') && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('tenant-logos/' . $tenant->id, 'public');
        }

        $settings = array_merge($currentSettings, [
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
            'notify_on_comment' => $validated['notify_on_comment'],
            'enable_invoice_bulk_action' => $validated['enable_invoice_bulk_action'],
            'api_enabled' => $validated['api_enabled'],
            'invoice_number_prefix' => $validated['invoice_number_prefix'] ?? 'INV-',
            'default_payment_terms' => $validated['default_payment_terms'] ?? 'Net 30',
            'default_tax_rate' => (float) ($validated['default_tax_rate'] ?? 0),
            'company_details' => $validated['company_details'] ?? [
                'name' => '',
                'address' => '',
                'phone' => '',
                'email' => '',
            ],
            'bank_details' => $validated['bank_details'] ?? '',
            'company_logo_path' => $logoPath,
            'smtp_host' => $validated['smtp_host'] ?? '',
            'smtp_port' => $validated['smtp_port'] ?? null,
            'smtp_username' => $validated['smtp_username'] ?? '',
            'smtp_password' => $validated['smtp_password'] ?? '',
            'smtp_encryption' => $validated['smtp_encryption'] ?? '',
            'mail_from_address' => $validated['mail_from_address'] ?? '',
            'mail_from_name' => $validated['mail_from_name'] ?? '',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'settings' => $settings,
        ]);

        return back()->with('success', 'Settings saved.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $tenant = $request->user()->tenant;
        $fromAddress = $tenant->getSetting('mail_from_address') ?: config('mail.from.address');
        $fromName = $tenant->getSetting('mail_from_name') ?: $tenant->getSetting('company_details.name', config('app.name'));
        $toAddress = $request->user()->email;

        $mailerName = TenantMailer::configureForTenant($tenant);

        try {
            Mail::mailer($mailerName)
                ->raw(
                    "This is a test email from your Digitizing Workflow application.\n\n"
                    . "If you received this message, your email settings are configured correctly.\n\n"
                    . "Tenant: {$tenant->name}\n"
                    . "From: {$fromAddress}\n"
                    . "Mailer: " . ($mailerName ?? 'default'),
                    function ($message) use ($fromAddress, $fromName, $toAddress, $tenant) {
                        $message->from($fromAddress, $fromName)
                            ->to($toAddress)
                            ->subject("Test Email — {$tenant->name}");
                    }
                );

            return back()->with('success', "Test email sent to {$toAddress}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
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
            'notify_on_comment' => true,
            'enable_invoice_bulk_action' => true,
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
            'company_logo_path' => null,
            'smtp_host' => '',
            'smtp_port' => null,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',
        ];
    }
}
