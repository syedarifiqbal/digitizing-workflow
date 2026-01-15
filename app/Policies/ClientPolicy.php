<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    private function canManage(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->canManage($user) && $client->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->canManage($user) && $client->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->canManage($user) && $client->tenant_id === $user->tenant_id;
    }
}
