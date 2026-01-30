<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
    DocumentTextIcon,
    BanknotesIcon,
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
</script>

<template>
    <div class="space-y-6">
        <!-- Top Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Orders -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 p-3">
                        <ClipboardDocumentListIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Orders</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.total ?? 0 }}</p>
                    <p v-if="stats?.orders?.today > 0" class="ml-2 flex items-baseline text-sm font-medium text-green-600">
                        +{{ stats.orders.today }} today
                    </p>
                </dd>
            </div>

            <!-- Revenue This Month -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 p-3">
                        <CurrencyDollarIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Revenue (This Month)</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ formatCurrency(stats?.revenue?.this_month) }}</p>
                </dd>
            </div>

            <!-- Active Clients -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 p-3">
                        <UserGroupIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Active Clients</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.clients?.active ?? 0 }}</p>
                    <p class="ml-2 text-sm text-slate-500">/ {{ stats?.clients?.total ?? 0 }} total</p>
                </dd>
            </div>

            <!-- Pending Actions -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-3">
                        <ExclamationTriangleIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Needs Attention</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.needs_attention ?? 0 }}</p>
                </dd>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Orders In Progress -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ClockIcon class="h-8 w-8 text-yellow-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">In Progress</p>
                        <p class="text-xl font-bold text-slate-900">{{ stats?.orders?.in_progress ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Awaiting Review -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <DocumentTextIcon class="h-8 w-8 text-indigo-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Awaiting Review</p>
                        <p class="text-xl font-bold text-slate-900">{{ stats?.orders?.awaiting_review ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Delivered This Month -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <CheckCircleIcon class="h-8 w-8 text-green-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Delivered (Month)</p>
                        <p class="text-xl font-bold text-slate-900">{{ stats?.orders?.delivered_this_month ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Unpaid Invoices -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <BanknotesIcon class="h-8 w-8 text-red-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Unpaid Invoices</p>
                        <p class="text-xl font-bold text-slate-900">{{ formatCurrency(stats?.invoices?.unpaid_amount) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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

            <!-- Revenue Overview -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Revenue Overview</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Today</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.revenue?.today) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">This Week</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.revenue?.this_week) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">This Month</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.revenue?.this_month) }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                            <dt class="text-sm text-slate-500">Last Month</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.revenue?.last_month) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">This Year</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ formatCurrency(stats?.revenue?.this_year) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Recent Orders & Top Performers -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Orders -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Recent Orders</h3>
                    <Link :href="route('orders.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="order in stats?.recent_orders" :key="order.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <Link :href="route('orders.show', order.id)" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                    {{ order.order_number }}
                                </Link>
                                <p class="text-sm text-slate-500 truncate max-w-[200px]">{{ order.title }}</p>
                            </div>
                            <div class="text-right">
                                <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize']">
                                    {{ order.status.replace('_', ' ') }}
                                </span>
                                <p class="text-xs text-slate-400 mt-1">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.recent_orders?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No recent orders
                    </div>
                </div>
            </div>

            <!-- Top Designers -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Top Designers (This Month)</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="(designer, index) in stats?.top_designers" :key="designer.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-bold text-white">
                                    {{ index + 1 }}
                                </span>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-slate-900">{{ designer.name }}</p>
                                    <p class="text-xs text-slate-500">{{ designer.completed_orders }} orders completed</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">{{ formatCurrency(designer.earnings) }}</p>
                                <p class="text-xs text-slate-500">earned</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.top_designers?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No designer activity this month
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission & Invoice Summary -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Commission Summary -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Commission Summary</h3>
                    <Link :href="route('commissions.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Total Unpaid</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900">{{ formatCurrency(stats?.commissions?.unpaid) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Paid This Month</dt>
                            <dd class="mt-1 text-lg font-semibold text-green-600">{{ formatCurrency(stats?.commissions?.paid_this_month) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Designer Bonuses</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900">{{ formatCurrency(stats?.commissions?.designer_total) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Sales Commissions</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900">{{ formatCurrency(stats?.commissions?.sales_total) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Invoice Summary -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Invoice Summary</h3>
                    <Link :href="route('invoices.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Draft</dt>
                            <dd class="mt-1 text-lg font-semibold text-slate-900">{{ stats?.invoices?.draft_count ?? 0 }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Sent (Unpaid)</dt>
                            <dd class="mt-1 text-lg font-semibold text-amber-600">{{ stats?.invoices?.sent_count ?? 0 }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Overdue</dt>
                            <dd class="mt-1 text-lg font-semibold text-red-600">{{ stats?.invoices?.overdue_count ?? 0 }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-medium text-slate-500">Paid This Month</dt>
                            <dd class="mt-1 text-lg font-semibold text-green-600">{{ stats?.invoices?.paid_this_month ?? 0 }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-semibold text-slate-900">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('orders.create')" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110">
                        New Order
                    </Link>
                    <Link :href="route('clients.create')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        Add Client
                    </Link>
                    <Link :href="route('invoices.create')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        Create Invoice
                    </Link>
                    <Link :href="route('users.create')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        Invite User
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
