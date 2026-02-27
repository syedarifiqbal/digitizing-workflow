<script setup>
import { useDateFormat } from '@/Composables/useDateFormat';

const { formatDate } = useDateFormat();

defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

const getIcon = (type) => {
    switch (type) {
        case 'created': return 'plus';
        case 'status_change': return 'arrow';
        case 'assigned': return 'user';
        case 'unassigned': return 'user-minus';
        case 'activity': return 'activity';
        default: return 'dot';
    }
};

const getIconColor = (type) => {
    switch (type) {
        case 'created': return 'bg-slate-400';
        case 'status_change': return 'bg-indigo-500';
        case 'assigned': return 'bg-green-500';
        case 'unassigned': return 'bg-red-400';
        case 'activity': return 'bg-amber-400';
        default: return 'bg-slate-400';
    }
};
</script>

<template>
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            <li v-for="(event, index) in events" :key="index" class="relative pb-8">
                <!-- Connector line -->
                <span
                    v-if="index !== events.length - 1"
                    class="absolute left-3 top-6 -ml-px h-full w-0.5 bg-slate-200"
                    aria-hidden="true"
                ></span>

                <div class="relative flex items-start space-x-3">
                    <!-- Icon -->
                    <div class="relative">
                        <span
                            :class="[
                                getIconColor(event.type),
                                'flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white'
                            ]"
                        >
                            <!-- Plus icon -->
                            <svg v-if="getIcon(event.type) === 'plus'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <!-- Arrow icon -->
                            <svg v-else-if="getIcon(event.type) === 'arrow'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            <!-- User icon -->
                            <svg v-else-if="getIcon(event.type) === 'user'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <!-- User minus icon -->
                            <svg v-else-if="getIcon(event.type) === 'user-minus'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a7 7 0 00-7 7h14M23 11H17" />
                            </svg>
                            <!-- Activity / note icon -->
                            <svg v-else-if="getIcon(event.type) === 'activity'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Default dot -->
                            <span v-else class="h-2 w-2 rounded-full bg-white"></span>
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-900">
                            {{ event.description }}
                            <span v-if="event.user" class="font-medium text-slate-600">
                                &mdash; {{ event.user }}
                            </span>
                        </p>
                        <p v-if="event.notes" class="mt-0.5 text-sm text-slate-500 italic">
                            "{{ event.notes }}"
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ formatDate(event.timestamp, true) }}
                        </p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
