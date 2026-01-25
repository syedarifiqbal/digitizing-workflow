<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    order: Object,
    inputFiles: Array,
    outputFiles: Array,
    showOutputFiles: Boolean,
    revisions: Array,
});

const getStatusColor = (status) => {
    const colors = {
        received: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        assigned: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        in_progress: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        submitted: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        in_review: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        revision_requested: 'bg-orange-50 text-orange-700 ring-orange-600/20',
        approved: 'bg-teal-50 text-teal-700 ring-teal-600/20',
        delivered: 'bg-green-50 text-green-700 ring-green-600/20',
        closed: 'bg-gray-100 text-gray-700 ring-gray-600/20',
        cancelled: 'bg-red-50 text-red-700 ring-red-600/20',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

const getPriorityColor = (priority) => {
    return priority === 'rush'
        ? 'bg-red-50 text-red-700 ring-red-600/20'
        : 'bg-slate-50 text-slate-700 ring-slate-600/20';
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-gray-900">Order {{ order.order_number }}</h2>
                    <p class="text-sm text-gray-500">{{ order.title }}</p>
                </div>
                <Link
                    :href="route('client.orders.index')"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Back to Orders
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Order Summary -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Order Summary</h3>
                    </div>
                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Order Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ order.order_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="getStatusColor(order.status)">
                                    {{ order.status_label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ order.type }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Priority</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="getPriorityColor(order.priority)">
                                    {{ order.priority_label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Quantity</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ order.quantity }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(order.created_at) }}</dd>
                        </div>
                        <div v-if="order.designer" class="col-span-full">
                            <dt class="text-xs font-medium text-gray-500">Assigned Designer</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ order.designer.name }}</dd>
                        </div>
                        <div v-if="order.description" class="col-span-full">
                            <dt class="text-xs font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ order.description }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Revision Notes -->
                <div v-if="revisions.length > 0" class="bg-orange-50 border border-orange-200 rounded-lg p-5">
                    <h3 class="text-sm font-semibold text-orange-900 mb-3">Revision Requested</h3>
                    <div v-for="revision in revisions" :key="revision.id" class="mb-3 last:mb-0">
                        <p class="text-sm text-orange-800">{{ revision.notes }}</p>
                        <p class="mt-1 text-xs text-orange-600">
                            Requested by {{ revision.requested_by?.name }} on {{ formatDate(revision.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Input Files -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Input Files</h3>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="inputFiles.length === 0" class="text-sm text-gray-500">
                            No input files uploaded.
                        </div>
                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="file in inputFiles" :key="file.id" class="py-3 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ file.filename }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatFileSize(file.file_size) }} • {{ file.extension }} • Uploaded {{ formatDate(file.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <a
                                    :href="file.download_url"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                >
                                    Download
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Output Files -->
                <div v-if="showOutputFiles" class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Deliverable Files</h3>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="outputFiles.length === 0" class="text-sm text-gray-500">
                            No deliverable files yet.
                        </div>
                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="file in outputFiles" :key="file.id" class="py-3 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ file.filename }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatFileSize(file.file_size) }} • {{ file.extension }} • Uploaded {{ formatDate(file.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <a
                                    :href="file.download_url"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                >
                                    Download
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
