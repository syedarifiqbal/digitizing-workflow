<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const sendingTest = ref(false);

const sendTestEmail = () => {
    sendingTest.value = true;
    router.post(route("settings.test-email"), {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingTest.value = false;
        },
    });
};
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Email / SMTP</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Configure a custom SMTP server and sender identity. Leave blank to use system defaults.
            </p>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-slate-700">SMTP Host</label>
                    <input
                        v-model="form.smtp_host"
                        id="smtp_host"
                        type="text"
                        placeholder="smtp.example.com"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.smtp_host" class="mt-1 text-xs text-red-600">{{ form.errors.smtp_host }}</p>
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-slate-700">SMTP Port</label>
                    <input
                        v-model="form.smtp_port"
                        id="smtp_port"
                        type="number"
                        placeholder="587"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.smtp_port" class="mt-1 text-xs text-red-600">{{ form.errors.smtp_port }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-slate-700">SMTP Username</label>
                    <input
                        v-model="form.smtp_username"
                        id="smtp_username"
                        type="text"
                        placeholder="user@example.com"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.smtp_username" class="mt-1 text-xs text-red-600">{{ form.errors.smtp_username }}</p>
                </div>
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-slate-700">SMTP Password</label>
                    <input
                        v-model="form.smtp_password"
                        id="smtp_password"
                        type="password"
                        placeholder="********"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.smtp_password" class="mt-1 text-xs text-red-600">{{ form.errors.smtp_password }}</p>
                </div>
            </div>

            <div>
                <label for="smtp_encryption" class="block text-sm font-medium text-slate-700">Encryption</label>
                <select
                    v-model="form.smtp_encryption"
                    id="smtp_encryption"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">None (system default)</option>
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>
                <p v-if="form.errors.smtp_encryption" class="mt-1 text-xs text-red-600">{{ form.errors.smtp_encryption }}</p>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h4 class="text-sm font-medium text-slate-900">Sender Identity</h4>
                <p class="mt-0.5 text-xs text-slate-500">Override the default "From" address and name on outgoing emails.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="mail_from_address" class="block text-sm font-medium text-slate-700">From Email</label>
                    <input
                        v-model="form.mail_from_address"
                        id="mail_from_address"
                        type="email"
                        placeholder="noreply@yourcompany.com"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.mail_from_address" class="mt-1 text-xs text-red-600">{{ form.errors.mail_from_address }}</p>
                </div>
                <div>
                    <label for="mail_from_name" class="block text-sm font-medium text-slate-700">From Name</label>
                    <input
                        v-model="form.mail_from_name"
                        id="mail_from_name"
                        type="text"
                        placeholder="Your Company Name"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p v-if="form.errors.mail_from_name" class="mt-1 text-xs text-red-600">{{ form.errors.mail_from_name }}</p>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-slate-900">Test Email</h4>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Send a test email to your account using the currently saved settings. Save first if you've made changes.
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="sendingTest"
                        @click="sendTestEmail"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ sendingTest ? "Sending..." : "Send Test Email" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
