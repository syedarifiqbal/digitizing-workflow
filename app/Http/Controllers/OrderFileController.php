<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class OrderFileController extends Controller
{
    public function destroy(Request $request, OrderFile $file): RedirectResponse
    {
        $order = $file->order;

        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (! $request->user()->isAdmin() && ! $request->user()->isManager()) {
            abort(403);
        }

        $disk = Storage::disk($file->disk);

        if ($disk->exists($file->path)) {
            $disk->delete($file->path);
        }

        $file->delete();

        return back()->with('success', 'File deleted.');
    }

    public function download(Request $request, OrderFile $file): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Invalid or expired download link.');
        }

        $order = $file->order;

        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($file->path, $file->original_name);
    }

    public function downloadZip(Request $request, Order $order, string $type): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Invalid or expired download link.');
        }

        if ($order->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (! in_array($type, ['input', 'output'])) {
            abort(400, 'Invalid file type.');
        }

        $query = $order->files()->where('type', $type);

        // For client portal users (those with a client_id), only include delivered output files
        if ($type === 'output' && $request->user()->client_id) {
            $query->where('is_delivered', true);
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            abort(404, 'No files found.');
        }

        $zipFileName = "{$order->order_number}-{$type}-files.zip";
        $tempPath = tempnam(sys_get_temp_dir(), 'zip_');

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create zip file.');
        }

        foreach ($files as $file) {
            $disk = Storage::disk($file->disk);
            if ($disk->exists($file->path)) {
                $zip->addFile($disk->path($file->path), $file->original_name);
            }
        }

        $zip->close();

        return response()->download($tempPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public static function signedUrl(OrderFile $file, int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'orders.files.download',
            now()->addMinutes($minutes),
            ['file' => $file->id]
        );
    }

    public static function signedZipUrl(Order $order, string $type, int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'orders.files.download-zip',
            now()->addMinutes($minutes),
            ['order' => $order->id, 'type' => $type]
        );
    }
}
