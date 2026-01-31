<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: "md",
        validator: (v) => ["sm", "md", "lg", "xl", "2xl"].includes(v),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["close"]);

const maxWidthClass = {
    sm: "max-w-sm",
    md: "max-w-md",
    lg: "max-w-lg",
    xl: "max-w-xl",
    "2xl": "max-w-2xl",
};

const close = () => {
    if (props.closeable) {
        emit("close");
    }
};

const onKeydown = (e) => {
    if (e.key === "Escape") {
        close();
    }
};
</script>

<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @keydown="onKeydown"
            >
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="fixed inset-0 bg-gray-500/75 transition-opacity"
                        @click="close"
                    ></div>
                    <div
                        class="relative w-full rounded-2xl bg-white p-6 shadow-xl"
                        :class="maxWidthClass[maxWidth]"
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.15s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
