<script setup>
import { reactive, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import DataTable from "@/Components/DataTable.vue";
import RowActions from "@/Components/RowActions.vue";
import { useDateFormat } from "@/Composables/useDateFormat";
import Button from "@/Components/Button.vue";

const { formatDate } = useDateFormat();

const props = defineProps({
    userGroups: Array,
});

const selectedIds = ref([]);

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

const columnsFor = (role) => {
    const cols = [
        { key: "user", label: "User" },
        { key: "status", label: "Status" },
    ];
    if (role === "Client") {
        cols.push({ key: "client", label: "Client Account" });
    }
    cols.push({ key: "actions", label: "", headerClass: "text-right" });
    return cols;
};

const roleAccentClass = {
    Admin: "border-indigo-200 bg-indigo-50 text-indigo-700",
    Manager: "border-purple-200 bg-purple-50 text-purple-700",
    Designer: "border-blue-200 bg-blue-50 text-blue-700",
    Sales: "border-green-200 bg-green-50 text-green-700",
    Client: "border-amber-200 bg-amber-50 text-amber-700",
};
</script>

<template>
    <AppLayout>
        <Head title="Users" />
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Users</h2>
                    <p class="text-sm text-slate-500">
                        Invite teammates, assign roles, and manage access.
                    </p>
                </div>
                <Button :href="route('users.create')" variant="primary">
                    Invite User
                </Button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Bulk action bar -->
            <div
                v-if="selectedIds.length"
                class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700 shadow-inner shadow-indigo-100"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium">{{ selectedIds.length }} user(s) selected</span>
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

            <!-- Per-role tables -->
            <div
                v-for="group in userGroups"
                :key="group.role"
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70 overflow-hidden"
            >
                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                        :class="roleAccentClass[group.role] ?? 'border-slate-200 bg-slate-50 text-slate-700'"
                    >
                        {{ group.role }}
                    </span>
                    <span class="text-sm text-slate-500">
                        {{ group.users.length }} {{ group.users.length === 1 ? "user" : "users" }}
                    </span>
                </div>

                <div class="p-6">
                    <DataTable
                        :columns="columnsFor(group.role)"
                        :rows="group.users"
                        selectable
                        v-model:selected-ids="selectedIds"
                        empty-text="No users."
                    >
                        <template #cell-user="{ row }">
                            <div class="font-medium text-slate-900">{{ row.name }}</div>
                            <div class="text-sm text-slate-500">{{ row.email }}</div>
                            <p class="text-xs text-slate-400">Invited {{ formatDate(row.created_at) }}</p>
                        </template>
                        <template #cell-status="{ row }">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                    row.is_active
                                        ? 'bg-emerald-500/20 text-emerald-300'
                                        : 'bg-amber-100 text-amber-700',
                                ]"
                            >
                                {{ row.is_active ? "Active" : "Inactive" }}
                            </span>
                        </template>
                        <template #cell-client="{ row }">
                            <span class="text-sm text-slate-900">{{ row.client?.name ?? "—" }}</span>
                        </template>
                        <template #cell-actions="{ row }">
                            <RowActions
                                :actions="[
                                    { type: 'edit', href: route('users.edit', row.id) },
                                    { type: 'delete', action: () => openDeleteModal(row.id) },
                                ]"
                            />
                        </template>
                    </DataTable>
                </div>
            </div>

            <p v-if="!userGroups?.length" class="text-center text-sm text-slate-500 py-12">
                No users yet.
            </p>
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
