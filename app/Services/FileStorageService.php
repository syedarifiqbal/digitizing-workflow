<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class FileStorageService
{
    public function storeOrderFile(Order $order, UploadedFile $file, string $type = 'input', ?User $uploader = null): OrderFile
    {
        $tenantId = $order->tenant_id;
        $disk = config('filesystems.default', 'local');
        $path = "tenants/{$tenantId}/orders/{$order->id}/{$type}";
        $filename = uniqid() . '-' . $file->getClientOriginalName();

        $storedPath = $file->storeAs($path, $filename, $disk);

        return $order->files()->create([
            'tenant_id' => $tenantId,
            'uploaded_by_user_id' => $uploader?->id ?? auth()->id(),
            'type' => $type,
            'disk' => $disk,
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
