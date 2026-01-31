<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function canManage(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Check if client can view this invoice (must be their invoice and not a draft).
     */
    private function clientCanView(User $user, Invoice $invoice): bool
    {
        return $user->isClient()
            && $user->client_id
            && $user->client_id === $invoice->client_id
            && $invoice->status !== InvoiceStatus::DRAFT;
    }

    public function viewAny(User $user): bool
    {
        // Admins/Managers can view all, Clients can view their own
        return $this->canManage($user) || $user->isClient();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $this->clientCanView($user, $invoice);
    }

    /**
     * Check if client can download the PDF (same as view).
     */
    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
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
