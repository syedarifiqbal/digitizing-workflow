<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    PlayIcon,
    BanknotesIcon,
    ChartBarIcon,
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
    assigned: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-purple-100 text-purple-800',
    in_review: 'bg-indigo-100 text-indigo-800',
    revision_requested: 'bg-orange-100 text-orange-800',
    approved: 'bg-emerald-100 text-emerald-800',
    delivered: 'bg-green-100 text-green-800',
    closed: 'bg-slate-100 text-slate-800',
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
            <!-- Assigned Orders -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 p-3">
                        <ClipboardDocumentListIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Assigned to Me</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.assigned ?? 0 }}</p>
                    <p v-if="stats?.orders?.rush > 0" class="ml-2 flex items-baseline text-sm font-medium text-red-600">
                        {{ stats.orders.rush }} rush
                    </p>
                </dd>
            </div>

            <!-- In Progress -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 p-3">
                        <PlayIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">In Progress</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.in_progress ?? 0 }}</p>
                </dd>
            </div>

            <!-- Needs Revision -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-orange-500 to-red-500 p-3">
                        <ExclamationTriangleIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Needs Revision</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold" :class="stats?.orders?.revision_requested > 0 ? 'text-orange-600' : 'text-slate-900'">
                        {{ stats?.orders?.revision_requested ?? 0 }}
                    </p>
                </dd>
            </div>

            <!-- Completed This Month -->
            <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
                <dt>
                    <div class="absolute rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-3">
                        <CheckCircleIcon class="h-6 w-6 text-white" />
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Completed (Month)</p>
                </dt>
                <dd class="ml-16 flex items-baseline">
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.orders?.completed_this_month ?? 0 }}</p>
                </dd>
            </div>
        </div>

        <!-- Earnings Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Earnings This Month -->
            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Earnings This Month</p>
                        <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats?.earnings?.this_month) }}</p>
                    </div>
                    <CurrencyDollarIcon class="h-10 w-10 text-emerald-200" />
                </div>
            </div>

            <!-- Unpaid Earnings -->
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

            <!-- Average Per Order -->
            <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ChartBarIcon class="h-8 w-8 text-indigo-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500">Avg. Per Order</p>
                        <p class="text-xl font-bold text-slate-900">{{ formatCurrency(stats?.earnings?.average_per_order) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Orders Needing Action -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <ExclamationTriangleIcon class="h-5 w-5 text-orange-500 mr-2" />
                        <h3 class="text-base font-semibold text-slate-900">Needs Your Action</h3>
                    </div>
                    <span v-if="stats?.action_required?.length > 0" class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">
                        {{ stats.action_required.length }}
                    </span>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="order in stats?.action_required" :key="order.id" class="px-6 py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('orders.show', order.id)" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                        {{ order.order_number }}
                                    </Link>
                                    <span :class="[getPriorityClass(order.priority), 'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium']">
                                        {{ order.priority }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 truncate">{{ order.title }}</p>
                                <p v-if="order.revision_notes" class="mt-1 text-xs text-orange-600 truncate">
                                    Revision: {{ order.revision_notes }}
                                </p>
                            </div>
                            <div class="ml-4 flex-shrink-0 text-right">
                                <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize']">
                                    {{ order.status.replace('_', ' ') }}
                                </span>
                                <p class="text-xs text-slate-400 mt-1">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.action_required?.length" class="px-6 py-8 text-center">
                        <CheckCircleIcon class="mx-auto h-10 w-10 text-green-400" />
                        <p class="mt-2 text-sm text-slate-500">All caught up! No orders need your action.</p>
                    </div>
                </div>
            </div>

            <!-- Current Work -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">My Current Work</h3>
                    <Link :href="route('designer.dashboard')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </Link>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="order in stats?.current_work" :key="order.id" class="px-6 py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('orders.show', order.id)" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                        {{ order.order_number }}
                                    </Link>
                                    <span v-if="order.priority === 'rush'" class="inline-flex items-center rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-800">
                                        RUSH
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 truncate">{{ order.title }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0 text-right">
                                <span :class="[getStatusColor(order.status), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize']">
                                    {{ order.status.replace('_', ' ') }}
                                </span>
                                <p v-if="order.due_at" class="text-xs text-slate-400 mt-1">Due: {{ formatDate(order.due_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.current_work?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No orders currently assigned
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance & Recent Completions -->
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
                            <dt class="text-sm text-slate-500">Total Completed (All Time)</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ stats?.performance?.total_completed ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-slate-500">Total Earnings (All Time)</dt>
                            <dd class="text-sm font-semibold text-emerald-600">{{ formatCurrency(stats?.performance?.total_earnings) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Recent Completions -->
            <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Recent Completions</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="order in stats?.recent_completions" :key="order.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <Link :href="route('orders.show', order.id)" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                    {{ order.order_number }}
                                </Link>
                                <p class="text-xs text-slate-500">{{ order.title }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-emerald-600">{{ formatCurrency(order.earnings) }}</p>
                                <p class="text-xs text-slate-400">{{ formatDate(order.delivered_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!stats?.recent_completions?.length" class="px-6 py-8 text-center text-sm text-slate-500">
                        No completed orders yet
                    </div>
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
                    <Link :href="route('designer.dashboard')" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110">
                        <ClipboardDocumentListIcon class="h-4 w-4 mr-2" />
                        My Work
                    </Link>
                    <Link :href="route('commissions.my')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        <CurrencyDollarIcon class="h-4 w-4 mr-2" />
                        My Earnings
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
