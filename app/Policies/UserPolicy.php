<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole('Admin', 'Manager');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManage($user) && $user->tenant_id === $model->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManage($user) && $user->tenant_id === $model->tenant_id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->canManage($user) && $user->tenant_id === $model->tenant_id;
    }
}
