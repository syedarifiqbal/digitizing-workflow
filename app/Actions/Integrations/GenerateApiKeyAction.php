<?php

namespace App\Actions\Integrations;

use App\Models\Tenant;
use Illuminate\Support\Str;

class GenerateApiKeyAction
{
    public function execute(Tenant $tenant): string
    {
        $plainKey = Str::random(40);
        $hash = hash('sha256', $plainKey);

        $settings = $tenant->settings ?? [];
        $settings['api_key_hash'] = $hash;
        $settings['api_key_last_four'] = substr($plainKey, -4);

        $tenant->forceFill([
            'settings' => $settings,
        ])->save();

        return $plainKey;
    }
}

