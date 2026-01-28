<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;

class WorkflowService
{
    /**
     * Defines which statuses can transition to which other statuses.
     * Key = current status, Value = array of allowed next statuses
     */
    private array $transitions = [
        'received' => ['assigned', 'cancelled'],
        'assigned' => ['in_progress', 'received', 'cancelled'],
        'in_progress' => ['submitted', 'cancelled'],
        'submitted' => ['in_review', 'revision_requested', 'cancelled'],
        'in_review' => ['revision_requested', 'approved', 'cancelled'],
        'revision_requested' => ['in_progress', 'cancelled'],
        'approved' => ['delivered', 'cancelled'],
        'delivered' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    /**
     * Transitions that a designer (assigned) is allowed to perform.
     */
    private array $designerTransitions = [
        'assigned' => ['in_progress'],
        'revision_requested' => ['in_progress'],
    ];

    /**
     * Get allowed transitions for a given status.
     */
    public function getAllowedTransitions(OrderStatus $currentStatus): array
    {
        $allowed = $this->transitions[$currentStatus->value] ?? [];

        return array_map(fn ($status) => OrderStatus::from($status), $allowed);
    }

    /**
     * Get allowed transitions filtered by user role.
     */
    public function getAllowedTransitionsForRole(OrderStatus $currentStatus, string $role): array
    {
        if ($role === 'designer') {
            $allowed = $this->designerTransitions[$currentStatus->value] ?? [];
        } else {
            // Admin/Manager can do all transitions except submitted (handled by submit work)
            $allowed = $this->transitions[$currentStatus->value] ?? [];
        }

        return array_map(fn ($status) => OrderStatus::from($status), $allowed);
    }

    /**
     * Check if a user role can perform a specific transition.
     */
    public function canRoleTransition(OrderStatus $from, OrderStatus $to, string $role): bool
    {
        $allowed = $this->getAllowedTransitionsForRole($from, $role);

        return in_array($to, $allowed);
    }

    /**
     * Check if a transition is allowed.
     */
    public function canTransitionTo(OrderStatus $from, OrderStatus $to): bool
    {
        $allowed = $this->transitions[$from->value] ?? [];

        return in_array($to->value, $allowed);
    }

    /**
     * Transition an order to a new status.
     *
     * @throws \InvalidArgumentException
     */
    public function transitionTo(Order $order, OrderStatus $newStatus): Order
    {
        if (! $this->canTransitionTo($order->status, $newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$order->status->value} to {$newStatus->value}"
            );
        }

        $order->update(['status' => $newStatus]);

        // Update timestamp fields based on status
        $this->updateTimestamps($order, $newStatus);

        return $order->fresh();
    }

    /**
     * Update relevant timestamp fields based on status change.
     */
    private function updateTimestamps(Order $order, OrderStatus $status): void
    {
        $updates = match ($status) {
            OrderStatus::SUBMITTED => ['submitted_at' => now()],
            OrderStatus::APPROVED => ['approved_at' => now()],
            OrderStatus::DELIVERED => ['delivered_at' => now()],
            default => [],
        };

        if (! empty($updates)) {
            $order->update($updates);
        }
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::RECEIVED => 'Received',
            OrderStatus::ASSIGNED => 'Assigned',
            OrderStatus::IN_PROGRESS => 'In Progress',
            OrderStatus::SUBMITTED => 'Submitted',
            OrderStatus::IN_REVIEW => 'In Review',
            OrderStatus::REVISION_REQUESTED => 'Revision Requested',
            OrderStatus::APPROVED => 'Approved',
            OrderStatus::DELIVERED => 'Delivered',
            OrderStatus::CLOSED => 'Closed',
            OrderStatus::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get button style/color for a status transition.
     */
    public function getTransitionStyle(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::IN_PROGRESS => 'primary',
            OrderStatus::SUBMITTED => 'success',
            OrderStatus::IN_REVIEW => 'info',
            OrderStatus::REVISION_REQUESTED => 'warning',
            OrderStatus::APPROVED => 'success',
            OrderStatus::DELIVERED => 'success',
            OrderStatus::CLOSED => 'secondary',
            OrderStatus::CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
