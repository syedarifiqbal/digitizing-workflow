<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    form: Object,
    stripeKeyStatus: {
        type: Object,
        default: () => ({ publishable: false, secret: false, webhook: false }),
    },
});

// ─── Stripe Keys Form (separate POST, keys never flow back to frontend) ──────
const keysForm = ref({
    stripe_publishable_key: '',
    stripe_secret_key: '',
    stripe_webhook_secret: '',
});
const keysSaving = ref(false);
const keysSuccess = ref(false);

const saveStripeKeys = () => {
    keysSaving.value = true;
    keysSuccess.value = false;

    router.post(route('settings.stripe-keys'), keysForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            keysSuccess.value = true;
            keysForm.value = { stripe_publishable_key: '', stripe_secret_key: '', stripe_webhook_secret: '' };
        },
        onFinish: () => { keysSaving.value = false; },
    });
};

const baseInput = 'mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <div class="space-y-6">

        <!-- Stripe Configuration -->
        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Stripe Payments</h3>
                <p class="mt-0.5 text-xs text-slate-500">Accept online payments for invoices via Stripe.</p>
            </div>
            <div class="px-5 py-4 space-y-5">

                <!-- Enable toggle -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Enable Stripe</p>
                        <p class="text-xs text-slate-500 mt-0.5">Show "Pay with Stripe" on invoices.</p>
                    </div>
                    <button
                        type="button"
                        @click="form.stripe_enabled = !form.stripe_enabled"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                            form.stripe_enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200',
                                form.stripe_enabled ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>

                <!-- Checkout Mode -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">Checkout Mode</label>
                    <p class="text-xs text-slate-500 mt-0.5">Hosted redirects to Stripe's page. Embedded keeps the customer on your site.</p>
                    <div class="mt-2 flex gap-3">
                        <label
                            v-for="opt in [{ value: 'hosted', label: 'Hosted (redirect)' }, { value: 'embedded', label: 'Embedded (on-page)' }]"
                            :key="opt.value"
                            :class="[
                                'flex items-center gap-2 rounded-md border px-3 py-2 text-sm cursor-pointer transition',
                                form.stripe_checkout_mode === opt.value
                                    ? 'border-indigo-300 bg-indigo-50 text-indigo-700'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" v-model="form.stripe_checkout_mode" :value="opt.value" class="sr-only" />
                            {{ opt.label }}
                        </label>
                    </div>
                </div>

                <!-- Allow Admin Payment -->
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div>
                        <p class="text-sm font-medium text-slate-800">Allow Admin / Staff Payment</p>
                        <p class="text-xs text-slate-500 mt-0.5">Show "Pay with Stripe" on the admin invoice view too.</p>
                    </div>
                    <button
                        type="button"
                        @click="form.stripe_allow_admin_payment = !form.stripe_allow_admin_payment"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                            form.stripe_allow_admin_payment ? 'bg-indigo-600' : 'bg-slate-200'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200',
                                form.stripe_allow_admin_payment ? 'translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- Stripe API Keys (separate form, one-way write) -->
        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Stripe API Keys</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    Keys are encrypted before storage. Leave a field blank to keep the current value.
                    Get your keys from the
                    <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-indigo-600 hover:underline">Stripe dashboard</a>.
                </p>
            </div>
            <form @submit.prevent="saveStripeKeys" class="px-5 py-4 space-y-4">

                <!-- Publishable Key -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-slate-700">Publishable Key</label>
                        <span
                            :class="stripeKeyStatus.publishable ? 'text-green-600' : 'text-slate-400'"
                            class="text-xs font-medium"
                        >
                            {{ stripeKeyStatus.publishable ? '✓ Configured' : 'Not set' }}
                        </span>
                    </div>
                    <input
                        v-model="keysForm.stripe_publishable_key"
                        type="password"
                        :placeholder="stripeKeyStatus.publishable ? 'pk_••••••••••••••••' : 'pk_live_...'"
                        autocomplete="off"
                        :class="baseInput"
                    />
                </div>

                <!-- Secret Key -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-slate-700">Secret Key</label>
                        <span
                            :class="stripeKeyStatus.secret ? 'text-green-600' : 'text-slate-400'"
                            class="text-xs font-medium"
                        >
                            {{ stripeKeyStatus.secret ? '✓ Configured' : 'Not set' }}
                        </span>
                    </div>
                    <input
                        v-model="keysForm.stripe_secret_key"
                        type="password"
                        :placeholder="stripeKeyStatus.secret ? 'sk_••••••••••••••••' : 'sk_live_...'"
                        autocomplete="off"
                        :class="baseInput"
                    />
                </div>

                <!-- Webhook Secret -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-slate-700">Webhook Signing Secret</label>
                        <span
                            :class="stripeKeyStatus.webhook ? 'text-green-600' : 'text-slate-400'"
                            class="text-xs font-medium"
                        >
                            {{ stripeKeyStatus.webhook ? '✓ Configured' : 'Not set' }}
                        </span>
                    </div>
                    <input
                        v-model="keysForm.stripe_webhook_secret"
                        type="password"
                        :placeholder="stripeKeyStatus.webhook ? 'whsec_••••••••••••' : 'whsec_...'"
                        autocomplete="off"
                        :class="baseInput"
                    />
                    <p class="mt-1 text-xs text-slate-400">
                        Webhook URL to register in Stripe:
                        <code class="bg-slate-100 px-1 rounded">{{ $page.props.ziggy?.url ?? '' }}/stripe/webhook/{{ $page.props.auth?.user?.tenant_slug ?? '{tenant-slug}' }}</code>
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        :disabled="keysSaving"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ keysSaving ? 'Saving...' : 'Save API Keys' }}
                    </button>
                    <span v-if="keysSuccess" class="text-xs text-green-600 font-medium">Keys saved securely.</span>
                </div>
            </form>
        </div>
    </div>
</template>
