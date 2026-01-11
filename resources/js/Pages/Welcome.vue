<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const form = useForm({
    email: '',
});

const submitted = ref(false);

const subscribe = () => {
    form.post(route('newsletter.store'), {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
            form.reset();
        },
    });
};

const flash = usePage().props.flash;
</script>

<template>
    <PublicLayout>
        <!-- Hero Section -->
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                    <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                        <div class="sm:text-center lg:text-left">
                            <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                                <span class="block">Manage digitizing orders</span>
                                <span class="block text-indigo-600">from intake to delivery</span>
                            </h1>
                            <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                                A complete workflow system for digitizing and embroidery businesses. Track orders, manage designers, handle revisions, and deliver files — all in one place.
                            </p>
                            <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                                <div class="rounded-md shadow">
                                    <a href="/register" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                                        Start Free
                                    </a>
                                </div>
                                <div class="mt-3 sm:mt-0 sm:ml-3">
                                    <a href="/features" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 md:py-4 md:text-lg md:px-10">
                                        View Features
                                    </a>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>

        <!-- Workflow Section -->
        <div class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">
                        Simple, Clear Workflow
                    </h2>
                    <p class="mt-4 text-lg text-gray-500">
                        From order intake to final delivery, track every step.
                    </p>
                </div>

                <div class="mt-12">
                    <div class="flex flex-wrap justify-center gap-4">
                        <div class="flex items-center">
                            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Received</span>
                            <span class="mx-2 text-gray-400">→</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Assigned</span>
                            <span class="mx-2 text-gray-400">→</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">In Progress</span>
                            <span class="mx-2 text-gray-400">→</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">Submitted</span>
                            <span class="mx-2 text-gray-400">→</span>
                        </div>
                        <div class="flex items-center">
                            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">Delivered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">
                        Everything You Need
                    </h2>
                </div>

                <div class="mt-12 grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Order Tracking</h3>
                        <p class="mt-2 text-gray-500">Track every order from intake to delivery with full visibility.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Designer Assignment</h3>
                        <p class="mt-2 text-gray-500">Assign orders to designers and track their workload.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Revision Management</h3>
                        <p class="mt-2 text-gray-500">Handle revision requests with version history.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">File Delivery</h3>
                        <p class="mt-2 text-gray-500">Deliver files securely to clients with download links.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Commission Tracking</h3>
                        <p class="mt-2 text-gray-500">Calculate and track designer commissions automatically.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">API Integration</h3>
                        <p class="mt-2 text-gray-500">Connect your website to create orders automatically.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="py-16 bg-indigo-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-white">
                        Stay Updated
                    </h2>
                    <p class="mt-4 text-lg text-indigo-100">
                        Get notified about new features and updates.
                    </p>
                </div>

                <div class="mt-8 max-w-md mx-auto">
                    <form @submit.prevent="subscribe" class="sm:flex">
                        <label for="email" class="sr-only">Email address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            id="email"
                            required
                            class="w-full px-5 py-3 placeholder-gray-500 focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white focus:border-white rounded-md"
                            placeholder="Enter your email"
                        />
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="mt-3 sm:mt-0 sm:ml-3 w-full sm:w-auto flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white disabled:opacity-50"
                        >
                            Subscribe
                        </button>
                    </form>
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-200">{{ form.errors.email }}</p>
                    <p v-if="submitted" class="mt-2 text-sm text-green-200">Thank you for subscribing!</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Ready to streamline your workflow?
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Start managing your digitizing orders today.
                </p>
                <div class="mt-8">
                    <a href="/register" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                        Get Started Free
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
