<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    clients: Array,
    priorityOptions: Array,
    typeOptions: Array,
    currency: String,
    defaultType: {
        type: String,
        default: "digitizing",
    },
    isQuote: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    client_id: "",
    title: "",
    instructions: "",
    type: props.defaultType ?? props.typeOptions?.[0]?.value ?? "digitizing",
    priority: props.priorityOptions?.[0]?.value ?? "normal",
    due_at: "",
    price_amount: "",
    currency: props.currency ?? "USD",
    source: "",
    attachments: [],
    quote: props.isQuote ? 1 : 0,
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
    form.post(route("orders.store"), {
        forceFormData: true,
    });
};

const selectedTypeLabel = computed(() => {
    const option = props.typeOptions?.find((opt) => opt.value === form.type);
    if (option?.label) {
        return option.label;
    }
    return (form.type ?? "").split("_").join(" ");
});

const baseInputClasses =
    "mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400";
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    New {{ selectedTypeLabel }}
                    {{ props.isQuote ? "Quote" : "Order" }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{
                        props.isQuote
                            ? "Create a quote for the selected service type."
                            : "Capture client intake details and upload artwork."
                    }}
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-8">
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="border-b border-slate-100 px-6 py-4">
                    <p class="text-sm text-slate-500">
                        {{
                            props.isQuote
                                ? "Share an estimate with a quick turnaround summary. Artwork upload is optional."
                                : "Include enough detail so designers know exactly what to deliver."
                        }}
                    </p>
                </div>
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
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ selectedTypeLabel }}
                            </p>
                            <input
                                type="hidden"
                                name="type"
                                :value="form.type"
                            />
                            <input
                                type="hidden"
                                name="quote"
                                :value="form.quote"
                            />
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

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="attachments"
                            >
                                Input artwork
                            </label>
                            <input
                                id="attachments"
                                type="file"
                                multiple
                                @change="handleFiles"
                                class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border file:border-slate-200 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-50"
                            />
                            <p
                                v-if="attachmentError"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ attachmentError }}
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <Link
                                :href="route('orders.index')"
                                class="text-sm text-slate-500 hover:text-slate-900"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110 disabled:opacity-60"
                            >
                                Create order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
