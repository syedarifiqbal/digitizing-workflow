<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    client: Object,
});

const form = useForm({
    name: props.client?.name ?? '',
    email: props.client?.email ?? '',
    phone: props.client?.phone ?? '',
    company: props.client?.company ?? '',
    notes: props.client?.notes ?? '',
    status: props.client?.status ?? 'active',
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Edit Client</h2>
                <p class="mt-1 text-sm text-gray-600">Update client information and contact preferences.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="name">Name</label>
                                <input
                                    v-model="form.name"
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="email">Email</label>
                                    <input
                                        v-model="form.email"
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="phone">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        id="phone"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="company">Company</label>
                                <input
                                    v-model="form.company"
                                    id="company"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.company }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="notes">Notes</label>
                                <textarea
                                    v-model="form.notes"
                                    id="notes"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.notes }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
                                <select
                                    v-model="form.status"
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <Link :href="route('clients.show', props.client.id)" class="text-sm text-gray-500 hover:text-gray-700">
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
