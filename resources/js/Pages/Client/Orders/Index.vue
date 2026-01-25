<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    orders: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status || 'all');
const priority = ref(props.filters.priority || 'all');

const applyFilters = () => {
    router.get(route('client.orders.index'), {
        search: search.value,
        status: status.value,
        priority: priority.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    priority.value = 'all';
    applyFilters();
};

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
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-gray-900">My Orders</h2>
                    <p class="text-sm text-gray-500">View and manage your orders.</p>
                </div>
                <Link
                    :href="route('client.orders.create')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Create Order
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
                    </div>
                    <div class="px-5 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Search</label>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Order number or title..."
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="applyFilters"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Status</label>
                                <select
                                    v-model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option value="all">All</option>
                                    <option value="received">Received</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="in_review">In Review</option>
                                    <option value="revision_requested">Revision Requested</option>
                                    <option value="approved">Approved</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="closed">Closed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Priority</label>
                                <select
                                    v-model="priority"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option value="all">All</option>
                                    <option value="normal">Normal</option>
                                    <option value="rush">Rush</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button
                                @click="clearFilters"
                                type="button"
                                class="text-sm text-indigo-600 underline hover:text-indigo-900"
                            >
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
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
                                <tr v-if="orders.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                        No orders found. <Link :href="route('client.orders.create')" class="text-indigo-600 hover:text-indigo-900">Create your first order</Link>.
                                    </td>
                                </tr>
                                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('client.orders.show', order.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ order.order_number }}
                                        </Link>
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

                    <!-- Pagination -->
                    <div v-if="orders.data.length > 0" class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ orders.meta.from }}</span> to <span class="font-medium">{{ orders.meta.to }}</span> of <span class="font-medium">{{ orders.meta.total }}</span> results
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in orders.links"
                                    :key="link.label"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm rounded border',
                                        link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
