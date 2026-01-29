<?php

namespace App\Http\Controllers;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Actions\Orders\AssignOrderAction;
use App\Actions\Orders\SubmitWorkAction;
use App\Actions\Orders\TransitionOrderStatusAction;
use App\Services\FileStorageService;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
        private readonly WorkflowService $workflowService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only(['search', 'status', 'priority', 'client_id', 'designer_id', 'sales_user_id', 'type', 'quote']);
        $tenantId = $request->user()->tenant_id;
        $quoteView = filter_var($filters['quote'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = Order::query()
            ->with(['client:id,name', 'designer:id,name', 'sales:id,name'])
            ->forTenant($tenantId)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%");
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
            ->when($filters['type'] ?? null, function ($query, $type) {
                if ($type !== 'all') {
                    $query->where('type', $type);
                }
            })
            ->when($filters['client_id'] ?? null, function ($query, $clientId) {
                if ($clientId !== 'all') {
                    $query->where('client_id', $clientId);
                }
            })
            ->when($filters['designer_id'] ?? null, function ($query, $designerId) {
                if ($designerId !== 'all') {
                    $query->where('designer_id', $designerId);
                }
            })
            ->when($filters['sales_user_id'] ?? null, function ($query, $salesUserId) {
                if ($salesUserId !== 'all') {
                    $query->where('sales_user_id', $salesUserId);
                }
            })
            ->when($quoteView, fn ($query) => $query->where('is_quote', true), function ($query) {
                $query->where(function ($inner) {
                    $inner->where('is_quote', false)->orWhereNull('is_quote');
                });
            });

        if ($request->user()->isDesigner()) {
            $query->where('designer_id', $request->user()->id);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        $baseCountQuery = Order::forTenant($tenantId)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type');
        $orderCounts = (clone $baseCountQuery)
            ->where(function ($q) {
                $q->where('is_quote', false)->orWhereNull('is_quote');
            })
            ->pluck('total', 'type');
        $quoteCounts = (clone $baseCountQuery)->where('is_quote', true)->pluck('total', 'type');

        $showOrderCards = $request->user()->tenant->getSetting('show_order_cards', false);

        return Inertia::render('Orders/Index', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'priority' => $filters['priority'] ?? 'all',
                'type' => $filters['type'] ?? 'all',
                'quote' => $quoteView ? '1' : '0',
                'client_id' => $filters['client_id'] ?? 'all',
                'designer_id' => $filters['designer_id'] ?? 'all',
                'sales_user_id' => $filters['sales_user_id'] ?? 'all',
            ],
            'statusOptions' => collect(OrderStatus::cases())->map(fn ($case) => [
                'label' => ucwords(str_replace('_', ' ', $case->value)),
                'value' => $case->value,
            ]),
            'priorityOptions' => collect(OrderPriority::cases())->map(fn ($case) => [
                'label' => ucwords(str_replace('_', ' ', $case->value)),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(OrderType::cases())
                ->reject(fn ($case) => $case === OrderType::QUOTATION)
                ->map(fn ($case) => [
                    'label' => ucwords(str_replace('_', ' ', $case->value)),
                    'value' => $case->value,
                ]),
            'clients' => $this->clientOptions($request)->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
            ]),
            'designers' => $this->designerOptions($request)->map(fn ($designer) => [
                'id' => $designer->id,
                'name' => $designer->name,
            ]),
            'salesUsers' => $this->salesOptions($request)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]),
            'counts' => [
                'orders' => [
                    'digitizing' => $orderCounts[OrderType::DIGITIZING->value] ?? 0,
                    'vector' => $orderCounts[OrderType::VECTOR->value] ?? 0,
                    'patch' => $orderCounts[OrderType::PATCH->value] ?? 0,
                ],
                'quotes' => [
                    'digitizing' => $quoteCounts[OrderType::DIGITIZING->value] ?? 0,
                    'vector' => $quoteCounts[OrderType::VECTOR->value] ?? 0,
                    'patch' => $quoteCounts[OrderType::PATCH->value] ?? 0,
                ],
            ],
            'typeStats' => $this->typeStats($tenantId, $filters['type'] ?? 'all', $quoteView),
            'showOrderCards' => $showOrderCards,
            'invoiceBulkActionEnabled' => $request->user()->tenant->getSetting('enable_invoice_bulk_action', true),
            'orders' => [
                'data' => $orders->through(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'po_number' => $order->po_number,
                    'title' => $order->title,
                    'type' => $order->type->value,
                    'status' => $order->status->value,
                    'priority' => $order->priority->value,
                    'client' => $order->client?->name,
                    'designer' => $order->designer?->name,
                    'sales' => $order->sales?->name,
                    'client_id' => $order->client_id,
                    'price' => (float) ($order->price ?? 0),
                    'currency' => $order->currency ?? $request->user()->tenant->getSetting('currency', 'USD'),
                    'is_invoiced' => (bool) $order->is_invoiced,
                    'is_invoice_eligible' => ! $order->is_invoiced && in_array($order->status->value, [OrderStatus::DELIVERED->value, OrderStatus::CLOSED->value], true),
                    'due_at' => optional($order->due_at)?->toDateTimeString(),
                    'created_at' => $order->created_at?->toDateTimeString(),
                ]),
                'links' => $orders->linkCollection(),
                'meta' => [
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'per_page' => $orders->perPage(),
                ],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Order::class);

        $defaultType = $request->string('type', OrderType::DIGITIZING->value)->toString();
        $isQuote = $request->boolean('quote');

        return Inertia::render('Orders/Create', [
            'clients' => $this->clientOptions($request)->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ]),
            'priorityOptions' => collect(OrderPriority::cases())->map(fn ($case) => [
                'label' => ucwords(str_replace('_', ' ', $case->value)),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(OrderType::cases())
                ->reject(fn ($case) => $case === OrderType::QUOTATION)
                ->map(fn ($case) => [
                    'label' => ucwords(str_replace('_', ' ', $case->value)),
                    'value' => $case->value,
                ]),
            'currency' => $request->user()->tenant->getSetting('currency', 'USD'),
            'defaultType' => $defaultType,
            'isQuote' => $isQuote,
            'fieldOptions' => $this->fieldOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $tenant = $request->user()->tenant;
        $data = $this->validateOrder($request);
        $isQuote = $request->boolean('quote');

        $order = DB::transaction(function () use ($request, $tenant, $data, $isQuote) {
            $sequence = ((int) Order::forTenant($tenant->id)->max('sequence')) + 1;
            $orderNumber = $this->generateOrderNumber($tenant->getSetting('order_number_prefix', ''), $sequence);

            /** @var Order $order */
            $currency = strtoupper($data['currency'] ?? $tenant->getSetting('currency', 'USD'));

            $order = Order::create([
                'tenant_id' => $tenant->id,
                'client_id' => $data['client_id'],
                'created_by' => $request->user()->id,
                'order_number' => $orderNumber,
                'po_number' => $data['po_number'] ?? null,
                'sequence' => $sequence,
                'type' => $data['type'],
                'is_quote' => $isQuote,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'height' => $data['height'] ?? null,
                'width' => $data['width'] ?? null,
                'placement' => $data['placement'] ?? null,
                'num_colors' => $data['num_colors'] ?? null,
                'file_format' => $data['file_format'] ?? null,
                'patch_type' => $data['patch_type'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'backing' => $data['backing'] ?? null,
                'merrow_border' => $data['merrow_border'] ?? null,
                'fabric' => $data['fabric'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'need_by' => $data['need_by'] ?? null,
                'color_type' => $data['color_type'] ?? null,
                'vector_order_type' => $data['vector_order_type'] ?? null,
                'required_format' => $data['required_format'] ?? null,
                'status' => OrderStatus::RECEIVED,
                'priority' => $data['priority'],
                'due_at' => $data['due_at'] ?? null,
                'price_amount' => $data['price_amount'] ?? null,
                'currency' => $currency,
                'source' => $data['source'] ?? null,
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $this->fileStorageService->storeOrderFile($order, $file, 'input');
            }

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order created.');
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['client', 'designer', 'sales', 'creator', 'files', 'statusHistory.changedBy', 'assignments.designer', 'assignments.assignedBy', 'revisions.requestedBy', 'commissions.user', 'comments.user']);

        $user = $request->user();
        $canAssign = $user->isAdmin() || $user->isManager();

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'po_number' => $order->po_number,
                'title' => $order->title,
                'type' => $order->type->value,
                'status' => $order->status->value,
                'priority' => $order->priority->value,
                'instructions' => $order->instructions,
                'height' => $order->height,
                'width' => $order->width,
                'placement' => $order->placement,
                'num_colors' => $order->num_colors,
                'file_format' => $order->file_format,
                'patch_type' => $order->patch_type,
                'quantity' => $order->quantity,
                'backing' => $order->backing,
                'merrow_border' => $order->merrow_border,
                'fabric' => $order->fabric,
                'shipping_address' => $order->shipping_address,
                'need_by' => $order->need_by?->format('Y-m-d'),
                'color_type' => $order->color_type,
                'vector_order_type' => $order->vector_order_type,
                'required_format' => $order->required_format,
                'client' => [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'email' => $order->client->email,
                ],
                'designer' => $order->designer ? [
                    'id' => $order->designer->id,
                    'name' => $order->designer->name,
                ] : null,
                'sales' => $order->sales ? [
                    'id' => $order->sales->id,
                    'name' => $order->sales->name,
                ] : null,
                'price_amount' => $order->price_amount,
                'currency' => $order->currency,
                'due_at' => optional($order->due_at)?->toDateTimeString(),
                'created_at' => $order->created_at?->toDateTimeString(),
            ],
            'inputFiles' => $order->files->where('type', 'input')->values()->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'size' => $file->size,
                'uploaded_at' => $file->created_at?->toDateTimeString(),
                'download_url' => URL::temporarySignedRoute(
                    'orders.files.download',
                    now()->addMinutes(30),
                    ['file' => $file->id]
                ),
            ]),
            'outputFiles' => in_array($order->status, [
                OrderStatus::SUBMITTED,
                OrderStatus::IN_REVIEW,
                OrderStatus::REVISION_REQUESTED,
                OrderStatus::APPROVED,
                OrderStatus::DELIVERED,
                OrderStatus::CLOSED,
            ]) ? $order->files->where('type', 'output')->values()->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'size' => $file->size,
                'uploaded_at' => $file->created_at?->toDateTimeString(),
                'download_url' => URL::temporarySignedRoute(
                    'orders.files.download',
                    now()->addMinutes(30),
                    ['file' => $file->id]
                ),
            ]) : [],
            'canAssign' => $canAssign,
            'designers' => $canAssign ? $this->designerOptions($request)->map(fn ($designer) => [
                'id' => $designer->id,
                'name' => $designer->name,
            ]) : [],
            'salesUsers' => $canAssign ? $this->salesOptions($request)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]) : [],
            'allowedTransitions' => collect($this->workflowService->getAllowedTransitionsForRole(
                    $order->status,
                    $user->isDesigner() ? 'designer' : 'admin'
                ))
                ->reject(fn ($status) => in_array($status, [
                    OrderStatus::SUBMITTED,
                    OrderStatus::REVISION_REQUESTED,
                    OrderStatus::DELIVERED,
                    OrderStatus::CANCELLED,
                ]))
                ->map(fn ($status) => [
                    'value' => $status->value,
                    'label' => $this->workflowService->getStatusLabel($status),
                    'style' => $this->workflowService->getTransitionStyle($status),
                ])
                ->values(),
            'canRequestRevision' => $canAssign && in_array($order->status, [OrderStatus::SUBMITTED, OrderStatus::IN_REVIEW]),
            'canDeliver' => $canAssign && $order->status === OrderStatus::APPROVED,
            'canCancel' => $canAssign && $this->workflowService->canTransitionTo($order->status, OrderStatus::CANCELLED),
            'revisions' => $order->revisions->map(fn ($rev) => [
                'id' => $rev->id,
                'notes' => $rev->notes,
                'status' => $rev->status,
                'requested_by' => $rev->requestedBy?->name,
                'created_at' => $rev->created_at?->toDateTimeString(),
                'resolved_at' => $rev->resolved_at?->toDateTimeString(),
            ]),
            'canSubmitWork' => $order->status === OrderStatus::IN_PROGRESS
                && $user->isDesigner()
                && $order->designer_id === $user->id,
            'maxUploadMb' => (int) $request->user()->tenant->getSetting('max_upload_mb', 25),
            'allowedOutputExtensions' => $request->user()->tenant->getSetting('allowed_output_extensions', ''),
            'timeline' => $this->buildTimeline($order),
            'enableDesignerTips' => $order->tenant->getSetting('enable_designer_tips', false),
            'currency' => $order->tenant->getSetting('currency', 'USD'),
            'commissions' => $order->commissions->map(fn ($commission) => [
                'id' => $commission->id,
                'user' => $commission->user ? [
                    'id' => $commission->user->id,
                    'name' => $commission->user->name,
                ] : null,
                'role_type' => $commission->role_type->value,
                'role_label' => $commission->role_type->label(),
                'base_amount' => $commission->base_amount,
                'extra_amount' => $commission->extra_amount,
                'total_amount' => $commission->total_amount,
                'currency' => $commission->currency,
                'earned_on_status' => $commission->earned_on_status,
                'earned_at' => $commission->earned_at?->toDateTimeString(),
                'is_paid' => $commission->is_paid,
                'paid_at' => $commission->paid_at?->toDateTimeString(),
                'notes' => $commission->notes,
            ]),
            'comments' => $order->comments
                ->when($user->hasRole('Client'), fn ($comments) => $comments->where('visibility', 'client'))
                ->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'visibility' => $comment->visibility,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at?->toDateTimeString(),
                ])
                ->values(),
        ]);
    }

    public function edit(Request $request, Order $order): Response
    {
        $this->authorize('update', $order);

        $order->load('files');

        return Inertia::render('Orders/Edit', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'po_number' => $order->po_number,
                'client_id' => $order->client_id,
                'title' => $order->title,
                'type' => $order->type->value,
                'priority' => $order->priority->value,
                'instructions' => $order->instructions,
                'height' => $order->height,
                'width' => $order->width,
                'placement' => $order->placement,
                'num_colors' => $order->num_colors,
                'file_format' => $order->file_format,
                'patch_type' => $order->patch_type,
                'quantity' => $order->quantity,
                'backing' => $order->backing,
                'merrow_border' => $order->merrow_border,
                'fabric' => $order->fabric,
                'shipping_address' => $order->shipping_address,
                'need_by' => $order->need_by?->format('Y-m-d'),
                'color_type' => $order->color_type,
                'vector_order_type' => $order->vector_order_type,
                'required_format' => $order->required_format,
                'price_amount' => $order->price_amount,
                'currency' => $order->currency,
                'due_at' => $order->due_at?->format('Y-m-d'),
                'source' => $order->source,
                'is_quote' => $order->is_quote,
            ],
            'files' => $order->files->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'type' => $file->type,
                'size' => $file->size,
            ]),
            'clients' => $this->clientOptions($request)->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ]),
            'priorityOptions' => collect(OrderPriority::cases())->map(fn ($case) => [
                'label' => ucwords(str_replace('_', ' ', $case->value)),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(OrderType::cases())
                ->reject(fn ($case) => $case === OrderType::QUOTATION)
                ->map(fn ($case) => [
                    'label' => ucwords(str_replace('_', ' ', $case->value)),
                    'value' => $case->value,
                ]),
            'maxUploadMb' => (int) $request->user()->tenant->getSetting('max_upload_mb', 25),
            'fieldOptions' => $this->fieldOptions(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $this->validateOrderUpdate($request, $order);

        $order->update([
            'client_id' => $data['client_id'],
            'po_number' => $data['po_number'] ?? null,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'height' => $data['height'] ?? null,
            'width' => $data['width'] ?? null,
            'placement' => $data['placement'] ?? null,
            'num_colors' => $data['num_colors'] ?? null,
            'file_format' => $data['file_format'] ?? null,
            'patch_type' => $data['patch_type'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'backing' => $data['backing'] ?? null,
            'merrow_border' => $data['merrow_border'] ?? null,
            'fabric' => $data['fabric'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'need_by' => $data['need_by'] ?? null,
            'color_type' => $data['color_type'] ?? null,
            'vector_order_type' => $data['vector_order_type'] ?? null,
            'required_format' => $data['required_format'] ?? null,
            'priority' => $data['priority'],
            'due_at' => $data['due_at'] ?? null,
            'price_amount' => $data['price_amount'] ?? null,
            'currency' => strtoupper($data['currency'] ?? $request->user()->tenant->getSetting('currency', 'USD')),
            'source' => $data['source'] ?? null,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $this->fileStorageService->storeOrderFile($order, $file, 'input');
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        $order->delete();

        return back()->with('success', 'Order deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $orders = Order::forTenant($request->user()->tenant_id)
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($orders as $order) {
            $this->authorize('delete', $order);
            $order->delete();
        }

        return back()->with('success', 'Selected orders deleted.');
    }

    public function assign(Request $request, Order $order, AssignOrderAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'designer_id' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id),
            ],
        ]);

        $designer = User::findOrFail($validated['designer_id']);

        if (! $designer->isDesigner()) {
            return back()->withErrors(['designer_id' => 'Selected user is not a designer.']);
        }

        $action->execute($order, $designer, $request->user());

        return back()->with('success', 'Designer assigned.');
    }

    public function unassign(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        // End current assignment
        $order->assignments()
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        // Clear designer from order
        $order->update(['designer_id' => null]);

        return back()->with('success', 'Designer unassigned.');
    }

    public function assignSales(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'sales_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id),
            ],
        ]);

        $salesUser = User::findOrFail($validated['sales_user_id']);

        if (! $salesUser->hasRole('Sales')) {
            return back()->withErrors(['sales_user_id' => 'Selected user is not a sales person.']);
        }

        $previousSales = $order->sales;

        $order->update(['sales_user_id' => $salesUser->id]);

        // Log in status history
        OrderStatusHistory::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'from_status' => $order->status->value,
            'to_status' => $order->status->value,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
            'notes' => $previousSales
                ? "Sales reassigned from {$previousSales->name} to {$salesUser->name}"
                : "Sales assigned to {$salesUser->name}",
        ]);

        return back()->with('success', 'Sales person assigned.');
    }

    public function unassignSales(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $previousSales = $order->sales;
        $order->update(['sales_user_id' => null]);

        if ($previousSales) {
            OrderStatusHistory::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'from_status' => $order->status->value,
                'to_status' => $order->status->value,
                'changed_by_user_id' => $request->user()->id,
                'changed_at' => now(),
                'notes' => "Sales unassigned ({$previousSales->name})",
            ]);
        }

        return back()->with('success', 'Sales person unassigned.');
    }

    public function updateStatus(Request $request, Order $order, TransitionOrderStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderStatus::cases()))],
        ]);

        $user = $request->user();
        $newStatus = OrderStatus::from($validated['status']);
        $role = $user->isDesigner() ? 'designer' : 'admin';

        if (! $this->workflowService->canRoleTransition($order->status, $newStatus, $role)) {
            return back()->withErrors(['status' => 'You do not have permission to perform this transition.']);
        }

        try {
            $order = $action->execute($order, $newStatus, $user);

            return back()->with('success', 'Order status updated.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function requestRevision(Request $request, Order $order, \App\Actions\Orders\RequestRevisionAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        if (! ($user->isAdmin() || $user->isManager())) {
            abort(403, 'Only admins and managers can request revisions.');
        }

        if (! in_array($order->status, [OrderStatus::SUBMITTED, OrderStatus::IN_REVIEW])) {
            return back()->withErrors(['status' => 'Revisions can only be requested for submitted or in-review orders.']);
        }

        $action->execute($order, $user, $validated['notes'] ?? null);

        return back()->with('success', 'Revision requested.');
    }

    public function cancelOrder(Request $request, Order $order, TransitionOrderStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if (! $this->workflowService->canTransitionTo($order->status, OrderStatus::CANCELLED)) {
            return back()->withErrors(['status' => 'This order cannot be cancelled.']);
        }

        $order = $action->execute($order, OrderStatus::CANCELLED, $user);

        // Log the cancellation reason
        $order->statusHistory()->create([
            'tenant_id' => $order->tenant_id,
            'from_status' => OrderStatus::CANCELLED->value,
            'to_status' => OrderStatus::CANCELLED->value,
            'changed_by_user_id' => $user->id,
            'changed_at' => now(),
            'notes' => "Cancellation reason: {$validated['reason']}",
        ]);

        return back()->with('success', 'Order cancelled.');
    }

    public function deliverOrder(Request $request, Order $order, TransitionOrderStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'file_ids' => ['nullable', 'array'],
            'file_ids.*' => ['integer', 'exists:order_files,id'],
            'designer_tip' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        if ($order->status !== OrderStatus::APPROVED) {
            return back()->withErrors(['status' => 'Only approved orders can be delivered.']);
        }

        // Store designer tip to pass to commission calculator
        $designerTip = null;
        if (isset($validated['designer_tip']) && $validated['designer_tip'] > 0) {
            $designerTip = (float) $validated['designer_tip'];
        }

        // Transition to delivered
        $order = $action->execute($order, OrderStatus::DELIVERED, $user, $designerTip);

        // Log delivery message
        if (! empty($validated['message'])) {
            $order->statusHistory()->create([
                'tenant_id' => $order->tenant_id,
                'from_status' => OrderStatus::DELIVERED->value,
                'to_status' => OrderStatus::DELIVERED->value,
                'changed_by_user_id' => $user->id,
                'changed_at' => now(),
                'notes' => "Delivery message: {$validated['message']}",
            ]);
        }

        // Send delivery notification to client with attachments
        $order->load('client');
        $notification = new \App\Notifications\OrderDeliveredNotification(
            $order,
            $validated['message'] ?? null,
            $validated['file_ids'] ?? []
        );

        $clientUser = User::where('client_id', $order->client_id)
            ->where('tenant_id', $order->tenant_id)
            ->first();

        if ($clientUser) {
            $clientUser->notify($notification);
        } elseif ($order->client?->email) {
            \Illuminate\Support\Facades\Notification::route('mail', $order->client->email)
                ->notify($notification);
        }

        return back()->with('success', 'Order delivered and client notified.');
    }

    public function submitWork(Request $request, Order $order, SubmitWorkAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $user = $request->user();

        if (! $user->isDesigner() || $order->designer_id !== $user->id) {
            abort(403, 'Only the assigned designer can submit work.');
        }

        $tenant = $user->tenant;
        $maxUploadMb = (int) ($tenant->getSetting('max_upload_mb', 25));
        $maxKilobytes = $maxUploadMb * 1024;
        $allowedExtensions = collect(explode(',', (string) $tenant->getSetting('allowed_output_extensions', '')))
            ->map(fn ($ext) => strtolower(trim($ext)))
            ->filter()
            ->values();

        $fileRules = ['file', 'max:'.$maxKilobytes];
        if ($allowedExtensions->isNotEmpty()) {
            $fileRules[] = 'mimes:'.$allowedExtensions->implode(',');
        }

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => $fileRules,
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $action->execute(
                $order,
                $request->file('files', []),
                $request->user(),
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Work submitted successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['files' => $e->getMessage()]);
        }
    }

    private function validateOrder(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;
        $maxUploadMb = (int) ($tenant->getSetting('max_upload_mb', 25));
        $maxKilobytes = $maxUploadMb * 1024;
        $allowedExtensions = collect(explode(',', (string) $tenant->getSetting('allowed_input_extensions', '')))
            ->map(fn ($ext) => strtolower(trim($ext)))
            ->filter()
            ->values();

        $attachmentRules = ['file', 'max:'.$maxKilobytes];
        if ($allowedExtensions->isNotEmpty()) {
            $attachmentRules[] = 'mimes:'.$allowedExtensions->implode(',');
        }

        $allowedTypes = collect(OrderType::cases())
            ->reject(fn ($case) => $case === OrderType::QUOTATION)
            ->map->value
            ->all();

        $rules = [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderPriority::cases()))],
            'type' => ['required', Rule::in($allowedTypes)],
            'due_at' => ['nullable', 'date'],
            'price_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'source' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => $attachmentRules,
            'quote' => ['nullable', 'boolean'],
            // Shared type-specific
            'height' => ['nullable', 'string', 'max:50'],
            'width' => ['nullable', 'string', 'max:50'],
            'placement' => ['nullable', 'string', 'max:100'],
            'num_colors' => ['nullable', 'integer', 'min:0'],
            // Digitizing
            'file_format' => ['nullable', 'string', 'max:100'],
            // Patch
            'patch_type' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'backing' => ['nullable', 'string', 'max:100'],
            'merrow_border' => ['nullable', 'string', 'max:100'],
            'fabric' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'need_by' => ['nullable', 'date'],
            // Vector
            'color_type' => ['nullable', 'string', 'max:100'],
            'vector_order_type' => ['nullable', 'string', 'max:100'],
            'required_format' => ['nullable', 'string', 'max:100'],
        ];

        return $request->validate($rules);
    }

    private function validateOrderUpdate(Request $request, Order $order): array
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;
        $maxUploadMb = (int) ($tenant->getSetting('max_upload_mb', 25));
        $maxKilobytes = $maxUploadMb * 1024;
        $allowedExtensions = collect(explode(',', (string) $tenant->getSetting('allowed_input_extensions', '')))
            ->map(fn ($ext) => strtolower(trim($ext)))
            ->filter()
            ->values();

        $attachmentRules = ['file', 'max:'.$maxKilobytes];
        if ($allowedExtensions->isNotEmpty()) {
            $attachmentRules[] = 'mimes:'.$allowedExtensions->implode(',');
        }

        $rules = [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderPriority::cases()))],
            'due_at' => ['nullable', 'date'],
            'price_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'source' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => $attachmentRules,
            // Shared type-specific
            'height' => ['nullable', 'string', 'max:50'],
            'width' => ['nullable', 'string', 'max:50'],
            'placement' => ['nullable', 'string', 'max:100'],
            'num_colors' => ['nullable', 'integer', 'min:0'],
            // Digitizing
            'file_format' => ['nullable', 'string', 'max:100'],
            // Patch
            'patch_type' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'backing' => ['nullable', 'string', 'max:100'],
            'merrow_border' => ['nullable', 'string', 'max:100'],
            'fabric' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'need_by' => ['nullable', 'date'],
            // Vector
            'color_type' => ['nullable', 'string', 'max:100'],
            'vector_order_type' => ['nullable', 'string', 'max:100'],
            'required_format' => ['nullable', 'string', 'max:100'],
        ];

        return $request->validate($rules);
    }

    private function typeStats(int $tenantId, string $type, bool $isQuote): ?array
    {
        if ($type === 'all') {
            return null;
        }

        $base = Order::forTenant($tenantId)
            ->where('type', $type)
            ->when($isQuote, fn ($query) => $query->where('is_quote', true), function ($query) {
                $query->where(function ($inner) {
                    $inner->where('is_quote', false)->orWhereNull('is_quote');
                });
            });

        $openStatuses = [
            OrderStatus::DELIVERED,
            OrderStatus::CLOSED,
            OrderStatus::CANCELLED,
        ];

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereNotIn('status', $openStatuses)->count(),
            'today' => (clone $base)->whereDate('created_at', Carbon::today())->count(),
            'in_progress' => (clone $base)->where('status', OrderStatus::IN_PROGRESS)->count(),
        ];
    }

    private function clientOptions(Request $request)
    {
        return Client::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function designerOptions(Request $request)
    {
        return User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Designer'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function salesOptions(Request $request)
    {
        return User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Sales'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function fieldOptions(): array
    {
        return [
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
                '.EMB/.DST',
                '.PES/.DST',
                '.EXP/.DST',
                '.CND/.DST',
                '.OFM/.DST',
                '.PXF/.DST',
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
        ];
    }

    private function generateOrderNumber(string $prefix, int $sequence): string
    {
        $prefix = $prefix !== '' ? strtoupper($prefix) : 'ORD';

        return sprintf('%s-%05d', $prefix, $sequence);
    }

    public function storeComment(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'visibility' => ['required', 'in:internal,client'],
        ]);

        // Only admins/managers can create internal comments
        if ($validated['visibility'] === 'internal' && !($request->user()->isAdmin() || $request->user()->isManager())) {
            abort(403, 'Only admins and managers can create internal comments.');
        }

        OrderComment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'visibility' => $validated['visibility'],
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    private function buildTimeline(Order $order): array
    {
        $events = collect();

        // Order created event
        $events->push([
            'type' => 'created',
            'description' => 'Order created',
            'user' => $order->creator?->name ?? 'System',
            'timestamp' => $order->created_at?->toDateTimeString(),
            'sort_at' => $order->created_at,
        ]);

        // Status change events
        foreach ($order->statusHistory as $history) {
            $fromLabel = ucwords(str_replace('_', ' ', $history->from_status->value));
            $toLabel = ucwords(str_replace('_', ' ', $history->to_status->value));

            $events->push([
                'type' => 'status_change',
                'description' => "Status changed from {$fromLabel} to {$toLabel}",
                'user' => $history->changedBy?->name ?? 'System',
                'notes' => $history->notes,
                'timestamp' => $history->changed_at?->toDateTimeString(),
                'sort_at' => $history->changed_at,
            ]);
        }

        // Assignment events
        foreach ($order->assignments as $assignment) {
            $events->push([
                'type' => 'assigned',
                'description' => "Assigned to {$assignment->designer?->name}",
                'user' => $assignment->assignedBy?->name ?? 'System',
                'timestamp' => $assignment->assigned_at?->toDateTimeString(),
                'sort_at' => $assignment->assigned_at,
            ]);

            if ($assignment->ended_at) {
                $events->push([
                    'type' => 'unassigned',
                    'description' => "Unassigned from {$assignment->designer?->name}",
                    'user' => null,
                    'timestamp' => $assignment->ended_at?->toDateTimeString(),
                    'sort_at' => $assignment->ended_at,
                ]);
            }
        }

        // Commission events
        foreach ($order->commissions as $commission) {
            $userName = $commission->user?->name ?? 'Unknown';
            $roleLabel = $commission->role_type->label();
            $amount = number_format($commission->total_amount, 2);

            $events->push([
                'type' => 'commission',
                'description' => "{$roleLabel} earned for {$userName}: {$commission->currency} {$amount}",
                'user' => 'System',
                'notes' => $commission->notes,
                'timestamp' => $commission->earned_at?->toDateTimeString(),
                'sort_at' => $commission->earned_at,
            ]);
        }

        return $events->sortBy('sort_at')->values()->map(fn ($event) => [
            'type' => $event['type'],
            'description' => $event['description'],
            'user' => $event['user'],
            'notes' => $event['notes'] ?? null,
            'timestamp' => $event['timestamp'],
        ])->all();
    }
}
