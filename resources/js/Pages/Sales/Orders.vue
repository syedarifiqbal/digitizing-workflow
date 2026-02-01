<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const props = defineProps({
    stats: Object,
    statusFilter: String,
    orders: Object,
});

const { formatDate } = useDateFormat();

// Helper to format currency
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount || 0);
};

const statusTabs = [
    { key: 'all', label: 'All' },
    { key: 'received', label: 'Received' },
    { key: 'assigned', label: 'Assigned' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'in_review', label: 'In Review' },
    { key: 'approved', label: 'Approved' },
    { key: 'delivered', label: 'Delivered' },
    { key: 'closed', label: 'Closed' },
];

const statCards = computed(() => [
    { key: 'received', label: 'Received', value: props.stats.received, color: 'text-gray-600', bg: 'bg-gray-50', border: 'border-gray-200' },
    { key: 'in_progress', label: 'In Progress', value: props.stats.in_progress, color: 'text-yellow-600', bg: 'bg-yellow-50', border: 'border-yellow-200' },
    { key: 'in_review', label: 'In Review', value: props.stats.in_review + props.stats.submitted, color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200' },
    { key: 'approved', label: 'Approved', value: props.stats.approved, color: 'text-emerald-600', bg: 'bg-emerald-50', border: 'border-emerald-200' },
    { key: 'delivered', label: 'Delivered', value: props.stats.delivered, color: 'text-green-600', bg: 'bg-green-50', border: 'border-green-200' },
    { key: 'closed', label: 'Closed', value: props.stats.closed, color: 'text-slate-600', bg: 'bg-slate-50', border: 'border-slate-200' },
]);

const statusBadge = (status) => {
    const map = {
        received: 'bg-gray-100 text-gray-700',
        assigned: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        submitted: 'bg-indigo-100 text-indigo-700',
        in_review: 'bg-purple-100 text-purple-700',
        approved: 'bg-emerald-100 text-emerald-700',
        delivered: 'bg-green-100 text-green-700',
        closed: 'bg-slate-100 text-slate-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const priorityBadge = (priority) => {
    return priority === 'rush' ? 'bg-red-100 text-red-700' : '';
};

const filterByStatus = (status) => {
    router.get(route('sales.orders'), { status }, { preserveState: true });
};

const isOverdue = (dueAt) => {
    if (!dueAt) return false;
    return new Date(dueAt) < new Date();
};
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900">My Orders</h1>
                <p class="mt-1 text-sm text-slate-500">Orders assigned to you for sales tracking</p>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <button
                    v-for="stat in statCards"
                    :key="stat.key"
                    @click="filterByStatus(stat.key)"
                    class="rounded-lg border p-3 text-left transition hover:shadow-md"
                    :class="[
                        stat.bg,
                        stat.border,
                        statusFilter === stat.key ? 'ring-2 ring-indigo-400 ring-offset-1' : ''
                    ]"
                >
                    <div class="text-xs font-medium text-slate-500">{{ stat.label }}</div>
                    <div class="mt-1 text-2xl font-bold" :class="stat.color">{{ stat.value }}</div>
                </button>
            </div>

            <!-- Status Filter Tabs -->
            <div class="mb-4 flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    @click="filterByStatus(tab.key)"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                    :class="statusFilter === tab.key
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                >
                    {{ tab.label }}
                    <span v-if="tab.key !== 'all' && stats[tab.key]" class="ml-1 text-xs opacity-70">
                        ({{ stats[tab.key] }})
                    </span>
                    <span v-if="tab.key === 'all'" class="ml-1 text-xs opacity-70">
                        ({{ stats.total }})
                    </span>
                </button>
            </div>

            <!-- Orders Table -->
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div v-if="orders.data.length === 0" class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="mt-2 text-sm text-slate-500">No orders found for this filter.</p>
                </div>

                <table v-else class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Order</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Client</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 lg:table-cell">Designer</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 lg:table-cell">Price</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 xl:table-cell">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orders.data" :key="order.id" class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <Link :href="route('orders.show', order.id)" class="font-medium text-slate-900 hover:text-indigo-600">
                                    {{ order.order_number }}
                                </Link>
                                <div class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ order.title }}</div>
                            </td>
                            <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">
                                {{ order.client || '-' }}
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium capitalize text-slate-700">
                                    {{ order.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge(order.status)">
                                    {{ statusLabel(order.status) }}
                                </span>
                                <span v-if="order.priority === 'rush'" class="ml-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium" :class="priorityBadge(order.priority)">
                                    Rush
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-sm text-slate-600 lg:table-cell">
                                {{ order.designer || '-' }}
                            </td>
                            <td class="hidden px-4 py-3 text-sm font-medium text-slate-900 lg:table-cell">
                                {{ order.price ? formatCurrency(order.price) : '-' }}
                            </td>
                            <td class="hidden px-4 py-3 xl:table-cell">
                                <span v-if="order.due_at" class="text-sm" :class="isOverdue(order.due_at) ? 'font-medium text-red-600' : 'text-slate-600'">
                                    {{ formatDate(order.due_at) }}
                                    <span v-if="isOverdue(order.due_at)" class="text-xs">(Overdue)</span>
                                </span>
                                <span v-else class="text-sm text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('orders.show', order.id)"
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="orders.total > orders.per_page" class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3">
                    <div class="text-sm text-slate-500">
                        Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                    </div>
                    <div class="flex gap-1">
                        <template v-for="link in orders.links" :key="link.url">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="inline-flex items-center rounded-md px-3 py-1 text-sm transition"
                                :class="link.active
                                    ? 'bg-indigo-100 font-medium text-indigo-700'
                                    : 'text-slate-500 hover:bg-slate-100'"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span
                                v-else
                                class="inline-flex items-center rounded-md px-3 py-1 text-sm text-slate-300"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
