<script setup>
import { computed, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    roles: Array,
    clients: Array,
});

const form = useForm({
    name: "",
    email: "",
    phone: "",
    role: props.roles?.[0] ?? "Admin",
    is_active: true,
    client_id: "",
});

const requiresClient = computed(() => form.role === "Client");

watch(
    () => form.role,
    (role) => {
        if (role !== "Client") {
            form.client_id = "";
        }
    }
);

const submit = () => {
    form.post(route("users.store"));
};

const baseInputClasses =
    "mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400";
</script>

<template>
    <AppLayout>
        <Head :title="`New User`" />
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Invite User
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Send an invitation email so the user can set their password.
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
                                for="name"
                                class="block text-sm font-medium text-slate-700"
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

                        <div>
                            <label
                                for="email"
                                class="block text-sm font-medium text-slate-700"
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

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    for="phone"
                                    class="block text-sm font-medium text-slate-700"
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

                            <div>
                                <label
                                    for="role"
                                    class="block text-sm font-medium text-slate-700"
                                    >Role</label
                                >
                                <select
                                    v-model="form.role"
                                    id="role"
                                    :class="baseInputClasses"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ role }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.role"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.role }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                for="status"
                                class="block text-sm font-medium text-slate-700"
                                >Status</label
                            >
                            <select
                                v-model="form.is_active"
                                id="status"
                                :class="baseInputClasses"
                            >
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                            <p
                                v-if="form.errors.status"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.is_active }}
                            </p>
                        </div>

                        <div v-if="requiresClient">
                            <label
                                for="client_id"
                                class="block text-sm font-medium text-slate-700"
                                >Linked client</label
                            >
                            <select
                                v-model="form.client_id"
                                id="client_id"
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

                        <div class="flex items-center justify-end gap-3">
                            <Button :href="route('users.index')">
                                Cancel
                            </Button>
                            <Button
                                as="button"
                                html-type="submit"
                                variant="primary"
                                :disabled="form.processing"
                            >
                                Send invite
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
