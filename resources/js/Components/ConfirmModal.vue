<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    title: {
        type: String,
        default: "Confirm action",
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
        default: "Confirm",
    },
    cancelLabel: {
        type: String,
        default: "Cancel",
    },
    variant: {
        type: String,
        default: "danger",
        validator: (v) => ["danger", "primary"].includes(v),
    },
});

const emit = defineEmits(["close", "confirm"]);

const close = () => emit("close");
const confirm = () => emit("confirm");

const confirmButtonClass = {
    danger: "bg-red-600 hover:bg-red-700",
    primary: "bg-indigo-600 hover:bg-indigo-700",
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
            <p class="text-sm text-slate-600">{{ message }}</p>
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="close"
                >
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    class="rounded-md px-4 py-2 text-sm font-medium text-white shadow"
                    :class="confirmButtonClass[variant]"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
