<?php

namespace App\Policies;

use App\Models\CommissionRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommissionRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CommissionRule $commissionRule): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commissionRule->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CommissionRule $commissionRule): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commissionRule->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CommissionRule $commissionRule): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commissionRule->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CommissionRule $commissionRule): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commissionRule->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CommissionRule $commissionRule): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->tenant_id === $commissionRule->tenant_id;
    }
}
