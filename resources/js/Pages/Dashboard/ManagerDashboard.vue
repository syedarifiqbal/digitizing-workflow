<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Placeholder for Manager Dashboard -->
        <div class="rounded-2xl bg-white shadow-lg shadow-slate-200/50 border border-slate-200 p-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Manager Dashboard</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Manager-specific dashboard coming soon. Similar to admin with team management focus.
                </p>
                <div class="mt-6">
                    <Link :href="route('orders.index')" class="inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110">
                        View Orders
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
