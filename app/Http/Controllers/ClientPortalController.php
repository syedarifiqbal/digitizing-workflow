<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\OrderFileController;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
            ->where('client_id', $client->id)
            ->whereIn('status', [OrderStatus::DELIVERED])
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
            'total' => Order::where('client_id', $client->id)
                ->count(),
            'in_progress' => Order::where('client_id', $client->id)
                ->whereIn('status', [
                    OrderStatus::RECEIVED,
                    OrderStatus::ASSIGNED,
                    OrderStatus::IN_PROGRESS,
                    OrderStatus::SUBMITTED,
                    OrderStatus::IN_REVIEW,
                ])
                ->count(),
            'delivered' => Order::where('client_id', $client->id)
                ->where('status', OrderStatus::DELIVERED)
                ->count(),
            'completed' => Order::where('client_id', $client->id)
                ->where('status', OrderStatus::CLOSED)
                ->count(),
        ];

        // Invoice statistics
        $invoiceStats = [
            'unpaid_count' => Invoice::where('client_id', $client->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
                ->count(),
            'total_due' => Invoice::where('client_id', $client->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
                ->sum('total_amount'),
            'overdue_count' => Invoice::where('client_id', $client->id)
                ->where('status', InvoiceStatus::OVERDUE)
                ->count(),
        ];

        return Inertia::render('Client/Dashboard', [
            'recentOrders' => $recentOrders,
            'ordersNeedingAttention' => $ordersNeedingAttention,
            'stats' => $stats,
            'invoiceStats' => $invoiceStats,
            'currency' => $user->tenant->getSetting('currency', 'USD'),
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
        ],
        [],
        [
            'input_files.*' => 'input_files',   // remap array keys to the field name
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
        ['sequence' => $sequence, 'order_number' => $orderNumber] = Order::nextOrderNumber(
            $tenant->id,
            $tenant->getSetting('order_number_prefix', '')
        );

        // Create order
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'order_number' => $orderNumber,
            'title' => $validated['title'],
            'sequence' => $sequence,
            'instructions' => $validated['description'] ?? null,
            'quantity' => $validated['quantity'],
            'priority' => $validated['priority'],
            'type' => $validated['type'],
            'is_quote' => $validated['is_quote'] ?? false,
            'status' => OrderStatus::RECEIVED,
            'created_by' => $user->id,
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
            'comments' => fn ($q) => $q->where('visibility', 'client')->latest(),
            'comments.user:id,name',
            'parent',
            'revisionOrders',
        ]);

        // Only show delivered output files to client (after delivery)
        $showOutputFiles = in_array($order->status, [
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
            'download_url' => URL::temporarySignedRoute(
                'orders.files.download',
                now()->addMinutes(30),
                ['file' => $file->id]
            ),
        ]);

        $outputFiles = $showOutputFiles
            ? $order->files->where('type', 'output')->where('is_delivered', true)->values()->map(fn ($file) => [
                'id' => $file->id,
                'filename' => $file->filename,
                'file_size' => $file->file_size,
                'extension' => $file->extension,
                'created_at' => $file->created_at,
                'uploader' => $file->uploader ? ['id' => $file->uploader->id, 'name' => $file->uploader->name] : null,
                'download_url' => URL::temporarySignedRoute(
                    'orders.files.download',
                    now()->addMinutes(30),
                    ['file' => $file->id]
                ),
            ])
            : [];

        return Inertia::render('Client/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'description' => $order->instructions,
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
            'downloadInputZipUrl' => $order->files->where('type', 'input')->isNotEmpty()
                ? OrderFileController::signedZipUrl($order, 'input')
                : null,
            'downloadOutputZipUrl' => ($showOutputFiles && $order->files->where('type', 'output')->where('is_delivered', true)->isNotEmpty())
                ? OrderFileController::signedZipUrl($order, 'output')
                : null,
            'parentOrder' => $order->parent ? [
                'id' => $order->parent->id,
                'order_number' => $order->parent->order_number,
            ] : null,
            'revisionOrders' => $order->revisionOrders->map(fn ($rev) => [
                'id' => $rev->id,
                'order_number' => $rev->order_number,
                'status' => $rev->status->value,
                'status_label' => $rev->status->label(),
                'created_at' => $rev->created_at,
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

        $comment = \App\Models\OrderComment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'visibility' => 'client', // Clients can only create client-visible comments
            'body' => $validated['body'],
        ]);

        // Send comment notifications
        $notification = new \App\Notifications\OrderCommentNotification($order, $comment, $user);

        // Notify admin/managers of the tenant
        $admins = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Manager']))
            ->where('id', '!=', $user->id)
            ->get();
        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        // Notify designer (if assigned)
        if ($order->designer && $order->designer_id !== $user->id) {
            $order->designer->notify($notification);
        }

        return back()->with('success', 'Comment added successfully.');
    }

    public function invoices(Request $request): Response
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        $filters = $request->only(['status']);

        $invoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereNot('status', InvoiceStatus::DRAFT) // Clients cannot see draft invoices
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->orderBy('issue_date', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'is_overdue' => $invoice->status === InvoiceStatus::OVERDUE,
            ]);

        // Calculate totals for client
        $totals = [
            'unpaid_count' => Invoice::where('client_id', $client->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
                ->count(),
            'total_due' => Invoice::where('client_id', $client->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
                ->sum('total_amount'),
            'overdue_count' => Invoice::where('client_id', $client->id)
                ->where('status', InvoiceStatus::OVERDUE)
                ->count(),
        ];

        return Inertia::render('Client/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'totals' => $totals,
            'currency' => $user->tenant->getSetting('currency', 'USD'),
        ]);
    }

    public function showInvoice(Request $request, Invoice $invoice): Response
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        // Check authorization
        if ($invoice->client_id !== $client->id || $invoice->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized to view this invoice.');
        }

        // Clients cannot view draft invoices
        if ($invoice->status === InvoiceStatus::DRAFT) {
            abort(403, 'This invoice is not available.');
        }

        $invoice->load([
            'items.order:id,order_number,title',
            'payments' => fn ($q) => $q->orderBy('payment_date', 'desc'),
        ]);

        // Calculate balance due
        $totalPaid = $invoice->payments->sum('amount');
        $balanceDue = $invoice->total_amount - $totalPaid;

        return Inertia::render('Client/Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'subtotal' => $invoice->subtotal,
                'tax_rate' => $invoice->tax_rate,
                'tax_amount' => $invoice->tax_amount,
                'discount_amount' => $invoice->discount_amount,
                'total_amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'notes' => $invoice->notes,
                'payment_terms' => $invoice->payment_terms,
                'is_overdue' => $invoice->status === InvoiceStatus::OVERDUE,
                'balance_due' => $balanceDue,
            ],
            'items' => $invoice->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
                'order' => $item->order ? [
                    'id' => $item->order->id,
                    'order_number' => $item->order->order_number,
                    'title' => $item->order->title,
                ] : null,
            ]),
            'payments' => $invoice->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
            ]),
            'companyDetails' => $user->tenant->getSetting('company_details', []),
            'bankDetails' => $user->tenant->getSetting('bank_details'),
        ]);
    }

    public function downloadInvoicePdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            abort(403, 'No client record found for this user.');
        }

        // Check authorization
        if ($invoice->client_id !== $client->id || $invoice->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized to download this invoice.');
        }

        // Clients cannot download draft invoices
        if ($invoice->status === InvoiceStatus::DRAFT) {
            abort(403, 'This invoice is not available.');
        }

        try {
            $pdf = $pdfService->make($invoice);
        } catch (RuntimeException $e) {
            abort(500, $e->getMessage());
        }

        $filename = ($invoice->invoice_number ?? 'invoice-' . $invoice->id) . '.pdf';

        return $pdf->download($filename);
    }
}
