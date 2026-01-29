<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only(['search', 'status', 'client_id', 'date_from', 'date_to']);

        $invoices = Invoice::query()
            ->with('client:id,name,company')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('company', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($filters['client_id'] ?? null, function ($query, $clientId) {
                if ($clientId !== 'all') {
                    $query->where('client_id', $clientId);
                }
            })
            ->when($filters['date_from'] ?? null, function ($query, $date) {
                $query->whereDate('issue_date', '>=', $date);
            })
            ->when($filters['date_to'] ?? null, function ($query, $date) {
                $query->whereDate('issue_date', '<=', $date);
            })
            ->latest('issue_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statusOptions = collect([
            ['label' => 'All Statuses', 'value' => 'all'],
        ])->merge(
            collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status) => [
                'label' => $status->label(),
                'value' => $status->value,
            ])
        );

        $clients = Client::orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => [
            'label' => $client->name,
            'value' => (string) $client->id,
        ])->prepend(['label' => 'All Clients', 'value' => 'all']);

        return Inertia::render('Invoices/Index', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
                'client_id' => $filters['client_id'] ?? 'all',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'statusOptions' => $statusOptions,
            'clients' => $clients,
            'invoices' => [
                'data' => $invoices->through(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number ?? sprintf('#%05d', $invoice->id),
                    'client_name' => $invoice->client?->name ?? '—',
                    'client_company' => $invoice->client?->company,
                    'status' => $invoice->status?->value,
                    'status_label' => $invoice->status?->label(),
                    'issue_date' => $invoice->issue_date?->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'total_amount' => $invoice->total_amount,
                    'currency' => $invoice->currency,
                    'is_overdue' => $invoice->status === InvoiceStatus::OVERDUE,
                ]),
                'links' => $invoices->linkCollection(),
                'meta' => [
                    'total' => $invoices->total(),
                    'from' => $invoices->firstItem(),
                    'to' => $invoices->lastItem(),
                    'per_page' => $invoices->perPage(),
                ],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        $user = $request->user();
        $tenant = $user->tenant;
        $tenantId = $user->tenant_id;

        $clientsCollection = Client::orderBy('name')->get(['id', 'name']);
        $clients = $clientsCollection->map(fn (Client $client) => [
            'id' => (string) $client->id,
            'name' => $client->name,
        ]);

        $prefillOrderIds = collect($request->input('orders', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $prefilledOrderIdsArray = $prefillOrderIds->values();

        $selectedClientId = $request->input('client_id');
        $prefilledOrders = collect();

        if ($prefillOrderIds->isNotEmpty()) {
            $prefilledOrders = Order::query()
                ->forTenant($tenantId)
                ->whereIn('id', $prefillOrderIds)
                ->get();

            if ($prefilledOrders->isEmpty()) {
                throw ValidationException::withMessages([
                    'orders' => 'Selected orders could not be found.',
                ]);
            }

            $prefilledClientId = $prefilledOrders->first()->client_id;
            $selectedClientId = $selectedClientId ?: (string) $prefilledClientId;

            $invalid = $prefilledOrders->first(fn (Order $order) => $order->client_id !== (int) $selectedClientId
                || $order->is_invoiced
                || ! in_array($order->status->value, [OrderStatus::DELIVERED->value, OrderStatus::CLOSED->value], true));

            if ($invalid) {
                throw ValidationException::withMessages([
                    'orders' => 'One or more selected orders are not eligible for invoicing.',
                ]);
            }
        }

        if (! $selectedClientId && $clientsCollection->isNotEmpty()) {
            $selectedClientId = (string) $clientsCollection->first()->id;
        }

        $initialOrders = collect();
        if ($selectedClientId) {
            $initialOrders = $this->eligibleOrdersQuery($tenantId, (int) $selectedClientId)
                ->limit(50)
                ->get()
                ->map(fn (Order $order) => $this->transformOrderForSelection($order))
                ->values();
        }

        $prefilledOrdersTransformed = $prefilledOrders
            ->map(fn (Order $order) => $this->transformOrderForSelection($order))
            ->unique('id')
            ->values();

        return Inertia::render('Invoices/Create', [
            'clients' => $clients,
            'defaults' => [
                'payment_terms' => $tenant?->getSetting('default_payment_terms', 'Net 30'),
                'tax_rate' => (float) ($tenant?->getSetting('default_tax_rate', 0)),
                'currency' => $tenant?->getSetting('currency', 'USD'),
            ],
            'initialClientId' => $selectedClientId ? (string) $selectedClientId : '',
            'initialOrders' => $initialOrders,
            'prefilledOrderIds' => $prefilledOrderIdsArray,
            'prefilledOrders' => $prefilledOrdersTransformed,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        $data = $request->validated();

        $orderIds = collect($data['order_ids'] ?? [])->map(fn ($id) => (int) $id)->filter();
        $orderNotes = collect($data['order_notes'] ?? []);
        $ordersById = collect();

        if ($orderIds->isNotEmpty()) {
            $orders = $this->eligibleOrdersQuery($tenantId, (int) $data['client_id'])
                ->whereIn('id', $orderIds)
                ->get()
                ->keyBy('id');

            if ($orders->count() !== $orderIds->count()) {
                throw ValidationException::withMessages([
                    'order_ids' => 'One or more selected orders are invalid or unavailable.',
                ]);
            }

            $ordersById = $orders;
        }

        $orderItems = $orderIds->map(function (int $orderId) use ($ordersById, $orderNotes) {
            $order = $ordersById->get($orderId);

            return [
                'order_id' => $orderId,
                'description' => trim(($order->order_number ?? 'Order #' . $orderId) . ' - ' . ($order->title ?? '')),
                'quantity' => 1,
                'unit_price' => (float) ($order->price ?? 0),
                'amount' => round((float) ($order->price ?? 0), 2),
                'note' => trim($orderNotes->get($orderId) ?? '') ?: null,
            ];
        });

        $customItems = collect($data['custom_items'] ?? [])
            ->filter(fn ($item) => filled($item['description'] ?? null))
            ->map(function ($item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                return [
                    'order_id' => null,
                    'description' => $item['description'],
                    'quantity' => $quantity > 0 ? $quantity : 1,
                    'unit_price' => $unitPrice,
                    'amount' => round(($quantity > 0 ? $quantity : 1) * $unitPrice, 2),
                    'note' => null,
                ];
            });

        $items = $orderItems->concat($customItems)->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'order_ids' => 'Add at least one order or custom line item.',
            ]);
        }

        $subtotal = $items->sum('amount');
        $taxRate = isset($data['tax_rate']) ? (float) $data['tax_rate'] : (float) ($user->tenant?->getSetting('default_tax_rate', 0));
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $discount = min($subtotal, (float) ($data['discount_amount'] ?? 0));
        $total = max($subtotal + $taxAmount - $discount, 0);

        DB::transaction(function () use ($items, $data, $tenantId, $user, $taxRate, $taxAmount, $discount, $total, $ordersById, $subtotal) {
            $nextSequence = (int) Invoice::where('tenant_id', $tenantId)->max('sequence') + 1;
            $prefix = $user->tenant?->getSetting('invoice_number_prefix', 'INV-') ?? 'INV-';
            $invoiceNumber = sprintf('%s%05d', $prefix, $nextSequence);

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'client_id' => $data['client_id'],
                'created_by' => $user->id,
                'sequence' => $nextSequence,
                'invoice_number' => $invoiceNumber,
                'status' => InvoiceStatus::DRAFT,
                'issue_date' => ! empty($data['issue_date']) ? $data['issue_date'] : now()->toDateString(),
                'due_date' => ! empty($data['due_date']) ? $data['due_date'] : null,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'currency' => $data['currency'],
                'notes' => $data['notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'tenant_id' => $tenantId,
                    'order_id' => $item['order_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'note' => $item['note'] ?? null,
                ]);

                if ($item['order_id'] && $ordersById->has($item['order_id'])) {
                    $order = $ordersById->get($item['order_id']);
                    $order->update([
                        'is_invoiced' => true,
                        'invoiced_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created.');
    }

    public function eligibleOrders(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'selected' => ['array'],
            'selected.*' => ['integer'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $clientId = (int) $data['client_id'];
        $selected = collect($data['selected'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();

        $query = $this->eligibleOrdersQuery($tenantId, $clientId)->limit(50);
        $orders = $query->get();

        if ($selected->isNotEmpty()) {
            $selectedOrders = $this->eligibleOrdersQuery($tenantId, $clientId)
                ->whereIn('id', $selected)
                ->get();
            $orders = $orders->merge($selectedOrders)->unique('id');
        }

        $orders = $orders
            ->map(fn (Order $order) => $this->transformOrderForSelection($order))
            ->values();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    private function eligibleOrdersQuery(int $tenantId, int $clientId)
    {
        return Order::query()
            ->select([
                'id',
                'order_number',
                'title',
                'price',
                'currency',
                'delivered_at',
                'status',
                'client_id',
            ])
            ->forTenant($tenantId)
            ->where('client_id', $clientId)
            ->whereIn('status', [OrderStatus::DELIVERED->value, OrderStatus::CLOSED->value])
            ->where('is_invoiced', false)
            ->where(function ($query) {
                $query->where('is_quote', false)->orWhereNull('is_quote');
            })
            ->orderByDesc('delivered_at')
            ->orderByDesc('id');
    }

    private function transformOrderForSelection(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'title' => $order->title,
            'price' => (float) ($order->price ?? 0),
            'currency' => $order->currency ?? 'USD',
            'delivered_at' => $order->delivered_at?->toDateTimeString(),
            'status' => $order->status->value,
            'client_id' => $order->client_id,
        ];
    }
}
