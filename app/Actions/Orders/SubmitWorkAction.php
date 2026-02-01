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
    public function execute(
        Order $order,
        array $files,
        User $submittedBy,
        ?string $notes = null,
        ?string $submittedWidth = null,
        ?string $submittedHeight = null,
        ?int $submittedStitchCount = null
    ): Order {
        return DB::transaction(function () use ($order, $files, $submittedBy, $notes, $submittedWidth, $submittedHeight, $submittedStitchCount) {
            // Upload output files
            foreach ($files as $file) {
                $this->fileStorageService->storeOrderFile($order, $file, 'output');
            }

            // Save submitted dimension/stitch fields
            $order->update([
                'submitted_width' => $submittedWidth,
                'submitted_height' => $submittedHeight,
                'submitted_stitch_count' => $submittedStitchCount,
            ]);

            $previousStatus = $order->status;
            $tenant = $order->tenant;

            // Check if auto-transition to SUBMITTED is enabled
            $autoSubmit = $tenant->getSetting('auto_submit_on_upload', true);

            if ($autoSubmit && $order->status === OrderStatus::IN_PROGRESS) {
                // Transition to submitted
                $order = $this->workflowService->transitionTo($order, OrderStatus::SUBMITTED);

                // Log status change
                $order->statusHistory()->create([
                    'tenant_id' => $order->tenant_id,
                    'from_status' => $previousStatus->value,
                    'to_status' => OrderStatus::SUBMITTED->value,
                    'changed_by_user_id' => $submittedBy->id,
                    'changed_at' => now(),
                    'notes' => $notes ?? 'Work files uploaded',
                ]);

                // Check if auto-transition to IN_REVIEW is enabled
                $autoReview = $tenant->getSetting('auto_review_on_submit', false);

                if ($autoReview) {
                    $order = $this->workflowService->transitionTo($order, OrderStatus::IN_REVIEW);

                    // Log automatic review transition
                    $order->statusHistory()->create([
                        'tenant_id' => $order->tenant_id,
                        'from_status' => OrderStatus::SUBMITTED->value,
                        'to_status' => OrderStatus::IN_REVIEW->value,
                        'changed_by_user_id' => $submittedBy->id,
                        'changed_at' => now(),
                        'notes' => 'Auto-transitioned to review',
                    ]);
                }

                // Process commissions
                $this->commissionCalculator->processOrderCommissions($order, $order->status->value);
            } else {
                // Just log the file upload without status change
                if (!$autoSubmit) {
                    $order->statusHistory()->create([
                        'tenant_id' => $order->tenant_id,
                        'from_status' => $order->status->value,
                        'to_status' => $order->status->value,
                        'changed_by_user_id' => $submittedBy->id,
                        'changed_at' => now(),
                        'notes' => ($notes ? $notes . ' - ' : '') . 'Work files uploaded (status unchanged)',
                    ]);
                }
            }

            return $order;
        });
    }
}
