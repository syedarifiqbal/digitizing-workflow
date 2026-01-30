<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    BanknotesIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ShoppingCartIcon,
    UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { useDateFormat } from '@/Composables/useDateFormat';

const props = defineProps({
    stats: Object,
});

const { formatDate } = useDateFormat();

// Helper to format currency
const formatCurrency = (amount, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount || 0);
};

// Status color mapping
const statusColors = {
    received: 'bg-gray-100 text-gray-800',
    assigned: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-purple-100 text-purple-800',
    in_review: 'bg-indigo-100 text-indigo-800',
    revision_requested: 'bg-orange-100 text-orange-800',
    approved: 'bg-emerald-100 text-emerald-800',
    delivered: 'bg-green-100 text-green-800',
    closed: 'bg-slate-100 text-slate-800',
    cancelled: 'bg-red-100 text-red-800',
};

const getStatusColor = (status) => statusColors[status] || 'bg-gray-100 text-gray-800';

// Priority styling
const getPriorityClass = (priority) => {
    return priority === 'rush'
        ? 'bg-red-100 text-red-800'
        : 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- My Orders -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 p-3">
                        <ClipboardDocumentListIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">My Orders</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.total ?? 0 }}</p>
                    <p v-if="stats?.orders?.this_month > 0" class="ml-2 flex items-baseline text-sm font-medium text-green-600">
                        +{{ stats.orders.this_month }} this month
                    </p>
                </dd>
            </div>

            <!-- Active Orders -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 p-3">
                        <ClockIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Active Orders</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.active ?? 0 }}</p>
                </dd>
            </div>

            <!-- Delivered This Month -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-3">
                        <CheckCircleIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Delivered (Month)</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.delivered_this_month ?? 0 }}</p>
                </dd>
            </div>

            <!-- My Clients -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 p-3">
                        <UserGroupIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">My Clients</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.clients?.total ?? 0 }}</p>
                    <p v-if="stats?.clients?.new_this_month > 0" class="ml-2 flex items-baseline text-sm font-medium text-green-600">
                        +{{ stats.clients.new_this_month }} new
                    </p>
                </dd>
            </div>
        </div>

        <!-- Earnings Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Commission This Month -->
            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Commission This Month</p>
                        <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats?.earnings?.this_month) }}</p>
                    </div>
                    <CurrencyDollarIcon class="h-10 w-10 text-emerald-200" />
                </div>
            </div>

            <!-- Unpaid Commission -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <BanknotesIcon class="h-8 w-8 text-amber-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Unpaid</p>
                        <p class="text-xl font-bold text-slate-900">{{ formatCurrency(stats?.earnings?.unpaid) }}</p>
                    </div>
                </div>
            </div>

            <!-- Paid This Month -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-8 w-8 text-green-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Paid This Month</p>
                        <p class="text-xl font-bold text-green-600">{{ formatCurrency(stats?.earnings?.paid_this_month) }}</p>
                    </div>
                </div>
            </div>

            <!-- Order Value This Month -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ShoppingCartIcon class="h-8 w-8 text-indigo-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Sales Value (Month)</p>
                        <p class="text-xl font-bold text-slate-900">{{ formatCurrency(stats?.earnings?.order_value_this_month) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Orders -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">My Recent Orders</h3>
                    <Link :href="route('orders.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="order in stats?.recent_orders" :key="order.id" class="px-6 py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('orders.show', order.id)" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                        {{ order.order_number }}
                                    </Link>
                                    <span v-if="order.priority === 'rush'" :class="[getPriorityClass(order.priority), 'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium']">
                                        RUSH
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 truncate">{{ order.title }}</p>
                                <p class="text-xs text-slate-400">{{ order.client_name }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0 text-right">
                                <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize']">
                                    {{ order.status.replace('_', ' ') }}
                                </span>
                                <p class="text-xs text-slate-400 mt-1">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.recent_orders?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No orders assigned yet
                    </div>
                </div>
            </div>

            <!-- Top Clients -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Top Clients (By Order Value)</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="(client, index) in stats?.top_clients" :key="client.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-bold text-white">
                                    {{ index + 1 }}
                                </span>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-slate-900">{{ client.name }}</p>
                                    <p class="text-xs text-slate-500">{{ client.orders_count }} orders</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">{{ formatCurrency(client.total_value) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.top_clients?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No client data yet
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance & Recent Commissions -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Performance Stats -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">My Performance</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Orders This Month</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ stats?.performance?.orders_this_month ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Orders Last Month</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ stats?.performance?.orders_last_month ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                            <dt class="text-sm text-slate-500">Total Orders (All Time)</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ stats?.performance?.total_orders ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Total Commission (All Time)</dt>
                            <dd class="text-sm font-semibold text-emerald-600">{{ formatCurrency(stats?.performance?.total_commission) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Total Sales Value (All Time)</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.performance?.total_sales_value) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Orders by Status -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Orders by Status</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div v-for="(count, status) in stats?.orders?.by_status" :key="status" class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span :class="[getStatusColor(status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize']">
                                    {{ status.replace('_', ' ') }}
                                </span>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">{{ count }}</span>
                        </div>
                        <div v-if="!stats?.orders?.by_status || Object.keys(stats.orders.by_status).length === 0" class="text-sm text-slate-500 text-center py-4">
                            No orders yet
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Commissions -->
        <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Recent Commissions</h3>
                <Link :href="route('commissions.my')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Order</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Order Value</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Commission</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-for="commission in stats?.recent_commissions" :key="commission.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="route('orders.show', commission.order_id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ commission.order_number }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ commission.client_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatCurrency(commission.order_value) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-emerald-600">{{ formatCurrency(commission.amount) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="commission.is_paid ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ commission.is_paid ? 'Paid' : 'Unpaid' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ formatDate(commission.created_at) }}</td>
                        </tr>
                        <tr v-if="!stats?.recent_commissions?.length">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">
                                No commissions earned yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-semibold text-slate-900">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('orders.index')" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110">
                        <ClipboardDocumentListIcon class="h-4 w-4 mr-2" />
                        My Orders
                    </Link>
                    <Link :href="route('commissions.my')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        <CurrencyDollarIcon class="h-4 w-4 mr-2" />
                        My Commissions
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
