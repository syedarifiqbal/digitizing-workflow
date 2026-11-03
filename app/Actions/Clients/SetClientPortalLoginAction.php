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
            ->first()
            ?? self::findReusableUser($client, $email)
            ?? new User([
                'tenant_id' => $client->tenant_id,
                'name' => $client->name,
            ]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->client_id = $client->id;
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

    /**
     * An existing (possibly soft-deleted) client login with this email that is not
     * linked to another client can be taken over, e.g. an old signup for the same person.
     * Staff accounts and logins of other clients are never reused.
     */
    public static function findReusableUser(Client $client, string $email): ?User
    {
        $user = User::withTrashed()
            ->where('tenant_id', $client->tenant_id)
            ->where('email', $email)
            ->first();

        if (! $user || ! $user->hasRole('Client')) {
            return null;
        }

        return in_array($user->client_id, [null, $client->id], true) ? $user : null;
    }
}
