<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $query = WebhookLog::latest();

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'success') {
                $query->successful();
            } elseif ($status === 'failed') {
                $query->failed();
            }
        }

        $logs = $query->paginate(25)->withQueryString();

        return Inertia::render('WebhookLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'status']),
        ]);
    }
}
