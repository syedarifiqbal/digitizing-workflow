<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\User;
use App\Notifications\OrderAssignedNotification;
use Illuminate\Support\Facades\DB;

class AssignOrderAction
{
    public function execute(Order $order, User $designer, User $assignedBy): OrderAssignment
    {
        $assignment = DB::transaction(function () use ($order, $designer, $assignedBy) {
            // End current active assignment if exists
            $order->assignments()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            // Create new assignment
            $assignment = OrderAssignment::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'designer_user_id' => $designer->id,
                'assigned_by_user_id' => $assignedBy->id,
                'assigned_at' => now(),
            ]);

            // Update order designer
            $order->update(['designer_id' => $designer->id]);

            // Transition status if currently RECEIVED
            if ($order->status === OrderStatus::RECEIVED) {
                $order->update(['status' => OrderStatus::ASSIGNED]);
            }

            return $assignment;
        });

        // Send notification if enabled in tenant settings
        $notifyOnAssignment = $order->tenant->getSetting('notify_on_assignment', true);

        if ($notifyOnAssignment) {
            $order->load('client');
            $designer->notify(new OrderAssignedNotification($order, $assignedBy));
        }

        return $assignment;
    }
}
