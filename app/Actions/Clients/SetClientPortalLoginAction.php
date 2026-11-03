<?php

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;

class SetClientPortalLoginAction
{
    /**
     * Create or update the portal login for a client.
     *
     * The login is marked as verified and active so the client can sign in right away,
     * without going through the invitation / signup flow.
     */
    public function execute(Client $client, string $email, ?string $password): User
    {
        $user = User::query()
            ->where('client_id', $client->id)
            ->orderBy('id')
            ->first() ?? new User([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'name' => $client->name,
            ]);

        $user->email = $email;
        $user->is_active = true;
        $user->email_verified_at ??= now();

        if ($password !== null && $password !== '') {
            $user->password = $password;
        }

        $user->save();

        if (! $user->hasRole('Client')) {
            $user->assignRole('Client');
        }

        return $user;
    }
}
