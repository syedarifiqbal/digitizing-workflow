<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function canManage(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user);
    }
}
