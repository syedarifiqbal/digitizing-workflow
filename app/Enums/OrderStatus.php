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
}
