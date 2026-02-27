<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDeliveryOption;
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
     * @param  array<array>         $deliveryOptions  Array of ['label', 'width', 'height', 'stitch_count']
     */
    public function execute(
        Order $order,
        array $files,
        User $submittedBy,
        ?string $notes = null,
        ?string $submittedWidth = null,
        ?string $submittedHeight = null,
        ?int $submittedStitchCount = null,
        array $deliveryOptions = []
    ): Order {
        return DB::transaction(function () use ($order, $files, $submittedBy, $notes, $submittedWidth, $submittedHeight, $submittedStitchCount, $deliveryOptions) {
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

            // Sync delivery options (replace all existing ones for this order)
            if (! empty($deliveryOptions)) {
                OrderDeliveryOption::where('order_id', $order->id)->delete();
                foreach ($deliveryOptions as $sortOrder => $optData) {
                    OrderDeliveryOption::create([
                        'order_id'    => $order->id,
                        'tenant_id'   => $order->tenant_id,
                        'label'       => $optData['label'] ?? 'Option A',
                        'width'       => $optData['width'] ?? null,
                        'height'      => $optData['height'] ?? null,
                        'stitch_count' => isset($optData['stitch_count']) ? (int) $optData['stitch_count'] : null,
                        'sort_order'  => $sortOrder,
                    ]);
                }
            } elseif ($submittedWidth || $submittedHeight || $submittedStitchCount) {
                // Fallback: auto-create Option A from the simple fields when no options array sent
                OrderDeliveryOption::updateOrCreate(
                    ['order_id' => $order->id, 'sort_order' => 0],
                    [
                        'tenant_id'   => $order->tenant_id,
                        'label'       => 'Option A',
                        'width'       => $submittedWidth,
                        'height'      => $submittedHeight,
                        'stitch_count' => $submittedStitchCount,
                    ]
                );
            }

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
                // Log the file upload without status change (covers both resubmissions and when auto-submit is off)
                $isResubmit = $order->status !== OrderStatus::IN_PROGRESS;
                $order->statusHistory()->create([
                    'tenant_id' => $order->tenant_id,
                    'from_status' => $order->status->value,
                    'to_status' => $order->status->value,
                    'changed_by_user_id' => $submittedBy->id,
                    'changed_at' => now(),
                    'notes' => ($notes ? $notes . ' - ' : '') . ($isResubmit ? 'Work files resubmitted' : 'Work files uploaded (status unchanged)'),
                ]);
            }

            return $order;
        });
    }
}
