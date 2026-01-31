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
    status: props.client?.status ?? "active",
});

const submit = () => {
    form.put(route("clients.update", props.client.id));
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

                        <div class="flex items-center justify-end gap-3">
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
