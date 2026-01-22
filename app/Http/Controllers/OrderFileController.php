<?php

namespace App\Http\Controllers;

use App\Models\OrderFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public static function signedUrl(OrderFile $file, int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'orders.files.download',
            now()->addMinutes($minutes),
            ['file' => $file->id]
        );
    }
}
