<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import { useDashboard } from '@/Composables/useDashboard';
import Button from '@/Components/Button.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MiniStatCard from '@/Components/Dashboard/MiniStatCard.vue';
import DashboardSection from '@/Components/Dashboard/DashboardSection.vue';

const props = defineProps({
    stats: Object,
});

const { formatCurrency, getStatusColor, formatDate } = useDashboard();
</script>

<template>
    <div class="space-y-6">
        <!-- Top Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total Orders"
                :value="stats?.orders?.total ?? 0"
                :icon="ClipboardDocumentListIcon"
                gradient="from-indigo-500 to-purple-600"
                :secondary-text="stats?.orders?.today > 0 ? `+${stats.orders.today} today` : ''"
            />
            <StatCard
                label="Revenue (This Month)"
                :value="formatCurrency(stats?.revenue?.this_month)"
                :icon="CurrencyDollarIcon"
                gradient="from-emerald-500 to-teal-600"
            />
            <StatCard
                label="Active Clients"
                :value="stats?.clients?.active ?? 0"
                :icon="UserGroupIcon"
                gradient="from-blue-500 to-cyan-600"
                :secondary-text="`/ ${stats?.clients?.total ?? 0} total`"
                secondary-class="text-slate-500"
            />
            <StatCard
                label="Needs Attention"
                :value="stats?.orders?.needs_attention ?? 0"
                :icon="ExclamationTriangleIcon"
                gradient="from-amber-500 to-orange-600"
            />
        </div>

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <MiniStatCard label="In Progress" :value="stats?.orders?.in_progress ?? 0" :icon="ClockIcon" icon-color="text-yellow-500" />
            <MiniStatCard label="Awaiting Review" :value="stats?.orders?.awaiting_review ?? 0" :icon="DocumentTextIcon" icon-color="text-indigo-500" />
            <MiniStatCard label="Delivered (Month)" :value="stats?.orders?.delivered_this_month ?? 0" :icon="CheckCircleIcon" icon-color="text-green-500" />
            <MiniStatCard label="Unpaid Invoices" :value="formatCurrency(stats?.invoices?.unpaid_amount)" :icon="BanknotesIcon" icon-color="text-red-500" />
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Orders by Status -->
            <DashboardSection title="Orders by Status">
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
            </DashboardSection>

            <!-- Revenue Overview -->
            <DashboardSection title="Revenue Overview">
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
            </DashboardSection>
        </div>

        <!-- Recent Orders & Top Performers -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <DashboardSection title="Recent Orders" :view-all-href="route('orders.index')">
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
            </DashboardSection>

            <!-- Top Designers -->
            <DashboardSection title="Top Designers (This Month)">
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
            </DashboardSection>
        </div>

        <!-- Commission & Invoice Summary -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <DashboardSection title="Commission Summary" :view-all-href="route('commissions.index')">
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
            </DashboardSection>

            <DashboardSection title="Invoice Summary" :view-all-href="route('invoices.index')">
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
            </DashboardSection>
        </div>

        <!-- Quick Actions -->
        <DashboardSection title="Quick Actions">
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    <Button :href="route('orders.create')" variant="primary">
                        New Order
                    </Button>
                    <Button :href="route('clients.create')">
                        Add Client
                    </Button>
                    <Button :href="route('invoices.create')">
                        Create Invoice
                    </Button>
                    <Button :href="route('users.create')">
                        Invite User
                    </Button>
                </div>
            </div>
        </DashboardSection>
    </div>
</template>
