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

                <!-- Additional Emails -->
                <div v-if="client.emails && client.emails.length" class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-slate-900">Additional Email Addresses</h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="(entry, index) in client.emails"
                                :key="index"
                                class="flex items-center gap-3 text-sm text-slate-700"
                            >
                                <span class="font-medium text-slate-900">{{ entry.email }}</span>
                                <span v-if="entry.label" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ entry.label }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Permanent Instructions -->
                <div
                    v-if="client.permanent_instructions && (
                        client.permanent_instructions.special_offer_note ||
                        client.permanent_instructions.price_instructions ||
                        client.permanent_instructions.for_digitizer ||
                        client.permanent_instructions.appreciation_bonus ||
                        (client.permanent_instructions.custom && client.permanent_instructions.custom.length)
                    )"
                    class="bg-amber-50 border border-amber-200 shadow sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-amber-900">Permanent Instructions</h3>
                        <dl class="mt-3 space-y-3">
                            <div v-if="client.permanent_instructions.special_offer_note">
                                <dt class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Special Offer / Note</dt>
                                <dd class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ client.permanent_instructions.special_offer_note }}</dd>
                            </div>
                            <div v-if="client.permanent_instructions.price_instructions">
                                <dt class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Price Instructions</dt>
                                <dd class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ client.permanent_instructions.price_instructions }}</dd>
                            </div>
                            <div v-if="client.permanent_instructions.for_digitizer">
                                <dt class="text-xs font-semibold text-amber-700 uppercase tracking-wide">For Digitizer</dt>
                                <dd class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ client.permanent_instructions.for_digitizer }}</dd>
                            </div>
                            <div v-if="client.permanent_instructions.appreciation_bonus">
                                <dt class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Appreciation Bonus</dt>
                                <dd class="mt-1 text-sm text-slate-800">${{ client.permanent_instructions.appreciation_bonus }}</dd>
                            </div>
                            <template v-if="client.permanent_instructions.custom && client.permanent_instructions.custom.length">
                                <div v-for="(item, index) in client.permanent_instructions.custom" :key="index">
                                    <dt class="text-xs font-semibold text-amber-700 uppercase tracking-wide">{{ item.key }}</dt>
                                    <dd class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ item.value }}</dd>
                                </div>
                            </template>
                        </dl>
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
