<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    BanknotesIcon,
    ShoppingCartIcon,
} from '@heroicons/vue/24/outline';
import { useDashboard } from '@/Composables/useDashboard';
import Button from '@/Components/Button.vue';
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
                label="My Orders"
                :value="stats?.orders?.total ?? 0"
                :icon="ClipboardDocumentListIcon"
                gradient="from-indigo-500 to-purple-600"
                :secondary-text="stats?.orders?.this_month > 0 ? `+${stats.orders.this_month} this month` : ''"
            />
            <StatCard
                label="Active Orders"
                :value="stats?.orders?.active ?? 0"
                :icon="ClockIcon"
                gradient="from-yellow-500 to-orange-500"
            />
            <StatCard
                label="Delivered (Month)"
                :value="stats?.orders?.delivered_this_month ?? 0"
                :icon="CheckCircleIcon"
                gradient="from-emerald-500 to-green-600"
            />
            <StatCard
                label="My Clients"
                :value="stats?.clients?.total ?? 0"
                :icon="UserGroupIcon"
                gradient="from-blue-500 to-cyan-600"
                :secondary-text="stats?.clients?.new_this_month > 0 ? `+${stats.clients.new_this_month} new` : ''"
            />
        </div>

        <!-- Earnings Row -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Commission This Month - gradient card -->
            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Commission This Month</p>
                        <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats?.earnings?.this_month) }}</p>
                    </div>
                    <CurrencyDollarIcon class="h-10 w-10 text-emerald-200" />
                </div>
            </div>

            <MiniStatCard label="Unpaid" :value="formatCurrency(stats?.earnings?.unpaid)" :icon="BanknotesIcon" icon-color="text-amber-500" />
            <MiniStatCard label="Paid This Month" :value="formatCurrency(stats?.earnings?.paid_this_month)" :icon="CheckCircleIcon" icon-color="text-green-500" value-class="text-green-600" />
            <MiniStatCard label="Sales Value (Month)" :value="formatCurrency(stats?.earnings?.order_value_this_month)" :icon="ShoppingCartIcon" icon-color="text-indigo-500" />
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Orders -->
            <DashboardSection title="My Recent Orders" :view-all-href="route('orders.index')">
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
            </DashboardSection>

            <!-- Top Clients -->
            <DashboardSection title="Top Clients (By Order Value)">
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
            </DashboardSection>
        </div>

        <!-- Performance & Orders by Status -->
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
            </DashboardSection>

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
        </div>

        <!-- Recent Commissions -->
        <DashboardSection title="Recent Commissions" :view-all-href="route('commissions.my')">
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
        </DashboardSection>

        <!-- Quick Actions -->
        <DashboardSection title="Quick Actions">
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    <Button :href="route('orders.index')" variant="primary">
                        <ClipboardDocumentListIcon class="h-4 w-4 mr-2" />
                        My Orders
                    </Button>
                    <Button :href="route('commissions.my')">
                        <CurrencyDollarIcon class="h-4 w-4 mr-2" />
                        My Commissions
                    </Button>
                </div>
            </div>
        </DashboardSection>
    </div>
</template>
