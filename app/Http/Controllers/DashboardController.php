<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleType;
use App\Models\Client;
use App\Models\Commission;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect clients to their portal dashboard
        if ($user->hasRole('Client')) {
            return Inertia::location(route('client.dashboard'));
        }

        // Determine role for dashboard type
        $role = $this->determineRole($user);

        // Fetch stats based on role
        $stats = match ($role) {
            'admin', 'manager' => $this->getAdminStats(),
            'designer' => $this->getDesignerStats($user),
            'sales' => $this->getSalesStats($user),
            default => [],
        };

        return Inertia::render('Dashboard', [
            'role' => $role,
            'stats' => $stats,
        ]);
    }

    private function determineRole(User $user): string
    {
        if ($user->hasRole('Admin')) {
            return 'admin';
        }
        if ($user->hasRole('Manager')) {
            return 'manager';
        }
        if ($user->hasRole('Designer')) {
            return 'designer';
        }
        if ($user->hasRole('Sales')) {
            return 'sales';
        }

        return 'admin'; // Fallback
    }

    private function getAdminStats(): array
    {
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Order stats
        $orderStats = $this->getOrderStats($startOfDay, $startOfMonth);

        // Revenue stats
        $revenueStats = $this->getRevenueStats($startOfDay, $startOfWeek, $startOfMonth, $startOfLastMonth, $endOfLastMonth, $startOfYear);

        // Client stats
        $clientStats = [
            'total' => Client::count(),
            'active' => Client::where('is_active', true)->count(),
        ];

        // Recent orders
        $recentOrders = Order::query()
            ->select('id', 'order_number', 'title', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        // Top designers this month
        $topDesigners = $this->getTopDesigners($startOfMonth);

        // Commission stats
        $commissionStats = $this->getCommissionStats($startOfMonth);

        // Invoice stats
        $invoiceStats = $this->getInvoiceStats($startOfMonth);

        return [
            'orders' => $orderStats,
            'revenue' => $revenueStats,
            'clients' => $clientStats,
            'recent_orders' => $recentOrders,
            'top_designers' => $topDesigners,
            'commissions' => $commissionStats,
            'invoices' => $invoiceStats,
        ];
    }

    private function getOrderStats(Carbon $startOfDay, Carbon $startOfMonth): array
    {
        $baseQuery = Order::query();

        // Count by status
        $byStatus = Order::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Needs attention: submitted (awaiting review)
        $needsAttention = Order::whereIn('status', [
                OrderStatus::SUBMITTED,
                OrderStatus::IN_REVIEW,
            ])
            ->count();

        return [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->where('created_at', '>=', $startOfDay)->count(),
            'in_progress' => (clone $baseQuery)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'awaiting_review' => (clone $baseQuery)->whereIn('status', [OrderStatus::SUBMITTED, OrderStatus::IN_REVIEW])->count(),
            'delivered_this_month' => (clone $baseQuery)
                ->where('status', OrderStatus::DELIVERED)
                ->where('delivered_at', '>=', $startOfMonth)
                ->count(),
            'needs_attention' => $needsAttention,
            'by_status' => $byStatus,
        ];
    }

    private function getRevenueStats(Carbon $startOfDay, Carbon $startOfWeek, Carbon $startOfMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonth, Carbon $startOfYear): array
    {
        $deliveredStatuses = [OrderStatus::DELIVERED, OrderStatus::CLOSED];

        return [
            'today' => Order::whereIn('status', $deliveredStatuses)
                ->where('delivered_at', '>=', $startOfDay)
                ->sum('price') ?? 0,
            'this_week' => Order::whereIn('status', $deliveredStatuses)
                ->where('delivered_at', '>=', $startOfWeek)
                ->sum('price') ?? 0,
            'this_month' => Order::whereIn('status', $deliveredStatuses)
                ->where('delivered_at', '>=', $startOfMonth)
                ->sum('price') ?? 0,
            'last_month' => Order::whereIn('status', $deliveredStatuses)
                ->whereBetween('delivered_at', [$startOfLastMonth, $endOfLastMonth])
                ->sum('price') ?? 0,
            'this_year' => Order::whereIn('status', $deliveredStatuses)
                ->where('delivered_at', '>=', $startOfYear)
                ->sum('price') ?? 0,
        ];
    }

    private function getTopDesigners(Carbon $startOfMonth): array
    {
        // Get designers with completed orders this month
        $designers = User::whereHas('roles', fn ($q) => $q->where('name', 'Designer'))
            ->withCount(['designedOrders as completed_orders' => function ($query) use ($startOfMonth) {
                $query->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
                    ->where('delivered_at', '>=', $startOfMonth);
            }])
            ->having('completed_orders', '>', 0)
            ->orderByDesc('completed_orders')
            ->limit(5)
            ->get();

        return $designers->map(function ($designer) use ($startOfMonth) {
            // Get earnings for this designer this month
            $earnings = Commission::where('user_id', $designer->id)
                ->where('role_type', RoleType::DESIGNER)
                ->where('created_at', '>=', $startOfMonth)
                ->sum(DB::raw('base_amount + extra_amount'));

            return [
                'id' => $designer->id,
                'name' => $designer->name,
                'completed_orders' => $designer->completed_orders,
                'earnings' => $earnings ?? 0,
            ];
        })->toArray();
    }

    private function getCommissionStats(Carbon $startOfMonth): array
    {
        $baseQuery = Commission::query();

        return [
            'unpaid' => (clone $baseQuery)
                ->where('is_paid', false)
                ->sum(DB::raw('base_amount + extra_amount')) ?? 0,
            'paid_this_month' => (clone $baseQuery)
                ->where('is_paid', true)
                ->where('paid_at', '>=', $startOfMonth)
                ->sum(DB::raw('base_amount + extra_amount')) ?? 0,
            'designer_total' => (clone $baseQuery)
                ->where('role_type', RoleType::DESIGNER)
                ->where('created_at', '>=', $startOfMonth)
                ->sum(DB::raw('base_amount + extra_amount')) ?? 0,
            'sales_total' => (clone $baseQuery)
                ->where('role_type', RoleType::SALES)
                ->where('created_at', '>=', $startOfMonth)
                ->sum(DB::raw('base_amount + extra_amount')) ?? 0,
        ];
    }

    private function getInvoiceStats(Carbon $startOfMonth): array
    {
        // Check if Invoice model exists (invoicing may not be implemented yet)
        if (! class_exists(Invoice::class)) {
            return [
                'draft_count' => 0,
                'sent_count' => 0,
                'overdue_count' => 0,
                'paid_this_month' => 0,
                'unpaid_amount' => 0,
            ];
        }

        try {
            $baseQuery = Invoice::query();

            return [
                'draft_count' => (clone $baseQuery)->where('status', InvoiceStatus::DRAFT)->count(),
                'sent_count' => (clone $baseQuery)->where('status', InvoiceStatus::SENT)->count(),
                'overdue_count' => (clone $baseQuery)->where('status', InvoiceStatus::OVERDUE)->count(),
                'paid_this_month' => (clone $baseQuery)
                    ->where('status', InvoiceStatus::PAID)
                    ->where('paid_at', '>=', $startOfMonth)
                    ->count(),
                'unpaid_amount' => (clone $baseQuery)
                    ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE])
                    ->sum('total_amount') ?? 0,
            ];
        } catch (\Exception $e) {
            // Invoice table may not exist yet
            return [
                'draft_count' => 0,
                'sent_count' => 0,
                'overdue_count' => 0,
                'paid_this_month' => 0,
                'unpaid_amount' => 0,
            ];
        }
    }

    private function getDesignerStats(User $user): array
    {
        $userId = $user->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Order stats for this designer
        $orderStats = $this->getDesignerOrderStats($userId, $startOfMonth);

        // Earnings stats
        $earningsStats = $this->getDesignerEarningsStats($userId, $startOfMonth);

        // Performance stats
        $performanceStats = $this->getDesignerPerformanceStats($userId, $startOfMonth, $startOfLastMonth, $endOfLastMonth);

        // Orders needing action (assigned)
        $actionRequired = Order::where('designer_id', $userId)
            ->where('status', OrderStatus::ASSIGNED)
            ->orderBy('priority', 'desc') // Rush first
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'title' => $order->title,
                    'status' => $order->status->value,
                    'priority' => $order->priority->value ?? 'normal',
                    'created_at' => $order->created_at->toIso8601String(),
                    'revision_notes' => null,
                ];
            });

        // Current work (in progress, submitted, in review)
        $currentWork = Order::where('designer_id', $userId)
            ->whereIn('status', [OrderStatus::IN_PROGRESS, OrderStatus::SUBMITTED, OrderStatus::IN_REVIEW])
            ->orderBy('priority', 'desc')
            ->orderBy('due_at', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'priority' => $order->priority->value ?? 'normal',
                'due_at' => $order->due_at?->toIso8601String(),
            ]);

        // Recent completions with earnings
        $recentCompletions = Order::where('designer_id', $userId)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->whereNotNull('delivered_at')
            ->orderByDesc('delivered_at')
            ->limit(5)
            ->get()
            ->map(function ($order) use ($userId) {
                $earnings = Commission::where('order_id', $order->id)
                    ->where('user_id', $userId)
                    ->where('role_type', RoleType::DESIGNER)
                    ->sum(DB::raw('base_amount + extra_amount'));

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'title' => $order->title,
                    'delivered_at' => $order->delivered_at->toIso8601String(),
                    'earnings' => $earnings ?? 0,
                ];
            });

        return [
            'orders' => $orderStats,
            'earnings' => $earningsStats,
            'performance' => $performanceStats,
            'action_required' => $actionRequired,
            'current_work' => $currentWork,
            'recent_completions' => $recentCompletions,
        ];
    }

    private function getDesignerOrderStats(int $userId, Carbon $startOfMonth): array
    {
        $baseQuery = Order::where('designer_id', $userId);

        // Count active orders (assigned through in_review)
        $activeStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
        ];

        $assigned = (clone $baseQuery)->whereIn('status', $activeStatuses)->count();
        $rush = (clone $baseQuery)->whereIn('status', $activeStatuses)->where('priority', 'rush')->count();

        return [
            'assigned' => $assigned,
            'rush' => $rush,
            'in_progress' => (clone $baseQuery)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'completed_this_month' => (clone $baseQuery)
                ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
                ->where('delivered_at', '>=', $startOfMonth)
                ->count(),
        ];
    }

    private function getDesignerEarningsStats(int $userId, Carbon $startOfMonth): array
    {
        $baseQuery = Commission::where('user_id', $userId)
            ->where('role_type', RoleType::DESIGNER);

        $thisMonth = (clone $baseQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        $unpaid = (clone $baseQuery)
            ->where('is_paid', false)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        $paidThisMonth = (clone $baseQuery)
            ->where('is_paid', true)
            ->where('paid_at', '>=', $startOfMonth)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        // Calculate average per order this month
        $ordersThisMonth = (clone $baseQuery)->where('created_at', '>=', $startOfMonth)->count();
        $averagePerOrder = $ordersThisMonth > 0 ? $thisMonth / $ordersThisMonth : 0;

        return [
            'this_month' => $thisMonth,
            'unpaid' => $unpaid,
            'paid_this_month' => $paidThisMonth,
            'average_per_order' => $averagePerOrder,
        ];
    }

    private function getDesignerPerformanceStats(int $userId, Carbon $startOfMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonth): array
    {
        $completedStatuses = [OrderStatus::DELIVERED, OrderStatus::CLOSED];

        $ordersThisMonth = Order::where('designer_id', $userId)
            ->whereIn('status', $completedStatuses)
            ->where('delivered_at', '>=', $startOfMonth)
            ->count();

        $ordersLastMonth = Order::where('designer_id', $userId)
            ->whereIn('status', $completedStatuses)
            ->whereBetween('delivered_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $totalCompleted = Order::where('designer_id', $userId)
            ->whereIn('status', $completedStatuses)
            ->count();

        $totalEarnings = Commission::where('user_id', $userId)
            ->where('role_type', RoleType::DESIGNER)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        return [
            'orders_this_month' => $ordersThisMonth,
            'orders_last_month' => $ordersLastMonth,
            'total_completed' => $totalCompleted,
            'total_earnings' => $totalEarnings,
        ];
    }

    private function getSalesStats(User $user): array
    {
        $tenantId = $user->tenant_id;
        $userId = $user->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Order stats for this sales user
        $orderStats = $this->getSalesOrderStats($userId, $startOfMonth);

        // Earnings stats
        $earningsStats = $this->getSalesEarningsStats($userId, $startOfMonth);

        // Client stats (needs $tenantId for DB::table query)
        $clientStats = $this->getSalesClientStats($tenantId, $userId, $startOfMonth);

        // Performance stats
        $performanceStats = $this->getSalesPerformanceStats($userId, $startOfMonth, $startOfLastMonth, $endOfLastMonth);

        // Recent orders
        $recentOrders = Order::where('sales_user_id', $userId)
            ->with('client:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'title' => $order->title,
                'status' => $order->status->value,
                'priority' => $order->priority->value ?? 'normal',
                'client_name' => $order->client?->name ?? 'N/A',
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        // Top clients by order value
        $topClients = $this->getSalesTopClients($userId);

        // Recent commissions
        $recentCommissions = $this->getSalesRecentCommissions($userId);

        return [
            'orders' => $orderStats,
            'earnings' => $earningsStats,
            'clients' => $clientStats,
            'performance' => $performanceStats,
            'recent_orders' => $recentOrders,
            'top_clients' => $topClients,
            'recent_commissions' => $recentCommissions,
        ];
    }

    private function getSalesOrderStats(int $userId, Carbon $startOfMonth): array
    {
        $baseQuery = Order::where('sales_user_id', $userId);

        // Count by status
        $byStatus = Order::where('sales_user_id', $userId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Active statuses (not delivered, closed, or cancelled)
        $activeStatuses = [
            OrderStatus::RECEIVED,
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::APPROVED,
        ];

        return [
            'total' => (clone $baseQuery)->count(),
            'this_month' => (clone $baseQuery)->where('created_at', '>=', $startOfMonth)->count(),
            'active' => (clone $baseQuery)->whereIn('status', $activeStatuses)->count(),
            'delivered_this_month' => (clone $baseQuery)
                ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
                ->where('delivered_at', '>=', $startOfMonth)
                ->count(),
            'by_status' => $byStatus,
        ];
    }

    private function getSalesEarningsStats(int $userId, Carbon $startOfMonth): array
    {
        $baseQuery = Commission::where('user_id', $userId)
            ->where('role_type', RoleType::SALES);

        $thisMonth = (clone $baseQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        $unpaid = (clone $baseQuery)
            ->where('is_paid', false)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        $paidThisMonth = (clone $baseQuery)
            ->where('is_paid', true)
            ->where('paid_at', '>=', $startOfMonth)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        // Order value this month (from delivered orders)
        $orderValueThisMonth = Order::where('sales_user_id', $userId)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->where('delivered_at', '>=', $startOfMonth)
            ->sum('price') ?? 0;

        return [
            'this_month' => $thisMonth,
            'unpaid' => $unpaid,
            'paid_this_month' => $paidThisMonth,
            'order_value_this_month' => $orderValueThisMonth,
        ];
    }

    private function getSalesClientStats(int $tenantId, int $userId, Carbon $startOfMonth): array
    {
        // Get unique clients from orders assigned to this sales user
        $totalClients = Order::where('sales_user_id', $userId)
            ->distinct('client_id')
            ->count('client_id');

        // New clients this month (first order from client was this month)
        // Uses DB::table which bypasses global scope — explicit tenant_id required
        $newClientsThisMonth = DB::table('orders')
            ->select('client_id')
            ->where('tenant_id', $tenantId)
            ->where('sales_user_id', $userId)
            ->groupBy('client_id')
            ->havingRaw('MIN(created_at) >= ?', [$startOfMonth])
            ->count();

        return [
            'total' => $totalClients,
            'new_this_month' => $newClientsThisMonth,
        ];
    }

    private function getSalesPerformanceStats(int $userId, Carbon $startOfMonth, Carbon $startOfLastMonth, Carbon $endOfLastMonth): array
    {
        $baseQuery = Order::where('sales_user_id', $userId);

        $ordersThisMonth = (clone $baseQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $ordersLastMonth = (clone $baseQuery)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $totalOrders = (clone $baseQuery)->count();

        $totalCommission = Commission::where('user_id', $userId)
            ->where('role_type', RoleType::SALES)
            ->sum(DB::raw('base_amount + extra_amount')) ?? 0;

        $totalSalesValue = Order::where('sales_user_id', $userId)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->sum('price') ?? 0;

        return [
            'orders_this_month' => $ordersThisMonth,
            'orders_last_month' => $ordersLastMonth,
            'total_orders' => $totalOrders,
            'total_commission' => $totalCommission,
            'total_sales_value' => $totalSalesValue,
        ];
    }

    private function getSalesTopClients(int $userId): array
    {
        return Order::where('sales_user_id', $userId)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CLOSED])
            ->select('client_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(price) as total_value'))
            ->groupBy('client_id')
            ->orderByDesc('total_value')
            ->limit(5)
            ->with('client:id,name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->client_id,
                'name' => $row->client?->name ?? 'Unknown',
                'orders_count' => $row->orders_count,
                'total_value' => $row->total_value ?? 0,
            ])
            ->toArray();
    }

    private function getSalesRecentCommissions(int $userId): array
    {
        return Commission::where('user_id', $userId)
            ->where('role_type', RoleType::SALES)
            ->with(['order:id,order_number,client_id,price', 'order.client:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($commission) => [
                'id' => $commission->id,
                'order_id' => $commission->order_id,
                'order_number' => $commission->order?->order_number ?? 'N/A',
                'client_name' => $commission->order?->client?->name ?? 'N/A',
                'order_value' => $commission->order?->price ?? 0,
                'amount' => $commission->base_amount + $commission->extra_amount,
                'is_paid' => $commission->is_paid,
                'created_at' => $commission->created_at->toIso8601String(),
            ])
            ->toArray();
    }
}
