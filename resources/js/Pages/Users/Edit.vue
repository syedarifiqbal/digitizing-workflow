<script setup>
import { computed, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    user: Object,
    roles: Array,
    clients: Array,
});

const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    phone: props.user?.phone ?? "",
    role: props.user?.role ?? props.roles?.[0] ?? "Admin",
    is_active: props.user?.is_active ? "1" : "0",
    client_id: props.user?.client_id ?? "",
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
    form.put(route("users.update", props.user.id));
};
</script>

<template>
    <AppLayout>
        <Head :title="`Edit User - ${user.name}`" />
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Edit User</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Update profile details, role, and status.
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg">
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
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <p
                                    v-if="form.errors.is_active"
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
                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    Save changes
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
