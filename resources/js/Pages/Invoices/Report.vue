<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, reactive, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import DatePicker from "@/Components/DatePicker.vue";
import { useDashboard } from "@/Composables/useDashboard";

const { formatCurrency } = useDashboard();

const props = defineProps({
    filters: Object,
    summary: Object,
    aging: Object,
    clients: Array,
    statusOptions: Array,
    currency: { type: String, default: "USD" },
});

const filterForm = reactive({
    status: props.filters?.status ?? "all",
    client_id: props.filters?.client_id ?? "all",
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

const applyFilters = () => {
    router.get(route("invoices.report"), { ...filterForm }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filterForm.status = "all";
    filterForm.client_id = "all";
    filterForm.date_from = "";
    filterForm.date_to = "";
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return filterForm.status !== "all" || filterForm.client_id !== "all" || filterForm.date_from || filterForm.date_to;
});

watch(() => filterForm.status, () => applyFilters(), { immediate: false });
watch(() => filterForm.client_id, () => applyFilters(), { immediate: false });
watch(() => filterForm.date_from, () => applyFilters(), { immediate: false });
watch(() => filterForm.date_to, () => applyFilters(), { immediate: false });

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.status !== "all") params.set("status", filterForm.status);
    if (filterForm.client_id !== "all") params.set("client_id", filterForm.client_id);
    if (filterForm.date_from) params.set("date_from", filterForm.date_from);
    if (filterForm.date_to) params.set("date_to", filterForm.date_to);
    const qs = params.toString();
    return route("invoices.export") + (qs ? "?" + qs : "");
});

const bucketLabels = {
    current: "Current",
    "1_30": "1–30 days",
    "31_60": "31–60 days",
    "61_90": "61–90 days",
    over_90: "90+ days",
};

const bucketColors = {
    current: "bg-green-100 text-green-800",
    "1_30": "bg-yellow-100 text-yellow-800",
    "31_60": "bg-orange-100 text-orange-800",
    "61_90": "bg-red-100 text-red-700",
    over_90: "bg-red-200 text-red-900",
};

const agingDetails = computed(() => props.aging?.details ?? []);
const agingBuckets = computed(() => props.aging?.buckets ?? {});
const agingTotal = computed(() => Object.values(agingBuckets.value).reduce((a, b) => a + b, 0));
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Invoice Reports</h2>
                    <p class="text-sm text-slate-500">Summary, aging, and export tools for your invoices.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Export CSV
                    </a>
                    <Link
                        :href="route('invoices.index')"
                        class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                    >
                        All Invoices
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Filters -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="border-b border-slate-100 px-5 py-4 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
                            <p class="text-xs text-slate-500">Filter report data by status, client, or date range.</p>
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
                    <div class="px-5 py-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Status</label>
                                <select v-model="filterForm.status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Client</label>
                                <select v-model="filterForm.client_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="client in clients" :key="client.value" :value="client.value">{{ client.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">From</label>
                                <DatePicker v-model="filterForm.date_from" placeholder="Start date" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">To</label>
                                <DatePicker v-model="filterForm.date_to" placeholder="End date" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Invoiced</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ formatCurrency(summary.total_invoiced, currency) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Paid</p>
                        <p class="mt-1 text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_paid, currency) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding</p>
                        <p class="mt-1 text-2xl font-bold text-yellow-600">{{ formatCurrency(summary.total_outstanding, currency) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/70">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Overdue</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_overdue, currency) }}</p>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">Invoice Count by Status</h3>
                    </div>
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap gap-3">
                            <div
                                v-for="(count, status) in summary.count_by_status"
                                :key="status"
                                class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2"
                            >
                                <span class="text-sm font-medium capitalize text-slate-700">{{ status.replace('_', ' ') }}</span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aging Report -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">Aging Report</h3>
                        <p class="text-xs text-slate-500">Outstanding invoices grouped by days past due.</p>
                    </div>
                    <div class="px-5 py-4 space-y-4">
                        <!-- Aging Buckets -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            <div
                                v-for="(amount, key) in agingBuckets"
                                :key="key"
                                class="rounded-xl border border-slate-200 p-3 text-center"
                            >
                                <p class="text-xs font-medium text-slate-500">{{ bucketLabels[key] }}</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ formatCurrency(amount, currency) }}</p>
                            </div>
                            <div class="rounded-xl border-2 border-slate-300 bg-slate-50 p-3 text-center">
                                <p class="text-xs font-medium text-slate-500">Total</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ formatCurrency(agingTotal, currency) }}</p>
                            </div>
                        </div>

                        <!-- Aging Details Table -->
                        <div v-if="agingDetails.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500">Invoice</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500">Client</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500">Status</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500">Due Date</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500">Days Overdue</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500">Amount</th>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500">Bucket</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in agingDetails" :key="item.id">
                                        <td class="px-3 py-2">
                                            <Link :href="route('invoices.show', item.id)" class="font-semibold text-indigo-600 hover:text-indigo-800">
                                                {{ item.number }}
                                            </Link>
                                        </td>
                                        <td class="px-3 py-2 text-slate-700">{{ item.client_name }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                {{ item.status_label }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-slate-700">{{ item.due_date ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right font-medium" :class="item.days_overdue > 60 ? 'text-red-600' : item.days_overdue > 30 ? 'text-orange-600' : 'text-slate-700'">
                                            {{ item.days_overdue }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900">
                                            {{ item.currency }} {{ Number(item.total_amount).toFixed(2) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="bucketColors[item.bucket]">
                                                {{ bucketLabels[item.bucket] }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-slate-500">No outstanding invoices.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
