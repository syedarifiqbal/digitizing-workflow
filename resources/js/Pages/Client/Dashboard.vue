<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/Button.vue';
import DataTable from '@/Components/DataTable.vue';
import { useDashboard } from '@/Composables/useDashboard';

const { formatCurrency, getStatusColor, getPriorityClass, formatDate } = useDashboard();

const props = defineProps({
    recentOrders: Array,
    ordersNeedingAttention: Array,
    stats: Object,
    invoiceStats: Object,
    currency: String,
});

const formatAmount = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const attentionColumns = [
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'updated', label: 'Updated' },
    { key: 'actions', label: '', headerClass: 'text-right' },
];

const recentColumns = [
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'created', label: 'Created' },
    { key: 'actions', label: '', headerClass: 'text-right' },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Welcome to your client portal.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Orders</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">{{ stats.total }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">In Progress</div>
                    <div class="mt-2 text-3xl font-bold text-indigo-600">{{ stats.in_progress }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Delivered</div>
                    <div class="mt-2 text-3xl font-bold text-green-600">{{ stats.delivered }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Completed</div>
                    <div class="mt-2 text-3xl font-bold text-slate-600">{{ stats.completed }}</div>
                </div>
            </div>

            <!-- Invoice Alert Widget -->
            <div v-if="invoiceStats?.unpaid_count > 0">
                <div class="rounded-2xl border px-6 py-5" :class="invoiceStats?.overdue_count > 0 ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'">
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
                                    Total due: {{ currency }} {{ formatAmount(invoiceStats?.total_due) }}
                                </p>
                            </div>
                        </div>
                        <Button
                            :href="route('client.invoices.index')"
                            :variant="invoiceStats?.overdue_count > 0 ? 'danger' : 'primary'"
                        >
                            View Invoices
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Orders Needing Attention -->
            <div v-if="ordersNeedingAttention.length > 0">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Orders Needing Your Attention</h3>
                </div>
                <DataTable :columns="attentionColumns" :rows="ordersNeedingAttention">
                    <template #cell-order="{ row }">
                        <div>
                            <Link
                                :href="route('client.orders.show', row.id)"
                                class="font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                {{ row.order_number }}
                            </Link>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ row.title }}</div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="getStatusColor(row.status)">
                            {{ row.status_label }}
                        </span>
                    </template>

                    <template #cell-priority="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="getPriorityClass(row.priority)">
                            {{ row.priority_label }}
                        </span>
                    </template>

                    <template #cell-updated="{ row }">
                        <div class="text-sm text-slate-900">{{ formatDate(row.updated_at) }}</div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="text-right">
                            <Link
                                :href="route('client.orders.show', row.id)"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                View
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- Recent Orders -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Recent Orders</h3>
                    <Link
                        :href="route('client.orders.index')"
                        class="text-sm text-indigo-600 hover:text-indigo-900"
                    >
                        View All
                    </Link>
                </div>
                <DataTable :columns="recentColumns" :rows="recentOrders" empty-text="No orders found.">
                    <template #empty>
                        <div class="text-center">
                            <p class="text-slate-400">No orders found.</p>
                            <Link :href="route('client.orders.create')" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-900">
                                Create your first order
                            </Link>
                        </div>
                    </template>

                    <template #cell-order="{ row }">
                        <div>
                            <Link
                                :href="route('client.orders.show', row.id)"
                                class="font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                {{ row.order_number }}
                            </Link>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ row.title }}</div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="getStatusColor(row.status)">
                            {{ row.status_label }}
                        </span>
                    </template>

                    <template #cell-priority="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="getPriorityClass(row.priority)">
                            {{ row.priority_label }}
                        </span>
                    </template>

                    <template #cell-created="{ row }">
                        <div class="text-sm text-slate-900">{{ formatDate(row.created_at) }}</div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="text-right">
                            <Link
                                :href="route('client.orders.show', row.id)"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                            >
                                View
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
