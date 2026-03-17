<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import RowActions from "@/Components/RowActions.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    orders:         Object,
    section:        String,
    activeTab:      String,
    typeFilter:     String,
    search:         String,
    stats:          Object,
    sectionCounts:  Object,
    completedStats: Object,
});

// ── Flat paginator accessors ──────────────────────────────────────────────────
const orderList     = computed(() => props.orders?.data ?? []);
const paginationLinks = computed(() => props.orders?.links ?? []);
const paginationMeta  = computed(() => ({
    total:    props.orders?.total    ?? 0,
    from:     props.orders?.from     ?? 0,
    to:       props.orders?.to       ?? 0,
    per_page: props.orders?.per_page ?? 15,
}));

// ── Search ────────────────────────────────────────────────────────────────────
const searchInput = ref(props.search ?? "");

let searchTimer = null;
const onSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route("client.orders.index"), {
            section: props.section,
            tab:     props.activeTab,
            type:    props.typeFilter,
            search:  searchInput.value,
        }, { preserveState: true, replace: true });
    }, 350);
};

// ── Section / Tab / Type navigation ──────────────────────────────────────────
const goSection = (section) => {
    router.get(route("client.orders.index"), { section }, { preserveState: false });
};

const goTab = (tab) => {
    router.get(route("client.orders.index"), { section: props.section, tab }, { preserveState: false });
};

const filterByType = (type) => {
    router.get(route("client.orders.index"), {
        section: props.section,
        tab:     "completed",
        type,
        search:  searchInput.value,
    }, { preserveState: true, replace: true });
};

// ── Type tabs ─────────────────────────────────────────────────────────────────
const typeTabs = [
    { key: "all",        label: "All" },
    { key: "digitizing", label: "Digitizing" },
    { key: "vector",     label: "Vector" },
    { key: "patch",      label: "Patch" },
];

const completedCards = computed(() => [
    { key: "all",        label: "Total",      value: props.completedStats?.total,      color: "text-slate-600",  bg: "bg-slate-50",  border: "border-slate-200" },
    { key: "digitizing", label: "Digitizing", value: props.completedStats?.digitizing, color: "text-indigo-600", bg: "bg-indigo-50", border: "border-indigo-200" },
    { key: "vector",     label: "Vector",     value: props.completedStats?.vector,     color: "text-cyan-600",   bg: "bg-cyan-50",   border: "border-cyan-200" },
    { key: "patch",      label: "Patch",      value: props.completedStats?.patch,      color: "text-rose-600",   bg: "bg-rose-50",   border: "border-rose-200" },
]);

// ── Badge helpers ─────────────────────────────────────────────────────────────
const getStatusColor = (status) => {
    const colors = {
        received:    "bg-slate-100 text-slate-700 ring-slate-600/20",
        assigned:    "bg-blue-50 text-blue-700 ring-blue-600/20",
        in_progress: "bg-purple-50 text-purple-700 ring-purple-600/20",
        submitted:   "bg-indigo-50 text-indigo-700 ring-indigo-600/20",
        in_review:   "bg-yellow-50 text-yellow-700 ring-yellow-600/20",
        approved:    "bg-teal-50 text-teal-700 ring-teal-600/20",
        delivered:   "bg-green-50 text-green-700 ring-green-600/20",
        closed:      "bg-slate-100 text-slate-700 ring-slate-600/20",
        cancelled:   "bg-red-50 text-red-700 ring-red-600/20",
    };
    return colors[status] || "bg-slate-100 text-slate-700";
};

const typeBadge = (type) => {
    const map = {
        digitizing: "bg-indigo-100 text-indigo-700",
        vector:     "bg-cyan-100 text-cyan-700",
        patch:      "bg-rose-100 text-rose-700",
    };
    return map[type] || "bg-slate-100 text-slate-700";
};

// ── Table columns ─────────────────────────────────────────────────────────────
const inProgressColumns = [
    { key: "order",    label: "Order" },
    { key: "status",   label: "Status" },
    { key: "priority", label: "Priority" },
    { key: "created",  label: "Created" },
    { key: "actions",  label: "", headerClass: "text-right" },
];

