<script setup>
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.tenant.name ?? "",
    email_verification_required:
        props.settings.email_verification_required ?? true,
    date_format: props.settings.date_format ?? "MM/DD/YYYY",
    commission_earned_on: props.settings.commission_earned_on ?? "delivered",
    allowed_input_extensions: props.settings.allowed_input_extensions ?? "",
    allowed_output_extensions: props.settings.allowed_output_extensions ?? "",
    max_upload_mb: props.settings.max_upload_mb ?? 25,
    currency: props.settings.currency ?? "USD",
    order_number_prefix: props.settings.order_number_prefix ?? "",
    show_order_cards: props.settings.show_order_cards ?? false,
    notify_on_assignment: props.settings.notify_on_assignment ?? true,
});

const submit = () => {
    form.put(route("settings.update"));
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-gray-900">Settings</h2>
                <p class="text-sm text-gray-500">
                    Configure company-wide preferences for workflows, uploads, and billing.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div
                    v-if="successMessage"
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ successMessage }}
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">

                            <!-- General -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">General</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Basic company information and preferences.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="name">
                                                Company Name
                                            </label>
                                            <input
                                                v-model="form.name"
                                                id="name"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.name }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="date_format">
                                                Date Format
                                            </label>
                                            <select
                                                v-model="form.date_format"
                                                id="date_format"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                                <option value="DD-MM-YYYY">DD-MM-YYYY</option>
                                                <option value="DD.MM.YYYY">DD.MM.YYYY</option>
                                            </select>
                                            <p v-if="form.errors.date_format" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.date_format }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-3 pt-2">
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.email_verification_required"
                                                id="email_verification_required"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="email_verification_required">
                                                Require email verification for non-admin users
                                            </label>
                                        </div>
                                        <p v-if="form.errors.email_verification_required" class="text-xs text-red-600">
                                            {{ form.errors.email_verification_required }}
                                        </p>

                                        <div class="flex items-center">
                                            <input
                                                v-model="form.show_order_cards"
                                                id="show_order_cards"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="show_order_cards">
                                                Show quick action cards on Orders dashboard
                                            </label>
                                        </div>
                                        <p v-if="form.errors.show_order_cards" class="text-xs text-red-600">
                                            {{ form.errors.show_order_cards }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- File Uploads -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">File Uploads</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Control allowed file types and size limits.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="allowed_input_extensions">
                                                Allowed Input Extensions
                                            </label>
                                            <input
                                                v-model="form.allowed_input_extensions"
                                                id="allowed_input_extensions"
                                                type="text"
                                                placeholder="jpg,jpeg,png,pdf"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p class="mt-1 text-xs text-gray-400">Comma-separated, no dots</p>
                                            <p v-if="form.errors.allowed_input_extensions" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.allowed_input_extensions }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="allowed_output_extensions">
                                                Allowed Output Extensions
                                            </label>
                                            <input
                                                v-model="form.allowed_output_extensions"
                                                id="allowed_output_extensions"
                                                type="text"
                                                placeholder="dst,emb,pes,exp,pdf"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p class="mt-1 text-xs text-gray-400">Comma-separated, no dots</p>
                                            <p v-if="form.errors.allowed_output_extensions" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.allowed_output_extensions }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="sm:w-1/3">
                                        <label class="block text-xs font-medium text-gray-700" for="max_upload_mb">
                                            Max Upload Size (MB)
                                        </label>
                                        <input
                                            v-model.number="form.max_upload_mb"
                                            id="max_upload_mb"
                                            type="number"
                                            min="1"
                                            max="100"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.max_upload_mb" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.max_upload_mb }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Financial</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Currency and order numbering preferences.</p>
                                </div>
                                <div class="px-5 py-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="currency">
                                                Currency
                                            </label>
                                            <input
                                                v-model="form.currency"
                                                id="currency"
                                                type="text"
                                                maxlength="3"
                                                class="mt-1 block w-32 rounded-md border-gray-300 text-sm shadow-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.currency }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="order_number_prefix">
                                                Order Number Prefix
                                            </label>
                                            <input
                                                v-model="form.order_number_prefix"
                                                id="order_number_prefix"
                                                type="text"
                                                maxlength="10"
                                                placeholder="e.g. ORD-"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.order_number_prefix" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.order_number_prefix }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">

                            <!-- Workflow -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Workflow</h3>
                                </div>
                                <div class="px-5 py-4">
                                    <label class="block text-xs font-medium text-gray-700" for="commission_earned_on">
                                        Commission Earned On
                                    </label>
                                    <select
                                        v-model="form.commission_earned_on"
                                        id="commission_earned_on"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="approved">Approved</option>
                                        <option value="delivered">Delivered</option>
                                    </select>
                                    <p class="mt-1.5 text-xs text-gray-400">When the designer's commission is considered earned.</p>
                                    <p v-if="form.errors.commission_earned_on" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.commission_earned_on }}
                                    </p>
                                </div>
                            </div>

                            <!-- Notifications -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                </div>
                                <div class="px-5 py-4">
                                    <div class="flex items-start">
                                        <input
                                            v-model="form.notify_on_assignment"
                                            id="notify_on_assignment"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label class="ml-2 block text-sm text-gray-700" for="notify_on_assignment">
                                            Email on assignment
                                        </label>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-400">Notify designers when orders are assigned to them.</p>
                                    <p v-if="form.errors.notify_on_assignment" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.notify_on_assignment }}
                                    </p>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full inline-flex justify-center items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                    >
                                        {{ form.processing ? 'Saving...' : 'Save Settings' }}
                                    </button>
                                    <p v-if="form.recentlySuccessful" class="mt-2 text-center text-xs text-green-600">
                                        Settings saved.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
