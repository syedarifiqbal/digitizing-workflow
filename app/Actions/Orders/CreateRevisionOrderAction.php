<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateRevisionOrderAction
{
    public function execute(Order $parentOrder, User $createdBy, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($parentOrder, $createdBy, $notes) {
            // Determine revision number suffix
            $revisionCount = Order::where('parent_order_id', $parentOrder->id)->count();
            $revisionNumber = $revisionCount + 1;
            $orderNumber = "{$parentOrder->order_number}-R{$revisionNumber}";

            $sequence = ((int) Order::forTenant($parentOrder->tenant_id)->max('sequence')) + 1;

            // Create the revision order with copied details
            $revisionOrder = Order::create([
                'tenant_id' => $parentOrder->tenant_id,
                'parent_order_id' => $parentOrder->id,
                'client_id' => $parentOrder->client_id,
                'created_by' => $createdBy->id,
                'designer_id' => null,
                'sales_user_id' => $parentOrder->sales_user_id,
                'order_number' => $orderNumber,
                'po_number' => $parentOrder->po_number,
                'sequence' => $sequence,
                'type' => $parentOrder->type,
                'is_quote' => false,
                'title' => $parentOrder->title,
                'instructions' => $notes ?? $parentOrder->instructions,
                'height' => $parentOrder->height,
                'width' => $parentOrder->width,
                'placement' => $parentOrder->placement,
                'num_colors' => $parentOrder->num_colors,
                'file_format' => $parentOrder->file_format,
                'patch_type' => $parentOrder->patch_type,
                'quantity' => $parentOrder->quantity,
                'backing' => $parentOrder->backing,
                'merrow_border' => $parentOrder->merrow_border,
                'fabric' => $parentOrder->fabric,
                'shipping_address' => $parentOrder->shipping_address,
                'need_by' => $parentOrder->need_by,
                'color_type' => $parentOrder->color_type,
                'vector_order_type' => $parentOrder->vector_order_type,
                'required_format' => $parentOrder->required_format,
                'status' => OrderStatus::RECEIVED,
                'priority' => $parentOrder->priority,
                'currency' => $parentOrder->currency,
                'source' => $parentOrder->source,
            ]);

            // Copy input files from parent
            $inputFiles = $parentOrder->files()->where('type', 'input')->get();
            foreach ($inputFiles as $file) {
                $newPath = str_replace(
                    "orders/{$parentOrder->id}/",
                    "orders/{$revisionOrder->id}/",
                    $file->path
                );

                // Copy the physical file
                if (Storage::disk($file->disk)->exists($file->path)) {
                    Storage::disk($file->disk)->copy($file->path, $newPath);
                }

                // Create the file record
                OrderFile::create([
                    'tenant_id' => $revisionOrder->tenant_id,
                    'order_id' => $revisionOrder->id,
                    'uploaded_by_user_id' => $createdBy->id,
                    'type' => 'input',
                    'disk' => $file->disk,
                    'path' => $newPath,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                ]);
            }

            // Log status history
            $revisionOrder->statusHistory()->create([
                'tenant_id' => $revisionOrder->tenant_id,
                'from_status' => OrderStatus::RECEIVED->value,
                'to_status' => OrderStatus::RECEIVED->value,
                'changed_by_user_id' => $createdBy->id,
                'changed_at' => now(),
                'notes' => "Revision order created from {$parentOrder->order_number}",
            ]);

            return $revisionOrder;
        });
    }
}
