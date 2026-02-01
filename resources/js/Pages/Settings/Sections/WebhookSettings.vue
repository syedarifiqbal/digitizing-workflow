<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const availableEvents = [
    { value: "order.delivered", label: "Order Delivered", description: "Fired when an order is delivered to the client." },
    { value: "order.closed", label: "Order Closed", description: "Fired when a delivered order is marked as closed." },
    { value: "order.cancelled", label: "Order Cancelled", description: "Fired when an order is cancelled." },
];

const toggleEvent = (eventValue) => {
    const idx = props.form.webhook_events.indexOf(eventValue);
    if (idx === -1) {
        props.form.webhook_events.push(eventValue);
    } else {
        props.form.webhook_events.splice(idx, 1);
    }
};

const sendingTest = ref(false);

const sendTestWebhook = () => {
    sendingTest.value = true;
    router.post(route("settings.test-webhook"), {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingTest.value = false;
        },
    });
};
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Webhooks</h3>
            <p class="mt-0.5 text-xs text-gray-500">
                Send HTTP POST notifications to an external URL when order events occur. Payloads are signed with HMAC SHA256.
            </p>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <label for="webhook_url" class="block text-sm font-medium text-gray-700">Webhook URL</label>
                <input
                    v-model="form.webhook_url"
                    id="webhook_url"
                    type="url"
                    placeholder="https://example.com/webhook"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <p v-if="form.errors.webhook_url" class="mt-1 text-xs text-red-600">{{ form.errors.webhook_url }}</p>
            </div>

            <div>
                <label for="webhook_secret" class="block text-sm font-medium text-gray-700">Webhook Secret</label>
                <input
                    v-model="form.webhook_secret"
                    id="webhook_secret"
                    type="text"
                    placeholder="your-secret-key"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                />
                <p class="mt-1 text-xs text-gray-500">Used to sign payloads. The receiving server should verify the <code class="text-xs bg-gray-100 px-1 rounded">X-Signature</code> header.</p>
                <p v-if="form.errors.webhook_secret" class="mt-1 text-xs text-red-600">{{ form.errors.webhook_secret }}</p>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <h4 class="text-sm font-medium text-gray-900">Events</h4>
                <p class="mt-0.5 text-xs text-gray-500">Select which events trigger a webhook delivery.</p>
            </div>

            <div class="space-y-3">
                <div
                    v-for="event in availableEvents"
                    :key="event.value"
                    class="flex items-start"
                >
                    <input
                        :id="'event-' + event.value"
                        type="checkbox"
                        :checked="form.webhook_events.includes(event.value)"
                        @change="toggleEvent(event.value)"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label :for="'event-' + event.value" class="ml-2">
                        <p class="text-sm font-medium text-gray-700">{{ event.label }}</p>
                        <p class="text-xs text-gray-500">{{ event.description }}</p>
                    </label>
                </div>
            </div>
            <p v-if="form.errors.webhook_events" class="mt-1 text-xs text-red-600">{{ form.errors.webhook_events }}</p>

            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Test Webhook</h4>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Send a test ping to the saved webhook URL. Save settings first if you've made changes.
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="sendingTest || !form.webhook_url"
                        @click="sendTestWebhook"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ sendingTest ? "Sending..." : "Send Test Ping" }}
                    </button>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <a
                    :href="route('webhook-logs.index')"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                >
                    View webhook delivery logs &rarr;
                </a>
            </div>
        </div>
    </div>
</template>
