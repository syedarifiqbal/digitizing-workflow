<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const eventFilter = ref(props.filters?.event ?? "");
const statusFilter = ref(props.filters?.status ?? "");
const expandedLog = ref(null);

const applyFilters = () => {
    const params = {};
    if (eventFilter.value) params.event = eventFilter.value;
    if (statusFilter.value) params.status = statusFilter.value;
    router.get(route("webhook-logs.index"), params, { preserveState: true, preserveScroll: true });
};

watch([eventFilter, statusFilter], applyFilters);

const toggleExpand = (logId) => {
    expandedLog.value = expandedLog.value === logId ? null : logId;
};

const formatPayload = (payload) => {
    try {
        return JSON.stringify(payload, null, 2);
    } catch {
        return String(payload);
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-slate-900">Webhook Logs</h2>
                <p class="text-sm text-slate-500">
                    History of outbound webhook deliveries and their responses.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center gap-4">
                        <select
                            v-model="eventFilter"
                            class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Events</option>
                            <option value="order.delivered">order.delivered</option>
                            <option value="order.closed">order.closed</option>
                            <option value="order.cancelled">order.cancelled</option>
                            <option value="webhook.test">webhook.test</option>
                        </select>

                        <select
                            v-model="statusFilter"
                            class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                        </select>

                        <a
                            :href="route('settings.edit')"
                            class="ml-auto text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            &larr; Back to Settings
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="log in logs.data"
                            :key="log.id"
                        >
                            <button
                                @click="toggleExpand(log.id)"
                                class="w-full px-5 py-3 text-left hover:bg-slate-50 transition flex items-center gap-4"
                            >
                                <span
                                    class="inline-flex h-2 w-2 rounded-full flex-shrink-0"
                                    :class="log.success ? 'bg-green-500' : 'bg-red-500'"
                                ></span>
                                <span class="text-sm font-mono text-slate-900 w-40 flex-shrink-0">{{ log.event }}</span>
                                <span class="text-xs text-slate-500 truncate flex-1">{{ log.url }}</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0"
                                    :class="log.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                >
                                    {{ log.response_status ?? 'ERR' }}
                                </span>
                                <span class="text-xs text-slate-400 flex-shrink-0 w-20 text-right">
                                    {{ log.attempts }} attempt{{ log.attempts !== 1 ? 's' : '' }}
                                </span>
                                <span class="text-xs text-slate-400 flex-shrink-0 w-36 text-right">{{ log.created_at }}</span>
                            </button>

                            <div
                                v-if="expandedLog === log.id"
                                class="px-5 py-4 bg-slate-50 border-t border-slate-100 space-y-3"
                            >
                                <div>
                                    <p class="text-xs font-semibold text-slate-500 uppercase">Payload</p>
                                    <pre class="mt-1 text-xs text-slate-700 bg-white border border-slate-200 rounded p-3 overflow-x-auto max-h-48">{{ formatPayload(log.payload) }}</pre>
                                </div>
                                <div v-if="log.response_body">
                                    <p class="text-xs font-semibold text-slate-500 uppercase">Response</p>
                                    <pre class="mt-1 text-xs text-slate-700 bg-white border border-slate-200 rounded p-3 overflow-x-auto max-h-48">{{ log.response_body }}</pre>
                                </div>
                            </div>
                        </div>

                        <div v-if="logs.data.length === 0" class="px-5 py-12 text-center">
                            <p class="text-sm text-slate-500">No webhook logs found.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-sm">
                        <p class="text-slate-500">
                            Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}
                        </p>
                        <div class="flex gap-1">
                            <a
                                v-for="link in logs.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded text-xs"
                                :class="link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                        ? 'text-slate-600 hover:bg-slate-100'
                                        : 'text-slate-300 cursor-default'
                                "
                                @click.prevent="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            ></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
