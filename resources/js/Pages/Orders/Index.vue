<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { EyeIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DataTable from '@/Components/DataTable.vue';
import PaginationControls from '@/Components/PaginationControls.vue';

const props = defineProps({
    filters: Object,
    orders: Object,
    statusOptions: Array,
    priorityOptions: Array,
    typeOptions: Array,
    clients: Array,
    designers: Array,
    counts: Object,
    typeStats: Object,
    showOrderCards: {
        type: Boolean,
        default: false,
    },
});

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
    priority: props.filters?.priority ?? 'all',
    type: props.filters?.type ?? 'all',
    quote: props.filters?.quote ?? '0',
    client_id: props.filters?.client_id ?? 'all',
    designer_id: props.filters?.designer_id ?? 'all',
});

const orders = computed(() => props.orders?.data ?? props.orders ?? []);
const paginationLinks = computed(() => props.orders?.links ?? props.orders?.data?.links ?? []);
const paginationMeta = computed(() => props.orders?.meta ?? props.orders?.data?.meta ?? null);
const counts = computed(() => props.counts ?? { orders: {}, quotes: {} });
const isQuoteView = computed(() => filters.quote === '1');
const countValue = (key) => (isQuoteView.value ? counts.value.quotes?.[key] : counts.value.orders?.[key]) ?? 0;
const quoteCount = (key) => counts.value.quotes?.[key] ?? 0;
const totalQuotes = computed(() => Object.values(counts.value.quotes ?? {}).reduce((sum, val) => sum + (val ?? 0), 0));
const coreTypes = ['digitizing', 'vector', 'patch'];
const statsTypes = [
    { key: 'digitizing', label: 'Digitizing Orders' },
    { key: 'vector', label: 'Vector Orders' },
    { key: 'patch', label: 'Patch Orders' },
];
const quoteStats = [
    { key: 'digitizing', label: 'Digitizing Quotes' },
    { key: 'vector', label: 'Vector Quotes' },
    { key: 'patch', label: 'Patch Quotes' },
];
const labelFor = (type) => type.charAt(0).toUpperCase() + type.slice(1);
const isAllView = computed(() => (filters.type ?? 'all') === 'all');
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
    router.get(route('orders.index'), filters, {
        preserveState: true,
        replace: true,
    });
};

const modal = reactive({
    show: false,
    ids: [],
    message: '',
});

const openDeleteModal = (ids) => {
    modal.ids = Array.isArray(ids) ? [...ids] : [ids];
    modal.message = modal.ids.length > 1
        ? `Delete ${modal.ids.length} selected order(s)?`
        : 'Delete this order?';
    modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    modal.ids = [];
    modal.message = '';
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
        router.delete(route('orders.bulk-destroy'), {
            ...baseOptions,
            data: { ids: [...modal.ids] },
        });
    } else {
        router.delete(route('orders.destroy', modal.ids[0]), baseOptions);
    }
};

const clearSelection = () => {
    selectedIds.value = [];
};

