<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    completedStats: Object,
    section: String,
    sectionCounts: Object,
    activeTab: String,
    statusFilter: String,
    typeFilter: String,
    orders: Object,
});

// ── Status filter tabs (In Progress tab only) ─────────────────────────────────
const statusTabs = [
    { key: 'all', label: 'All' },
    { key: 'assigned', label: 'Assigned' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'in_review', label: 'In Review' },
    { key: 'approved', label: 'Approved' },
];

// ── Type filter tabs (Completed tab only) ─────────────────────────────────────
const typeTabs = [
    { key: 'all', label: 'All' },
    { key: 'digitizing', label: 'Digitizing' },
    { key: 'vector', label: 'Vector' },
    { key: 'patch', label: 'Patch' },
];

// ── In-progress stat cards ────────────────────────────────────────────────────
const statCards = computed(() => [
    { key: 'assigned',    label: 'Assigned',    value: props.stats.assigned,    color: 'text-blue-600',   bg: 'bg-blue-50',   border: 'border-blue-200' },
    { key: 'in_progress', label: 'In Progress', value: props.stats.in_progress, color: 'text-yellow-600', bg: 'bg-yellow-50', border: 'border-yellow-200' },
    { key: 'submitted',   label: 'Submitted',   value: props.stats.submitted,   color: 'text-indigo-600', bg: 'bg-indigo-50', border: 'border-indigo-200' },
    { key: 'in_review',   label: 'In Review',   value: props.stats.in_review,   color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200' },
    { key: 'approved',    label: 'Approved',    value: props.stats.approved,    color: 'text-green-600',  bg: 'bg-green-50',  border: 'border-green-200' },
]);

// ── Completed stat cards ──────────────────────────────────────────────────────
const completedCards = computed(() => [
    { key: 'all',        label: 'Total',      value: props.completedStats.total,      color: 'text-slate-600',  bg: 'bg-slate-50',  border: 'border-slate-200' },
    { key: 'digitizing', label: 'Digitizing', value: props.completedStats.digitizing, color: 'text-indigo-600', bg: 'bg-indigo-50', border: 'border-indigo-200' },
    { key: 'vector',     label: 'Vector',     value: props.completedStats.vector,     color: 'text-cyan-600',   bg: 'bg-cyan-50',   border: 'border-cyan-200' },
    { key: 'patch',      label: 'Patch',      value: props.completedStats.patch,      color: 'text-rose-600',   bg: 'bg-rose-50',   border: 'border-rose-200' },
]);

// ── Badge helpers ─────────────────────────────────────────────────────────────
const statusBadge = (status) => {
    const map = {
        assigned:    'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        submitted:   'bg-indigo-100 text-indigo-700',
        in_review:   'bg-purple-100 text-purple-700',
        approved:    'bg-green-100 text-green-700',
        delivered:   'bg-emerald-100 text-emerald-700',
        closed:      'bg-slate-100 text-slate-600',
    };
    return map[status] || 'bg-slate-100 text-slate-700';
};

const typeBadge = (type) => {
    const map = {
        digitizing: 'bg-indigo-100 text-indigo-700',
        vector:     'bg-cyan-100 text-cyan-700',
        patch:      'bg-rose-100 text-rose-700',
        quotation:  'bg-amber-100 text-amber-700',
    };
    return map[type] || 'bg-slate-100 text-slate-700';
};

const priorityBadge = (priority) => {
    const map = {
        normal:       'bg-slate-100 text-slate-600',
        rush:         'bg-amber-100 text-amber-700',
        super_urgent: 'bg-red-100 text-red-700',
    };
    return map[priority] || 'bg-slate-100 text-slate-600';
};

const statusLabel = (s) => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

// ── Navigation helpers ────────────────────────────────────────────────────────
const goSection = (section) => {
    router.get(route('designer.dashboard'), { section }, { preserveState: false });
};

const goTab = (tab) => {
    router.get(route('designer.dashboard'), { section: props.section, tab }, { preserveState: false });
};

const filterByStatus = (status) => {
    router.get(route('designer.dashboard'), { section: props.section, tab: 'in_progress', status }, { preserveState: true });
};

const filterByType = (type) => {
    router.get(route('designer.dashboard'), { section: props.section, tab: 'completed', type }, { preserveState: true });
};

// ── Order actions ─────────────────────────────────────────────────────────────
const startWork = (orderId) => {
    router.patch(route('orders.status', orderId), { status: 'in_progress' }, { preserveScroll: true });
};

const submitWork = (orderId) => {
    router.post(route('orders.submit-work', orderId), {}, { preserveScroll: true });
};

// ── Date helpers ──────────────────────────────────────────────────────────────
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const isOverdue = (dueAt) => dueAt && new Date(dueAt) < new Date();
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900">My Work</h1>
                <p class="mt-1 text-sm text-slate-500">Your assigned orders and completed work</p>
            </div>

            <!-- ── Section Tabs: Orders | Quotes ──────────────────────────── -->
            <div class="mb-1 flex gap-1 border-b border-slate-200">
                <button
                    @click="goSection('orders')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition"
                    :class="section !== 'quotes'
                        ? 'border-indigo-600 text-indigo-700'
                        : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Orders
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                        :class="section !== 'quotes' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'">
                        {{ sectionCounts?.orders ?? 0 }}
                    </span>
                </button>
                <button
                    @click="goSection('quotes')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition"
                    :class="section === 'quotes'
                        ? 'border-indigo-600 text-indigo-700'
                        : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    Quotes
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                        :class="section === 'quotes' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'">
                        {{ sectionCounts?.quotes ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- ── Secondary Tabs: In Progress | Completed ────────────────── -->
            <div class="mb-6 flex gap-1 border-b border-slate-100">
                <button
                    @click="goTab('in_progress')"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition"
                    :class="activeTab !== 'completed'
                        ? 'border-slate-700 text-slate-900'
                        : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    In Progress
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                        :class="activeTab !== 'completed' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-400'">
                        {{ stats.total_active }}
                    </span>
                </button>
                <button
                    @click="goTab('completed')"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition"
                    :class="activeTab === 'completed'
                        ? 'border-slate-700 text-slate-900'
                        : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    Completed
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                        :class="activeTab === 'completed' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-400'">
                        {{ completedStats.total }}
                    </span>
                </button>
            </div>

            <!-- ── IN PROGRESS ──────────────────────────────────────────────── -->
            <template v-if="activeTab !== 'completed'">
                <!-- Stat Cards -->
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <button
                        v-for="stat in statCards"
                        :key="stat.key"
                        @click="filterByStatus(stat.key)"
                        class="rounded-lg border p-3 text-left transition hover:shadow-md"
                        :class="[stat.bg, stat.border, statusFilter === stat.key ? 'ring-2 ring-indigo-400 ring-offset-1' : '']"
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
                        <span class="ml-1 text-xs opacity-70">
                            ({{ tab.key === 'all' ? stats.total_active : (stats[tab.key] ?? 0) }})
                        </span>
                    </button>
                </div>

                <!-- In-Progress Table -->
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div v-if="orders.data.length === 0" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-2 text-sm text-slate-500">No active {{ section === 'quotes' ? 'quotes' : 'orders' }}.</p>
                    </div>

                    <table v-else class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Order</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Client</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 lg:table-cell">Due Date</th>
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
                                    <span v-if="order.priority !== 'normal'" class="mt-0.5 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium md:hidden" :class="priorityBadge(order.priority)">
                                        {{ order.priority === 'super_urgent' ? 'Urgent' : 'Rush' }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ order.client || '-' }}</td>
                                <td class="hidden px-4 py-3 md:table-cell">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="typeBadge(order.type)">
                                        {{ order.type }}
                                    </span>
                                    <span v-if="order.priority !== 'normal'" class="ml-1 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium" :class="priorityBadge(order.priority)">
                                        {{ order.priority === 'super_urgent' ? 'Urgent' : 'Rush' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge(order.status)">
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <span v-if="order.due_at" class="text-sm" :class="isOverdue(order.due_at) ? 'font-medium text-red-600' : 'text-slate-600'">
                                        {{ formatDate(order.due_at) }}
                                        <span v-if="isOverdue(order.due_at)" class="text-xs">(Overdue)</span>
                                    </span>
                                    <span v-else class="text-sm text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="order.can_start"
                                            @click="startWork(order.id)"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-indigo-700"
                                        >
                                            Start
                                        </button>
                                        <button
                                            v-if="order.can_submit"
                                            @click="submitWork(order.id)"
                                            class="inline-flex items-center rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white transition hover:bg-green-700"
                                        >
                                            Submit
                                        </button>
                                        <Link
                                            :href="route('orders.show', order.id)"
                                            class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="orders.total > orders.per_page" class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3">
                        <div class="text-sm text-slate-500">
                            Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }}
                        </div>
                        <div class="flex gap-1">
                            <template v-for="link in orders.links" :key="link.url">
                                <Link v-if="link.url" :href="link.url" class="inline-flex items-center rounded-md px-3 py-1 text-sm transition"
                                    :class="link.active ? 'bg-indigo-100 font-medium text-indigo-700' : 'text-slate-500 hover:bg-slate-100'"
                                    v-html="link.label" preserve-scroll />
                                <span v-else class="inline-flex items-center rounded-md px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ── COMPLETED ────────────────────────────────────────────────── -->
            <template v-else>
                <!-- Type Stat Cards -->
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button
                        v-for="card in completedCards"
                        :key="card.key"
                        @click="filterByType(card.key)"
                        class="rounded-lg border p-3 text-left transition hover:shadow-md"
                        :class="[card.bg, card.border, typeFilter === card.key ? 'ring-2 ring-indigo-400 ring-offset-1' : '']"
                    >
                        <div class="text-xs font-medium text-slate-500">{{ card.label }}</div>
                        <div class="mt-1 text-2xl font-bold" :class="card.color">{{ card.value }}</div>
                    </button>
                </div>

                <!-- Type Filter Tabs -->
                <div class="mb-4 flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1">
                    <button
                        v-for="tab in typeTabs"
                        :key="tab.key"
                        @click="filterByType(tab.key)"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="typeFilter === tab.key
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                    >
                        {{ tab.label }}
                        <span class="ml-1 text-xs opacity-70">
                            ({{ tab.key === 'all' ? completedStats.total : (completedStats[tab.key] ?? 0) }})
                        </span>
                    </button>
                </div>

                <!-- Completed Table -->
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div v-if="orders.data.length === 0" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-2 text-sm text-slate-500">No completed {{ section === 'quotes' ? 'quotes' : 'orders' }} yet.</p>
                    </div>

                    <table v-else class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Order</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 lg:table-cell">Delivered</th>
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
                                <td class="hidden px-4 py-3 text-sm text-slate-600 sm:table-cell">{{ order.client || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="typeBadge(order.type)">
                                        {{ order.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge(order.status)">
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 text-sm text-slate-600 lg:table-cell">
                                    {{ formatDate(order.delivered_at) }}
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
                            Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }}
                        </div>
                        <div class="flex gap-1">
                            <template v-for="link in orders.links" :key="link.url">
                                <Link v-if="link.url" :href="link.url" class="inline-flex items-center rounded-md px-3 py-1 text-sm transition"
                                    :class="link.active ? 'bg-indigo-100 font-medium text-indigo-700' : 'text-slate-500 hover:bg-slate-100'"
                                    v-html="link.label" preserve-scroll />
                                <span v-else class="inline-flex items-center rounded-md px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
