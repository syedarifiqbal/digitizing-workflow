<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderTimeline from '@/Components/OrderTimeline.vue';
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

const props = defineProps({
    order: Object,
    inputFiles: Array,
    outputFiles: Array,
    canAssign: Boolean,
    designers: Array,
    allowedTransitions: Array,
    canSubmitWork: Boolean,
    maxUploadMb: Number,
    allowedOutputExtensions: String,
    timeline: Array,
});

const selectedDesigner = ref(props.order?.designer?.id ?? '');
const assigning = ref(false);
const transitioning = ref(false);
const submitting = ref(false);
const submitFiles = ref([]);
const submitNotes = ref('');
const fileInput = ref(null);

const page = usePage();

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

const changeStatus = (status) => {
    transitioning.value = true;
    router.patch(route('orders.status', props.order.id), {
        status: status,
    }, {
        preserveScroll: true,
        onFinish: () => {
            transitioning.value = false;
        },
    });
};

const handleFileSelect = (event) => {
    submitFiles.value = Array.from(event.target.files);
};

const removeFile = (index) => {
    submitFiles.value.splice(index, 1);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitWork = () => {
    if (!submitFiles.value.length) return;

    submitting.value = true;

    const formData = new FormData();
    submitFiles.value.forEach((file) => {
        formData.append('files[]', file);
    });
    if (submitNotes.value) {
        formData.append('notes', submitNotes.value);
    }

    router.post(route('orders.submit-work', props.order.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            submitFiles.value = [];
            submitNotes.value = '';
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const getButtonClass = (style) => {
    const classes = {
        primary: 'bg-indigo-600 hover:bg-indigo-700 text-white',
        success: 'bg-green-600 hover:bg-green-700 text-white',
        info: 'bg-blue-600 hover:bg-blue-700 text-white',
        warning: 'bg-yellow-500 hover:bg-yellow-600 text-white',
        danger: 'bg-red-600 hover:bg-red-700 text-white',
        secondary: 'bg-gray-600 hover:bg-gray-700 text-white',
    };
    return classes[style] || classes.secondary;
};

const statusBadgeClass = (status) => {
    const map = {
        received: 'bg-gray-100 text-gray-700',
        assigned: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-indigo-100 text-indigo-700',
        submitted: 'bg-purple-100 text-purple-700',
        in_review: 'bg-cyan-100 text-cyan-700',
        revision_requested: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        closed: 'bg-gray-100 text-gray-600',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};

const priorityBadgeClass = (priority) => {
    return priority === 'rush'
        ? 'bg-red-50 text-red-700 ring-1 ring-red-200'
        : 'bg-gray-50 text-gray-600 ring-1 ring-gray-200';
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-semibold text-gray-900">{{ order.order_number }}</h2>
                            <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusBadgeClass(order.status)]">
                                {{ (order.status || '').split('_').join(' ') }}
                            </span>
                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize', priorityBadgeClass(order.priority)]">
                                {{ order.priority }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">{{ order.title }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('orders.edit', order.id)"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('orders.index')"
                        class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
                    >
                        Back
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- Main Content (Left) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Order Details -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Order Details</h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ (order.type || '').split('_').join(' ') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Client</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.client.name }}</dd>
                                        <dd class="text-xs text-gray-500">{{ order.client.email }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Designer</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.designer?.name ?? 'Unassigned' }}</dd>
                                    </div>
                                    <div v-if="order.po_number">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">PO #</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.po_number }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Price</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.price_amount ? `${order.currency} ${order.price_amount}` : '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Due Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.due_at ? formatDate(order.due_at) : '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(order.created_at, true) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Digitizing Details -->
                        <div v-if="order.type === 'digitizing' && (order.height || order.width || order.placement || order.file_format)" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Digitizing Details</h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4">
                                    <div v-if="order.height">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Height</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.height }}"</dd>
                                    </div>
                                    <div v-if="order.width">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Width</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.width }}"</dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Placement</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.placement }}</dd>
                                    </div>
                                    <div v-if="order.file_format">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">File Format</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.file_format }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Patch Details -->
                        <div v-if="order.type === 'patch' && (order.patch_type || order.quantity || order.height || order.backing)" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Patch Details</h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                                    <div v-if="order.patch_type">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Patch Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.patch_type }}</dd>
                                    </div>
                                    <div v-if="order.quantity">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Quantity</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.quantity }}</dd>
                                    </div>
                                    <div v-if="order.height">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Size</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.height }}" x {{ order.width }}"</dd>
                                    </div>
                                    <div v-if="order.backing">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Backing</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.backing }}</dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Placement</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.placement }}</dd>
                                    </div>
                                    <div v-if="order.merrow_border">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Merrow Border</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.merrow_border }}</dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Colors</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.num_colors }}</dd>
                                    </div>
                                    <div v-if="order.fabric">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fabric</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.fabric }}</dd>
                                    </div>
                                    <div v-if="order.need_by">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Need By</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(order.need_by) }}</dd>
                                    </div>
                                </dl>
                                <div v-if="order.shipping_address" class="mt-4 pt-4 border-t border-gray-100">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Shipping Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ order.shipping_address }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Vector Details -->
                        <div v-if="order.type === 'vector' && (order.color_type || order.vector_order_type || order.required_format)" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Vector Details</h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4">
                                    <div v-if="order.color_type">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Color Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.color_type }}</dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Colors</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.num_colors }}</dd>
                                    </div>
                                    <div v-if="order.vector_order_type">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Order Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.vector_order_type }}</dd>
                                    </div>
                                    <div v-if="order.required_format">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Format</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ order.required_format }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Instructions</h3>
                            </div>
                            <div class="px-5 py-4">
                                <p class="whitespace-pre-line text-sm text-gray-700 leading-relaxed">
                                    {{ order.instructions || 'No instructions provided.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Input Files -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Input Files</h3>
                            </div>
                            <div v-if="inputFiles?.length">
                                <div v-for="file in inputFiles" :key="file.id" class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ file.original_name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatSize(file.size) }} &bull; {{ formatDate(file.uploaded_at, true) }}</p>
                                    </div>
                                    <a
                                        :href="file.download_url"
                                        class="ml-3 inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                            <div v-else class="px-5 py-4">
                                <p class="text-sm text-gray-500">No input files uploaded.</p>
                            </div>
                        </div>

                        <!-- Output Files -->
                        <div v-if="outputFiles?.length" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Output Files</h3>
                            </div>
                            <div>
                                <div v-for="file in outputFiles" :key="file.id" class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ file.original_name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatSize(file.size) }} &bull; {{ formatDate(file.uploaded_at, true) }}</p>
                                    </div>
                                    <a
                                        :href="file.download_url"
                                        class="ml-3 inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Work Form -->
                        <div v-if="canSubmitWork" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Submit Work</h3>
                            </div>
                            <div class="px-5 py-4 space-y-4">
                                <p class="text-xs text-gray-500">
                                    Upload your completed files to submit for review.
                                    <span v-if="allowedOutputExtensions"> Allowed: {{ allowedOutputExtensions }}.</span>
                                    Max size: {{ maxUploadMb }}MB per file.
                                </p>

                                <div>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        @change="handleFileSelect"
                                    />
                                    <p v-if="page.props.errors?.files" class="mt-1 text-xs text-red-600">
                                        {{ page.props.errors.files }}
                                    </p>
                                </div>

                                <div v-if="submitFiles.length" class="rounded-md border border-gray-200 divide-y divide-gray-100">
                                    <div v-for="(file, index) in submitFiles" :key="index" class="flex items-center justify-between px-3 py-2">
                                        <div>
                                            <p class="text-xs font-medium text-gray-900">{{ file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ formatSize(file.size) }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-xs text-red-500 hover:text-red-700"
                                            @click="removeFile(index)"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label for="submit_notes" class="block text-xs font-medium text-gray-700">Notes (optional)</label>
                                    <textarea
                                        v-model="submitNotes"
                                        id="submit_notes"
                                        rows="2"
                                        placeholder="Any notes about your submission..."
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    ></textarea>
                                </div>

                                <button
                                    type="button"
                                    :disabled="!submitFiles.length || submitting"
                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                                    @click="submitWork"
                                >
                                    {{ submitting ? 'Submitting...' : 'Submit Work' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (Right) -->
                    <div class="space-y-6">

                        <!-- Actions -->
                        <div v-if="allowedTransitions?.length" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Actions</h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="transition in allowedTransitions"
                                        :key="transition.value"
                                        type="button"
                                        :disabled="transitioning"
                                        :class="[
                                            'inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm disabled:opacity-50',
                                            getButtonClass(transition.style)
                                        ]"
                                        @click="changeStatus(transition.value)"
                                    >
                                        {{ transition.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Designer Assignment -->
                        <div v-if="canAssign" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Assign Designer</h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="space-y-3">
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
                                    <div class="flex gap-2">
                                        <button
                                            v-if="selectedDesigner && selectedDesigner !== order.designer?.id"
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="assigning"
                                            @click="assignDesigner"
                                        >
                                            Assign
                                        </button>
                                        <button
                                            v-if="order.designer && selectedDesigner === ''"
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                            :disabled="assigning"
                                            @click="unassignDesigner"
                                        >
                                            Unassign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Timeline -->
                        <div v-if="timeline?.length" class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">Activity</h3>
                            </div>
                            <div class="px-5 py-4">
                                <OrderTimeline :events="timeline" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
