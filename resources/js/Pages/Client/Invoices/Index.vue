<script setup>
import { computed, reactive } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    invoices: Object,
    filters: Object,
    totals: Object,
    currency: String,
});

const filters = reactive({
    status: props.filters?.status ?? "all",
});

const invoices = computed(
    () => props.invoices?.data?.data ?? props.invoices?.data ?? []
);
const paginationLinks = computed(
    () => props.invoices?.links ?? props.invoices?.data?.links ?? []
);
const paginationMeta = computed(
    () => props.invoices?.meta ?? props.invoices?.data?.meta ?? null
);

const submitFilters = () => {
    router.get(route("client.invoices.index"), filters, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filters.status = "all";
    submitFilters();
};

const getStatusColor = (status) => {
    const colors = {
        sent: "bg-blue-50 text-blue-700 ring-blue-600/20",
        partially_paid: "bg-yellow-50 text-yellow-700 ring-yellow-600/20",
        paid: "bg-green-50 text-green-700 ring-green-600/20",
        overdue: "bg-red-50 text-red-700 ring-red-600/20",
        cancelled: "bg-gray-100 text-gray-700 ring-gray-600/20",
        void: "bg-gray-100 text-gray-700 ring-gray-600/20",
    };
    return colors[status] || "bg-gray-100 text-gray-700";
};

const formatAmount = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const invoiceColumns = [
    { key: "invoice", label: "Invoice" },
    { key: "status", label: "Status" },
    { key: "issue_date", label: "Issue Date" },
    { key: "due_date", label: "Due Date" },
    { key: "amount", label: "Amount", headerClass: "text-right" },
    { key: "actions", label: "", headerClass: "text-right" },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">My Invoices</h2>
                    <p class="text-sm text-slate-500">View and download your invoices.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Unpaid Invoices
                    </div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ totals?.unpaid_count || 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Total Due
                    </div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ currency }} {{ formatAmount(totals?.total_due) }}
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-lg shadow-slate-200/50">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Overdue
                    </div>
                    <div class="mt-2 text-3xl font-bold" :class="totals?.overdue_count > 0 ? 'text-red-600' : 'text-slate-900'">
                        {{ totals?.overdue_count || 0 }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="p-6">
                    <form @submit.prevent="submitFilters" class="grid gap-5 md:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Status</label>
                            <select
                                v-model="filters.status"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                                @change="submitFilters"
                            >
                                <option value="all">All statuses</option>
                                <option value="sent">Sent</option>
                                <option value="partially_paid">Partially Paid</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                type="button"
                                @click="clearFilters"
                                class="text-sm text-indigo-600 underline hover:text-indigo-900"
                            >
                                Clear filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DataTable -->
            <DataTable :columns="invoiceColumns" :rows="invoices" empty-text="No invoices found.">
                <template #empty>
                    <div class="text-center">
                        <p class="text-slate-400">No invoices found.</p>
                    </div>
                </template>

                <template #cell-invoice="{ row }">
                    <div>
                        <Link
                            :href="route('client.invoices.show', row.id)"
                            class="font-medium text-indigo-600 hover:text-indigo-900"
                        >
                            {{ row.invoice_number }}
                        </Link>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                        :class="getStatusColor(row.status)"
                    >
                        {{ row.status_label }}
                    </span>
                </template>

                <template #cell-issue_date="{ row }">
                    <div class="text-sm text-slate-900">
                        {{ formatDate(row.issue_date) }}
                    </div>
                </template>

                <template #cell-due_date="{ row }">
                    <div class="text-sm" :class="row.is_overdue ? 'text-red-600 font-medium' : 'text-slate-900'">
                        {{ formatDate(row.due_date) }}
                    </div>
                </template>

                <template #cell-amount="{ row }">
                    <div class="text-right text-sm font-medium text-slate-900">
                        {{ row.currency }} {{ formatAmount(row.total_amount) }}
                    </div>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-3">
                        <Link
                            :href="route('client.invoices.show', row.id)"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                        >
                            View
                        </Link>
                        <a
                            :href="route('client.invoices.pdf', row.id)"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900"
                            target="_blank"
                        >
                            PDF
                        </a>
                    </div>
                </template>
            </DataTable>

            <!-- Pagination -->
            <PaginationControls :meta="paginationMeta" :links="paginationLinks" label="invoices" />
        </div>
    </AppLayout>
</template>
