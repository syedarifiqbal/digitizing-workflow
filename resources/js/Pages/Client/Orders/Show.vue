<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/Button.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    order: Object,
    inputFiles: Array,
    outputFiles: Array,
    showOutputFiles: Boolean,
    downloadInputZipUrl: String,
    downloadOutputZipUrl: String,
    parentOrder: Object,
    revisionOrders: Array,
    comments: Array,
});

const newComment = ref('');
const submitting = ref(false);

const submitComment = () => {
    if (!newComment.value.trim()) return;

    submitting.value = true;
    router.post(route('client.orders.comments.store', props.order.id), {
        body: newComment.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newComment.value = '';
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        received: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        assigned: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        in_progress: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        submitted: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        in_review: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        approved: 'bg-teal-50 text-teal-700 ring-teal-600/20',
        delivered: 'bg-green-50 text-green-700 ring-green-600/20',
        closed: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        cancelled: 'bg-red-50 text-red-700 ring-red-600/20',
    };
    return colors[status] || 'bg-slate-100 text-slate-700';
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('client.orders.index')"
                            class="text-slate-400 hover:text-slate-600"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <h2 class="text-2xl font-semibold text-slate-900">Order {{ order.order_number }}</h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="getStatusColor(order.status)">
                            {{ order.status_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ order.title }}</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">

            <!-- Order Summary -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Order Summary</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Order Number</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ order.order_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                :class="getStatusColor(order.status)">
                                {{ order.status_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Type</dt>
                        <dd class="mt-1 text-sm text-slate-900 capitalize">{{ order.type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Priority</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                :class="getPriorityColor(order.priority)">
                                {{ order.priority_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Quantity</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ order.quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(order.created_at) }}</dd>
                    </div>
                    <div v-if="order.designer" class="col-span-full">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assigned Designer</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ order.designer.name }}</dd>
                    </div>
                    <div v-if="order.description" class="col-span-full">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt>
                        <dd class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ order.description }}</dd>
                    </div>
                </div>
            </div>

            <!-- Parent Order Link -->
            <div v-if="parentOrder" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6">
                <h3 class="text-sm font-semibold text-indigo-900 mb-2">Revision of</h3>
                <Link
                    :href="route('client.orders.show', parentOrder.id)"
                    class="text-sm font-medium text-indigo-700 hover:text-indigo-900"
                >
                    {{ parentOrder.order_number }}
                </Link>
            </div>

            <!-- Revision Orders -->
            <div v-if="revisionOrders?.length > 0" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Revision Orders</h3>
                </div>
                <div class="px-6 py-5">
                    <ul class="divide-y divide-slate-100">
                        <li v-for="rev in revisionOrders" :key="rev.id" class="py-3 flex items-center justify-between">
                            <div>
                                <Link
                                    :href="route('client.orders.show', rev.id)"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                >
                                    {{ rev.order_number }}
                                </Link>
                                <p class="text-xs text-slate-500">{{ formatDate(rev.created_at) }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                :class="getStatusColor(rev.status)">
                                {{ rev.status_label }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Input Files -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Input Files</h3>
                    <a
                        v-if="downloadInputZipUrl && inputFiles.length"
                        :href="downloadInputZipUrl"
                        class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100"
                    >
                        Download All
                    </a>
                </div>
                <div class="px-6 py-5">
                    <div v-if="inputFiles.length === 0" class="text-sm text-slate-500">
                        No input files uploaded.
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="file in inputFiles" :key="file.id" class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ file.filename }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ formatFileSize(file.file_size) }} &bull; {{ file.extension }} &bull; Uploaded {{ formatDate(file.created_at) }}
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
            <div v-if="showOutputFiles" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Deliverable Files</h3>
                    <a
                        v-if="downloadOutputZipUrl && outputFiles.length"
                        :href="downloadOutputZipUrl"
                        class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100"
                    >
                        Download All
                    </a>
                </div>
                <div class="px-6 py-5">
                    <div v-if="outputFiles.length === 0" class="text-sm text-slate-500">
                        No deliverable files yet.
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="file in outputFiles" :key="file.id" class="py-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ file.filename }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ formatFileSize(file.file_size) }} &bull; {{ file.extension }} &bull; Uploaded {{ formatDate(file.created_at) }}
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

            <!-- Comments -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Comments</h3>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <!-- Comment List -->
                    <div v-if="comments.length > 0" class="space-y-3 mb-4">
                        <div v-for="comment in comments" :key="comment.id" class="border-l-2 border-indigo-200 pl-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ comment.user.name }}</p>
                                    <p class="text-xs text-slate-500">{{ formatDate(comment.created_at) }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{{ comment.body }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No comments yet.</p>

                    <!-- Add Comment Form -->
                    <div class="pt-4 border-t border-slate-200">
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Add a comment</label>
                        <textarea
                            v-model="newComment"
                            rows="3"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            placeholder="Type your comment..."
                        ></textarea>
                        <div class="mt-3 flex justify-end">
                            <Button
                                primary
                                @click="submitComment"
                                :disabled="!newComment.trim() || submitting"
                            >
                                {{ submitting ? 'Posting...' : 'Post Comment' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
