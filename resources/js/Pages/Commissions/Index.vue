<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    filters: Object,
    roleTypeOptions: Array,
    users: Array,
    totals: Object,
    currency: String,
    commissions: Object,
});

const search = ref(props.filters.search);
const roleType = ref(props.filters.role_type);
const userId = ref(props.filters.user_id);
const isPaid = ref(props.filters.is_paid);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedCommissions = ref([]);
const commissionColumns = [
    { key: "select", label: "", headerClass: "w-12" },
    { key: "user", label: "User" },
    { key: "order", label: "Order" },
    { key: "type", label: "Type" },
    {
        key: "amount",
        label: "Amount",
        headerClass: "text-right",
        cellClass: "text-right",
    },
    { key: "earned", label: "Earned" },
    { key: "status", label: "Status" },
    {
        key: "actions",
        label: "Actions",
        headerClass: "text-right",
        cellClass: "text-right",
    },
];
const commissionRows = computed(() => {
    const rows = props.commissions?.data;
    if (Array.isArray(rows)) {
        return rows;
    }
    if (rows && Array.isArray(rows.data)) {
        return rows.data;
    }
    return [];
});
const paginationMeta = computed(() => props.commissions?.meta ?? {});
const paginationLinks = computed(() => props.commissions?.links ?? []);
const formatCurrency = (value) => Number(value ?? 0).toFixed(2);

