<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <!-- Header -->
        <div class="bg-indigo-600 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl font-extrabold text-white">Pricing</h1>
                <p class="mt-4 text-xl text-indigo-100">Simple, transparent pricing for your business</p>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-3">
                    <!-- Starter Plan -->
                    <div class="border border-gray-200 rounded-lg p-8">
                        <h3 class="text-lg font-medium text-gray-900">Starter</h3>
                        <p class="mt-2 text-gray-500">For small digitizing shops</p>
                        <p class="mt-4">
                            <span class="text-4xl font-extrabold text-gray-900">$29</span>
                            <span class="text-gray-500">/month</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Up to 3 users
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                100 orders/month
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                5GB storage
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Email support
                            </li>
                        </ul>
                        <a href="/register" class="mt-8 block w-full text-center px-6 py-3 border border-indigo-600 text-indigo-600 rounded-md hover:bg-indigo-50">
                            Get Started
                        </a>
                    </div>

                    <!-- Professional Plan -->
                    <div class="border-2 border-indigo-600 rounded-lg p-8 relative">
                        <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-0">
                            <span class="bg-indigo-600 text-white text-sm font-medium px-3 py-1 rounded-full">Popular</span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Professional</h3>
                        <p class="mt-2 text-gray-500">For growing businesses</p>
                        <p class="mt-4">
                            <span class="text-4xl font-extrabold text-gray-900">$79</span>
                            <span class="text-gray-500">/month</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Up to 10 users
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Unlimited orders
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                25GB storage
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                API access
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Priority support
                            </li>
                        </ul>
                        <a href="/register" class="mt-8 block w-full text-center px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Get Started
                        </a>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="border border-gray-200 rounded-lg p-8">
                        <h3 class="text-lg font-medium text-gray-900">Enterprise</h3>
                        <p class="mt-2 text-gray-500">For large operations</p>
                        <p class="mt-4">
                            <span class="text-4xl font-extrabold text-gray-900">Custom</span>
                        </p>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Unlimited users
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Unlimited orders
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Unlimited storage
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Custom integrations
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Dedicated support
                            </li>
                        </ul>
                        <a href="/contact" class="mt-8 block w-full text-center px-6 py-3 border border-indigo-600 text-indigo-600 rounded-md hover:bg-indigo-50">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="py-16 bg-gray-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
                <div class="space-y-6">
                    <div class="bg-white rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900">Can I try before I buy?</h3>
                        <p class="mt-2 text-gray-500">Yes! All plans come with a 14-day free trial. No credit card required.</p>
                    </div>
                    <div class="bg-white rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900">Can I change plans later?</h3>
                        <p class="mt-2 text-gray-500">Absolutely. You can upgrade or downgrade at any time. Changes take effect on your next billing cycle.</p>
                    </div>
                    <div class="bg-white rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900">What payment methods do you accept?</h3>
                        <p class="mt-2 text-gray-500">We accept all major credit cards and PayPal. Enterprise customers can pay via invoice.</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
