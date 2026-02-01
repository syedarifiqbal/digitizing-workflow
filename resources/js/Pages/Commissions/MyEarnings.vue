<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    filters: Object,
    orderTypeOptions: Array,
    roleType: String,
    roleLabel: String,
    totals: Object,
    currency: String,
    commissions: Object,
});

const isPaid = ref(props.filters.is_paid);
const orderType = ref(props.filters.type);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const applyFilters = () => {
    router.get(
        route("commissions.my"),
        {
            is_paid: isPaid.value,
            type: orderType.value,
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
    isPaid.value = "";
    orderType.value = "all";
    startDate.value = "";
    endDate.value = "";
    applyFilters();
};

const exportCsv = () => {
    window.location.href = route("commissions.my.export", {
        is_paid: isPaid.value,
        type: orderType.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-slate-900">My Earnings</h2>
                <p class="text-sm text-slate-500">
                    Track your {{ roleLabel.toLowerCase() }} earnings and
                    payment history.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                    <div
                        class="bg-white rounded-lg border border-slate-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                        >
                            Total Earned
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-slate-900">
                            {{ currency }}
                            {{
                                parseFloat(totals?.total_earned || 0).toFixed(2)
                            }}
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-lg border border-slate-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                        >
                            Total Paid
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">
                            {{ currency }}
                            {{ parseFloat(totals?.total_paid || 0).toFixed(2) }}
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-lg border border-slate-200 px-5 py-4"
                    >
                        <div
                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                        >
                            Pending Payment
                        </div>
                        <div
                            class="mt-1 text-2xl font-semibold text-yellow-600"
                        >
                            {{ currency }}
                            {{
                                parseFloat(totals?.total_unpaid || 0).toFixed(2)
                            }}
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div
                    class="bg-white shadow-sm rounded-lg border border-slate-200 mb-6"
                >
                    <div class="px-5 py-4 border-b border-slate-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-900">
                                Filters
                            </h3>
                            <button
                                @click="exportCsv"
                                type="button"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                            >
                                Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="px-5 py-4">
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                        >
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-700"
                                    >Order Type</label
                                >
                                <select
                                    v-model="orderType"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option value="all">All Types</option>
                                    <option
                                        v-for="option in orderTypeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-700"
                                    >Payment Status</label
                                >
                                <select
                                    v-model="isPaid"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                    <option value="">All</option>
                                    <option value="0">Unpaid</option>
                                    <option value="1">Paid</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-700"
                                    >Start Date</label
                                >
                                <input
                                    v-model="startDate"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="applyFilters"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-slate-700"
                                    >End Date</label
                                >
                                <input
                                    v-model="endDate"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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

                <!-- Earnings Table -->
                <div
                    class="bg-white shadow-sm rounded-lg border border-slate-200"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Order
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Amount
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Earned
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Notes
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="!commissions?.data?.length">
                                    <td
                                        colspan="5"
                                        class="px-4 py-8 text-center text-sm text-slate-500"
                                    >
                                        No earnings found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="commission in commissions?.data
                                        ?.data || []"
                                    :key="commission.id"
                                    class="hover:bg-slate-50"
                                >
                                    <td class="px-4 py-3">
                                        <Link
                                            v-if="commission.order"
                                            :href="
                                                route(
                                                    'orders.show',
                                                    commission.order.id
                                                )
                                            "
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ commission.order.order_number }}
                                        </Link>
                                        <div
                                            v-if="commission.order"
                                            class="text-xs text-slate-500 truncate max-w-xs"
                                        >
                                            {{ commission.order.title }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            {{ commission.currency }}
                                            {{
                                                parseFloat(
                                                    commission.total_amount || 0
                                                ).toFixed(2)
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                parseFloat(
                                                    commission.extra_amount || 0
                                                ) > 0
                                            "
                                            class="text-xs text-slate-500"
                                        >
                                            Base: {{ commission.currency }}
                                            {{
                                                parseFloat(
                                                    commission.base_amount || 0
                                                ).toFixed(2)
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                parseFloat(
                                                    commission.extra_amount || 0
                                                ) > 0
                                            "
                                            class="text-xs text-indigo-600 font-medium"
                                        >
                                            + Tip: {{ commission.currency }}
                                            {{
                                                parseFloat(
                                                    commission.extra_amount || 0
                                                ).toFixed(2)
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-900">
                                            {{
                                                formatDate(commission.earned_at)
                                            }}
                                        </div>
                                        <div class="text-xs text-slate-500">
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
                                                    : "Pending"
                                            }}
                                        </span>
                                        <div
                                            v-if="
                                                commission.is_paid &&
                                                commission.paid_at
                                            "
                                            class="text-xs text-slate-500 mt-1"
                                        >
                                            {{ formatDate(commission.paid_at) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="commission.notes"
                                            class="text-xs text-slate-600 max-w-xs"
                                        >
                                            {{ commission.notes }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="commissions?.data?.length > 0"
                        class="border-t border-slate-200 px-4 py-3 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-700">
                                Showing
                                <span class="font-medium">{{
                                    commissions.meta?.from || 0
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    commissions.meta?.to || 0
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    commissions.meta?.total || 0
                                }}</span>
                                results
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in commissions?.links || []"
                                    :key="link.label"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm rounded border',
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50',
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
