<?php

namespace App\Http\Controllers;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Client;
use App\Models\Order;
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

        $filters = $request->only(['search', 'status', 'priority', 'client_id', 'designer_id', 'type', 'quote']);
        $tenantId = $request->user()->tenant_id;
        $quoteView = filter_var($filters['quote'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = Order::query()
            ->with(['client:id,name', 'designer:id,name'])
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
            'orders' => [
                'data' => $orders->through(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'title' => $order->title,
                    'type' => $order->type->value,
                    'status' => $order->status->value,
                    'priority' => $order->priority->value,
                    'client' => $order->client?->name,
                    'designer' => $order->designer?->name,
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
                'created_by_user_id' => $request->user()->id,
                'order_number' => $orderNumber,
                'sequence' => $sequence,
                'type' => $data['type'],
                'is_quote' => $isQuote,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
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

        $order->load(['client', 'designer', 'files']);

        $user = $request->user();
        $canAssign = $user->isAdmin() || $user->isManager();

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'type' => $order->type->value,
                'status' => $order->status->value,
                'priority' => $order->priority->value,
                'instructions' => $order->instructions,
                'client' => [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'email' => $order->client->email,
                ],
                'designer' => $order->designer ? [
                    'id' => $order->designer->id,
                    'name' => $order->designer->name,
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
            'allowedTransitions' => collect($this->workflowService->getAllowedTransitions($order->status))
                ->reject(fn ($status) => $status === OrderStatus::SUBMITTED)
                ->map(fn ($status) => [
                    'value' => $status->value,
                    'label' => $this->workflowService->getStatusLabel($status),
                    'style' => $this->workflowService->getTransitionStyle($status),
                ])
                ->values(),
            'canSubmitWork' => $order->status === OrderStatus::IN_PROGRESS
                && $user->isDesigner()
                && $order->designer_id === $user->id,
            'maxUploadMb' => (int) $request->user()->tenant->getSetting('max_upload_mb', 25),
            'allowedOutputExtensions' => $request->user()->tenant->getSetting('allowed_output_extensions', ''),
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
                'client_id' => $order->client_id,
                'title' => $order->title,
                'type' => $order->type->value,
                'priority' => $order->priority->value,
                'instructions' => $order->instructions,
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
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $this->validateOrderUpdate($request);

        $order->update([
            'client_id' => $data['client_id'],
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
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

    public function updateStatus(Request $request, Order $order, TransitionOrderStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderStatus::cases()))],
        ]);

        try {
            $newStatus = OrderStatus::from($validated['status']);
            $action->execute($order, $newStatus, $request->user());

            return back()->with('success', 'Order status updated.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
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

        return $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['required', 'string', 'max:255'],
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
        ]);
    }

    private function validateOrderUpdate(Request $request): array
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

        return $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderPriority::cases()))],
            'due_at' => ['nullable', 'date'],
            'price_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'source' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => $attachmentRules,
        ]);
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

    private function generateOrderNumber(string $prefix, int $sequence): string
    {
        $prefix = $prefix !== '' ? strtoupper($prefix) : 'ORD';

        return sprintf('%s-%05d', $prefix, $sequence);
    }
}
