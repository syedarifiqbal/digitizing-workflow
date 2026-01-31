<script setup>
defineProps({
    label: String,
    value: [String, Number],
    icon: [Object, Function],
    iconColor: { type: String, default: 'text-slate-500' },
    valueClass: { type: String, default: 'text-slate-900' },
});
</script>

<template>
    <div class="rounded-2xl bg-white p-5 shadow-md shadow-slate-200/50 border border-slate-200">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <component :is="icon" class="h-8 w-8" :class="iconColor" />
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="text-xl font-bold" :class="valueClass">{{ value }}</p>
            </div>
        </div>
    </div>
</template>
