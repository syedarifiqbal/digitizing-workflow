<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';

const showMobileMenu = ref(false);
const showUserMenu = ref(false);

const page = usePage();
const user = page.props.auth?.user;

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route('logout'));
};
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
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none"
                            >
                                <span>{{ user?.name }}</span>
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                v-show="showUserMenu"
                                @click="showUserMenu = false"
                                class="fixed inset-0 z-10"
                            ></div>

                            <div
                                v-show="showUserMenu"
                                class="absolute right-0 z-20 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                            >
                                <button
                                    @click="logout"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Logout
                                </button>
                            </div>
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
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ user?.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ user?.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <button
                            @click="logout"
                            class="block w-full text-left px-4 py-2 text-base font-medium text-gray-600 hover:bg-gray-50"
                        >
                            Logout
                        </button>
                    </div>
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
