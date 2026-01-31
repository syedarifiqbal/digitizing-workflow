<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    viewAllHref: { type: String, default: '' },
    headerIcon: { type: [Object, Function], default: null },
    badge: { type: [String, Number], default: null },
});
</script>

<template>
    <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200">
        <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center">
                <component v-if="headerIcon" :is="headerIcon" class="h-5 w-5 text-orange-500 mr-2" />
                <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="badge != null && badge !== ''" class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">
                    {{ badge }}
                </span>
                <Link v-if="viewAllHref" :href="viewAllHref" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all
                </Link>
            </div>
        </div>
        <slot />
    </div>
</template>
