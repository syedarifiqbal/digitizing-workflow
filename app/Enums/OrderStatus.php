<?php

namespace App\Enums;

enum OrderStatus: string
{
    case RECEIVED = 'received';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case IN_REVIEW = 'in_review';
    case REVISION_REQUESTED = 'revision_requested';
    case APPROVED = 'approved';
    case DELIVERED = 'delivered';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::RECEIVED => 'Received',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::SUBMITTED => 'Submitted',
            self::IN_REVIEW => 'In Review',
            self::REVISION_REQUESTED => 'Revision Requested',
            self::APPROVED => 'Approved',
            self::DELIVERED => 'Delivered',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
