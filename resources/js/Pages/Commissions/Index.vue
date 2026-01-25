<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
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

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedCommissions.value = props.commissions.data.data
            .filter((c) => !c.is_paid)
            .map((c) => c.id);
    } else {
        selectedCommissions.value = [];
    }
};

const allSelected = computed(() => {
    const unpaid = props.commissions.data.data.filter((c) => !c.is_paid);
    return (
        unpaid.length > 0 && selectedCommissions.value.length === unpaid.length
    );
});
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
                            {{ currency }} {{ totals.total_earned.toFixed(2) }}
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
                            {{ currency }} {{ totals.total_paid.toFixed(2) }}
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
                            {{ currency }} {{ totals.total_unpaid.toFixed(2) }}
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
                    class="bg-white shadow-sm rounded-lg border border-gray-200"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            :checked="allSelected"
                                            @change="toggleAll"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        User
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Order
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Type
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Amount
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Earned
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="commissions.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No commissions found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="commission in commissions.data.data"
                                    :key="commission.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            v-if="!commission.is_paid"
                                            type="checkbox"
                                            :value="commission.id"
                                            v-model="selectedCommissions"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{
                                                commission.user?.name ??
                                                "Unknown"
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            v-if="commission.order"
                                            :href="
                                                route(
                                                    'orders.show',
                                                    commission.order.id
                                                )
                                            "
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ commission.order.order_number }}
                                        </Link>
                                        <div
                                            v-if="commission.order"
                                            class="text-xs text-gray-500 truncate max-w-xs"
                                        >
                                            {{ commission.order.title }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="{
                                                'bg-blue-50 text-blue-700 ring-blue-600/20':
                                                    commission.role_type ===
                                                    'sales',
                                                'bg-purple-50 text-purple-700 ring-purple-600/20':
                                                    commission.role_type ===
                                                    'designer',
                                            }"
                                        >
                                            {{ commission.role_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ commission.currency }}
                                            {{
                                                commission.total_amount.toFixed(
                                                    2
                                                )
                                            }}
                                        </div>
                                        <div
                                            v-if="commission.extra_amount > 0"
                                            class="text-xs text-gray-500"
                                        >
                                            Base: {{ commission.currency }}
                                            {{
                                                commission.base_amount.toFixed(
                                                    2
                                                )
                                            }}
                                        </div>
                                        <div
                                            v-if="commission.extra_amount > 0"
                                            class="text-xs text-indigo-600"
                                        >
                                            + Tip: {{ commission.currency }}
                                            {{
                                                commission.extra_amount.toFixed(
                                                    2
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900">
                                            {{
                                                formatDate(commission.earned_at)
                                            }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            on {{ commission.earned_on_status }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20':
                                                    commission.is_paid,
                                                'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20':
                                                    !commission.is_paid,
                                            }"
                                        >
                                            {{
                                                commission.is_paid
                                                    ? "Paid"
                                                    : "Unpaid"
                                            }}
                                        </span>
                                        <div
                                            v-if="
                                                commission.is_paid &&
                                                commission.paid_at
                                            "
                                            class="text-xs text-gray-500 mt-1"
                                        >
                                            {{ formatDate(commission.paid_at) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            v-if="!commission.is_paid"
                                            @click="markAsPaid(commission.id)"
                                            type="button"
                                            class="text-xs text-green-600 hover:text-green-900 font-medium"
                                        >
                                            Mark Paid
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="commissions.data.length > 0"
                        class="border-t border-gray-200 px-4 py-3 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{
                                    commissions.meta.from
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    commissions.meta.to
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    commissions.meta.total
                                }}</span>
                                results
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in commissions.links"
                                    :key="link.label"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm rounded border',
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url
                                            ? 'opacity-50 cursor-not-allowed'
                                            : '',
                                    ]"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