const completedColumns = [
    { key: "order",     label: "Order" },
    { key: "type",      label: "Type" },
    { key: "status",    label: "Status" },
    { key: "delivered", label: "Delivered" },
    { key: "actions",   label: "", headerClass: "text-right" },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">My Orders</h2>
                    <p class="text-sm text-slate-500">View and manage your orders and quotes.</p>
                </div>
                <Button :href="route('client.orders.create')" variant="primary">
                    Create Order
                </Button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">

            <!-- ── Section Tabs: Orders | Quotes ──────────────────────────── -->
            <div class="flex gap-1 border-b border-slate-200">
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
            <div class="flex gap-1 border-b border-slate-100">
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
                        {{ stats?.in_progress ?? 0 }}
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
                        {{ stats?.completed ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- Search bar (shared) -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input
                        v-model="searchInput"
                        type="text"
                        :placeholder="section === 'quotes' ? 'Search quote number or title...' : 'Search order number or title...'"
                        class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                        @input="onSearch"
                    />
                </div>
            </div>

            <!-- ── IN PROGRESS ──────────────────────────────────────────── -->
            <template v-if="activeTab !== 'completed'">
                <DataTable
                    :columns="inProgressColumns"
                    :rows="orderList"
                    :row-class="(row) => row.parent_order_id ? 'bg-indigo-50 !border-l-2 !border-l-indigo-300' : ''"
                >
                    <template #empty>
                        <div class="text-center py-8">
                            <p class="text-slate-400">No active {{ section === 'quotes' ? 'quotes' : 'orders' }}.</p>
                            <Link :href="route('client.orders.create')" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-900">
                                Create a new order
                            </Link>
                        </div>
                    </template>

                    <template #cell-order="{ row }">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <Link :href="route('client.orders.show', row.id)" class="font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ row.order_number }}
                                </Link>
                                <span v-if="row.parent_order_id" class="inline-flex items-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-xs font-medium text-indigo-700">REV</span>
                            </div>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ row.title }}</div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="getStatusColor(row.status)">
                            {{ row.status_label }}
                        </span>
                    </template>

                    <template #cell-priority="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="row.priority === 'rush' ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-slate-50 text-slate-700 ring-slate-600/20'">
                            {{ row.priority_label }}
                        </span>
                    </template>

                    <template #cell-created="{ row }">
                        <div class="text-sm text-slate-900">{{ formatDate(row.created_at) }}</div>
                    </template>

                    <template #cell-actions="{ row }">
                        <RowActions :actions="[{ type: 'view', href: route('client.orders.show', row.id) }]" />
                    </template>
                </DataTable>

                <PaginationControls :meta="paginationMeta" :links="paginationLinks" :label="section === 'quotes' ? 'quotes' : 'orders'" />
            </template>

            <!-- ── COMPLETED ───────────────────────────────────────────── -->
            <template v-else>
                <!-- Type Stat Cards -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
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
                <div class="flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1">
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
                            ({{ tab.key === 'all' ? completedStats?.total : (completedStats?.[tab.key] ?? 0) }})
                        </span>
                    </button>
                </div>

                <DataTable
                    :columns="completedColumns"
                    :rows="orderList"
                    :row-class="(row) => row.parent_order_id ? 'bg-indigo-50 !border-l-2 !border-l-indigo-300' : ''"
                    :empty-text="`No completed ${section === 'quotes' ? 'quotes' : 'orders'} yet.`"
                >
                    <template #cell-order="{ row }">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <Link :href="route('client.orders.show', row.id)" class="font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ row.order_number }}
                                </Link>
                                <span v-if="row.parent_order_id" class="inline-flex items-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-xs font-medium text-indigo-700">REV</span>
                            </div>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ row.title }}</div>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="typeBadge(row.type)">
                            {{ row.type }}
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="getStatusColor(row.status)">
                            {{ row.status_label }}
                        </span>
                    </template>

                    <template #cell-delivered="{ row }">
                        <div class="text-sm text-slate-900">{{ formatDate(row.delivered_at) }}</div>
                    </template>

                    <template #cell-actions="{ row }">
                        <RowActions :actions="[{ type: 'view', href: route('client.orders.show', row.id) }]" />
                    </template>
                </DataTable>

                <PaginationControls :meta="paginationMeta" :links="paginationLinks" :label="section === 'quotes' ? 'quotes' : 'orders'" />
            </template>

        </div>
    </AppLayout>
</template>