const orderColumns = [
    { key: 'order', label: 'Order' },
    { key: 'type', label: 'Type' },
    { key: 'client', label: 'Client' },
    { key: 'designer', label: 'Designer' },
    { key: 'status', label: 'Status' },
    { key: 'priority', label: 'Priority' },
    { key: 'actions', label: '', headerClass: 'text-right' },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        <template v-if="isQuoteView">
                            {{ isAllView ? 'All Quotes' : `${labelFor(filters.type)} Quotes` }}
                        </template>
                        <template v-else>
                            {{ isAllView ? 'All Orders' : `${labelFor(filters.type)} Orders` }}
                        </template>
                    </h2>
                    <p class="text-sm text-gray-600">
                        <template v-if="isQuoteView">
                            Manage {{ isAllView ? 'all pending quotes' : `${labelFor(filters.type)} quotes` }} for clients.
                        </template>
                        <template v-else>
                            Track {{ isAllView ? 'all intake orders' : `${labelFor(filters.type)} jobs` }}, priorities, and workflow status.
                        </template>
                    </p>
                </div>
                <Link
                    v-if="!isAllView"
                    :href="route('orders.create', { type: filters.type, quote: isQuoteView ? 1 : 0 })"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    New {{ labelFor(filters.type) }} {{ isQuoteView ? 'Quote' : 'Order' }}
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
                <div v-if="showTypeStats" class="grid gap-6 md:grid-cols-4">
                    <div class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ typeStats.total }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">Open</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ typeStats.open }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">Today</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ typeStats.today }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">In Progress</p>
                        <p class="mt-2 text-3xl.font-semibold text-gray-900">{{ typeStats.in_progress }}</p>
                    </div>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    <div v-if="isAllView && !isQuoteView" v-for="stat in statsTypes" :key="'stat-'+stat.key" class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ countValue(stat.key) }}</p>
                    </div>
                    <div v-if="showOrderCards && isAllView && !isQuoteView" class="rounded-lg border shadow-sm bg-white">
                        <div class="rounded-t-lg bg-blue-600 px-4 py-3 text-white font-semibold">Quotes</div>
                        <ul class="divide-y divide-gray-100">
                            <li v-for="type in coreTypes" :key="'quote-summary-'+type">
                                <div class="flex flex-col">
                                    <Link
                                        :href="route('orders.create', { type, quote: 1 })"
                                        class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        New {{ labelFor(type) }} Quote
                                    </Link>
                                    <Link
                                        :href="route('orders.index', { type, quote: 1 })"
                                        class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 -mt-1"
                                    >
                                        All {{ labelFor(type) }} Quotes
                                        <span class="text-xs font-semibold text-gray-500">{{ quoteCount(type) }}</span>
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <Link
                                    :href="route('orders.index', { quote: 1 })"
                                    class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    All Quotes
                                    <span class="text-xs font-semibold text-gray-500">{{ totalQuotes }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div v-if="showOrderCards && isAllView && !isQuoteView" class="rounded-lg border shadow-sm bg-white">
                        <div class="rounded-t-lg bg-indigo-600 px-4 py-3 text-white font-semibold">Orders</div>
                        <ul class="divide-y divide-gray-100">
                            <li v-for="type in coreTypes" :key="'quick-order-'+type">
                                <div class="flex flex-col">
                                    <Link
                                        :href="route('orders.create', { type })"
                                        class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        New {{ labelFor(type) }} Order
                                    </Link>
                                    <Link
                                        :href="route('orders.index', { type })"
                                        class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 -mt-1"
                                    >
                                        All {{ labelFor(type) }} Orders
                                        <span class="text-xs font-semibold text-gray-500">{{ countValue(type) }}</span>
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div v-if="showOrderCards && isAllView && !isQuoteView" class="rounded-lg border shadow-sm bg-white">
                        <div class="rounded-t-lg bg-red-600 px-4 py-3 text-white font-semibold">Administration</div>
                        <ul class="divide-y divide-gray-100">
                            <li>
                                <Link :href="route('clients.index')" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    Customers
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('users.index')" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    Employees
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('settings.edit')" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    Tenant Settings
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="showOrderCards && isAllView && isQuoteView" class="grid gap-6 md:grid-cols-3">
                    <div v-for="stat in quoteStats" :key="'quote-stat-'+stat.key" class="rounded-lg bg-white p-5 shadow-sm border">
                        <p class="text-sm text-gray-500">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ countValue(stat.key) }}</p>
                    </div>
                </div>
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submitFilters" class="grid gap-4 md:grid-cols-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="search">Search</label>
                                <input
                                    v-model="filters.search"
                                    id="search"
                                    type="text"
                                    placeholder="Search order # or title"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
                                <select
                                    v-model="filters.status"
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All statuses</option>
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="priority">Priority</label>
                                <select
                                    v-model="filters.priority"
                                    id="priority"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All priorities</option>
                                    <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="type">Type</label>
                                <select
                                    v-model="filters.type"
                                    id="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All types</option>
                                    <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="client">Client</label>
                                <select
                                    v-model="filters.client_id"
                                    id="client"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All clients</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="designer">Designer</label>
                                <select
                                    v-model="filters.designer_id"
                                    id="designer"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All designers</option>
                                    <option v-for="designer in designers" :key="designer.id" :value="designer.id">
                                        {{ designer.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-6 flex justify-end gap-3">
                                <template v-if="isAllView">
                                    <Link
                                        :href="route('orders.create', { type: 'digitizing', quote: isQuoteView ? 1 : 0 })"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        New Digitizing {{ isQuoteView ? 'Quote' : 'Order' }}
                                    </Link>
                                    <Link
                                        :href="route('orders.create', { type: 'vector', quote: isQuoteView ? 1 : 0 })"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        New Vector {{ isQuoteView ? 'Quote' : 'Order' }}
                                    </Link>
                                    <Link
                                        :href="route('orders.create', { type: 'patch', quote: isQuoteView ? 1 : 0 })"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        New Patch {{ isQuoteView ? 'Quote' : 'Order' }}
                                    </Link>
                                </template>
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                                >
                                    Apply filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <div
                            v-if="selectedIds.length"
                            class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700"
                        >
                            <div class="flex flex-wrap items-center gap-3">
                                <span>{{ selectedIds.length }} order(s) selected</span>
                                <button type="button" class="text-indigo-700 underline" @click="clearSelection">
                                    Clear
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-red-700"
                                    @click="openDeleteModal(selectedIds)"
                                >
                                    Delete selected
                                </button>
                            </div>
                        </div>

                        <DataTable
                            :columns="orderColumns"
                            :rows="orders.data"
                            selectable
                            v-model:selected-ids="selectedIds"
                            :empty-text="isQuoteView ? 'No quotes found.' : 'No orders found.'"
                        >
                            <template #cell-order="{ row }">
                                <div class="font-medium text-gray-900">{{ row.order_number }}</div>
                                <p class="text-sm text-gray-600">{{ row.title }}</p>
                                <p class="text-xs text-gray-400">Created {{ row.created_at }}</p>
                            </template>
                            <template #cell-type="{ row }">
                                <span class="text-sm capitalize text-gray-900">
                                    {{ (row.type || '').split('_').join(' ') }}
                                </span>
                            </template>
                            <template #cell-client="{ row }">
                                <span class="text-sm text-gray-900">{{ row.client ?? '—' }}</span>
                            </template>
                            <template #cell-designer="{ row }">
                                <span class="text-sm text-gray-900">{{ row.designer ?? '—' }}</span>
                            </template>
                            <template #cell-status="{ row }">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize',
                                        row.status === 'delivered' || row.status === 'approved'
                                            ? 'bg-green-100 text-green-800'
                                            : row.status === 'revision_requested'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ (row.status || '').split('_').join(' ') }}
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
                                <div class="text-right text-sm font-medium space-x-1">
                                    <Link
                                        :href="route('orders.show', row.id)"
                                        class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-gray-900"
                                        title="View"
                                    >
                                        <span class="sr-only">View</span>
                                        <EyeIcon class="h-5 w-5" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full p-2 text-gray-500 hover:text-red-600"
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
