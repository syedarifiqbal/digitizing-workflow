<script setup>
import { computed, reactive, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";
import RowActions from "@/Components/RowActions.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    orders: Object,
    filters: Object,
});

const filters = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    priority: props.filters?.priority ?? "all",
});

const orders = computed(() => props.orders?.data?.data ?? props.orders?.data ?? []);
const paginationLinks = computed(() => props.orders?.links ?? props.orders?.data?.links ?? []);
const paginationMeta = computed(() => props.orders?.meta ?? props.orders?.data?.meta ?? null);

const submitFilters = () => {
    router.get(route("client.orders.index"), filters, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filters.search = "";
    filters.status = "all";
    filters.priority = "all";
    submitFilters();
};

const getStatusColor = (status) => {
    const colors = {
        received: "bg-slate-100 text-slate-700 ring-slate-600/20",
        assigned: "bg-blue-50 text-blue-700 ring-blue-600/20",
        in_progress: "bg-purple-50 text-purple-700 ring-purple-600/20",
        submitted: "bg-indigo-50 text-indigo-700 ring-indigo-600/20",
        in_review: "bg-yellow-50 text-yellow-700 ring-yellow-600/20",
        approved: "bg-teal-50 text-teal-700 ring-teal-600/20",
        delivered: "bg-green-50 text-green-700 ring-green-600/20",
        closed: "bg-gray-100 text-gray-700 ring-gray-600/20",
        cancelled: "bg-red-50 text-red-700 ring-red-600/20",
    };
    return colors[status] || "bg-gray-100 text-gray-700";
};

const getPriorityColor = (priority) => {
    return priority === "rush"
        ? "bg-red-50 text-red-700 ring-red-600/20"
        : "bg-slate-50 text-slate-700 ring-slate-600/20";
};

const orderColumns = [
    { key: "order", label: "Order" },
    { key: "status", label: "Status" },
    { key: "priority", label: "Priority" },
    { key: "created", label: "Created" },
    { key: "actions", label: "", headerClass: "text-right" },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">My Orders</h2>
                    <p class="text-sm text-slate-500">View and manage your orders.</p>
                </div>
                <Link
                    :href="route('client.orders.create')"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    Create Order
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- Filters -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="p-6">
                    <form @submit.prevent="submitFilters" class="grid gap-5 md:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Search</label>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Order number or title..."
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                                @input="submitFilters"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Status</label>
                            <select
                                v-model="filters.status"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                                @change="submitFilters"
                            >
                                <option value="all">All statuses</option>
                                <option value="received">Received</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="submitted">Submitted</option>
                                <option value="in_review">In Review</option>
                                <option value="approved">Approved</option>
                                <option value="delivered">Delivered</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Priority</label>
                            <select
                                v-model="filters.priority"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                                @change="submitFilters"
                            >
                                <option value="all">All priorities</option>
                                <option value="normal">Normal</option>
                                <option value="rush">Rush</option>
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
            <DataTable :columns="orderColumns" :rows="orders" empty-text="No orders found.">
                <template #empty>
                    <div class="text-center">
                        <p class="text-slate-400">No orders found.</p>
                        <Link
                            :href="route('client.orders.create')"
                            class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            Create your first order
                        </Link>
                    </div>
                </template>

                <template #cell-order="{ row }">
                    <div>
                        <Link
                            :href="route('client.orders.show', row.id)"
                            class="font-medium text-indigo-600 hover:text-indigo-900"
                        >
                            {{ row.order_number }}
                        </Link>
                        <div class="text-xs text-slate-500 truncate max-w-xs">
                            {{ row.title }}
                        </div>
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

                <template #cell-priority="{ row }">
                    <span
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                        :class="getPriorityColor(row.priority)"
                    >
                        {{ row.priority_label }}
                    </span>
                </template>

                <template #cell-created="{ row }">
                    <div class="text-sm text-slate-900">
                        {{ formatDate(row.created_at) }}
                    </div>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions
                        :actions="[
                            { type: 'view', href: route('client.orders.show', row.id) },
                        ]"
                    />
                </template>
            </DataTable>

            <!-- Pagination -->
            <PaginationControls :meta="paginationMeta" :links="paginationLinks" label="orders" />
        </div>
    </AppLayout>
</template>
