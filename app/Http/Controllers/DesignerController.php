<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignerController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isDesigner()) {
            abort(403);
        }

        $tenantId   = $user->tenant_id;
        $designerId = $user->id;

        $section      = in_array($request->get('section'), ['quotes']) ? 'quotes' : 'orders';
        $activeTab    = $request->get('tab', 'in_progress');
        $statusFilter = $request->get('status', 'all');
        $typeFilter   = $request->get('type', 'all');

        $isQuote   = $section === 'quotes';
        $baseQuery = Order::query()
            ->with(['client:id,name'])
            ->forTenant($tenantId)
            ->where('designer_id', $designerId)
            ->where('is_quote', $isQuote);

        $inProgressStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::IN_PROGRESS,
            OrderStatus::SUBMITTED,
            OrderStatus::IN_REVIEW,
            OrderStatus::APPROVED,
        ];

        $completedStatuses = [OrderStatus::DELIVERED, OrderStatus::CLOSED];

        // Section switcher counts
        $sectionCounts = [
            'orders' => Order::query()->forTenant($tenantId)->where('designer_id', $designerId)->where('is_quote', false)->count(),
            'quotes' => Order::query()->forTenant($tenantId)->where('designer_id', $designerId)->where('is_quote', true)->count(),
        ];

        // Stats for in-progress tab
        $stats = [
            'assigned'    => (clone $baseQuery)->where('status', OrderStatus::ASSIGNED)->count(),
            'in_progress' => (clone $baseQuery)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'submitted'   => (clone $baseQuery)->where('status', OrderStatus::SUBMITTED)->count(),
            'in_review'   => (clone $baseQuery)->where('status', OrderStatus::IN_REVIEW)->count(),
            'approved'    => (clone $baseQuery)->where('status', OrderStatus::APPROVED)->count(),
            'total_active' => (clone $baseQuery)->whereIn('status', $inProgressStatuses)->count(),
        ];

        // Stats for completed tab
        $completedStats = [
            'total'      => (clone $baseQuery)->whereIn('status', $completedStatuses)->count(),
            'digitizing' => (clone $baseQuery)->whereIn('status', $completedStatuses)->where('type', 'digitizing')->count(),
            'vector'     => (clone $baseQuery)->whereIn('status', $completedStatuses)->where('type', 'vector')->count(),
            'patch'      => (clone $baseQuery)->whereIn('status', $completedStatuses)->where('type', 'patch')->count(),
        ];

        if ($activeTab === 'completed') {
            $ordersQuery = (clone $baseQuery)
                ->whereIn('status', $completedStatuses)
                ->when($typeFilter !== 'all', fn ($q) => $q->where('type', $typeFilter))
                ->latest('delivered_at');
        } else {
            $ordersQuery = (clone $baseQuery)
                ->whereIn('status', $inProgressStatuses)
                ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
                ->orderByRaw("CASE priority WHEN 'super_urgent' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
                ->latest();
        }

        $orders = $ordersQuery->paginate(20)->withQueryString();

        $orders->through(fn (Order $order) => [
            'id'           => $order->id,
            'order_number' => $order->order_number,
            'title'        => $order->title,
            'type'         => $order->type->value,
            'status'       => $order->status->value,
            'priority'     => $order->priority->value,
            'client'       => $order->client?->name,
            'due_at'       => $order->due_at?->toDateTimeString(),
            'delivered_at' => $order->delivered_at?->toDateTimeString(),
            'created_at'   => $order->created_at?->toDateTimeString(),
            'can_start'    => $order->status === OrderStatus::ASSIGNED,
            'can_submit'   => $order->status === OrderStatus::IN_PROGRESS,
        ]);

        return Inertia::render('Designer/Dashboard', [
            'stats'          => $stats,
            'completedStats' => $completedStats,
            'section'        => $section,
            'sectionCounts'  => $sectionCounts,
            'activeTab'      => $activeTab,
            'statusFilter'   => $statusFilter,
            'typeFilter'     => $typeFilter,
            'orders'         => $orders,
        ]);
    }
}
