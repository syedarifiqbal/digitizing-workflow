<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreateRevisionOrderAction;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\OrderFileController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoicePdfService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ClientPortalController extends Controller
{
    /**
     * Resolve the Client for the authenticated user.
     * Uses withTrashed() so that soft-deleted clients (admin deleted them
     * after the user was created) don't produce a false "no client" 403.
     */
    private function resolveClient(Request $request): Client
    {
        $user = $request->user();

        if (!$user->client_id) {
            abort(403, 'Your account is not linked to a client record. Please contact support.');
        }

        $client = $user->client()->withTrashed()->first();

        if (!$client) {
            abort(403, 'No client record found for your account. Please contact support.');
        }

        return $client;
    }

    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        $client = $this->resolveClient($request);

        $orderMap = fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'title' => $order->title,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'priority' => $order->priority->value,
            'priority_label' => $order->priority->label(),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'delivered_at' => $order->delivered_at,
        ];

        // In-progress orders
        $inProgressOrders = Order::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [
                OrderStatus::RECEIVED,
                OrderStatus::ASSIGNED,
                OrderStatus::IN_PROGRESS,
                OrderStatus::SUBMITTED,
                OrderStatus::IN_REVIEW,
                OrderStatus::APPROVED,
            ])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'order_number', 'title', 'status', 'priority', 'created_at', 'updated_at', 'delivered_at'])
            ->map($orderMap);

        // Completed orders (last 10)
        $completedOrders = Order::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->orderBy('delivered_at', 'desc')
            ->limit(10)
            ->get(['id', 'order_number', 'title', 'status', 'priority', 'created_at', 'updated_at', 'delivered_at'])
            ->map($orderMap);

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
            'inProgressOrders' => $inProgressOrders,
            'completedOrders' => $completedOrders,
            'stats' => $stats,
            'invoiceStats' => $invoiceStats,
            'currency' => $user->tenant->getSetting('currency', 'USD'),
        ]);
    }

    public function orders(Request $request): Response
    {
        $client = $this->resolveClient($request);

        $section    = in_array($request->get('section'), ['quotes']) ? 'quotes' : 'orders';
        $activeTab  = $request->get('tab', 'in_progress');
        $typeFilter = $request->get('type', 'all');
        $search     = $request->get('search', '');

        $inProgressStatuses = [
            OrderStatus::RECEIVED,
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::APPROVED,
        ];
        $completedStatuses = [OrderStatus::DELIVERED, OrderStatus::CLOSED];

        $isQuote   = $section === 'quotes';
        $baseQuery = Order::query()->where('client_id', $client->id)->where('is_quote', $isQuote);

        // Tab counts for current section
        $stats = [
            'in_progress' => (clone $baseQuery)->whereIn('status', $inProgressStatuses)->count(),
            'completed'   => (clone $baseQuery)->whereIn('status', $completedStatuses)->count(),
        ];

        // Section switcher counts (for tab badges)
        $sectionCounts = [
            'orders' => Order::query()->where('client_id', $client->id)->where('is_quote', false)->count(),
            'quotes' => Order::query()->where('client_id', $client->id)->where('is_quote', true)->count(),
        ];

        // Completed type counts
        $completedBase = (clone $baseQuery)->whereIn('status', $completedStatuses);
        $completedStats = [
            'total'      => (clone $completedBase)->count(),
            'digitizing' => (clone $completedBase)->where('type', 'digitizing')->count(),
            'vector'     => (clone $completedBase)->where('type', 'vector')->count(),
            'patch'      => (clone $completedBase)->where('type', 'patch')->count(),
        ];

        $ordersQuery = (clone $baseQuery)
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('order_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%");
            }));

        if ($activeTab === 'completed') {
            $ordersQuery->whereIn('status', $completedStatuses)
                ->when($typeFilter !== 'all', fn ($q) => $q->where('type', $typeFilter))
                ->orderBy('delivered_at', 'desc');
        } else {
            $ordersQuery->whereIn('status', $inProgressStatuses)
                ->orderBy('created_at', 'desc');
        }

        $orders = $ordersQuery->paginate(15)->withQueryString()
            ->through(fn ($order) => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'title'          => $order->title,
                'type'           => $order->type->value,
                'status'         => $order->status->value,
                'status_label'   => $order->status->label(),
                'priority'       => $order->priority->value,
                'priority_label' => $order->priority->label(),
                'parent_order_id'=> $order->parent_order_id,
                'created_at'     => $order->created_at,
                'delivered_at'   => $order->delivered_at,
            ]);

        return Inertia::render('Client/Orders/Index', [
            'orders'         => $orders,
            'section'        => $section,
            'activeTab'      => $activeTab,
            'typeFilter'     => $typeFilter,
            'search'         => $search,
            'stats'          => $stats,
            'sectionCounts'  => $sectionCounts,
            'completedStats' => $completedStats,
        ]);
    }

    public function createOrder(Request $request): Response
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

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
            'fieldOptions' => [
                'placements' => [
                    'Cap Front', 'Cap Side', 'Cap Back', 'Low Profile Cap',
                    'Left Chest', 'Right Chest', 'Front Pocket', 'Full Front',
                    'Jacket Back', 'Cap/Chest', 'Knit Caps', 'Beanie Caps',
                    'Visor', 'Sleeve', 'Patches', 'Apron', 'Applique Design',
                    'Bags', 'Towel', 'Gloves', 'Blankets', 'Sweatshirt',
                    'Hoodie', 'Wrist Band', 'Seat Cover', 'Quilt',
                ],
                'fileFormats' => [
                    'Tajima Machine File (.DST)',
                    'Barudan Machine File (.DSB)',
                    'Janome Machine File (.JEF)',
                    'Compucon Machine File (.XXX)',
                    'Happy Machine File (.TAP)',
                    'Toyota Machine File (.100)',
                    '.EMB/.DST', '.PES/.DST', '.EXP/.DST',
                    '.CND/.DST', '.OFM/.DST', '.PXF/.DST',
                ],
                'patchTypes' => [
                    'Embroidered Patch', 'Sublimated', 'Emb + Sublimated',
                    'PVC Patch', 'Leather Patch', 'Chenille Patch',
                    'Woven Label', 'Lapel Pins',
                ],
                'backings' => [
                    'Iron on Patch', 'Velcro Patch', 'Plain sew on Patch',
                    'Peel and Stick', 'Peel & Stick + IronOn', 'Not Sure',
                ],
                'merrowBorders' => [
                    'Yes', 'No', 'Not Sure',
                ],
                'fabrics' => [
                    'Twill', 'Felt', 'Canvas', 'Denim', 'Leather',
                    'Velvet', 'Cotton', 'Polyester', 'Not Sure',
                ],
                'colorTypes' => [
                    'Pantone Matching System (PMS)',
                    'Three Color Process (RGB)',
                    'Four Color Process (CMYK)',
                ],
                'vectorOrderTypes' => [
                    'Screen Printing', 'DTG Printing', 'Silk Screening',
                    'Vinyl Cutting', 'Sublimation', 'Photo Touchup',
                ],
                'requiredFormats' => [
                    'AI (Adobe Illustrator)',
                    'EPS (Encapsulated PostScript)',
                    'PDF (Vector)',
                    'SVG (Scalable Vector)',
                    'CDR (CorelDRAW)',
                    'PSD (Photoshop)',
                    'PNG (High Res)',
                ],
            ],
        ]);
    }

    public function storeOrder(Request $request)
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

        $tenant = $user->tenant;

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'po_number'          => 'nullable|string|max:100',
            'quantity'           => 'nullable|integer|min:1',
            'priority'           => 'required|in:normal,rush',
            'type'               => 'required|in:digitizing,vector,patch',
            'is_quote'           => 'boolean',
            'width'              => 'nullable|string|max:50',
            'height'             => 'nullable|string|max:50',
            'placement'          => 'nullable|string|max:100',
            'file_format'        => 'nullable|string|max:100',
            'num_colors'         => 'nullable|integer|min:0',
            'patch_type'         => 'nullable|string|max:100',
            'backing'            => 'nullable|string|max:100',
            'merrow_border'      => 'nullable|string|max:100',
            'fabric'             => 'nullable|string|max:100',
            'shipping_address'   => 'nullable|string|max:1000',
            'need_by'            => 'nullable|date',
            'color_type'         => 'nullable|string|max:100',
            'vector_order_type'  => 'nullable|string|max:100',
            'required_format'    => 'nullable|string|max:100',
            'input_files'        => 'required|array|min:1',
            'input_files.*'      => 'file|max:' . ($tenant->getSetting('max_upload_mb', 25) * 1024),
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
            'tenant_id'          => $tenant->id,
            'client_id'          => $client->id,
            'sales_user_id'      => $client->sales_user_id,
            'order_number'       => $orderNumber,
            'sequence'           => $sequence,
            'title'              => $validated['title'],
            'po_number'          => $validated['po_number'] ?? null,
            'instructions'       => $validated['description'] ?? null,
            'quantity'           => $validated['quantity'] ?? null,
            'priority'           => $validated['priority'],
            'type'               => $validated['type'],
            'is_quote'           => $validated['is_quote'] ?? false,
            'width'              => $validated['width'] ?? null,
            'height'             => $validated['height'] ?? null,
            'placement'          => $validated['placement'] ?? null,
            'file_format'        => $validated['file_format'] ?? null,
            'num_colors'         => $validated['num_colors'] ?? null,
            'patch_type'         => $validated['patch_type'] ?? null,
            'backing'            => $validated['backing'] ?? null,
            'merrow_border'      => $validated['merrow_border'] ?? null,
            'fabric'             => $validated['fabric'] ?? null,
            'shipping_address'   => $validated['shipping_address'] ?? null,
            'need_by'            => $validated['need_by'] ?? null,
            'color_type'         => $validated['color_type'] ?? null,
            'vector_order_type'  => $validated['vector_order_type'] ?? null,
            'required_format'    => $validated['required_format'] ?? null,
            'status'             => OrderStatus::RECEIVED,
            'created_by'         => $user->id,
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
        $client = $this->resolveClient($request);

        // Ensure client can only view their own orders
        if ($order->client_id !== $client->id || $order->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized to view this order.');
        }

        $order->load([
            'client',
            'files' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'files.uploader:id,name',
            'comments' => fn ($q) => $q->with('user:id,name')->where('visibility', 'client')->latest(),
            'parent',
            'revisionOrders',
            'deliveryOptions',
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
                'created_at' => $order->created_at,
                'submitted_at' => $order->submitted_at,
                'approved_at' => $order->approved_at,
                'delivered_at' => $order->delivered_at,
                'updated_at' => $order->updated_at,
                // Work specs (original order form)
                'height'         => $order->height,
                'width'          => $order->width,
                'placement'      => $order->placement,
                'num_colors'     => $order->num_colors,
                'file_format'    => $order->file_format,
                'patch_type'     => $order->patch_type,
                'backing'        => $order->backing,
                'merrow_border'  => $order->merrow_border,
                'fabric'         => $order->fabric,
                'need_by'        => $order->need_by?->format('Y-m-d'),
                'color_type'     => $order->color_type,
                'vector_order_type' => $order->vector_order_type,
                'required_format'   => $order->required_format,
                // Submitted work specs
                'submitted_width' => $order->submitted_width,
                'submitted_height' => $order->submitted_height,
                'submitted_stitch_count' => $order->submitted_stitch_count,
            ],
            'deliveryOptions' => $order->deliveryOptions->map(fn ($o) => [
                'id'           => $o->id,
                'label'        => $o->label,
                'width'        => $o->width,
                'height'       => $o->height,
                'stitch_count' => $o->stitch_count,
                'price'        => $o->price !== null ? (float) $o->price : null,
                'currency'     => $o->currency,
            ]),
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
            'permanentInstructions' => [],
            'canCreateRevision' => in_array($order->status, [OrderStatus::DELIVERED, OrderStatus::CLOSED]),
        ]);
    }

    public function createRevision(Request $request, Order $order, CreateRevisionOrderAction $action)
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

        if ($order->client_id !== $client->id || $order->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized.');
        }

        if (! in_array($order->status, [OrderStatus::DELIVERED, OrderStatus::CLOSED])) {
            return back()->withErrors(['order' => 'Revisions can only be requested for delivered or closed orders.']);
        }

        $tenant = $user->tenant;
        $maxKilobytes = (int) ($tenant->getSetting('max_upload_mb', 25)) * 1024;

        $validated = $request->validate([
            'notes'   => ['nullable', 'string', 'max:2000'],
            'files'   => ['nullable', 'array'],
            'files.*' => ['file', 'max:' . $maxKilobytes],
        ]);

        $revisionOrder = $action->execute($order, $user, $validated['notes'] ?? null);

        $fileStorageService = app(\App\Services\FileStorageService::class);
        foreach ($request->file('files', []) as $file) {
            $fileStorageService->storeOrderFile($revisionOrder, $file, 'input', $user);
        }

        return redirect()->route('client.orders.show', $revisionOrder)
            ->with('success', 'Revision order created. Our team will review it shortly.');
    }

    public function storeComment(Request $request, Order $order)
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

        if ($order->client_id !== $client->id || $order->tenant_id !== $user->tenant_id) {
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

        /**
         * Commented these line because we don't need to send any notification to designer from client's comment since client comment will only be visible to admin
         */
        // Notify designer (if assigned)
        // if ($order->designer && $order->designer_id !== $user->id) {
            // $order->designer->notify($notification);
        // }

        return back()->with('success', 'Comment added successfully.');
    }

    public function invoices(Request $request): Response
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

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
        $client = $this->resolveClient($request);

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
            'stripe' => $this->clientStripeProps($user->tenant, $invoice),
        ]);
    }

    private function clientStripeProps(\App\Models\Tenant $tenant, Invoice $invoice): array
    {
        $stripe = new StripeService($tenant);
        $payableStatuses = [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE];

        return [
            'enabled'         => $stripe->isEnabled(),
            'checkout_mode'   => $stripe->getCheckoutMode(),
            'publishable_key' => $stripe->getCheckoutMode() === 'embedded' ? $stripe->getPublishableKey() : null,
            'payable'         => in_array($invoice->status, $payableStatuses),
        ];
    }

    public function downloadInvoicePdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        $user = $request->user();
        $client = $this->resolveClient($request);

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
