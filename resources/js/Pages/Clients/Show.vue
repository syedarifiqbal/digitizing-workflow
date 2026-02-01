<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/Button.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { ref } from 'vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    client: Object,
    orders: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);

const destroyClient = () => {
    showModal.value = true;
};

const confirmDelete = () => {
    router.delete(route('clients.destroy', props.client.id), {
        onFinish: () => {
            showModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Client - ${client.name}`" />

        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-800">{{ client.name }}</h2>
                    <p class="text-sm text-slate-600">{{ client.company || 'No company listed' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button :href="route('clients.edit', client.id)">
                        Edit
                    </Button>
                    <Button :href="route('clients.index')" variant="primary">
                        Back to list
                    </Button>
                    <Button
                        as="button"
                        variant="danger"
                        @click="destroyClient"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-slate-900">Contact Info</h3>
                        <dl class="mt-4 grid gap-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Email</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ client.email ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Phone</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ client.phone ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                            client.status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-yellow-100 text-yellow-800',
                                        ]"
                                    >
                                        {{ client.status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Created</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ formatDate(client.created_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-slate-900">Notes</h3>
                        <p class="mt-3 text-sm text-slate-700 whitespace-pre-line">
                            {{ client.notes || 'No notes yet.' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-slate-900">Order History</h3>
                            <p class="text-sm text-slate-500">Orders integration coming in Phase 4.</p>
                        </div>

                        <div class="mt-4 rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            No orders to display yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showModal"
            message="Are you sure you want to delete this client?"
            confirm-label="Delete"
            @close="showModal = false"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
