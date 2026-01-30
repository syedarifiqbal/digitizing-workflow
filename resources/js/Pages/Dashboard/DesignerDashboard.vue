<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Placeholder for Designer Dashboard -->
        <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200 p-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Designer Dashboard</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Designer-specific dashboard coming soon. Track your assigned orders, submissions, and earnings.
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <Link :href="route('designer.dashboard')" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110">
                        My Work
                    </Link>
                    <Link :href="route('commissions.my')" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        My Earnings
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
