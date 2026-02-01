<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Enums\OrderType;
use App\Models\Commission;
use App\Models\User;
use App\Services\CommissionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CommissionController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Commission::class);

        $filters = $request->only(['search', 'role_type', 'user_id', 'is_paid', 'start_date', 'end_date']);
        $tenantId = $request->user()->tenant_id;

        $query = Commission::query()
            ->with(['user:id,name', 'order:id,order_number,title'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('order', function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['role_type'] ?? null, function ($query, $roleType) {
                if ($roleType !== 'all') {
                    $query->where('role_type', $roleType);
                }
            })
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                if ($userId !== 'all') {
                    $query->where('user_id', $userId);
                }
            })
            ->when(isset($filters['is_paid']), function ($query) use ($filters) {
                $isPaid = filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_paid', $isPaid);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->whereDate('earned_at', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->whereDate('earned_at', '<=', $endDate);
            });

        $commissions = $query->latest('earned_at')->paginate(20)->withQueryString();

        // Calculate totals
        $totals = [
            'total_earned' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->where('is_paid', true)->sum('total_amount'),
            'total_unpaid' => (clone $query)->where('is_paid', false)->sum('total_amount'),
        ];

        // Get users based on selected role type
        $selectedRole = $filters['role_type'] ?? 'all';
        $users = $this->getUsersByRole($request, $selectedRole);

        return Inertia::render('Commissions/Index', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role_type' => $selectedRole,
                'user_id' => $filters['user_id'] ?? 'all',
                'is_paid' => $filters['is_paid'] ?? '',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
            ],
            'roleTypeOptions' => [
                ['label' => 'All Roles', 'value' => 'all'],
                ['label' => 'Sales Commission', 'value' => RoleType::SALES->value],
                ['label' => 'Designer Bonus', 'value' => RoleType::DESIGNER->value],
            ],
            'users' => $users,
            'totals' => $totals,
            'currency' => $request->user()->tenant->getSetting('currency', 'USD'),
            'commissions' => [
                'data' => $commissions->through(fn (Commission $commission) => [
                    'id' => $commission->id,
                    'user' => $commission->user ? [
                        'id' => $commission->user->id,
                        'name' => $commission->user->name,
                    ] : null,
                    'order' => $commission->order ? [
                        'id' => $commission->order->id,
                        'order_number' => $commission->order->order_number,
                        'title' => $commission->order->title,
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
                'links' => $commissions->linkCollection(),
                'meta' => [
                    'total' => $commissions->total(),
                    'from' => $commissions->firstItem(),
                    'to' => $commissions->lastItem(),
                    'per_page' => $commissions->perPage(),
                ],
            ],
        ]);
    }

    public function myCommissions(Request $request): InertiaResponse
    {
        $user = $request->user();
        $filters = $request->only(['is_paid', 'type', 'start_date', 'end_date']);

        // Determine role type based on user role
        $roleType = $user->hasRole('Sales') ? RoleType::SALES : RoleType::DESIGNER;

        $query = Commission::query()
            ->with(['order:id,order_number,title,type'])
            ->where('user_id', $user->id)
            ->where('role_type', $roleType)
            ->when(isset($filters['is_paid']), function ($query) use ($filters) {
                $isPaid = filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_paid', $isPaid);
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                if ($type !== 'all') {
                    $query->whereHas('order', function ($q) use ($type) {
                        $q->where('type', $type);
                    });
                }
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->whereDate('earned_at', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->whereDate('earned_at', '<=', $endDate);
            });

        $commissions = $query->latest('earned_at')->paginate(20)->withQueryString();

        // Calculate totals
        $totals = [
            'total_earned' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->where('is_paid', true)->sum('total_amount'),
            'total_unpaid' => (clone $query)->where('is_paid', false)->sum('total_amount'),
        ];

        return Inertia::render('Commissions/MyEarnings', [
            'filters' => [
                'is_paid' => $filters['is_paid'] ?? '',
                'type' => $filters['type'] ?? 'all',
                'start_date' => $filters['start_date'] ?? '',
                'end_date' => $filters['end_date'] ?? '',
            ],
            'orderTypeOptions' => collect(OrderType::cases())
                ->reject(fn ($case) => $case === OrderType::QUOTATION)
                ->map(fn ($case) => [
                    'label' => ucwords(str_replace('_', ' ', $case->value)),
                    'value' => $case->value,
                ]),
            'roleType' => $roleType->value,
            'roleLabel' => $roleType->label(),
            'totals' => $totals,
            'currency' => $user->tenant->getSetting('currency', 'USD'),
            'commissions' => [
                'data' => $commissions->through(fn (Commission $commission) => [
                    'id' => $commission->id,
                    'order' => $commission->order ? [
                        'id' => $commission->order->id,
                        'order_number' => $commission->order->order_number,
                        'title' => $commission->order->title,
                        'type' => $commission->order->type->value,
                    ] : null,
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
                'links' => $commissions->linkCollection(),
                'meta' => [
                    'total' => $commissions->total(),
                    'from' => $commissions->firstItem(),
                    'to' => $commissions->lastItem(),
                    'per_page' => $commissions->perPage(),
                ],
            ],
        ]);
    }

    public function markAsPaid(Request $request, Commission $commission): RedirectResponse
    {
        $this->authorize('update', $commission);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $commission->markAsPaid($validated['notes'] ?? null);

        return back()->with('success', 'Commission marked as paid.');
    }

    public function bulkMarkAsPaid(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Commission::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $commissions = Commission::whereIn('id', $validated['ids'])->get();

        foreach ($commissions as $commission) {
            $this->authorize('update', $commission);
            $commission->markAsPaid($validated['notes'] ?? null);
        }

        return back()->with('success', count($commissions) . ' commission(s) marked as paid.');
    }

    public function updateExtraAmount(Request $request, Commission $commission): RedirectResponse
    {
        $this->authorize('update', $commission);

        $validated = $request->validate([
            'extra_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $calculator = app(CommissionCalculator::class);
        $calculator->updateExtraAmount(
            $commission,
            $validated['extra_amount'],
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Commission tip updated successfully.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Commission::class);

        $filters = $request->only(['search', 'role_type', 'is_paid', 'start_date', 'end_date']);
        $tenantId = $request->user()->tenant_id;

        $commissions = Commission::query()
            ->with(['user:id,name', 'order:id,order_number,title'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('order', function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($filters['role_type'] ?? null, function ($query, $roleType) {
                if ($roleType !== 'all') {
                    $query->where('role_type', $roleType);
                }
            })
            ->when(isset($filters['is_paid']), function ($query) use ($filters) {
                $isPaid = filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_paid', $isPaid);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->whereDate('earned_at', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->whereDate('earned_at', '<=', $endDate);
            })
            ->orderBy('earned_at', 'desc')
            ->get();

        $csv = $this->generateCsv($commissions);

        $filename = 'commissions_' . now()->format('Y-m-d_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportMy(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['is_paid', 'start_date', 'end_date']);
        $roleType = $user->hasRole('Sales') ? RoleType::SALES : RoleType::DESIGNER;

        $commissions = Commission::query()
            ->with(['order:id,order_number,title'])
            ->where('user_id', $user->id)
            ->where('role_type', $roleType)
            ->when(isset($filters['is_paid']), function ($query) use ($filters) {
                $isPaid = filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_paid', $isPaid);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->whereDate('earned_at', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->whereDate('earned_at', '<=', $endDate);
            })
            ->orderBy('earned_at', 'desc')
            ->get();

        $csv = $this->generateCsv($commissions, false);

        $filename = 'my_earnings_' . now()->format('Y-m-d_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function generateCsv($commissions, bool $includeUserColumn = true): string
    {
        $output = fopen('php://temp', 'r+');

        // Headers
        $headers = ['ID', 'Order Number', 'Order Title'];
        if ($includeUserColumn) {
            $headers[] = 'User';
        }
        $headers = array_merge($headers, [
            'Role Type',
            'Base Amount',
            'Extra Amount',
            'Total Amount',
            'Currency',
            'Earned On',
            'Earned At',
            'Paid',
            'Paid At',
            'Notes',
        ]);

        fputcsv($output, $headers);

        // Data
        foreach ($commissions as $commission) {
            $row = [
                $commission->id,
                $commission->order?->order_number ?? 'N/A',
                $commission->order?->title ?? 'N/A',
            ];

            if ($includeUserColumn) {
                $row[] = $commission->user?->name ?? 'Unknown';
            }

            $row = array_merge($row, [
                $commission->role_type->label(),
                number_format($commission->base_amount, 2),
                number_format($commission->extra_amount, 2),
                number_format($commission->total_amount, 2),
                $commission->currency,
                $commission->earned_on_status,
                $commission->earned_at?->toDateTimeString() ?? '',
                $commission->is_paid ? 'Yes' : 'No',
                $commission->paid_at?->toDateTimeString() ?? '',
                $commission->notes ?? '',
            ]);

            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function getUsersByRole(Request $request, string $roleType): array
    {
        if ($roleType === 'all') {
            return [['id' => 'all', 'name' => 'All Users']];
        }

        $roleName = $roleType === RoleType::SALES->value ? 'Sales' : 'Designer';

        $users = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->prepend(['id' => 'all', 'name' => 'All Users'])
            ->toArray();

        return $users;
    }
}
