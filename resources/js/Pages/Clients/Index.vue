<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
    EyeIcon,
    PencilSquareIcon,
    ArrowsRightLeftIcon,
    TrashIcon,
} from "@heroicons/vue/24/outline";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";

const props = defineProps({
    filters: Object,
    clients: Object,
});

const filters = reactive({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
});

const statusOptions = [
    { label: "All", value: "all" },
    { label: "Active", value: "active" },
    { label: "Inactive", value: "inactive" },
];

const clients = computed(() => props.clients?.data?.data ?? props.clients?.data ?? []);
const paginationLinks = computed(
    () => props.clients?.links ?? props.clients?.data?.links ?? []
);
const paginationMeta = computed(
    () => props.clients?.meta ?? props.clients?.data?.meta ?? null
);

const selectedIds = ref([]);

watch(
    () => props.clients,
    () => {
        selectedIds.value = [];
    }
);

const submitFilters = () => {
    router.get(route("clients.index"), filters, {
        preserveState: true,
        replace: true,
    });
};

const modal = reactive({
    show: false,
    message: "",
    ids: [],
    bulk: false,
});

const openDeleteModal = (ids, bulk = false) => {
    modal.ids = Array.isArray(ids) ? [...ids] : [ids];
    modal.bulk = bulk;
    modal.message = bulk
        ? `Delete ${modal.ids.length} selected client(s)?`
        : "Delete this client?";
    modal.show = true;
};

const closeModal = () => {
    modal.show = false;
    modal.ids = [];
    modal.bulk = false;
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
            closeModal();
            selectedIds.value = [];
        },
        onFinish: () => {
            modal.show = false;
        },
    };

    if (modal.bulk || modal.ids.length > 1) {
        router.delete(route("clients.bulk-destroy"), {
            ...baseOptions,
            data: { ids: [...modal.ids] },
        });
    } else {
        router.delete(route("clients.destroy", modal.ids[0]), baseOptions);
    }
};

const clearSelections = () => {
    selectedIds.value = [];
};

const toggleStatus = (client) => {
    router.patch(
        route("clients.status", client.id),
        {},
        { preserveScroll: true }
    );
};

const clientColumns = [
    { key: "name", label: "Client" },
    { key: "contact", label: "Contact" },
    { key: "company", label: "Company" },
    { key: "status", label: "Status" },
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
                        Clients
                    </h2>
                    <p class="text-sm text-slate-500">
                        Manage customer records and track their history.
                    </p>
                </div>
                <Link
                    :href="route('clients.create')"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    New Client
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="p-6">
                    <form
                        @submit.prevent="submitFilters"
                        class="grid gap-5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="search"
                                >Search</label
                            >
                            <input
                                v-model.trim="filters.search"
                                type="text"
                                id="search"
                                placeholder="Search name, email, company"
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
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                            >
                                Apply filters
                            </button>
                        </div>
                    </form>
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
                                >{{ selectedIds.length }} client(s)
                                selected</span
                            >
                            <button
                                type="button"
                                class="text-indigo-600 underline"
                                @click="clearSelections"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                    class="inline-flex items-center rounded-full bg-red-500/10 px-3 py-1 text-xs font-semibold text-red-600"
                                @click="openDeleteModal(selectedIds, true)"
                            >
                                Delete selected
                            </button>
                        </div>
                    </div>

                    <DataTable
                        :columns="clientColumns"
                        :rows="clients"
                        selectable
                        v-model:selected-ids="selectedIds"
                        empty-text="No clients found."
                    >
                        <template #cell-name="{ row }">
                            <Link
                                :href="route('clients.show', row.id)"
                                class="font-medium text-slate-900 hover:text-indigo-600"
                            >
                                {{ row.name }}
                            </Link>
                            <p class="text-sm text-slate-500">
                                Created {{ row.created_at }}
                            </p>
                        </template>
                        <template #cell-contact="{ row }">
                            <div class="text-sm text-slate-900">
                                {{ row.email ?? "—" }}
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ row.phone ?? "" }}
                            </div>
                        </template>
                        <template #cell-company="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.company ?? "—"
                            }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize',
                                    row.status === 'active'
                                        ? 'bg-emerald-500/20 text-emerald-300'
                                        : 'bg-amber-500/20 text-amber-300',
                                ]"
                            >
                                {{ row.status }}
                            </span>
                        </template>
                        <template #cell-actions="{ row }">
                            <div
                                class="text-right text-sm font-medium space-x-1"
                            >
                                <Link
                                    :href="route('clients.show', row.id)"
                                    class="inline-flex items-center rounded-full p-2 text-slate-500 hover:text-slate-900"
                                    title="View"
                                >
                                    <span class="sr-only">View</span>
                                    <EyeIcon class="h-5 w-5" />
                                </Link>
                                <Link
                                    :href="route('clients.edit', row.id)"
                                    class="inline-flex items-center rounded-full p-2 text-slate-400 hover:text-indigo-600"
                                    title="Edit"
                                >
                                    <span class="sr-only">Edit</span>
                                    <PencilSquareIcon class="h-5 w-5" />
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-full p-2 text-slate-400 hover:text-amber-300"
                                    @click="toggleStatus(row)"
                                    :title="
                                        row.status === 'active'
                                            ? 'Mark inactive'
                                            : 'Mark active'
                                    "
                                >
                                    <span class="sr-only">Toggle status</span>
                                    <ArrowsRightLeftIcon class="h-5 w-5" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-full p-2 text-slate-400 hover:text-red-400"
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
                        label="clients"
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
