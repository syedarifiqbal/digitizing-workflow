<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showMobileMenu = ref(false);
const isSingleTenant = usePage().props.app?.is_single_tenant ?? false;
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Navigation -->
        <nav class="bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link href="/" class="text-xl font-bold text-slate-800">
                                Digitizing Workflow
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div v-if="!isSingleTenant" class="hidden sm:ml-10 sm:flex sm:space-x-8">
                            <Link
                                href="/features"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Features
                            </Link>
                            <Link
                                href="/pricing"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Pricing
                            </Link>
                            <Link
                                href="/contact"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Contact
                            </Link>
                        </div>
                    </div>

                    <!-- Auth Links -->
                    <div v-if="!isSingleTenant" class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-4">
                        <Link
                            href="/login"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700"
                        >
                            Login
                        </Link>
                        <Link
                            href="/register"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            Start Free
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div v-if="!isSingleTenant" class="-mr-2 flex items-center sm:hidden">
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100"
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
            <div v-if="!isSingleTenant" :class="{ block: showMobileMenu, hidden: !showMobileMenu }" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <Link
                        href="/features"
                        class="block pl-3 pr-4 py-2 text-base font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Features
                    </Link>
                    <Link
                        href="/pricing"
                        class="block pl-3 pr-4 py-2 text-base font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Pricing
                    </Link>
                    <Link
                        href="/contact"
                        class="block pl-3 pr-4 py-2 text-base font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Contact
                    </Link>
                </div>
                <div class="pt-4 pb-3 border-t border-slate-200">
                    <div class="space-y-1">
                        <Link
                            href="/login"
                            class="block pl-3 pr-4 py-2 text-base font-medium text-slate-600 hover:bg-slate-50"
                        >
                            Login
                        </Link>
                        <Link
                            href="/register"
                            class="block pl-3 pr-4 py-2 text-base font-medium text-indigo-600 hover:bg-slate-50"
                        >
                            Start Free
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer v-if="!isSingleTenant" class="bg-slate-50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 tracking-wider uppercase">Product</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/features" class="text-sm text-slate-600 hover:text-slate-900">Features</Link></li>
                            <li><Link href="/pricing" class="text-sm text-slate-600 hover:text-slate-900">Pricing</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 tracking-wider uppercase">Company</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/contact" class="text-sm text-slate-600 hover:text-slate-900">Contact</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 tracking-wider uppercase">Account</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/login" class="text-sm text-slate-600 hover:text-slate-900">Login</Link></li>
                            <li><Link href="/register" class="text-sm text-slate-600 hover:text-slate-900">Register</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 border-t border-slate-200 pt-8">
                    <p class="text-sm text-slate-500 text-center">
                        &copy; {{ new Date().getFullYear() }} Digitizing Workflow. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
