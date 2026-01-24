<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderRevision;
use App\Models\User;
use App\Notifications\RevisionRequestedNotification;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class RequestRevisionAction
{
    public function __construct(
        private WorkflowService $workflowService,
        private TransitionOrderStatusAction $transitionAction
    ) {}

    public function execute(Order $order, User $requestedBy, ?string $notes = null): OrderRevision
    {
        return DB::transaction(function () use ($order, $requestedBy, $notes) {
            // Create the revision record
            $revision = OrderRevision::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'requested_by_user_id' => $requestedBy->id,
                'notes' => $notes,
                'status' => 'open',
            ]);

            // Transition order to REVISION_REQUESTED if not already
            if ($order->status !== OrderStatus::REVISION_REQUESTED) {
                $this->transitionAction->execute($order, OrderStatus::REVISION_REQUESTED, $requestedBy);
            }

            // Notify the assigned designer
            if ($order->designer) {
                $order->designer->notify(new RevisionRequestedNotification($order, $requestedBy, $notes));
            }

            return $revision;
        });
    }
}
