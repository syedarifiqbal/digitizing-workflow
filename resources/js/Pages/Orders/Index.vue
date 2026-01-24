<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { EyeIcon, PencilSquareIcon, TrashIcon } from "@heroicons/vue/24/outline";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    filters: Object,
    orders: Object,
    statusOptions: Array,
    priorityOptions: Array,
    typeOptions: Array,
    clients: Array,
    designers: Array,
    salesUsers: Array,
    counts: Object,
    typeStats: Object,
    showOrderCards: {
        type: Boolean,
        default: false,
    },
});

const filters = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    priority: props.filters?.priority ?? "all",
    type: props.filters?.type ?? "all",
    quote: props.filters?.quote ?? "0",
    client_id: props.filters?.client_id ?? "all",
    designer_id: props.filters?.designer_id ?? "all",
    sales_user_id: props.filters?.sales_user_id ?? "all",
});

const orders = computed(() => {
    return props.orders?.data?.data ?? props.orders?.data ?? [];
});
const paginationLinks = computed(
    () => props.orders?.links ?? props.orders?.data?.links ?? []
);
const paginationMeta = computed(
    () => props.orders?.meta ?? props.orders?.data?.meta ?? null
);
const counts = computed(() => props.counts ?? { orders: {}, quotes: {} });
const isQuoteView = computed(() => filters.quote === "1");
const countValue = (key) =>
    (isQuoteView.value
        ? counts.value.quotes?.[key]
        : counts.value.orders?.[key]) ?? 0;
const quoteCount = (key) => counts.value.quotes?.[key] ?? 0;
const totalQuotes = computed(() =>
    Object.values(counts.value.quotes ?? {}).reduce(
        (sum, val) => sum + (val ?? 0),
        0
    )
);
const coreTypes = ["digitizing", "vector", "patch"];
const statsTypes = [
    { key: "digitizing", label: "Digitizing Orders" },
    { key: "vector", label: "Vector Orders" },
    { key: "patch", label: "Patch Orders" },
];
const quoteStats = [
    { key: "digitizing", label: "Digitizing Quotes" },
    { key: "vector", label: "Vector Quotes" },
    { key: "patch", label: "Patch Quotes" },
];
const labelFor = (type) => type.charAt(0).toUpperCase() + type.slice(1);
const isAllView = computed(() => (filters.type ?? "all") === "all");
const typeStats = computed(() => props.typeStats ?? null);
const showTypeStats = computed(() => !isAllView.value && typeStats.value);

const selectedIds = ref([]);

watch(
    () => props.orders,
    () => {
        selectedIds.value = [];
    }
);

const submitFilters = () => {
    router.get(route("orders.index"), filters, {
        preserveState: true,
        replace: true,
    });
};

const modal = reactive({
    show: false,
    ids: [],
    message: "",
});

const openDeleteModal = (ids) => {
    modal.ids = Array.isArray(ids) ? [...ids] : [ids];
    modal.message =
        modal.ids.length > 1
            ? `Delete ${modal.ids.length} selected order(s)?`
            : "Delete this order?";
    modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    modal.ids = [];
    modal.message = "";
};

const confirmDelete = () => {
    if (!modal.ids.length) {
        closeModal();
        return;
    }

    const baseOptions = {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            closeModal();
        },
        onFinish: () => {
            modal.show = false;
        },
    };

    if (modal.ids.length > 1) {
        router.delete(route("orders.bulk-destroy"), {
            ...baseOptions,
            data: { ids: [...modal.ids] },
        });
    } else {
        router.delete(route("orders.destroy", modal.ids[0]), baseOptions);
    }
};

const clearSelection = () => {
    selectedIds.value = [];
};

