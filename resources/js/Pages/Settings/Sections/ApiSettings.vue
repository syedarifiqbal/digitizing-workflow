<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apiKeyLastFour: {
        type: [String, null],
        default: null,
    },
    apiKeyMessage: {
        type: [String, null],
        default: null,
    },
});

const emit = defineEmits(["generate"]);
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Intake API</h3>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Enable or disable the external intake API and manage keys.
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                    :class="form.api_enabled ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'"
                >
                    <span class="h-2 w-2 rounded-full" :class="form.api_enabled ? 'bg-green-500' : 'bg-slate-400'"></span>
                    {{ form.api_enabled ? "Enabled" : "Disabled" }}
                </span>
            </div>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        v-model="form.api_enabled"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    Enable intake API
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    Requests with a valid API key can create clients and orders.
                </p>
                <p v-if="form.errors.api_enabled" class="mt-1 text-xs text-red-600">
                    {{ form.errors.api_enabled }}
                </p>
            </div>

            <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <p class="font-medium text-slate-900">Current key</p>
                <p v-if="apiKeyLastFour" class="mt-1">
                    Last generated key ends with <span class="font-semibold">•••• {{ apiKeyLastFour }}</span>
                </p>
                <p v-else class="mt-1">
                    No API key has been generated yet.
                </p>
                <p class="mt-2 text-slate-500">
                    Keys are shown only once at generation. Regenerating immediately revokes the previous key.
                </p>
            </div>

            <div
                v-if="apiKeyMessage"
                class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs text-indigo-900 break-all"
            >
                <p class="font-semibold">New API Key</p>
                <p class="mt-1">{{ apiKeyMessage }}</p>
                <p class="mt-2 font-medium">Copy this key now. It will not be shown again.</p>
            </div>

            <button
                type="button"
                :disabled="!form.api_enabled"
                @click="emit('generate')"
                class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-white transition"
                :class="form.api_enabled ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-300 cursor-not-allowed'"
            >
                {{ apiKeyLastFour ? "Regenerate API Key" : "Generate API Key" }}
            </button>
        </div>
    </div>
</template>
