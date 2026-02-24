<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);

        RateLimiter::for('api', function (Request $request) {
            $tenant = $request->attributes->get('apiTenant');

            return Limit::perMinute(60)->by(optional($tenant)->id ?: $request->ip());
        });

        $this->configureMailFromTenantSettings();
    }

    private function configureMailFromTenantSettings(): void
    {
        try {
            $tenantId = config('app.forced_tenant_id');
            if (! $tenantId) {
                return;
            }

            $tenant = \App\Models\Tenant::find($tenantId);
            if (! $tenant) {
                return;
            }

            $host = $tenant->getSetting('smtp_host');
            if (empty($host)) {
                return;
            }

            config([
                'mail.mailers.smtp.host'       => $host,
                'mail.mailers.smtp.port'       => $tenant->getSetting('smtp_port', 587),
                'mail.mailers.smtp.username'   => $tenant->getSetting('smtp_username', ''),
                'mail.mailers.smtp.password'   => $tenant->getSetting('smtp_password', ''),
                'mail.mailers.smtp.encryption' => $tenant->getSetting('smtp_encryption') ?: null,
                'mail.from.address'            => $tenant->getSetting('mail_from_address') ?: config('mail.from.address'),
                'mail.from.name'               => $tenant->getSetting('mail_from_name') ?: config('mail.from.name'),
                'app.name'                     => $tenant->getSetting('mail_from_name') ?: $tenant->name ?: config('app.name'),
            ]);
        } catch (\Throwable) {
            // Don't break the app if tenant mail config can't be loaded
        }
    }
}
