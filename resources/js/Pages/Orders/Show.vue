<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OrderTimeline from '@/Components/OrderTimeline.vue';

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
                <!-- Status Actions -->
                <div v-if="allowedTransitions?.length" class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-3">Actions</h3>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="transition in allowedTransitions"
                                :key="transition.value"
                                type="button"
                                :disabled="transitioning"
                                :class="[
                                    'inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm disabled:opacity-50',
                                    getButtonClass(transition.style)
                                ]"
                                @click="changeStatus(transition.value)"
                            >
                                {{ transition.label }}
                            </button>
                        </div>
                    </div>
                </div>

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

                <!-- Input Files -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Input Files</h3>
                        <div v-if="inputFiles?.length" class="mt-4 divide-y divide-gray-200 border rounded-md">
                            <div v-for="file in inputFiles" :key="file.id" class="flex items-center justify-between px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ file.original_name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatSize(file.size) }} &bull; Uploaded {{ file.uploaded_at }}</p>
                                </div>
                                <a
                                    :href="file.download_url"
                                    class="inline-flex items-center rounded bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                >
                                    Download
                                </a>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-gray-500">No input files uploaded.</p>
                    </div>
                </div>

                <!-- Output Files (visible only after submission) -->
                <div v-if="outputFiles?.length" class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Output Files</h3>
                        <div class="mt-4 divide-y divide-gray-200 border rounded-md">
                            <div v-for="file in outputFiles" :key="file.id" class="flex items-center justify-between px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ file.original_name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatSize(file.size) }} &bull; Uploaded {{ file.uploaded_at }}</p>
                                </div>
                                <a
                                    :href="file.download_url"
                                    class="inline-flex items-center rounded bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                >
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div v-if="timeline?.length" class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Activity</h3>
                        <OrderTimeline :events="timeline" />
                    </div>
                </div>

                <!-- Submit Work Form -->
                <div v-if="canSubmitWork" class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Submit Work</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Upload your completed files to submit this order for review.
                            <span v-if="allowedOutputExtensions" class="block mt-1">
                                Allowed formats: {{ allowedOutputExtensions }}
                            </span>
                            <span class="block mt-1">Max file size: {{ maxUploadMb }}MB</span>
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    multiple
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    @change="handleFileSelect"
                                />
                                <p v-if="page.props.errors?.files" class="mt-1 text-sm text-red-600">
                                    {{ page.props.errors.files }}
                                </p>
                            </div>

                            <!-- Selected files list -->
                            <div v-if="submitFiles.length" class="divide-y divide-gray-200 border rounded-md">
                                <div v-for="(file, index) in submitFiles" :key="index" class="flex items-center justify-between px-4 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatSize(file.size) }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-red-500 hover:text-red-700 text-sm"
                                        @click="removeFile(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="submit_notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                                <textarea
                                    v-model="submitNotes"
                                    id="submit_notes"
                                    rows="3"
                                    placeholder="Any notes about the submitted work..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                ></textarea>
                            </div>

                            <div>
                                <button
                                    type="button"
                                    :disabled="!submitFiles.length || submitting"
                                    class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                                    @click="submitWork"
                                >
                                    {{ submitting ? 'Submitting...' : 'Submit Work' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
