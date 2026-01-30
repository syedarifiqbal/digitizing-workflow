<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use InvalidArgumentException;

class InvoiceWorkflowService
{
    /**
     * Allowed status transitions.
     */
    private array $transitions = [
        InvoiceStatus::DRAFT->value => [InvoiceStatus::SENT->value, InvoiceStatus::CANCELLED->value],
        InvoiceStatus::SENT->value => [
            InvoiceStatus::PARTIALLY_PAID->value,
            InvoiceStatus::PAID->value,
            InvoiceStatus::OVERDUE->value,
            InvoiceStatus::CANCELLED->value,
            InvoiceStatus::VOID->value,
        ],
        InvoiceStatus::PARTIALLY_PAID->value => [
            InvoiceStatus::PAID->value,
            InvoiceStatus::OVERDUE->value,
            InvoiceStatus::CANCELLED->value,
            InvoiceStatus::VOID->value,
            InvoiceStatus::SENT->value,
        ],
        InvoiceStatus::OVERDUE->value => [
            InvoiceStatus::PARTIALLY_PAID->value,
            InvoiceStatus::PAID->value,
            InvoiceStatus::CANCELLED->value,
            InvoiceStatus::VOID->value,
            InvoiceStatus::SENT->value,
        ],
        InvoiceStatus::PAID->value => [InvoiceStatus::VOID->value],
        InvoiceStatus::CANCELLED->value => [],
        InvoiceStatus::VOID->value => [],
    ];

    public function getAllowedTransitions(InvoiceStatus $current): array
    {
        $allowed = $this->transitions[$current->value] ?? [];

        return array_map(fn (string $status) => InvoiceStatus::from($status), $allowed);
    }

    public function canTransitionTo(InvoiceStatus $from, InvoiceStatus $to): bool
    {
        $allowed = $this->transitions[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public function transitionTo(Invoice $invoice, InvoiceStatus $status): Invoice
    {
        if (! $this->canTransitionTo($invoice->status, $status)) {
            throw new InvalidArgumentException(
                "Cannot transition invoice {$invoice->id} from {$invoice->status->value} to {$status->value}."
            );
        }

        $updates = ['status' => $status];

        if ($status === InvoiceStatus::SENT && ! $invoice->sent_at) {
            $updates['sent_at'] = now();
        }

        if ($status === InvoiceStatus::PAID) {
            $updates['paid_at'] = now();
        }

        if ($status !== InvoiceStatus::PAID && $invoice->status === InvoiceStatus::PAID) {
            $updates['paid_at'] = null;
        }

        $invoice->update($updates);

        return $invoice->refresh();
    }
}
