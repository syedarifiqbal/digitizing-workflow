<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesController extends Controller
{
    public function orders(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasRole('Sales')) {
            abort(403);
        }

        $tenantId = $user->tenant_id;
        $salesUserId = $user->id;

        $statusFilter = $request->get('status', 'all');

        $baseQuery = Order::query()
            ->with(['client:id,name', 'designer:id,name'])
            ->forTenant($tenantId)
            ->where('sales_user_id', $salesUserId);

        // Stats
        $stats = [
            'received' => (clone $baseQuery)->where('status', OrderStatus::RECEIVED)->count(),
            'assigned' => (clone $baseQuery)->where('status', OrderStatus::ASSIGNED)->count(),
            'in_progress' => (clone $baseQuery)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'submitted' => (clone $baseQuery)->where('status', OrderStatus::SUBMITTED)->count(),
            'in_review' => (clone $baseQuery)->where('status', OrderStatus::IN_REVIEW)->count(),
            'revision_requested' => (clone $baseQuery)->where('status', OrderStatus::REVISION_REQUESTED)->count(),
            'approved' => (clone $baseQuery)->where('status', OrderStatus::APPROVED)->count(),
            'delivered' => (clone $baseQuery)->where('status', OrderStatus::DELIVERED)->count(),
            'closed' => (clone $baseQuery)->where('status', OrderStatus::CLOSED)->count(),
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
            'designer' => $order->designer?->name,
            'price' => $order->price,
            'due_at' => $order->due_at?->toDateTimeString(),
            'created_at' => $order->created_at?->toDateTimeString(),
        ]);

        return Inertia::render('Sales/Orders', [
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'orders' => $orders,
        ]);
    }
}
