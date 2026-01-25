<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\CommissionCalculator;
use App\Services\FileStorageService;
use App\Services\WorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubmitWorkAction
{
    public function __construct(
        private WorkflowService $workflowService,
        private FileStorageService $fileStorageService,
        private CommissionCalculator $commissionCalculator
    ) {}

    /**
     * @param  array<UploadedFile>  $files
     */
    public function execute(Order $order, array $files, User $submittedBy, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $files, $submittedBy, $notes) {
            // Upload output files
            foreach ($files as $file) {
                $this->fileStorageService->storeOrderFile($order, $file, 'output');
            }

            // Resolve any open revisions
            $order->revisions()
                ->where('status', 'open')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

            // Transition to submitted
            $previousStatus = $order->status;
            $order = $this->workflowService->transitionTo($order, OrderStatus::SUBMITTED);

            // Log status change
            $order->statusHistory()->create([
                'tenant_id' => $order->tenant_id,
                'from_status' => $previousStatus->value,
                'to_status' => OrderStatus::SUBMITTED->value,
                'changed_by_user_id' => $submittedBy->id,
                'changed_at' => now(),
                'notes' => $notes,
            ]);

            // Process commissions
            $this->commissionCalculator->processOrderCommissions($order, OrderStatus::SUBMITTED->value);

            return $order;
        });
    }
}
