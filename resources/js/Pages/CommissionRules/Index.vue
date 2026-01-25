<script setup>
import { reactive, ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";

const props = defineProps({
    rules: Array,
    availableUsers: Array,
    existingUserIds: Array,
    roleType: String,
    roleLabel: String,
    typeOptions: Array,
    currency: String,
});

const showModal = ref(false);
const editingRule = ref(null);
const saving = ref(false);

const form = reactive({
    user_id: "",
    role_type: props.roleType,
    type: "fixed",
    fixed_amount: "",
    percent_rate: "",
    currency: props.currency || "USD",
    is_active: true,
});

const availableUsersFiltered = computed(() => {
    if (editingRule.value) {
        return props.availableUsers;
    }
    return props.availableUsers.filter(
        (u) => !props.existingUserIds.includes(u.id)
    );
});

const showFixed = computed(
    () => form.type === "fixed" || form.type === "hybrid"
);
const showPercent = computed(
    () => form.type === "percent" || form.type === "hybrid"
);

const userLabel = computed(() => {
    return props.roleType === "sales" ? "Sales Person" : "Designer";
});

const openCreateModal = () => {
    editingRule.value = null;
    form.user_id = "";
    form.role_type = props.roleType;
    form.type = "fixed";
    form.fixed_amount = "";
    form.percent_rate = "";
    form.currency = props.currency || "USD";
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (rule) => {
    editingRule.value = rule;
    form.user_id = rule.user_id;
    form.role_type = rule.role_type;
    form.type = rule.type;
    form.fixed_amount = rule.fixed_amount ?? "";
    form.percent_rate = rule.percent_rate ?? "";
    form.currency = rule.currency;
    form.is_active = rule.is_active;
    showModal.value = true;
};

const saveRule = () => {
    saving.value = true;

    const data = {
        type: form.type,
        fixed_amount: showFixed.value ? form.fixed_amount || null : null,
        percent_rate: showPercent.value ? form.percent_rate || null : null,
        currency: form.currency,
        is_active: form.is_active,
    };

    if (editingRule.value) {
        router.put(
            route("commission-rules.update", editingRule.value.id),
            data,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showModal.value = false;
                },
                onFinish: () => {
                    saving.value = false;
                },
            }
        );
    } else {
        router.post(
            route("commission-rules.store"),
            {
                ...data,
                user_id: form.user_id,
                role_type: form.role_type,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showModal.value = false;
                },
                onFinish: () => {
                    saving.value = false;
                },
            }
        );
    }
};

const deleteModal = reactive({ show: false, id: null, message: "" });

const openDeleteModal = (rule) => {
    deleteModal.id = rule.id;
    deleteModal.message = `Delete ${props.roleLabel.toLowerCase()} rule for ${
        rule.user.name
    }?`;
    deleteModal.show = true;
};

const confirmDelete = () => {
    router.delete(route("commission-rules.destroy", deleteModal.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
        },
    });
};

const formatType = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const formatAmount = (rule) => {
    const parts = [];
    if (
        (rule.type === "fixed" || rule.type === "hybrid") &&
        rule.fixed_amount
    ) {
        parts.push(
            `${rule.currency} ${parseFloat(rule.fixed_amount).toFixed(2)}`
        );
    }
    if (
        (rule.type === "percent" || rule.type === "hybrid") &&
        rule.percent_rate
    ) {
        parts.push(`${parseFloat(rule.percent_rate).toFixed(1)}%`);
    }
    return parts.join(" + ") || "—";
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        {{ roleLabel }} Rules
                    </h2>
                    <p class="text-sm text-slate-500">
                        Configure {{ roleLabel.toLowerCase() }} rates for each
                        {{ userLabel.toLowerCase() }}.
                    </p>
                </div>
                <button
                    v-if="availableUsersFiltered.length"
                    type="button"
                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                    @click="openCreateModal"
                >
                    Add Rule
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-4xl">
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div v-if="rules.length === 0" class="px-6 py-12 text-center">
                    <svg
                        class="mx-auto h-12 w-12 text-slate-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <p class="mt-2 text-sm text-slate-500">
                        No {{ roleLabel.toLowerCase() }} rules configured yet.
                    </p>
                    <button
                        v-if="availableUsersFiltered.length"
                        type="button"
                        class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="openCreateModal"
                    >
                        Add First Rule
                    </button>
                </div>

                <table v-else class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                {{ userLabel }}
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Type
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Rate
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="rule in rules"
                            :key="rule.id"
                            class="transition hover:bg-slate-50"
                        >
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">
                                    {{ rule.user.name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ rule.user.email }}
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                                >
                                    {{ formatType(rule.type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-900">
                                {{ formatAmount(rule) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                        rule.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-500',
                                    ]"
                                >
                                    {{ rule.is_active ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        @click="openEditModal(rule)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-red-600 hover:text-red-800"
                                        @click="openDeleteModal(rule)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-gray-500/75"
                    @click="showModal = false"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{
                            editingRule
                                ? `Edit ${roleLabel} Rule`
                                : `Add ${roleLabel} Rule`
                        }}
                    </h3>

                    <div class="mt-4 space-y-4">
                        <div v-if="!editingRule">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >{{ userLabel }}</label
                            >
                            <select
                                v-model="form.user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select a {{ userLabel.toLowerCase() }}
                                </option>
                                <option
                                    v-for="user in availableUsersFiltered"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                        </div>

                        <div v-else>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >{{ userLabel }}</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ editingRule.user.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Commission Type</label
                            >
                            <div class="mt-2 flex gap-3">
                                <label
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.type"
                                        :value="option.value"
                                        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">{{
                                        option.label
                                    }}</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="showFixed">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Fixed Amount</label
                            >
                            <div class="mt-1 flex items-center gap-2">
                                <input
                                    v-model="form.fixed_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <span
                                    class="text-sm text-gray-500 whitespace-nowrap"
                                    >{{ form.currency }}</span
                                >
                            </div>
                        </div>

                        <div v-if="showPercent">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Percent Rate</label
                            >
                            <div class="mt-1 flex items-center gap-2">
                                <input
                                    v-model="form.percent_rate"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    placeholder="0.0"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Percentage of order price amount.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_active" class="text-sm text-gray-700"
                                >Active</label
                            >
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="showModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="
                                saving || (!editingRule && !form.user_id)
                            "
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            @click="saveRule"
                        >
                            {{
                                saving
                                    ? "Saving..."
                                    : editingRule
                                    ? "Update"
                                    : "Create"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="deleteModal.show"
            :message="deleteModal.message"
            confirm-label="Delete"
            @close="deleteModal.show = false"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
