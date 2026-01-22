<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    notes: '',
    status: 'active',
});

const submit = () => {
    form.post(route('clients.store'));
};

const baseInputClasses =
    "mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-400";
</script>

<template>
    <AppLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">New Client</h2>
                <p class="mt-1 text-sm text-slate-500">Add a customer to the tenant workspace.</p>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-8">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="name">Name</label>
                                <input
                                    v-model="form.name"
                                    id="name"
                                    type="text"
                                    :class="baseInputClasses"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
                                    <input
                                        v-model="form.email"
                                        id="email"
                                        type="email"
                                        :class="baseInputClasses"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700" for="phone">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        id="phone"
                                        type="text"
                                        :class="baseInputClasses"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium.text-slate-700" for="company">Company</label>
                                <input
                                    v-model="form.company"
                                    id="company"
                                    type="text"
                                    :class="baseInputClasses"
                                />
                                <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.company }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="notes">Notes</label>
                                <textarea
                                    v-model="form.notes"
                                    id="notes"
                                    rows="4"
                                    :class="baseInputClasses"
                                ></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.notes }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="status">Status</label>
                                <select
                                    v-model="form.status"
                                    id="status"
                                    :class="baseInputClasses"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <Link
                                    :href="route('clients.index')"
                                    class="text-sm font-medium text-slate-500 hover:text-slate-900"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110 disabled:opacity-60"
                                >
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
