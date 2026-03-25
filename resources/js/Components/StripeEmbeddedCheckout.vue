<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { loadStripe } from "@stripe/stripe-js";
import axios from "axios";

const props = defineProps({
    /** POST endpoint that returns { client_secret, publishable_key } */
    endpoint: { type: String, required: true },
});

const emit = defineEmits(["close"]);

const containerRef = ref(null);
const loading = ref(true);
const error = ref(null);
let checkout = null;

onMounted(async () => {
    try {
        const { data } = await axios.post(props.endpoint);
        const { client_secret, publishable_key } = data;

        const stripe = await loadStripe(publishable_key);
        checkout = await stripe.initEmbeddedCheckout({ clientSecret: client_secret });
        checkout.mount(containerRef.value);
        loading.value = false;
    } catch (e) {
        error.value =
            e.response?.data?.message ||
            e.message ||
            "Failed to load checkout. Please try again.";
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    checkout?.destroy();
});
</script>

<template>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 px-4 py-10"
        @click.self="$emit('close')"
    >
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">Pay with Stripe</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    @click="$emit('close')"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Skeleton loader -->
            <div v-if="loading" class="space-y-4 p-6">
                <div class="h-8 animate-pulse rounded-lg bg-slate-100" />
                <div class="h-12 animate-pulse rounded-lg bg-slate-100" />
                <div class="h-12 animate-pulse rounded-lg bg-slate-100" />
                <div class="h-12 animate-pulse rounded-lg bg-slate-100" />
                <div class="h-10 animate-pulse rounded-lg bg-slate-200" />
            </div>

            <!-- Error state -->
            <div v-if="error" class="p-6">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
                <button
                    type="button"
                    class="mt-4 text-sm text-slate-500 underline hover:text-slate-700"
                    @click="$emit('close')"
                >
                    Close
                </button>
            </div>

            <!-- Stripe mounts here; hidden while loading or on error -->
            <div
                ref="containerRef"
                class="p-4"
                :class="{ hidden: loading || error }"
            />
        </div>
    </div>
</template>
