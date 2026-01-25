<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    recentOrders: Array,
    ordersNeedingAttention: Array,
    stats: Object,
});

const getStatusColor = (status) => {
    const colors = {
        received: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        assigned: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        in_progress: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        submitted: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        in_review: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        revision_requested: 'bg-orange-50 text-orange-700 ring-orange-600/20',
        approved: 'bg-teal-50 text-teal-700 ring-teal-600/20',
        delivered: 'bg-green-50 text-green-700 ring-green-600/20',
        closed: 'bg-gray-100 text-gray-700 ring-gray-600/20',
        cancelled: 'bg-red-50 text-red-700 ring-red-600/20',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const getPriorityColor = (priority) => {
    return priority === 'rush'
        ? 'bg-red-50 text-red-700 ring-red-600/20'
        : 'bg-slate-50 text-slate-700 ring-slate-600/20';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-gray-900">Dashboard</h2>
                <p class="text-sm text-gray-500">Welcome to your client portal.</p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
                    <div class="bg-white rounded-lg border border-gray-200 px-5 py-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Orders</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 px-5 py-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">In Progress</div>
                        <div class="mt-1 text-2xl font-semibold text-indigo-600">{{ stats.in_progress }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 px-5 py-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Delivered</div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">{{ stats.delivered }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 px-5 py-4">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-600">{{ stats.completed }}</div>
                    </div>
                </div>

                <!-- Orders Needing Attention -->
                <div v-if="ordersNeedingAttention.length > 0" class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Orders Needing Your Attention</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Updated</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="order in ordersNeedingAttention" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ order.order_number }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ order.title }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="getStatusColor(order.status)">
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="getPriorityColor(order.priority)">
                                            {{ order.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900">{{ formatDate(order.updated_at) }}</div>
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
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Recent Orders</h3>
                        <Link
                            :href="route('client.orders.index')"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Created</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="recentOrders.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                        No orders found. <Link :href="route('client.orders.create')" class="text-indigo-600 hover:text-indigo-900">Create your first order</Link>.
                                    </td>
                                </tr>
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ order.order_number }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ order.title }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="getStatusColor(order.status)">
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="getPriorityColor(order.priority)">
                                            {{ order.priority_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900">{{ formatDate(order.created_at) }}</div>
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
