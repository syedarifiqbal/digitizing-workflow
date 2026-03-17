<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
    allowFiles: { type: Boolean, default: true },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'confirm']);

const notes = ref('');
const files = ref([]);
const fileInput = ref(null);

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const handleFiles = (event) => {
    files.value = [...files.value, ...Array.from(event.target.files ?? [])];
    if (fileInput.value) fileInput.value.value = '';
};

const removeFile = (index) => {
    files.value.splice(index, 1);
};

const close = () => {
    notes.value = '';
    files.value = [];
    emit('close');
};

const confirm = () => {
    emit('confirm', { notes: notes.value || null, files: files.value });
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Create Revision Order</h3>
                <p class="mt-1 text-sm text-slate-500">
                    A new revision order will be created with all details copied from this order.
                </p>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Notes / Instructions <span class="text-slate-400 font-normal">(optional)</span>
                </label>
                <textarea
                    v-model="notes"
                    rows="3"
                    placeholder="Describe what changes are needed for the revision..."
                    class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                ></textarea>
            </div>

            <!-- File upload (admin only) -->
            <div v-if="allowFiles">
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Input Artwork Files <span class="text-slate-400 font-normal">(optional)</span>
                </label>
                <p class="text-xs text-slate-500 mb-2">Attach any new reference files. Existing input files from the parent order are copied automatically.</p>
                <input ref="fileInput" type="file" multiple class="hidden" @change="handleFiles" />
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 w-full justify-center"
                    @click="fileInput?.click()"
                >
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Files
                </button>
                <ul v-if="files.length" class="mt-2 space-y-1.5">
                    <li
                        v-for="(file, idx) in files"
                        :key="idx"
                        class="flex items-center justify-between gap-2 rounded-md bg-slate-50 px-3 py-1.5 text-sm"
                    >
                        <span class="truncate text-slate-800">{{ file.name }}</span>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ formatFileSize(file.size) }}</span>
                        <button type="button" class="text-red-400 hover:text-red-600 flex-shrink-0" @click="removeFile(idx)">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Errors -->
            <div
                v-if="errors?.['files.0'] || errors?.files || errors?.status || errors?.notes"
                class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700"
            >
                {{ errors?.['files.0'] || errors?.files || errors?.status || errors?.notes }}
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-1">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="close"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    @click="confirm"
                >
                    {{ submitting ? 'Creating...' : 'Create Revision Order' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
