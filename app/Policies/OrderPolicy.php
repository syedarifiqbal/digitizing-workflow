<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isDesigner();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        if ($user->isDesigner()) {
            return $order->designer_id === $user->id;
        }

        if ($user->isClient()) {
            return $order->client_id === $user->client_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        // Assigned designer can update (submit work, change status)
        if ($user->isDesigner() && $order->designer_id === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->tenant_id === $order->tenant_id && $user->isAdmin();
    }
}
