<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    order: Object,
    files: Array,
    canAssign: Boolean,
    designers: Array,
});

const selectedDesigner = ref(props.order?.designer?.id ?? '');
const assigning = ref(false);

const formatSize = (size) => {
    if (!size) return '0 KB';
    const kb = size / 1024;
    if (kb < 1024) return `${kb.toFixed(1)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

const assignDesigner = () => {
    if (!selectedDesigner.value) return;

    assigning.value = true;
    router.post(route('orders.assign', props.order.id), {
        designer_id: selectedDesigner.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            assigning.value = false;
        },
    });
};

const unassignDesigner = () => {
    assigning.value = true;
    router.delete(route('orders.unassign', props.order.id), {
        preserveScroll: true,
        onFinish: () => {
            assigning.value = false;
            selectedDesigner.value = '';
        },
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Order {{ order.order_number }}</h2>
                    <p class="text-sm text-gray-600">{{ order.title }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('orders.edit', order.id)"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('orders.index')"
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
                    >
                        Back to list
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Status</h3>
                            <p class="mt-1 text-lg font-semibold capitalize">{{ (order.status || '').split('_').join(' ') }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Priority</h3>
                            <p class="mt-1 text-lg font-semibold capitalize">{{ order.priority }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Type</h3>
                            <p class="mt-1 text-lg font-semibold capitalize">{{ (order.type || '').split('_').join(' ') }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Client</h3>
                            <p class="mt-1 text-lg text-gray-900">{{ order.client.name }}</p>
                            <p class="text-sm text-gray-500">{{ order.client.email }}</p>
                        </div>

                        <!-- Designer Assignment -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Designer</h3>
                            <template v-if="canAssign">
                                <div class="mt-1 flex items-center gap-2">
                                    <select
                                        v-model="selectedDesigner"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="assigning"
                                    >
                                        <option value="">Unassigned</option>
                                        <option
                                            v-for="designer in designers"
                                            :key="designer.id"
                                            :value="designer.id"
                                        >
                                            {{ designer.name }}
                                        </option>
                                    </select>
                                    <button
                                        v-if="selectedDesigner && selectedDesigner !== order.designer?.id"
                                        type="button"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        :disabled="assigning"
                                        @click="assignDesigner"
                                    >
                                        Assign
                                    </button>
                                    <button
                                        v-if="order.designer && selectedDesigner === ''"
                                        type="button"
                                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                        :disabled="assigning"
                                        @click="unassignDesigner"
                                    >
                                        Unassign
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                <p class="mt-1 text-lg text-gray-900">{{ order.designer?.name ?? 'Unassigned' }}</p>
                            </template>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Quoted price</h3>
                            <p class="mt-1 text-lg text-gray-900">
                                {{ order.price_amount ? `${order.currency} ${order.price_amount}` : '—' }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Due date</h3>
                            <p class="mt-1 text-lg text-gray-900">{{ order.due_at ?? 'Not set' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Instructions</h3>
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-700">
                            {{ order.instructions || 'No instructions provided.' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Input Files</h3>
                        <div v-if="files?.length" class="mt-4 divide-y divide-gray-200 border rounded-md">
                            <div v-for="file in files" :key="file.id" class="flex items-center justify-between px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ file.original_name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatSize(file.size) }} • Uploaded {{ file.uploaded_at }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs uppercase text-gray-500">{{ file.type }}</span>
                                    <a
                                        :href="file.download_url"
                                        class="inline-flex items-center rounded bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-gray-500">No files uploaded yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
