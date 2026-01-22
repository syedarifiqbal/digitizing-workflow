<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        default: null,
    },
    links: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'results',
    },
});

const summary = computed(() => {
    const meta = props.meta;
    if (!meta) {
        return null;
    }

    const total = meta.total ?? 0;
    const perPage = meta.per_page ?? (meta.to && meta.from ? meta.to - meta.from + 1 : total);
    const from = meta.from ?? (total > 0 ? 1 : 0);
    const to = meta.to ?? (total > 0 ? Math.min(total, from + Math.max(perPage - 1, 0)) : 0);

    return {
        total,
        from,
        to,
    };
});

const hasLinks = computed(() => Array.isArray(props.links) && props.links.length > 0);
</script>

<template>
    <div v-if="summary || hasLinks" class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p v-if="summary" class="text-sm text-gray-600">
            Showing
            <span class="font-medium text-gray-900">{{ summary.from }}</span>
            -
            <span class="font-medium text-gray-900">{{ summary.to }}</span>
            of
            <span class="font-medium text-gray-900">{{ summary.total }}</span>
            {{ label }}
        </p>

        <div v-if="hasLinks" class="flex flex-wrap gap-2">
            <template v-for="link in links" :key="link.url ?? link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    class="rounded border px-3 py-1 text-sm"
                    :class="link.active
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-600'
                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                />
                <span
                    v-else
                    v-html="link.label"
                    class="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400"
                />
            </template>
        </div>
    </div>
</template>
