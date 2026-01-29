<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, reactive, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    invoices: {
        type: Object,
        required: true,
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    client_id: props.filters?.client_id ?? "all",
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

const invoiceColumns = [
    { key: "number", label: "Invoice" },
    { key: "client_name", label: "Client" },
    { key: "status_label", label: "Status" },
    { key: "issue_date", label: "Issued" },
    { key: "due_date", label: "Due" },
    {
        key: "total_amount",
        label: "Amount",
        headerClass: "text-right",
        cellClass: "text-right",
    },
];

const rows = computed(() => props.invoices?.data?.data ?? []);
const paginationMeta = computed(() => props.invoices?.meta ?? null);
const paginationLinks = computed(() => props.invoices?.links ?? []);

const applyFilters = () => {
    router.get(
        route("invoices.index"),
        { ...filterForm },
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    filterForm.search = "";
    filterForm.status = "all";
    filterForm.client_id = "all";
    filterForm.date_from = "";
    filterForm.date_to = "";
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return (
        filterForm.search ||
        filterForm.status !== "all" ||
        filterForm.client_id !== "all" ||
        filterForm.date_from ||
        filterForm.date_to
    );
});

watch(
    () => filterForm.status,
    () => applyFilters(),
    { immediate: false }
);

watch(
    () => filterForm.client_id,
    () => applyFilters(),
    { immediate: false }
);

watch(
    () => filterForm.date_from,
    () => applyFilters(),
    { immediate: false }
);

watch(
    () => filterForm.date_to,
    () => applyFilters(),
    { immediate: false }
);
</script>

<template>
    <AppLayout>
        <template #header>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        Invoices
                    </h2>
                    <p class="text-sm text-slate-500">
                        Track issued invoices, payment statuses, and outstanding
                        balances.
                    </p>
                </div>
                <Link
                    :href="route('invoices.create')"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    New Invoice
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <div
                        class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                    >
                        <div class="border-b border-slate-100 px-5 py-4">
                            <div
                                class="flex flex-wrap items-center justify-between gap-4"
                            >
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Filters
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        Refine invoices by client, status, or
                                        date range.
                                    </p>
                                </div>
                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    class="text-sm text-indigo-600 underline hover:text-indigo-900"
                                    @click="clearFilters"
                                >
                                    Clear filters
                                </button>
                            </div>
                        </div>
                        <div class="px-5 py-4">
                            <div
                                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700"
                                        for="search"
                                    >
                                        Search
                                    </label>
                                    <input
                                        v-model="filterForm.search"
                                        id="search"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Invoice # or client"
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700"
                                        for="status"
                                    >
                                        Status
                                    </label>
                                    <select
                                        v-model="filterForm.status"
                                        id="status"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
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
                                        class="block text-xs font-medium text-gray-700"
                                        for="client"
                                    >
                                        Client
                                    </label>
                                    <select
                                        v-model="filterForm.client_id"
                                        id="client"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option
                                            v-for="client in clients"
                                            :key="client.value"
                                            :value="client.value"
                                        >
                                            {{ client.label }}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1"
                                >
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-gray-700"
                                        >
                                            Issued From
                                        </label>
                                        <DatePicker
                                            v-model="filterForm.date_from"
                                            id="date_from"
                                            placeholder="Start date"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-gray-700"
                                        >
                                            Issued To
                                        </label>
                                        <DatePicker
                                            v-model="filterForm.date_to"
                                            id="date_to"
                                            placeholder="End date"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                    >
                        <div class="p-6">
                            <DataTable
                                :columns="invoiceColumns"
                                :rows="rows"
                                empty-text="No invoices found."
                            >
                                <template #cell-number="{ row }">
                                    <div class="font-semibold text-slate-900">
                                        {{ row.number }}
                                    </div>
                                </template>

                                <template #cell-client_name="{ row }">
                                    <div class="font-medium text-slate-900">
                                        {{ row.client_name }}
                                    </div>
                                    <div
                                        v-if="row.client_company"
                                        class="text-xs text-slate-500"
                                    >
                                        {{ row.client_company }}
                                    </div>
                                </template>

                                <template #cell-status_label="{ row }">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            row.is_overdue
                                                ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                                : 'bg-slate-100 text-slate-700'
                                        "
                                    >
                                        {{ row.status_label }}
                                    </span>
                                </template>

                                <template #cell-issue_date="{ row }">
                                    <span class="text-sm text-slate-900">
                                        {{ row.issue_date ?? "—" }}
                                    </span>
                                </template>

                                <template #cell-due_date="{ row }">
                                    <div class="text-sm text-slate-900">
                                        {{ row.due_date ?? "—" }}
                                    </div>
                                </template>

                                <template #cell-total_amount="{ row }">
                                    <div
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ row.currency }}
                                        {{
                                            Number(
                                                row.total_amount ?? 0
                                            ).toFixed(2)
                                        }}
                                    </div>
                                </template>
                            </DataTable>

                            <PaginationControls
                                v-if="rows.length > 0"
                                :meta="paginationMeta"
                                :links="paginationLinks"
                                label="invoices"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
