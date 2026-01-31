<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDashboard } from '@/Composables/useDashboard';

const { formatCurrency, getStatusColor, getPriorityClass, formatDate } = useDashboard();

const props = defineProps({
    recentOrders: Array,
    ordersNeedingAttention: Array,
    stats: Object,
    invoiceStats: Object,
    currency: String,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Welcome to your client portal.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
                    <div class="bg-white rounded-lg border border-slate-200 px-5 py-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Orders</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-5 py-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">In Progress</div>
                        <div class="mt-1 text-2xl font-semibold text-indigo-600">{{ stats.in_progress }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-5 py-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Delivered</div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">{{ stats.delivered }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-5 py-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Completed</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-600">{{ stats.completed }}</div>
                    </div>
                </div>

                <!-- Invoice Alert Widget -->
                <div v-if="invoiceStats?.unpaid_count > 0" class="mb-6">
                    <div class="rounded-lg border px-5 py-4" :class="invoiceStats?.overdue_count > 0 ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    <svg v-if="invoiceStats?.overdue_count > 0" class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else class="h-8 w-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold" :class="invoiceStats?.overdue_count > 0 ? 'text-red-800' : 'text-amber-800'">
                                        <span v-if="invoiceStats?.overdue_count > 0">
                                            {{ invoiceStats.overdue_count }} overdue invoice{{ invoiceStats.overdue_count > 1 ? 's' : '' }}
                                        </span>
                                        <span v-else>
                                            {{ invoiceStats.unpaid_count }} unpaid invoice{{ invoiceStats.unpaid_count > 1 ? 's' : '' }}
                                        </span>
                                    </p>
                                    <p class="text-sm" :class="invoiceStats?.overdue_count > 0 ? 'text-red-700' : 'text-amber-700'">
                                        Total due: {{ currency }} {{ formatCurrency(invoiceStats?.total_due).replace('$', '') }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="route('client.invoices.index')"
                                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-white shadow-sm"
                                :class="invoiceStats?.overdue_count > 0 ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'"
                            >
                                View Invoices
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Orders Needing Attention -->
                <div v-if="ordersNeedingAttention.length > 0" class="bg-white shadow-sm rounded-lg border border-slate-200 mb-6">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Orders Needing Your Attention</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Updated</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="order in ordersNeedingAttention" :key="order.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-slate-900">{{ order.order_number }}</div>
                                        <div class="text-xs text-slate-500 truncate max-w-xs">{{ order.title }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']">
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[getPriorityClass(order.priority), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']">
                                            {{ order.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-900">{{ formatDate(order.updated_at) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="route('client.orders.show', order.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Recent Orders</h3>
                        <Link
                            :href="route('client.orders.index')"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Created</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="recentOrders.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No orders found. <Link :href="route('client.orders.create')" class="text-indigo-600 hover:text-indigo-900">Create your first order</Link>.
                                    </td>
                                </tr>
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-slate-900">{{ order.order_number }}</div>
                                        <div class="text-xs text-slate-500 truncate max-w-xs">{{ order.title }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']">
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[getPriorityClass(order.priority), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']">
                                            {{ order.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-900">{{ formatDate(order.created_at) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="route('client.orders.show', order.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
