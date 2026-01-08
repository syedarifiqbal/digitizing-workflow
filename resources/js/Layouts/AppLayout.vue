<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showMobileMenu = ref(false);

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-xl font-bold text-gray-800">
                                Digitizing Workflow
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                                :class="route().current('dashboard')
                                    ? 'border-indigo-500 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            >
                                Dashboard
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="relative">
                            <span class="text-sm text-gray-700">
                                {{ page.props.auth?.user?.name ?? 'Guest' }}
                            </span>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showMobileMenu, 'inline-flex': !showMobileMenu }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showMobileMenu, 'inline-flex': showMobileMenu }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div :class="{ block: showMobileMenu, hidden: !showMobileMenu }" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <Link
                        :href="route('dashboard')"
                        class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium"
                        :class="route().current('dashboard')
                            ? 'border-indigo-500 text-indigo-700 bg-indigo-50'
                            : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
