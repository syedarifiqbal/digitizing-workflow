<script setup>
import { computed, ref } from "vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import { TrashIcon } from "@heroicons/vue/24/outline";
import AppLayout from "@/Layouts/AppLayout.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    order: Object,
    files: Array,
    clients: Array,
    priorityOptions: Array,
    typeOptions: Array,
    maxUploadMb: {
        type: Number,
        default: 25,
    },
});

const form = useForm({
    client_id: props.order?.client_id ?? "",
    title: props.order?.title ?? "",
    instructions: props.order?.instructions ?? "",
    priority: props.order?.priority ?? "normal",
    due_at: props.order?.due_at ?? "",
    price_amount: props.order?.price_amount ?? "",
    currency: props.order?.currency ?? "USD",
    source: props.order?.source ?? "",
    attachments: [],
});

const handleFiles = (event) => {
    form.attachments = Array.from(event.target.files ?? []);
};

const attachmentError = computed(() => {
    const entries = Object.entries(form.errors ?? {});
    const match = entries.find(([key]) => key.startsWith("attachments"));
    return match ? match[1] : null;
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: "PUT",
    })).post(route("orders.update", props.order.id), {
        forceFormData: true,
    });
};

const selectedTypeLabel = computed(() => {
    const option = props.typeOptions?.find((opt) => opt.value === props.order?.type);
    if (option?.label) {
        return option.label;
    }
    return (props.order?.type ?? "").split("_").join(" ");
});

const formatSize = (size) => {
    if (!size) return "0 KB";
    const kb = size / 1024;
    if (kb < 1024) return `${kb.toFixed(1)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

const deleteModal = ref({ show: false, fileId: null, fileName: "" });

const openDeleteModal = (file) => {
    deleteModal.value = {
        show: true,
        fileId: file.id,
        fileName: file.original_name,
    };
};

const closeDeleteModal = () => {
    deleteModal.value = { show: false, fileId: null, fileName: "" };
};

const confirmDeleteFile = () => {
    if (!deleteModal.value.fileId) return;

    router.delete(route("orders.files.destroy", deleteModal.value.fileId), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const baseInputClasses =
    "mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400";
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Edit Order {{ order.order_number }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Update order details. Type cannot be changed after creation.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-8">
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="client"
                                >Client</label
                            >
                            <select
                                v-model="form.client_id"
                                id="client"
                                :class="baseInputClasses"
                            >
                                <option value="">Select client</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }} ({{
                                        client.email ?? "no email"
                                    }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.client_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.client_id }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="title"
                                >Order title</label
                            >
                            <input
                                v-model="form.title"
                                id="title"
                                type="text"
                                :class="baseInputClasses"
                            />
                            <p
                                v-if="form.errors.title"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="instructions"
                                >Instructions</label
                            >
                            <textarea
                                v-model="form.instructions"
                                id="instructions"
                                rows="5"
                                :class="baseInputClasses"
                            ></textarea>
                            <p
                                v-if="form.errors.instructions"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.instructions }}
                            </p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="priority"
                                    >Priority</label
                                >
                                <select
                                    v-model="form.priority"
                                    id="priority"
                                    :class="baseInputClasses"
                                >
                                    <option
                                        v-for="option in priorityOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.priority"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.priority }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="due_at"
                                    >Due date</label
                                >
                                <DatePicker
                                    v-model="form.due_at"
                                    id="due_at"
                                    placeholder="Select due date"
                                />
                                <p
                                    v-if="form.errors.due_at"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.due_at }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Order type</label
                            >
                            <p class="mt-1 text-sm font-semibold text-slate-900 capitalize">
                                {{ selectedTypeLabel }}
                            </p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="price_amount"
                                >
                                    Quoted price
                                </label>
                                <input
                                    v-model="form.price_amount"
                                    id="price_amount"
                                    type="number"
                                    step="0.01"
                                    :class="baseInputClasses"
                                />
                                <p
                                    v-if="form.errors.price_amount"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.price_amount }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="currency"
                                >
                                    Currency
                                </label>
                                <input
                                    v-model="form.currency"
                                    id="currency"
                                    type="text"
                                    maxlength="3"
                                    :class="[baseInputClasses, 'uppercase']"
                                />
                                <p
                                    v-if="form.errors.currency"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.currency }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="source"
                            >
                                Source
                            </label>
                            <input
                                v-model="form.source"
                                id="source"
                                type="text"
                                placeholder="Website, phone, API, etc."
                                :class="baseInputClasses"
                            />
                            <p
                                v-if="form.errors.source"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.source }}
                            </p>
                        </div>

                        <!-- Existing Files -->
                        <div v-if="files?.length">
                            <label class="block text-sm font-medium text-slate-700">
                                Current files
                            </label>
                            <div class="mt-2 divide-y divide-slate-200 rounded-xl border border-slate-200">
                                <div
                                    v-for="file in files"
                                    :key="file.id"
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ formatSize(file.size) }}
                                            <span class="ml-2 uppercase">{{ file.type }}</span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete file"
                                        @click="openDeleteModal(file)"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Upload New Files -->
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="attachments"
                            >
                                Add more files
                            </label>
                            <input
                                id="attachments"
                                type="file"
                                multiple
                                @change="handleFiles"
                                class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border file:border-slate-200 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-50"
                            />
                            <p class="mt-1 text-xs text-slate-500">
                                Max {{ maxUploadMb }}MB per file
                            </p>
                            <p
                                v-if="attachmentError"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ attachmentError }}
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <Link
                                :href="route('orders.show', order.id)"
                                class="text-sm text-slate-500 hover:text-slate-900"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110 disabled:opacity-60"
                            >
                                Update order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="deleteModal.show"
            :message="`Delete file '${deleteModal.fileName}'?`"
            confirm-label="Delete"
            @close="closeDeleteModal"
            @confirm="confirmDeleteFile"
        />
    </AppLayout>
</template>
