<script setup>
const props = defineProps({
    title: {
        type: String,
        default: 'Confirm action',
    },
    message: {
        type: String,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => emit('close');
const confirm = () => emit('confirm');
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/50 px-4">
            <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
                <div class="border-b px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600">{{ message }}</p>
                </div>
                <div class="flex justify-end gap-3 border-t px-6 py-4">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="close"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        @click="confirm"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
