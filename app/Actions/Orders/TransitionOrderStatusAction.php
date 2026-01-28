<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\CommissionCalculator;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class TransitionOrderStatusAction
{
    public function __construct(
        private WorkflowService $workflowService,
        private CommissionCalculator $commissionCalculator
    ) {}

    public function execute(Order $order, OrderStatus $newStatus, User $changedBy, ?float $designerTip = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $changedBy, $designerTip) {
            $previousStatus = $order->status;

            // Use workflow service to transition
            $order = $this->workflowService->transitionTo($order, $newStatus);

            // Log the status change
            $order->statusHistory()->create([
                'tenant_id' => $order->tenant_id,
                'from_status' => $previousStatus->value,
                'to_status' => $newStatus->value,
                'changed_by_user_id' => $changedBy->id,
                'changed_at' => now(),
            ]);

            // Process commissions based on new status
            $this->commissionCalculator->processOrderCommissions($order, $newStatus->value, $designerTip);

            return $order;
        });
    }
}