const applyFilters = () => {
    router.get(
        route("commissions.index"),
        {
            search: search.value,
            role_type: roleType.value,
            user_id: userId.value,
            is_paid: isPaid.value,
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    search.value = "";
    roleType.value = "all";
    userId.value = "all";
    isPaid.value = "";
    startDate.value = "";
    endDate.value = "";
    applyFilters();
};

const exportCsv = () => {
    window.location.href = route("commissions.export", {
        search: search.value,
        role_type: roleType.value,
        user_id: userId.value,
        is_paid: isPaid.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const markAsPaid = (commissionId) => {
    if (!confirm("Mark this commission as paid?")) return;

    router.post(
        route("commissions.mark-paid", commissionId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedCommissions.value = selectedCommissions.value.filter(
                    (id) => id !== commissionId
                );
            },
        }
    );
};

const bulkMarkAsPaid = () => {
    if (selectedCommissions.value.length === 0) {
        alert("Please select commissions to mark as paid");
        return;
    }

    if (
        !confirm(
            `Mark ${selectedCommissions.value.length} commission(s) as paid?`
        )
    )
        return;

    router.post(
        route("commissions.bulk-mark-paid"),
        {
            ids: selectedCommissions.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedCommissions.value = [];
            },
        }
    );
};

const toggleAll = (checked) => {
    if (checked) {
        selectedCommissions.value = commissionRows.value
            .filter((c) => !c.is_paid)
            .map((c) => c.id);
        return;
    }

    selectedCommissions.value = [];
};

const allSelected = computed(() => {
    const unpaid = commissionRows.value.filter((c) => !c.is_paid);
    return (
        unpaid.length > 0 && selectedCommissions.value.length === unpaid.length
    );
});

const toggleRowSelection = (id, checked) => {
    const next = new Set(selectedCommissions.value);
    if (checked) {
        next.add(id);
    } else {
        next.delete(id);
    }
    selectedCommissions.value = Array.from(next);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-gray-900">
                    All Commissions
                </h2>
                <p class="text-sm text-gray-500">
                    Manage and track all sales commissions and designer
                    earnings.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                    <div
                        class="bg-white rounded-lg border border-gray-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                        >
                            Total Earned
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ currency }}
                            {{ formatCurrency(totals.total_earned) }}
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-lg border border-gray-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                        >
                            Total Paid
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">
                            {{ currency }}
                            {{ formatCurrency(totals.total_paid) }}
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-lg border border-gray-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                        >
                            Total Unpaid
                        </div>
                        <div
                            class="mt-1 text-2xl font-semibold text-yellow-600"
                        >
                            {{ currency }}
                            {{ formatCurrency(totals.total_unpaid) }}
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div
                    class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6"
                >
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">
                                Filters
                            </h3>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="selectedCommissions.length > 0"
                                    @click="bulkMarkAsPaid"
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                >
                                    Mark {{ selectedCommissions.length }} as
                                    Paid
                                </button>
                                <button
                                    @click="exportCsv"
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                >
                                    Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-4">
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6"
                        >
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700"
                                    >Search</label
                                >
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="User or order..."
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700"
                                    >Role Type</label
                                >
                                <select
                                    v-model="roleType"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option
                                        v-for="option in roleTypeOptions"
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
                                    >User</label
                                >
                                <select
                                    v-model="userId"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option
                                        v-for="user in users"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700"
                                    >Payment Status</label
                                >
                                <select
                                    v-model="isPaid"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option value="">All</option>
                                    <option value="0">Unpaid</option>
                                    <option value="1">Paid</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700"
                                    >Start Date</label
                                >
                                <input
                                    v-model="startDate"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-700"
                                    >End Date</label
                                >
                                <input
                                    v-model="endDate"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <button
                                @click="clearFilters"
                                type="button"
                                class="text-sm text-indigo-600 underline hover:text-indigo-900"
                            >
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Commissions Table -->

                <div
                    class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                >
                    <div class="p-6">
                        <DataTable
                            :columns="commissionColumns"
                            :rows="commissionRows"
                            row-key="id"
                            empty-text="No commissions found."
                        >
                            <template #head-select>
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    :checked="allSelected"
                                    @change="toggleAll($event.target.checked)"
                                />
                            </template>

                            <template #cell-select="{ row }">
                                <div class="flex justify-center">
                                    <input
                                        v-if="!row.is_paid"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="
                                            selectedCommissions.includes(row.id)
                                        "
                                        @change="
                                            toggleRowSelection(
                                                row.id,
                                                $event.target.checked
                                            )
                                        "
                                    />
                                    <span v-else class="text-xs text-gray-400"
                                        >—</span
                                    >
                                </div>
                            </template>

                            <template #cell-user="{ row }">
                                <div class="font-medium text-gray-900">
                                    {{ row.user?.name ?? "Unassigned" }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ row.user?.email ?? "—" }}
                                </div>
                            </template>

                            <template #cell-order="{ row }">
                                <div class="font-medium text-gray-900">
                                    <Link
                                        v-if="row.order"
                                        :href="
                                            route('orders.show', {
                                                order: row.order.id,
                                            })
                                        "
                                        class="text-indigo-600 hover:text-indigo-800"
                                    >
                                        {{ row.order?.order_number }}
                                    </Link>
                                    <span v-else>—</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ row.order?.title ?? "" }}
                                </div>
                            </template>

                            <template #cell-type="{ row }">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20':
                                            row.role_type === 'sales',
                                        'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20':
                                            row.role_type === 'designer',
                                    }"
                                >
                                    {{ row.role_label }}
                                </span>
                            </template>

                            <template #cell-amount="{ row }">
                                <div
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{ row.currency }} {{ row.total_amount }}
                                </div>
                                <div
                                    v-if="row.extra_amount > 0"
                                    class="text-xs text-gray-500"
                                >
                                    Base: {{ row.currency }}
                                    {{ row.base_amount }}
                                </div>
                                <div
                                    v-if="row.extra_amount > 0"
                                    class="text-xs text-indigo-600"
                                >
                                    + Tip: {{ row.currency }}
                                    {{ row.extra_amount }}
                                </div>
                            </template>

                            <template #cell-earned="{ row }">
                                <div class="text-sm text-gray-900">
                                    {{ formatDate(row.earned_at) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    on {{ row.earned_on_status }}
                                </div>
                            </template>

                            <template #cell-status="{ row }">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20':
                                            row.is_paid,
                                        'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20':
                                            !row.is_paid,
                                    }"
                                >
                                    {{ row.is_paid ? "Paid" : "Unpaid" }}
                                </span>
                                <div
                                    v-if="row.is_paid && row.paid_at"
                                    class="text-xs text-gray-500 mt-1"
                                >
                                    {{ formatDate(row.paid_at) }}
                                </div>
                            </template>

                            <template #cell-actions="{ row }">
                                <button
                                    v-if="!row.is_paid"
                                    @click="markAsPaid(row.id)"
                                    type="button"
                                    class="text-xs text-green-600 hover:text-green-900 font-medium"
                                >
                                    Mark Paid
                                </button>
                            </template>
                        </DataTable>
                        <PaginationControls
                            v-if="commissionRows.length > 0"
                            :meta="paginationMeta"
                            :links="paginationLinks"
                            label="commissions"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
