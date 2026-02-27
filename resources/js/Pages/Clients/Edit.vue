<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    client: Object,
});

const form = useForm({
    name: props.client?.name ?? "",
    email: props.client?.email ?? "",
    phone: props.client?.phone ?? "",
    company: props.client?.company ?? "",
    notes: props.client?.notes ?? "",
    status: props.client?.is_active ? "active" : "inactive",
    permanent_instructions: {
        special_offer_note: props.client?.permanent_instructions?.special_offer_note ?? "",
        price_instructions: props.client?.permanent_instructions?.price_instructions ?? "",
        for_digitizer: props.client?.permanent_instructions?.for_digitizer ?? "",
        appreciation_bonus: props.client?.permanent_instructions?.appreciation_bonus ?? "",
        custom: props.client?.permanent_instructions?.custom ?? [],
    },
    emails: (props.client?.emails ?? []).map(e => ({ email: e.email, label: e.label ?? "" })),
});

const addCustomInstruction = () => {
    form.permanent_instructions.custom.push({ key: "", value: "" });
};

const removeCustomInstruction = (index) => {
    form.permanent_instructions.custom.splice(index, 1);
};

const addEmail = () => {
    form.emails.push({ email: "", label: "" });
};

const removeEmail = (index) => {
    form.emails.splice(index, 1);
};

const submit = () => {
    form.transform(data => ({
        ...data,
        is_active: data.status === "active",
    })).put(route("clients.update", props.client.id));
};

const baseInputClasses =
    "mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400";
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Client - ${client.name}`" />

        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Edit Client
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Update client information and contact preferences.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-8">
            <div
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
            >
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="name"
                                >Name</label
                            >
                            <input
                                v-model="form.name"
                                id="name"
                                type="text"
                                :class="baseInputClasses"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="email"
                                    >Email</label
                                >
                                <input
                                    v-model="form.email"
                                    id="email"
                                    type="email"
                                    :class="baseInputClasses"
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                    for="phone"
                                    >Phone</label
                                >
                                <input
                                    v-model="form.phone"
                                    id="phone"
                                    type="text"
                                    :class="baseInputClasses"
                                />
                                <p
                                    v-if="form.errors.phone"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.phone }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="company"
                                >Company</label
                            >
                            <input
                                v-model="form.company"
                                id="company"
                                type="text"
                                :class="baseInputClasses"
                            />
                            <p
                                v-if="form.errors.company"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.company }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="notes"
                                >Notes</label
                            >
                            <textarea
                                v-model="form.notes"
                                id="notes"
                                rows="4"
                                :class="baseInputClasses"
                            ></textarea>
                            <p
                                v-if="form.errors.notes"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.notes }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                for="status"
                                >Status</label
                            >
                            <select
                                v-model="form.status"
                                id="status"
                                :class="baseInputClasses"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <p
                                v-if="form.errors.status"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Additional Email Addresses -->
                        <div class="border-t border-slate-200 pt-6">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Additional Email Addresses</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Extra emails for delivery notifications and invoices.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="addEmail"
                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    + Add Email
                                </button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(entry, index) in form.emails"
                                    :key="index"
                                    class="flex items-start gap-2"
                                >
                                    <div class="flex-1">
                                        <input
                                            v-model="entry.email"
                                            type="email"
                                            placeholder="email@example.com"
                                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                        />
                                        <p v-if="form.errors[`emails.${index}.email`]" class="mt-1 text-xs text-red-600">
                                            {{ form.errors[`emails.${index}.email`] }}
                                        </p>
                                    </div>
                                    <input
                                        v-model="entry.label"
                                        type="text"
                                        placeholder="Label (e.g. Billing)"
                                        class="w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    />
                                    <button
                                        type="button"
                                        @click="removeEmail(index)"
                                        class="mt-0.5 text-red-500 hover:text-red-700 text-sm font-medium px-1"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Permanent Instructions -->
                        <div class="border-t border-slate-200 pt-6">
                            <div class="mb-3">
                                <h4 class="text-sm font-semibold text-slate-800">Permanent Instructions</h4>
                                <p class="text-xs text-slate-500 mt-0.5">These notes are shown on every order page for this client.</p>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Special Offer / Note</label>
                                    <textarea
                                        v-model="form.permanent_instructions.special_offer_note"
                                        rows="2"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Price Instructions</label>
                                    <textarea
                                        v-model="form.permanent_instructions.price_instructions"
                                        rows="2"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">For Digitizer</label>
                                    <textarea
                                        v-model="form.permanent_instructions.for_digitizer"
                                        rows="2"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Appreciation Bonus ($)</label>
                                    <input
                                        v-model="form.permanent_instructions.appreciation_bonus"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    />
                                </div>

                                <!-- Custom notes -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-medium text-slate-700">Custom Notes</label>
                                        <button
                                            type="button"
                                            @click="addCustomInstruction"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            + Add Custom Note
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(custom, index) in form.permanent_instructions.custom"
                                            :key="index"
                                            class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3"
                                        >
                                            <div class="flex-1 space-y-2">
                                                <input
                                                    v-model="custom.key"
                                                    type="text"
                                                    placeholder="Label"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                                />
                                                <textarea
                                                    v-model="custom.value"
                                                    rows="2"
                                                    placeholder="Value"
                                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                                                ></textarea>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeCustomInstruction(index)"
                                                class="text-red-500 hover:text-red-700 text-sm px-1"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
                            <Button :href="route('clients.show', props.client.id)">
                                Cancel
                            </Button>
                            <Button
                                as="button"
                                html-type="submit"
                                variant="primary"
                                :disabled="form.processing"
                            >
                                Update
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
