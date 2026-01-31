<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PlayIcon,
    BanknotesIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { useDashboard } from '@/Composables/useDashboard';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MiniStatCard from '@/Components/Dashboard/MiniStatCard.vue';
import DashboardSection from '@/Components/Dashboard/DashboardSection.vue';

const props = defineProps({
    stats: Object,
});

const { formatCurrency, getStatusColor, getPriorityClass, formatDate } = useDashboard();
</script>

<template>
    <div class="space-y-6">
        <!-- Top Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Assigned to Me"
                :value="stats?.orders?.assigned ?? 0"
                :icon="ClipboardDocumentListIcon"
                gradient="from-blue-500 to-cyan-600"
                :secondary-text="stats?.orders?.rush > 0 ? `${stats.orders.rush} rush` : ''"
                secondary-class="text-red-600"
            />
            <StatCard
                label="In Progress"
                :value="stats?.orders?.in_progress ?? 0"
                :icon="PlayIcon"
                gradient="from-yellow-500 to-orange-500"
            />
            <StatCard
                label="Needs Revision"
                :value="stats?.orders?.revision_requested ?? 0"
                :icon="ExclamationTriangleIcon"
                gradient="from-orange-500 to-red-500"
            />
            <StatCard
                label="Completed (Month)"
                :value="stats?.orders?.completed_this_month ?? 0"
                :icon="CheckCircleIcon"
                gradient="from-emerald-500 to-green-600"
            />
        </div>

        <!-- Earnings Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Earnings This Month - gradient card -->
            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Earnings This Month</p>
                        <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats?.earnings?.this_month) }}</p>
                    </div>
                    <CurrencyDollarIcon class="h-10 w-10 text-emerald-200" />
                </div>
            </div>

            <MiniStatCard label="Unpaid" :value="formatCurrency(stats?.earnings?.unpaid)" :icon="BanknotesIcon" icon-color="text-amber-500" />
            <MiniStatCard label="Paid This Month" :value="formatCurrency(stats?.earnings?.paid_this_month)" :icon="CheckCircleIcon" icon-color="text-green-500" value-class="text-green-600" />
            <MiniStatCard label="Avg. Per Order" :value="formatCurrency(stats?.earnings?.average_per_order)" :icon="ChartBarIcon" icon-color="text-indigo-500" />
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Orders Needing Action -->
            <DashboardSection
                title="Needs Your Action"
                :header-icon="ExclamationTriangleIcon"
                :badge="stats?.action_required?.length > 0 ? stats.action_required.length : null"
            >
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
            </DashboardSection>

            <!-- Current Work -->
            <DashboardSection title="My Current Work" :view-all-href="route('designer.dashboard')">
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
            </DashboardSection>
        </div>

        <!-- Performance & Recent Completions -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <DashboardSection title="My Performance">
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
            </DashboardSection>

            <DashboardSection title="Recent Completions">
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
            </DashboardSection>
        </div>

        <!-- Quick Actions -->
        <DashboardSection title="Quick Actions">
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
        </DashboardSection>
    </div>
</template>
