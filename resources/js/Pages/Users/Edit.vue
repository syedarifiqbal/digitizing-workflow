<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    clients: Array,
});

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    role: props.user?.role ?? props.roles?.[0] ?? 'Admin',
    status: props.user?.status ?? 'active',
    client_id: props.user?.client_id ?? '',
});

const requiresClient = computed(() => form.role === 'Client');

watch(
    () => form.role,
    (role) => {
        if (role !== 'Client') {
            form.client_id = '';
        }
    }
);

const submit = () => {
    form.put(route('users.update', props.user.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Edit User</h2>
                <p class="mt-1 text-sm text-gray-600">Update profile details, role, and status.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input
                                    v-model="form.name"
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input
                                    v-model="form.email"
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        id="phone"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                                </div>

                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                    <select
                                        v-model="form.role"
                                        id="role"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role">
                                            {{ role }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                                </div>
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    v-model="form.status"
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>

                            <div v-if="requiresClient">
                                <label for="client_id" class="block text-sm font-medium text-gray-700">Linked client</label>
                                <select
                                    v-model="form.client_id"
                                    id="client_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }} ({{ client.email ?? 'no email' }})
                                    </option>
                                </select>
                                <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.client_id }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('users.index')" class="text-sm text-gray-500 hover:text-gray-700">
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
