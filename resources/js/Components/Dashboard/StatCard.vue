<script setup>
defineProps({
    label: String,
    value: [String, Number],
    icon: [Object, Function],
    gradient: { type: String, default: 'from-indigo-500 to-purple-600' },
    secondaryText: { type: String, default: '' },
    secondaryClass: { type: String, default: 'text-green-600' },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/50 border border-slate-200">
        <dt>
            <div class="absolute rounded-xl bg-gradient-to-br p-3" :class="gradient">
                <component :is="icon" class="h-6 w-6 text-white" />
            </div>
            <p class="ml-16 truncate text-sm font-medium text-slate-500">{{ label }}</p>
        </dt>
        <dd class="ml-16 flex items-baseline">
            <p class="text-2xl font-bold text-slate-900">{{ value }}</p>
            <p v-if="secondaryText" class="ml-2 flex items-baseline text-sm font-medium" :class="secondaryClass">
                {{ secondaryText }}
            </p>
        </dd>
    </div>
</template>
