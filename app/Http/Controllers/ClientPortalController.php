<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        // Get the client associated with this user
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        // Recent orders (last 5)
        $recentOrders = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('client_id', $client->id)
            ->with(['designer:id,name', 'sales:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'order_number', 'title', 'status', 'priority', 'designer_id', 'sales_user_id', 'created_at', 'delivered_at'])
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'priority' => $order->priority->value,
                'priority_label' => $order->priority->label(),
                'designer' => $order->designer ? ['id' => $order->designer->id, 'name' => $order->designer->name] : null,
                'sales' => $order->sales ? ['id' => $order->sales->id, 'name' => $order->sales->name] : null,
                'created_at' => $order->created_at,
                'delivered_at' => $order->delivered_at,
            ]);

        // Orders needing attention (revision requested or delivered)
        $ordersNeedingAttention = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('client_id', $client->id)
            ->whereIn('status', [OrderStatus::REVISION_REQUESTED, OrderStatus::DELIVERED])
            ->with(['designer:id,name', 'sales:id,name'])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'order_number', 'title', 'status', 'priority', 'designer_id', 'sales_user_id', 'updated_at'])
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'priority' => $order->priority->value,
                'priority_label' => $order->priority->label(),
                'designer' => $order->designer ? ['id' => $order->designer->id, 'name' => $order->designer->name] : null,
                'sales' => $order->sales ? ['id' => $order->sales->id, 'name' => $order->sales->name] : null,
                'updated_at' => $order->updated_at,
            ]);

        // Order statistics
        $stats = [
            'total' => Order::where('tenant_id', $user->tenant_id)
                ->where('client_id', $client->id)
                ->count(),
            'in_progress' => Order::where('tenant_id', $user->tenant_id)
                ->where('client_id', $client->id)
                ->whereIn('status', [
                    OrderStatus::RECEIVED,
                    OrderStatus::ASSIGNED,
                    OrderStatus::IN_PROGRESS,
                    OrderStatus::SUBMITTED,
                    OrderStatus::IN_REVIEW,
                    OrderStatus::REVISION_REQUESTED,
                ])
                ->count(),
            'delivered' => Order::where('tenant_id', $user->tenant_id)
                ->where('client_id', $client->id)
                ->where('status', OrderStatus::DELIVERED)
                ->count(),
            'completed' => Order::where('tenant_id', $user->tenant_id)
                ->where('client_id', $client->id)
                ->where('status', OrderStatus::CLOSED)
                ->count(),
        ];

        return Inertia::render('Client/Dashboard', [
            'recentOrders' => $recentOrders,
            'ordersNeedingAttention' => $ordersNeedingAttention,
            'stats' => $stats,
        ]);
    }

    public function orders(Request $request): Response
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        $filters = $request->only(['search', 'status', 'priority']);

        $orders = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('client_id', $client->id)
            ->with(['designer:id,name', 'sales:id,name'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($filters['priority'] ?? null, function ($query, $priority) {
                if ($priority !== 'all') {
                    $query->where('priority', $priority);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'priority' => $order->priority->value,
                'priority_label' => $order->priority->label(),
                'designer' => $order->designer ? ['id' => $order->designer->id, 'name' => $order->designer->name] : null,
                'sales' => $order->sales ? ['id' => $order->sales->id, 'name' => $order->sales->name] : null,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'delivered_at' => $order->delivered_at,
            ]);

        return Inertia::render('Client/Orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    public function createOrder(Request $request): Response
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        $tenant = $user->tenant;

        return Inertia::render('Client/Orders/Create', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'email' => $client->email,
            ],
            'allowedInputExtensions' => explode(',', $tenant->getSetting('allowed_input_extensions', 'jpg,jpeg,png,pdf')),
            'maxUploadMb' => $tenant->getSetting('max_upload_mb', 25),
        ]);
    }

    public function storeOrder(Request $request)
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        $tenant = $user->tenant;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'priority' => 'required|in:normal,rush',
            'type' => 'required|in:digitizing,vector,patch',
            'is_quote' => 'boolean',
            'input_files' => 'required|array|min:1',
            'input_files.*' => 'file|max:' . ($tenant->getSetting('max_upload_mb', 25) * 1024),
        ]);

        $allowedExtensions = explode(',', $tenant->getSetting('allowed_input_extensions', 'jpg,jpeg,png,pdf'));

        // Validate file extensions
        foreach ($request->file('input_files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return back()->withErrors([
                    'input_files' => "File type .{$extension} is not allowed. Allowed types: " . implode(', ', $allowedExtensions)
                ]);
            }
        }

        // Generate order number
        $prefix = $tenant->getSetting('order_number_prefix', '');
        $lastOrder = Order::where('tenant_id', $tenant->id)->latest('id')->first();
        $nextNumber = $lastOrder ? (intval(substr($lastOrder->order_number, strlen($prefix))) + 1) : 1;
        $orderNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $sequence = ((int) Order::forTenant($tenant->id)->max('sequence')) + 1;

        // Create order
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'order_number' => $orderNumber,
            'title' => $validated['title'],
            'sequence' => $sequence,
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'priority' => $validated['priority'],
            'type' => $validated['type'],
            'is_quote' => $validated['is_quote'] ?? false,
            'status' => OrderStatus::RECEIVED,
            'created_by_user_id' => $user->id,
        ]);

        // Upload files
        $fileStorageService = app(\App\Services\FileStorageService::class);
        foreach ($request->file('input_files') as $file) {
            $fileStorageService->storeOrderFile($order, $file, 'input', $user);
        }

        return redirect()->route('client.orders.show', $order)
            ->with('success', 'Order created successfully!');
    }

    public function showOrder(Request $request, Order $order): Response
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        // Ensure client can only view their own orders
        if ($order->client_id !== $client->id || $order->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized to view this order.');
        }

        $order->load([
            'client',
            'designer:id,name',
            'sales:id,name',
            'files' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'files.uploader:id,name',
            'revisions' => fn ($q) => $q->where('status', 'pending')->orderBy('created_at', 'desc'),
            'revisions.requestedBy:id,name',
            'comments' => fn ($q) => $q->where('visibility', 'client')->latest(),
            'comments.user:id,name',
        ]);

        // Only show output files after submission
        $showOutputFiles = in_array($order->status, [
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::APPROVED,
            OrderStatus::DELIVERED,
            OrderStatus::CLOSED,
        ]);

        $inputFiles = $order->files->where('type', 'input')->map(fn ($file) => [
            'id' => $file->id,
            'filename' => $file->filename,
            'file_size' => $file->file_size,
            'extension' => $file->extension,
            'created_at' => $file->created_at,
            'uploader' => $file->uploader ? ['id' => $file->uploader->id, 'name' => $file->uploader->name] : null,
            'download_url' => route('orders.files.download', ['file' => $file->id]),
        ]);

        $outputFiles = $showOutputFiles
            ? $order->files->where('type', 'output')->map(fn ($file) => [
                'id' => $file->id,
                'filename' => $file->filename,
                'file_size' => $file->file_size,
                'extension' => $file->extension,
                'created_at' => $file->created_at,
                'uploader' => $file->uploader ? ['id' => $file->uploader->id, 'name' => $file->uploader->name] : null,
                'download_url' => route('orders.files.download', ['file' => $file->id]),
            ])
            : [];

        return Inertia::render('Client/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'description' => $order->description,
                'quantity' => $order->quantity,
                'priority' => $order->priority->value,
                'priority_label' => $order->priority->label(),
                'type' => $order->type,
                'is_quote' => $order->is_quote,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'designer' => $order->designer ? ['id' => $order->designer->id, 'name' => $order->designer->name] : null,
                'sales' => $order->sales ? ['id' => $order->sales->id, 'name' => $order->sales->name] : null,
                'created_at' => $order->created_at,
                'submitted_at' => $order->submitted_at,
                'approved_at' => $order->approved_at,
                'delivered_at' => $order->delivered_at,
                'updated_at' => $order->updated_at,
            ],
            'inputFiles' => $inputFiles,
            'outputFiles' => $outputFiles,
            'showOutputFiles' => $showOutputFiles,
            'revisions' => $order->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'notes' => $revision->notes,
                'status' => $revision->status,
                'requested_by' => $revision->requestedBy ? ['id' => $revision->requestedBy->id, 'name' => $revision->requestedBy->name] : null,
                'created_at' => $revision->created_at,
            ]),
            'comments' => $order->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => ['id' => $comment->user->id, 'name' => $comment->user->name],
                'created_at' => $comment->created_at,
            ]),
        ]);
    }

    public function storeComment(Request $request, Order $order)
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client || $order->client_id !== $client->id || $order->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized to comment on this order.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        \App\Models\OrderComment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'visibility' => 'client', // Clients can only create client-visible comments
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Comment added successfully.');
    }
}
