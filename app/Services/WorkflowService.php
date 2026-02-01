<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;

class WorkflowService
{
    private array $transitions = [
        'received' => ['assigned', 'cancelled'],
        'assigned' => ['in_progress', 'received', 'cancelled'],
        'in_progress' => ['submitted', 'cancelled'],
        'submitted' => ['in_review', 'cancelled'],
        'in_review' => ['approved', 'cancelled'],
        'approved' => ['delivered', 'cancelled'],
        'delivered' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    private array $designerTransitions = [
        'assigned' => ['in_progress'],
    ];

    public function getAllowedTransitions(OrderStatus $currentStatus): array
    {
        $allowed = $this->transitions[$currentStatus->value] ?? [];

        return array_map(fn ($status) => OrderStatus::from($status), $allowed);
    }

    public function getAllowedTransitionsForRole(OrderStatus $currentStatus, string $role): array
    {
        if ($role === 'designer') {
            $allowed = $this->designerTransitions[$currentStatus->value] ?? [];
        } else {
            $allowed = $this->transitions[$currentStatus->value] ?? [];
        }

        return array_map(fn ($status) => OrderStatus::from($status), $allowed);
    }

    public function canRoleTransition(OrderStatus $from, OrderStatus $to, string $role): bool
    {
        $allowed = $this->getAllowedTransitionsForRole($from, $role);

        return in_array($to, $allowed);
    }

    public function canTransitionTo(OrderStatus $from, OrderStatus $to): bool
    {
        $allowed = $this->transitions[$from->value] ?? [];

        return in_array($to->value, $allowed);
    }

    public function transitionTo(Order $order, OrderStatus $newStatus): Order
    {
        if (! $this->canTransitionTo($order->status, $newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$order->status->value} to {$newStatus->value}"
            );
        }

        $order->update(['status' => $newStatus]);

        $this->updateTimestamps($order, $newStatus);

        return $order->fresh();
    }

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

    public function getStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::RECEIVED => 'Received',
            OrderStatus::ASSIGNED => 'Assigned',
            OrderStatus::IN_PROGRESS => 'In Progress',
            OrderStatus::SUBMITTED => 'Submitted',
            OrderStatus::IN_REVIEW => 'In Review',
            OrderStatus::APPROVED => 'Approved',
            OrderStatus::DELIVERED => 'Delivered',
            OrderStatus::CLOSED => 'Closed',
            OrderStatus::CANCELLED => 'Cancelled',
        };
    }

    public function getTransitionStyle(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::IN_PROGRESS => 'primary',
            OrderStatus::SUBMITTED => 'success',
            OrderStatus::IN_REVIEW => 'info',
            OrderStatus::APPROVED => 'success',
            OrderStatus::DELIVERED => 'success',
            OrderStatus::CLOSED => 'secondary',
            OrderStatus::CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
