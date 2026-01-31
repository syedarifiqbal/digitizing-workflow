<script setup>
import AdminDashboard from './AdminDashboard.vue';

defineProps({
    stats: Object,
});
</script>

<template>
    <AdminDashboard :stats="stats" />
</template>
