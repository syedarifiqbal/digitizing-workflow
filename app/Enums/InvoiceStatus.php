<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::VOID => 'Void',
        };
    }

    /**
     * Whether an invoice in this status can still have its details edited.
     * Cancelled/Void are terminal states and stay locked; everything else
     * (including Sent/Overdue/Partially Paid/Paid) remains editable.
     */
    public function isEditable(): bool
    {
        return match ($this) {
            self::CANCELLED, self::VOID => false,
            default => true,
        };
    }
}
