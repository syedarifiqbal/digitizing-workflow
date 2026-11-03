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

        if (in_array($user->client_id, [null, $client->id], true)) {
            return $user;
        }

        // Login of a client that has since been deleted: free to take over.
        return Client::find($user->client_id) ? null : $user;
    }

    /**
     * Human readable reason why an email cannot be used for this client's login.
     */
    public static function describeConflict(string $email, Client $client): string
    {
        $user = User::withTrashed()
            ->where('tenant_id', $client->tenant_id)
            ->where('email', $email)
            ->first();

        if (! $user) {
            return 'This email is already in use.';
        }

        if ($user->hasRole('Client')) {
            $other = Client::find($user->client_id);

            return 'This email is already the login of another client' . ($other ? " ({$other->name})" : '') . '.';
        }

        $roles = $user->roles->pluck('name')->implode(', ');

        return 'This email already belongs to a team member' . ($roles ? " ({$roles})" : '') . '. Use a different email for this client.';
    }
}
