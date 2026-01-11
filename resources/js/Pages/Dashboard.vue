<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const user = page.props.auth?.user;
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            Welcome back, {{ user?.name }}!
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Here's what's happening with your orders.
                        </p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Total Orders</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900">0</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">In Progress</div>
                            <div class="mt-1 text-3xl font-semibold text-yellow-600">0</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Pending Review</div>
                            <div class="mt-1 text-3xl font-semibold text-blue-600">0</div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-sm font-medium text-gray-500">Delivered</div>
                            <div class="mt-1 text-3xl font-semibold text-green-600">0</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="flex flex-wrap gap-4">
                            <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                New Order
                            </button>
                            <button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                View All Orders
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
