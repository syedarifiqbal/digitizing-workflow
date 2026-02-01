<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;

class TenantMailer
{
    public static function configureForTenant(Tenant $tenant): ?string
    {
        $host = $tenant->getSetting('smtp_host');

        if (empty($host)) {
            return null;
        }

        $mailerName = "tenant-{$tenant->id}";

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $tenant->getSetting('smtp_port', 587),
            'username' => $tenant->getSetting('smtp_username', ''),
            'password' => $tenant->getSetting('smtp_password', ''),
            'encryption' => $tenant->getSetting('smtp_encryption') ?: null,
            'timeout' => null,
        ]);

        return $mailerName;
    }
}
