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

        $tenantId = $user->tenant_id;
        $designerId = $user->id;

        $statusFilter = $request->get('status', 'all');

        $baseQuery = Order::query()
            ->with(['client:id,name'])
            ->forTenant($tenantId)
            ->where('designer_id', $designerId);

        // Stats
        $stats = [
            'assigned' => (clone $baseQuery)->where('status', OrderStatus::ASSIGNED)->count(),
            'in_progress' => (clone $baseQuery)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'submitted' => (clone $baseQuery)->where('status', OrderStatus::SUBMITTED)->count(),
            'revision_requested' => (clone $baseQuery)->where('status', OrderStatus::REVISION_REQUESTED)->count(),
            'in_review' => (clone $baseQuery)->where('status', OrderStatus::IN_REVIEW)->count(),
            'approved' => (clone $baseQuery)->where('status', OrderStatus::APPROVED)->count(),
            'total' => (clone $baseQuery)->count(),
        ];

        // Filtered orders
        $ordersQuery = (clone $baseQuery)
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->latest();

        $orders = $ordersQuery->paginate(15)->withQueryString();

        $orders->through(fn (Order $order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'title' => $order->title,
            'type' => $order->type->value,
            'status' => $order->status->value,
            'priority' => $order->priority->value,
            'client' => $order->client?->name,
            'due_at' => optional($order->due_at)?->toDateTimeString(),
            'created_at' => $order->created_at?->toDateTimeString(),
            'can_start' => $order->status === OrderStatus::ASSIGNED || $order->status === OrderStatus::REVISION_REQUESTED,
            'can_submit' => $order->status === OrderStatus::IN_PROGRESS,
        ]);

        return Inertia::render('Designer/Dashboard', [
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'orders' => $orders,
        ]);
    }
}