const orderColumns = [
    { key: "order", label: "Order" },
    { key: "type", label: "Type" },
    { key: "client", label: "Client" },
    { key: "designer", label: "Designer" },
    { key: "sales", label: "Sales" },
    { key: "status", label: "Status" },
    { key: "priority", label: "Priority" },
    { key: "actions", label: "", headerClass: "text-right" },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        <template v-if="isQuoteView">
                            {{
                                isAllView
                                    ? "All Quotes"
                                    : `${labelFor(filters.type)} Quotes`
                            }}
                        </template>
                        <template v-else>
                            {{
                                isAllView
                                    ? "All Orders"
                                    : `${labelFor(filters.type)} Orders`
                            }}
                        </template>
                    </h2>
                    <p class="text-sm text-slate-500">
                        <template v-if="isQuoteView">
                            Manage
                            {{
                                isAllView
                                    ? "all pending quotes"
                                    : `${labelFor(filters.type)} quotes`
                            }}
                            for clients.
                        </template>
                        <template v-else>
                            Track
                            {{
                                isAllView
                                    ? "all intake orders"
                                    : `${labelFor(filters.type)} jobs`
                            }}, priorities, and workflow status.
                        </template>
                    </p>
                </div>
                <Link
                    v-if="!isAllView"
                    :href="
                        route('orders.create', {
                            type: filters.type,
                            quote: isQuoteView ? 1 : 0,
                        })
                    "
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    New {{ labelFor(filters.type) }}
                    {{ isQuoteView ? "Quote" : "Order" }}
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <div v-if="showTypeStats" class="grid gap-6 md:grid-cols-4">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ typeStats.total }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">Open</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ typeStats.open }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">Today</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ typeStats.today }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">In Progress</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ typeStats.in_progress }}
                    </p>
                </div>
            </div>
            <div
                v-if="showOrderCards && isAllView && isQuoteView"
                class="grid gap-6 md:grid-cols-3"
            >
                <div
                    v-for="stat in quoteStats"
                    :key="'quote-stat-' + stat.key"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">{{ stat.label }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ quoteCount(stat.key) }}
                    </p>
                </div>
            </div>
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="p-6">
                    <form
                        @submit.prevent="submitFilters"
                        class="grid gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="search"
                                >Search</label
                            >
                            <input
                                v-model="filters.search"
                                id="search"
                                type="text"
                                placeholder="Search order # or title"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder-slate-400 focus:border-indigo-400 focus:ring-indigo-400"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="status"
                                >Status</label
                            >
                            <select
                                v-model="filters.status"
                                id="status"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All statuses</option>
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="priority"
                                >Priority</label
                            >
                            <select
                                v-model="filters.priority"
                                id="priority"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All priorities</option>
                                <option
                                    v-for="option in priorityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="type"
                                >Type</label
                            >
                            <select
                                v-model="filters.type"
                                id="type"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All types</option>
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="client"
                                >Client</label
                            >
                            <select
                                v-model="filters.client_id"
                                id="client"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All clients</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="designer"
                                >Designer</label
                            >
                            <select
                                v-model="filters.designer_id"
                                id="designer"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All designers</option>
                                <option
                                    v-for="designer in designers"
                                    :key="designer.id"
                                    :value="designer.id"
                                >
                                    {{ designer.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="salesUsers && salesUsers.length">
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="sales_user"
                                >Sales</label
                            >
                            <select
                                v-model="filters.sales_user_id"
                                id="sales_user"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option value="all">All sales</option>
                                <option
                                    v-for="user in salesUsers"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <div
                            class="md:col-span-full flex flex-wrap items-center gap-3"
                        >
                            <template v-if="isAllView">
                                <Link
                                    :href="
                                        route('orders.create', {
                                            type: 'digitizing',
                                            quote: isQuoteView ? 1 : 0,
                                        })
                                    "
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50"
                                >
                                    New Digitizing
                                    {{ isQuoteView ? "Quote" : "Order" }}
                                </Link>
                                <Link
                                    :href="
                                        route('orders.create', {
                                            type: 'vector',
                                            quote: isQuoteView ? 1 : 0,
                                        })
                                    "
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50"
                                >
                                    New Vector
                                    {{ isQuoteView ? "Quote" : "Order" }}
                                </Link>
                                <Link
                                    :href="
                                        route('orders.create', {
                                            type: 'patch',
                                            quote: isQuoteView ? 1 : 0,
                                        })
                                    "
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50"
                                >
                                    New Patch
                                    {{ isQuoteView ? "Quote" : "Order" }}
                                </Link>
                            </template>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                                >
                                    Apply filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="isAllView" class="grid gap-6 md:grid-cols-3">
                <div
                    v-if="!isQuoteView"
                    v-for="stat in statsTypes"
                    :key="'overview-' + stat.key"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow"
                >
                    <p class="text-sm text-slate-500">{{ stat.label }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ countValue(stat.key) }}
                    </p>
                </div>

                <div
                    v-if="showOrderCards && !isQuoteView"
                    class="rounded-2xl border border-slate-200 bg-white shadow md:col-span-2"
                >
                    <div class="grid gap-6 md:grid-cols-2">
                        <div
                            class="rounded-2xl border border-slate-100 bg-gradient-to-r from-sky-100 to-cyan-100 p-4 text-slate-900"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Quick Quotes
                            </p>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li
                                    v-for="type in coreTypes"
                                    :key="'grid-quote-' + type"
                                    class="flex items-center justify-between"
                                >
                                    <Link
                                        :href="
                                            route('orders.create', {
                                                type,
                                                quote: 1,
                                            })
                                        "
                                        class="font-medium text-slate-700 underline decoration-dotted decoration-slate-400 hover:text-indigo-600"
                                    >
                                        New {{ labelFor(type) }} Quote
                                    </Link>
                                    <span class="text-slate-500">{{
                                        quoteCount(type)
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-100 bg-gradient-to-r from-indigo-100 to-purple-100 p-4 text-slate-900"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Quick Orders
                            </p>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li
                                    v-for="type in coreTypes"
                                    :key="'grid-order-' + type"
                                    class="flex items-center justify-between"
                                >
                                    <Link
                                        :href="route('orders.create', { type })"
                                        class="font-medium text-slate-700 underline decoration-dotted decoration-slate-400 hover:text-indigo-600"
                                    >
                                        New {{ labelFor(type) }} Order
                                    </Link>
                                    <span class="text-slate-500">{{
                                        countValue(type)
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div
                    v-if="showOrderCards && !isQuoteView"
                    class="rounded-2xl border border-slate-200 bg-white shadow"
                >
                    <div
                        class="rounded-t-2xl bg-gradient-to-r from-rose-100 to-orange-100 px-4 py-3 text-sm font-semibold text-slate-800"
                    >
                        Administration
                    </div>
                    <ul
                        class="divide-y divide-slate-100 text-sm text-slate-600"
                    >
                        <li>
                            <Link
                                :href="route('clients.index')"
                                class="block px-4 py-3 transition hover:bg-slate-50"
                            >
                                Clients
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('users.index')"
                                class="block px-4 py-3 transition hover:bg-slate-50"
                            >
                                Team Members
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('settings.edit')"
                                class="block px-4 py-3 transition hover:bg-slate-50"
                            >
                                Tenant Settings
                            </Link>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="showOrderCards && isQuoteView"
                    class="rounded-2xl border border-slate-200 bg-white shadow"
                >
                    <div
                        class="rounded-t-2xl bg-gradient-to-r from-sky-100 to-cyan-100 px-4 py-3 text-sm font-semibold text-slate-800"
                    >
                        Quote Overview
                    </div>
                    <ul
                        class="divide-y divide-slate-100 text-sm text-slate-600"
                    >
                        <li
                            v-for="stat in quoteStats"
                            :key="'quote-overview-' + stat.key"
                            class="flex items-center justify-between px-4 py-3"
                        >
                            <span>{{ stat.label }}</span>
                            <span class="font-semibold text-slate-900">{{
                                quoteCount(stat.key)
                            }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="p-6">
                    <div
                        v-if="selectedIds.length"
                        class="mb-5 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700 shadow-inner shadow-indigo-100"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium"
                                >{{ selectedIds.length }} order(s)
                                selected</span
                            >
                            <button
                                type="button"
                                class="text-indigo-600 underline"
                                @click="clearSelection"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-full bg-red-500/10 px-3 py-1 text-xs font-semibold text-red-600"
                                @click="openDeleteModal(selectedIds)"
                            >
                                Delete selected
                            </button>
                        </div>
                    </div>

                    <DataTable
                        :columns="orderColumns"
                        :rows="orders"
                        selectable
                        v-model:selected-ids="selectedIds"
                        :empty-text="
                            isQuoteView
                                ? 'No quotes found.'
                                : 'No orders found.'
                        "
                    >
                        <template #cell-order="{ row }">
                            <div class="font-medium text-slate-900">
                                {{ row.order_number }}
                                <span v-if="row.po_number" class="ml-1 text-xs font-normal text-slate-400">({{ row.po_number }})</span>
                            </div>
                            <p class="text-sm text-slate-500">
                                {{ row.title }}
                            </p>
                            <p class="text-xs text-slate-400">
                                Created {{ formatDate(row.created_at, true) }}
                            </p>
                        </template>
                        <template #cell-type="{ row }">
                            <span class="text-sm capitalize text-slate-900">
                                {{ (row.type || "").split("_").join(" ") }}
                            </span>
                        </template>
                        <template #cell-client="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.client ?? "—"
                            }}</span>
                        </template>
                        <template #cell-designer="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.designer ?? "—"
                            }}</span>
                        </template>
                        <template #cell-sales="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.sales ?? "—"
                            }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize',
                                    row.status === 'delivered' ||
                                    row.status === 'approved'
                                        ? 'bg-green-100 text-green-800'
                                        : row.status === 'revision_requested'
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ (row.status || "").split("_").join(" ") }}
                            </span>
                        </template>
                        <template #cell-priority="{ row }">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize',
                                    row.priority === 'rush'
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-blue-100 text-blue-800',
                                ]"
                            >
                                {{ row.priority }}
                            </span>
                        </template>
                        <template #cell-actions="{ row }">
                            <div
                                class="text-right text-sm font-medium space-x-1"
                            >
                                <Link
                                    :href="route('orders.show', row.id)"
                                    class="inline-flex items-center rounded-full p-2 text-slate-500 hover:text-slate-900"
                                    title="View"
                                >
                                    <span class="sr-only">View</span>
                                    <EyeIcon class="h-5 w-5" />
                                </Link>
                                <Link
                                    :href="route('orders.edit', row.id)"
                                    class="inline-flex items-center rounded-full p-2 text-slate-500 hover:text-indigo-600"
                                    title="Edit"
                                >
                                    <span class="sr-only">Edit</span>
                                    <PencilSquareIcon class="h-5 w-5" />
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-full p-2 text-slate-500 hover:text-red-600"
                                    @click="openDeleteModal(row.id)"
                                    title="Delete"
                                >
                                    <span class="sr-only">Delete</span>
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </template>
                    </DataTable>
                    <PaginationControls
                        :meta="paginationMeta"
                        :links="paginationLinks"
                        :label="isQuoteView ? 'quotes' : 'orders'"
                    />
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="modal.show"
            :message="modal.message"
            confirm-label="Delete"
            @close="closeModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
