<?php

namespace App\Policies;

use App\Models\Commission;
use App\Models\User;

class CommissionPolicy
{
    /**
     * Determine if the user can view any commissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine if the user can view the commission.
     */
    public function view(User $user, Commission $commission): bool
    {
        // Admins and managers can view all
        if ($user->isAdmin() || $user->isManager()) {
            return $user->tenant_id === $commission->tenant_id;
        }

        // Users can view their own commissions
        return $user->id === $commission->user_id && $user->tenant_id === $commission->tenant_id;
    }

    /**
     * Determine if the user can update the commission (mark as paid).
     */
    public function update(User $user, Commission $commission): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commission->tenant_id;
    }
}
