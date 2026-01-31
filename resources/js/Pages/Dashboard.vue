<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import AdminDashboard from "@/Pages/Dashboard/AdminDashboard.vue";
import ManagerDashboard from "@/Pages/Dashboard/ManagerDashboard.vue";
import DesignerDashboard from "@/Pages/Dashboard/DesignerDashboard.vue";
import SalesDashboard from "@/Pages/Dashboard/SalesDashboard.vue";
import { roleTitle } from "../Utils/Helper.js";

const props = defineProps({
    role: {
        type: String,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Map role to component
const dashboardComponent = computed(() => {
    const roleMap = {
        admin: AdminDashboard,
        manager: ManagerDashboard,
        designer: DesignerDashboard,
        sales: SalesDashboard,
    };
    return roleMap[props.role] || AdminDashboard;
});

// Role-specific greeting
const roleGreeting = computed(() => {
    const greetings = {
        admin: "Here's your business overview.",
        manager: "Here's your team overview.",
        designer: "Here's your work summary.",
        sales: "Here's your sales overview.",
    };
    return greetings[props.role] || "Here's what's happening.";
});
</script>

<template>
    <AppLayout>
        <Head :title="`${roleTitle(props.role)} Dashboard`" />

        <template #header>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Welcome back, {{ user?.name }}!
                </h2>
                <p class="mt-1 text-sm text-slate-500">{{ roleGreeting }}</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <component :is="dashboardComponent" :stats="stats" />
        </div>
    </AppLayout>
</template>
