<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\InvoicePaymentRecordedNotification;
use App\Notifications\InvoiceSentNotification;
use App\Services\InvoicePdfService;
use App\Services\InvoiceWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Illuminate\Notifications\Notification as BaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceWorkflowService $workflow,
        private readonly InvoicePdfService $pdfService
    )
    {
    }
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
                    'can_edit' => $request->user()->can('update', $invoice) && $invoice->status === InvoiceStatus::DRAFT,
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

        return Inertia::render('Invoices/Form', [
            'mode' => 'create',
            'invoice' => null,
            'clients' => $clients,
            'defaults' => [
                'payment_terms' => $tenant?->getSetting('default_payment_terms', 'Net 30'),
                'tax_rate' => (float) ($tenant?->getSetting('default_tax_rate', 0)),
                'currency' => $tenant?->getSetting('currency', 'USD'),
            ],
            'initialClientId' => $selectedClientId ? (string) $selectedClientId : '',
            'initialOrders' => $initialOrders->all(),
            'prefilledOrderIds' => $prefilledOrderIdsArray,
            'prefilledOrders' => $prefilledOrdersTransformed->all(),
            'initialOrderNotes' => (object) [],
            'initialCustomItems' => [],
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

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items.order', 'payments.recordedBy']);
        $companyDetails = $request->user()->tenant->getSetting('company_details', []);
        $paidAmount = (float) $invoice->payments->sum('amount');
        $balance = max((float) $invoice->total_amount - $paidAmount, 0);

        return Inertia::render('Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number ?? sprintf('#%05d', $invoice->id),
                'status' => $invoice->status?->value,
                'status_label' => $invoice->status?->label(),
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'subtotal' => (float) $invoice->subtotal,
                'tax_rate' => (float) $invoice->tax_rate,
                'tax_amount' => (float) $invoice->tax_amount,
                'discount_amount' => (float) $invoice->discount_amount,
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => $paidAmount,
                'balance_due' => $balance,
                'currency' => $invoice->currency,
                'payment_terms' => $invoice->payment_terms,
                'notes' => $invoice->notes,
                'client' => [
                    'name' => $invoice->client?->name ?? '',
                    'company' => $invoice->client?->company,
                ],
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'amount' => (float) $item->amount,
                    'note' => $item->note,
                    'order_number' => $item->order?->order_number,
                ]),
                'payments' => $invoice->payments
                    ->sortByDesc('payment_date')
                    ->values()
                    ->map(fn (InvoicePayment $payment) => [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'payment_date' => $payment->payment_date?->toDateString(),
                        'reference' => $payment->reference,
                        'notes' => $payment->notes,
                        'recorded_by' => $payment->recordedBy?->name,
                    ])
                    ->all(),
            ],
            'companyDetails' => $companyDetails,
            'canEdit' => $request->user()->can('update', $invoice) && $invoice->status === InvoiceStatus::DRAFT,
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== InvoiceStatus::DRAFT, 403);

        $invoice->load(['items' => fn ($query) => $query->orderBy('id')]);
        $user = $request->user();
        $tenant = $user->tenant;
        $tenantId = $tenant->id;

        $clientsCollection = Client::orderBy('name')->get(['id', 'name']);
        $clients = $clientsCollection->map(fn (Client $client) => [
            'id' => (string) $client->id,
            'name' => $client->name,
        ]);

        $orderItems = $invoice->items->whereNotNull('order_id');
        $selectedOrderIds = $orderItems->pluck('order_id')->map(fn ($id) => (int) $id)->values();
        $selectedOrderIdsArray = $selectedOrderIds->all();
        $orderNotes = $orderItems->mapWithKeys(fn ($item) => [$item->order_id => $item->note])->toArray();
        $customItems = $invoice->items
            ->whereNull('order_id')
            ->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])
            ->values()
            ->all();

        $initialOrders = $this->eligibleOrdersQuery($tenantId, $invoice->client_id, $invoice->id)
            ->limit(50)
            ->get()
            ->map(fn (Order $order) => $this->transformOrderForSelection($order))
            ->values()
            ->all();

        return Inertia::render('Invoices/Form', [
            'mode' => 'edit',
            'invoice' => [
                'id' => $invoice->id,
                'client_id' => (string) $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'payment_terms' => $invoice->payment_terms,
                'tax_rate' => (float) $invoice->tax_rate,
                'discount_amount' => (float) $invoice->discount_amount,
                'currency' => $invoice->currency,
                'notes' => $invoice->notes,
                'selected_order_ids' => $selectedOrderIdsArray,
            ],
            'clients' => $clients,
            'defaults' => [
                'payment_terms' => $tenant?->getSetting('default_payment_terms', 'Net 30'),
                'tax_rate' => (float) ($tenant?->getSetting('default_tax_rate', 0)),
                'currency' => $tenant?->getSetting('currency', 'USD'),
            ],
            'initialClientId' => (string) $invoice->client_id,
            'initialOrders' => $initialOrders,
            'prefilledOrderIds' => $selectedOrderIdsArray,
            'prefilledOrders' => $initialOrders,
            'initialOrderNotes' => (object) $orderNotes,
            'initialCustomItems' => $customItems,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== InvoiceStatus::DRAFT, 403);

        $data = $request->validated();
        $clientId = $invoice->client_id;
        if ((int) ($data['client_id'] ?? $clientId) !== $clientId) {
            throw ValidationException::withMessages([
                'client_id' => 'Client cannot be changed for this invoice.',
            ]);
        }
        $tenantId = $request->user()->tenant_id;

        $orderIds = collect($data['order_ids'] ?? [])->map(fn ($id) => (int) $id)->filter();
        $orderNotes = collect($data['order_notes'] ?? []);
        $ordersById = collect();

        if ($orderIds->isNotEmpty()) {
            $orders = $this->eligibleOrdersQuery($tenantId, $clientId, $invoice->id)
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
        $taxRate = isset($data['tax_rate']) ? (float) $data['tax_rate'] : (float) ($request->user()->tenant?->getSetting('default_tax_rate', 0));
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $discount = min($subtotal, (float) ($data['discount_amount'] ?? 0));
        $total = max($subtotal + $taxAmount - $discount, 0);

        $existingOrderIds = $invoice->items()->whereNotNull('order_id')->pluck('order_id');

        DB::transaction(function () use ($invoice, $items, $data, $taxRate, $taxAmount, $discount, $total, $orderIds, $ordersById, $existingOrderIds, $subtotal) {
            $invoice->items()->delete();

            $invoice->update([
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
                    'tenant_id' => $invoice->tenant_id,
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

            $removedOrderIds = $existingOrderIds->diff($orderIds);
            if ($removedOrderIds->isNotEmpty()) {
                Order::whereIn('id', $removedOrderIds)->update([
                    'is_invoiced' => false,
                    'invoiced_at' => null,
                ]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if($invoice->status !== InvoiceStatus::DRAFT, 403);

        $data = $request->validate([
            'message' => ['nullable', 'string'],
            'attach_pdf' => ['nullable', 'boolean'],
        ]);

        $attachPdf = (bool) ($data['attach_pdf'] ?? false);
        $pdfData = null;
        $pdfName = null;

        if ($attachPdf) {
            try {
                if ($this->pdfService) {
                    $pdf = $this->pdfService->make($invoice);
                    $pdfData = $pdf->output();
                    $pdfName = ($invoice->invoice_number ?? 'invoice-' . $invoice->id) . '.pdf';
                } else {
                    return back()->withErrors([
                        'pdf' => 'PDF generation service is not configured. Install barryvdh/laravel-dompdf to enable attachments.',
                    ]);
                }
            } catch (\RuntimeException $e) {
                return back()->withErrors(['pdf' => $e->getMessage()]);
            }
        }

        $invoice = $this->workflow->transitionTo($invoice, InvoiceStatus::SENT);
        $invoice->logActivity('sent', 'Invoice sent to client.', $request->user()->id);
        $invoice->load('client');
        $this->notifyClient(
            $invoice,
            new InvoiceSentNotification($invoice, $data['message'] ?? null, $pdfData, $pdfName)
        );

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice marked as sent.');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if(! in_array($invoice->status, [InvoiceStatus::DRAFT, InvoiceStatus::SENT], true), 403);

        $this->workflow->transitionTo($invoice, InvoiceStatus::CANCELLED);
        $invoice->logActivity('cancelled', 'Invoice cancelled.', $request->user()->id);
        $this->releaseOrders($invoice);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice cancelled.');
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if(! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE, InvoiceStatus::PAID], true), 403);

        $this->workflow->transitionTo($invoice, InvoiceStatus::VOID);
        $invoice->logActivity('voided', 'Invoice voided.', $request->user()->id);
        $this->releaseOrders($invoice);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice voided.');
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if(! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE], true), 403);

        $this->workflow->transitionTo($invoice, InvoiceStatus::PAID);
        $invoice->logActivity('marked_paid', 'Invoice marked as paid.', $request->user()->id);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice marked as paid.');
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        abort_if(! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE], true), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        $invoice->load('payments', 'client');
        $this->syncPaymentStatus($invoice);
        $invoice->refresh()->load('payments', 'client');
        $balance = max((float) $invoice->total_amount - (float) $invoice->payments->sum('amount'), 0);

        $invoice->logActivity('payment_recorded', "Payment of {$data['amount']} recorded via {$data['payment_method']}.", $request->user()->id, [
            'payment_id' => $payment->id,
            'amount' => (float) $data['amount'],
            'method' => $data['payment_method'],
            'balance' => $balance,
        ]);

        $this->notifyClient(
            $invoice,
            new InvoicePaymentRecordedNotification(
                $invoice,
                (float) $payment->amount,
                $balance
            )
        );

        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded.');
    }

    public function print(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing(['client', 'items.order', 'payments']);
        $companyDetails = $request->user()->tenant->getSetting('company_details', []);
        $paidAmount = (float) $invoice->payments->sum('amount');
        $balance = max((float) $invoice->total_amount - $paidAmount, 0);

        $logoPath = $request->user()->tenant->getSetting('company_logo_path');
        $companyLogo = null;
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($logoPath);
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($logoPath) ?? 'image/png';
            $companyLogo = 'data:' . $mime . ';base64,' . base64_encode($contents);
        }

        $statusInfo = match ($invoice->status) {
            InvoiceStatus::PAID => ['label' => 'Paid', 'background' => '#dcfce7', 'border' => '#86efac', 'color' => '#15803d'],
            InvoiceStatus::PARTIALLY_PAID => ['label' => 'Partially Paid', 'background' => '#fef3c7', 'border' => '#fcd34d', 'color' => '#b45309'],
            InvoiceStatus::OVERDUE => ['label' => 'Overdue', 'background' => '#fee2e2', 'border' => '#fecaca', 'color' => '#b91c1c'],
            InvoiceStatus::VOID, InvoiceStatus::CANCELLED => ['label' => $invoice->status->label(), 'background' => '#f3f4f6', 'border' => '#e5e7eb', 'color' => '#4b5563'],
            InvoiceStatus::SENT => ['label' => 'Sent', 'background' => '#dbeafe', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
            default => ['label' => $invoice->status?->label() ?? 'Draft', 'background' => '#f3f4f6', 'border' => '#e5e7eb', 'color' => '#374151'],
        };

        return view('pdf.invoice', [
            'invoice' => $invoice,
            'companyDetails' => $companyDetails,
            'companyLogo' => $companyLogo,
            'bankDetails' => $request->user()->tenant->getSetting('bank_details', ''),
            'statusInfo' => $statusInfo,
            'paidAmount' => $paidAmount,
            'balance' => $balance,
            'printView' => true,
        ]);
    }

    public function downloadPdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if (! $this->pdfService) {
            abort(501, 'PDF generation is not configured. Please install barryvdh/laravel-dompdf.');
        }

        try {
            $pdf = $this->pdfService->make($invoice);
        } catch (RuntimeException $e) {
            abort(500, $e->getMessage());
        }

        $filename = ($invoice->invoice_number ?? 'invoice-' . $invoice->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function syncPaymentStatus(Invoice $invoice): void
    {
        $paidAmount = (float) $invoice->payments->sum('amount');
        $balance = max((float) $invoice->total_amount - $paidAmount, 0);

        if ($balance <= 0) {
            if ($invoice->status !== InvoiceStatus::PAID) {
                $this->workflow->transitionTo($invoice, InvoiceStatus::PAID);
            }
        } elseif ($paidAmount > 0) {
            if ($invoice->status !== InvoiceStatus::PARTIALLY_PAID) {
                $this->workflow->transitionTo($invoice, InvoiceStatus::PARTIALLY_PAID);
            }
        } elseif ($invoice->status === InvoiceStatus::PARTIALLY_PAID) {
            $this->workflow->transitionTo($invoice, InvoiceStatus::SENT);
        }
    }

    private function releaseOrders(Invoice $invoice): void
    {
        $orderIds = $invoice->items()->whereNotNull('order_id')->pluck('order_id');

        if ($orderIds->isNotEmpty()) {
            Order::whereIn('id', $orderIds)->update([
                'is_invoiced' => false,
                'invoiced_at' => null,
            ]);
        }
    }

    protected function notifyClient(Invoice $invoice, BaseNotification $notification): void
    {
        $email = $invoice->client?->email;

        if (! $email) {
            return;
        }

        NotificationFacade::route('mail', $email)->notify($notification);
    }

    public function eligibleOrders(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'selected' => ['array'],
            'selected.*' => ['integer'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $clientId = (int) $data['client_id'];
        $selected = collect($data['selected'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();
        $invoiceId = $data['invoice_id'] ?? null;

        if ($invoiceId) {
            $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($invoiceId);
            $this->authorize('update', $invoice);
            if ($invoice->client_id !== $clientId) {
                throw ValidationException::withMessages([
                    'client_id' => 'Client mismatch for the invoice.',
                ]);
            }
        }
        else {
            $this->authorize('create', Invoice::class);
        }

        $query = $this->eligibleOrdersQuery($tenantId, $clientId, $invoiceId)->limit(50);
        $orders = $query->get();

        if ($selected->isNotEmpty()) {
            $selectedOrders = $this->eligibleOrdersQuery($tenantId, $clientId, $invoiceId)
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

    private function eligibleOrdersQuery(int $tenantId, int $clientId, ?int $invoiceId = null)
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
            ->where(function ($query) use ($invoiceId) {
                $query->where(function ($inner) {
                    $inner->whereIn('status', [OrderStatus::DELIVERED->value, OrderStatus::CLOSED->value])
                        ->where('is_invoiced', false)
                        ->where(function ($q) {
                            $q->where('is_quote', false)->orWhereNull('is_quote');
                        });
                });

                if ($invoiceId) {
                    $query->orWhereIn('id', function ($sub) use ($invoiceId) {
                        $sub->select('order_id')
                            ->from('invoice_items')
                            ->where('invoice_id', $invoiceId)
                            ->whereNotNull('order_id');
                    });
                }
            })
            ->orderByDesc('delivered_at')
            ->orderByDesc('id');
    }

    public function report(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only(['status', 'client_id', 'date_from', 'date_to']);
        $tenantId = $request->user()->tenant_id;

        $query = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['status'] ?? null, function ($q, $status) {
                if ($status !== 'all') {
                    $q->where('status', $status);
                }
            })
            ->when($filters['client_id'] ?? null, function ($q, $clientId) {
                if ($clientId !== 'all') {
                    $q->where('client_id', $clientId);
                }
            })
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '<=', $d));

        $summary = [
            'total_invoiced' => (float) (clone $query)->sum('total_amount'),
            'total_paid' => (float) (clone $query)->whereIn('status', [InvoiceStatus::PAID])->sum('total_amount'),
            'total_outstanding' => (float) (clone $query)->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])->sum('total_amount'),
            'total_overdue' => (float) (clone $query)->where('status', InvoiceStatus::OVERDUE)->sum('total_amount'),
            'count_by_status' => collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $s) => [
                $s->value => (int) (clone $query)->where('status', $s)->count(),
            ])->all(),
        ];

        $agingBuckets = $this->calculateAging($tenantId);

        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name'])->map(fn (Client $c) => [
            'label' => $c->name,
            'value' => (string) $c->id,
        ])->prepend(['label' => 'All Clients', 'value' => 'all']);

        $statusOptions = collect([['label' => 'All Statuses', 'value' => 'all']])
            ->merge(collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $s) => ['label' => $s->label(), 'value' => $s->value]));

        return Inertia::render('Invoices/Report', [
            'filters' => [
                'status' => $filters['status'] ?? 'all',
                'client_id' => $filters['client_id'] ?? 'all',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            'summary' => $summary,
            'aging' => $agingBuckets,
            'clients' => $clients,
            'statusOptions' => $statusOptions,
            'currency' => $request->user()->tenant->getSetting('currency', 'USD'),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = $request->only(['status', 'client_id', 'date_from', 'date_to']);
        $tenantId = $request->user()->tenant_id;

        $invoices = Invoice::query()
            ->with('client:id,name,company')
            ->where('tenant_id', $tenantId)
            ->when($filters['status'] ?? null, function ($q, $status) {
                if ($status !== 'all') {
                    $q->where('status', $status);
                }
            })
            ->when($filters['client_id'] ?? null, function ($q, $clientId) {
                if ($clientId !== 'all') {
                    $q->where('client_id', $clientId);
                }
            })
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderBy('issue_date', 'desc')
            ->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Invoice Number', 'Client', 'Company', 'Status', 'Issue Date', 'Due Date',
            'Subtotal', 'Tax Rate', 'Tax Amount', 'Discount', 'Total', 'Currency', 'Payment Terms', 'Notes',
        ]);

        foreach ($invoices as $invoice) {
            fputcsv($output, [
                $invoice->invoice_number ?? sprintf('#%05d', $invoice->id),
                $invoice->client?->name ?? '',
                $invoice->client?->company ?? '',
                $invoice->status?->label() ?? '',
                $invoice->issue_date?->toDateString() ?? '',
                $invoice->due_date?->toDateString() ?? '',
                number_format((float) $invoice->subtotal, 2, '.', ''),
                number_format((float) $invoice->tax_rate, 2, '.', ''),
                number_format((float) $invoice->tax_amount, 2, '.', ''),
                number_format((float) $invoice->discount_amount, 2, '.', ''),
                number_format((float) $invoice->total_amount, 2, '.', ''),
                $invoice->currency,
                $invoice->payment_terms ?? '',
                $invoice->notes ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = 'invoices_' . now()->format('Y-m-d_His') . '.csv';

        return \Illuminate\Support\Facades\Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function calculateAging(int $tenantId): array
    {
        $outstanding = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
            ->with('client:id,name')
            ->get();

        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        $details = [];

        foreach ($outstanding as $invoice) {
            $dueDate = $invoice->due_date ?? $invoice->issue_date;
            $daysOverdue = $dueDate ? (int) now()->diffInDays($dueDate, false) * -1 : 0;
            $amount = (float) $invoice->total_amount;

            if ($daysOverdue <= 0) {
                $buckets['current'] += $amount;
                $bucket = 'current';
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30'] += $amount;
                $bucket = '1_30';
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60'] += $amount;
                $bucket = '31_60';
            } elseif ($daysOverdue <= 90) {
                $buckets['61_90'] += $amount;
                $bucket = '61_90';
            } else {
                $buckets['over_90'] += $amount;
                $bucket = 'over_90';
            }

            $details[] = [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number ?? sprintf('#%05d', $invoice->id),
                'client_name' => $invoice->client?->name ?? '—',
                'total_amount' => $amount,
                'due_date' => $dueDate?->toDateString(),
                'days_overdue' => max($daysOverdue, 0),
                'bucket' => $bucket,
                'status' => $invoice->status?->value,
                'status_label' => $invoice->status?->label(),
                'currency' => $invoice->currency,
            ];
        }

        return [
            'buckets' => $buckets,
            'details' => $details,
        ];
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
