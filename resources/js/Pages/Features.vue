<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <!-- Header -->
        <div class="bg-indigo-600 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl font-extrabold text-white">Features</h1>
                <p class="mt-4 text-xl text-indigo-100">Everything you need to manage your digitizing business</p>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Order Management -->
                <div class="mb-16">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Order Management</h2>
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Order Tracking</h3>
                            <p class="mt-2 text-slate-500">Track every order through its lifecycle with status updates and timeline visibility.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Priority & Due Dates</h3>
                            <p class="mt-2 text-slate-500">Set priorities and due dates to ensure timely delivery of all orders.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Audit Timeline</h3>
                            <p class="mt-2 text-slate-500">Complete history of every action taken on an order for accountability.</p>
                        </div>
                    </div>
                </div>

                <!-- Team Management -->
                <div class="mb-16">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Team Management</h2>
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Designer Assignment</h3>
                            <p class="mt-2 text-slate-500">Assign orders to designers and track who is working on what.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Role-Based Access</h3>
                            <p class="mt-2 text-slate-500">Admin, Manager, Designer, and Client roles with appropriate permissions.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Commission Tracking</h3>
                            <p class="mt-2 text-slate-500">Flexible commission rules: fixed, percentage, or hybrid per designer.</p>
                        </div>
                    </div>
                </div>

                <!-- Workflow -->
                <div class="mb-16">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Workflow & Delivery</h2>
                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Versioned Submissions</h3>
                            <p class="mt-2 text-slate-500">Each submission creates a new version (v1, v2, v3...) for clear history.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Revision Requests</h3>
                            <p class="mt-2 text-slate-500">Request revisions with notes and attachments, tracked in the timeline.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">File Delivery</h3>
                            <p class="mt-2 text-slate-500">Deliver files to clients with secure download links and email notifications.</p>
                        </div>
                    </div>
                </div>

                <!-- Integrations -->
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Integrations</h2>
                    <div class="grid gap-8 md:grid-cols-2">
                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">API Intake</h3>
                            <p class="mt-2 text-slate-500">Accept orders from your website via REST API. Perfect for WordPress or custom sites.</p>
                        </div>

                        <div class="border border-slate-200 rounded-lg p-6">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-slate-900">Webhooks</h3>
                            <p class="mt-2 text-slate-500">Receive real-time notifications when orders are delivered, closed, or cancelled.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="py-16 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-extrabold text-slate-900">Ready to get started?</h2>
                <p class="mt-4 text-lg text-slate-500">Start managing your digitizing orders today.</p>
                <div class="mt-8">
                    <a href="/register" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Start Free
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
