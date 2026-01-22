<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { PencilSquareIcon, TrashIcon } from "@heroicons/vue/24/outline";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import DataTable from "@/Components/DataTable.vue";
import PaginationControls from "@/Components/PaginationControls.vue";

const props = defineProps({
    filters: Object,
    users: Object,
    roles: Array,
});

const filters = reactive({
    search: props.filters?.search ?? "",
    role: props.filters?.role ?? "all",
    status: props.filters?.status ?? "all",
});

const roleOptions = computed(() => [
    { label: "All roles", value: "all" },
    ...(props.roles || []).map((role) => ({ label: role, value: role })),
]);

const statusOptions = [
    { label: "All statuses", value: "all" },
    { label: "Active", value: "active" },
    { label: "Inactive", value: "inactive" },
];

const users = computed(() => props.users?.data ?? props.users ?? []);
const paginationLinks = computed(
    () => props.users?.links ?? props.users?.data?.links ?? []
);
const paginationMeta = computed(
    () => props.users?.meta ?? props.users?.data?.meta ?? null
);

const selectedIds = ref([]);

watch(
    () => props.users,
    () => {
        selectedIds.value = [];
    }
);

const submitFilters = () => {
    router.get(route("users.index"), filters, {
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
            ? `Delete ${modal.ids.length} selected user(s)?`
            : "Delete this user?";
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
        router.delete(route("users.bulk-destroy"), {
            ...baseOptions,
            data: { ids: [...modal.ids] },
        });
    } else {
        router.delete(route("users.destroy", modal.ids[0]), baseOptions);
    }
};

const clearSelection = () => {
    selectedIds.value = [];
};

const userColumns = [
    { key: "user", label: "User" },
    { key: "role", label: "Role" },
    { key: "status", label: "Status" },
    { key: "client", label: "Client" },
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
                    <h2 class="text-2xl font-semibold text-slate-900">Users</h2>
                    <p class="text-sm text-slate-500">
                        Invite teammates, assign roles, and manage access.
                    </p>
                </div>
                <Link
                    :href="route('users.create')"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    Invite User
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
                        class="grid gap-5 md:grid-cols-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-300"
                                for="search"
                                >Search</label
                            >
                            <input
                                v-model="filters.search"
                                id="search"
                                type="text"
                                placeholder="Search name or email"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder-slate-400 focus:border-indigo-400 focus:ring-indigo-400"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-300"
                                for="role"
                                >Role</label
                            >
                            <select
                                v-model="filters.role"
                                id="role"
                                class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                            >
                                <option
                                    v-for="option in roleOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-300"
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
                                >{{ selectedIds.length }} user(s) selected</span
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
                        :columns="userColumns"
                        :rows="users"
                        selectable
                        v-model:selected-ids="selectedIds"
                        empty-text="No users found."
                    >
                        <template #cell-user="{ row }">
                            <div class="font-medium text-slate-900">
                                {{ row.name }}
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ row.email }}
                            </div>
                            <p class="text-xs text-slate-500">
                                Invited {{ row.created_at }}
                            </p>
                        </template>
                        <template #cell-role="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.role ?? "—"
                            }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5 capitalize',
                                    row.status === 'active'
                                        ? 'bg-emerald-500/20 text-emerald-300'
                                        : 'bg-amber-100 text-amber-700',
                                ]"
                            >
                                {{ row.status }}
                            </span>
                        </template>
                        <template #cell-client="{ row }">
                            <span class="text-sm text-slate-900">{{
                                row.client?.name ?? "—"
                            }}</span>
                        </template>
                        <template #cell-actions="{ row }">
                            <div
                                class="text-right text-sm font-medium space-x-1"
                            >
                                <Link
                                    :href="route('users.edit', row.id)"
                                    class="inline-flex items-center rounded-full p-2 text-slate-400 hover:text-indigo-300"
                                    title="Edit"
                                >
                                    <span class="sr-only">Edit</span>
                                    <PencilSquareIcon class="h-5 w-5" />
                                </Link>
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
                        label="users"
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
