<script setup>
import { computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
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
    sales_commission_earned_on: props.settings.sales_commission_earned_on ?? "delivered",
    designer_bonus_earned_on: props.settings.designer_bonus_earned_on ?? "delivered",
    enable_designer_tips: props.settings.enable_designer_tips ?? false,
    auto_assign_on_designer: props.settings.auto_assign_on_designer ?? true,
    auto_submit_on_upload: props.settings.auto_submit_on_upload ?? true,
    auto_review_on_submit: props.settings.auto_review_on_submit ?? false,
    allowed_input_extensions: props.settings.allowed_input_extensions ?? "",
    allowed_output_extensions: props.settings.allowed_output_extensions ?? "",
    max_upload_mb: props.settings.max_upload_mb ?? 25,
    currency: props.settings.currency ?? "USD",
    order_number_prefix: props.settings.order_number_prefix ?? "",
    show_order_cards: props.settings.show_order_cards ?? false,
    notify_on_assignment: props.settings.notify_on_assignment ?? true,
    api_enabled: props.settings.api_enabled ?? false,
    invoice_number_prefix: props.settings.invoice_number_prefix ?? "INV-",
    default_payment_terms: props.settings.default_payment_terms ?? "Net 30",
    default_tax_rate: props.settings.default_tax_rate ?? 0,
    company_details: {
        name: props.settings.company_details?.name ?? "",
        address: props.settings.company_details?.address ?? "",
        phone: props.settings.company_details?.phone ?? "",
        email: props.settings.company_details?.email ?? "",
    },
    bank_details: props.settings.bank_details ?? "",
});

const submit = () => {
    form.put(route("settings.update"));
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
const apiKeyMessage = computed(() => page.props.flash?.api_key);
const apiKeyLastFour = computed(() => props.settings.api_key_last_four ?? null);

const generateApiKey = () => {
    router.post(route("settings.api-key.generate"), {}, { preserveScroll: true });
};
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
                            <!-- Invoice Settings -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Invoice Settings</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Defaults applied when creating invoices.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="invoice_number_prefix">
                                                Invoice Prefix
                                            </label>
                                            <input
                                                v-model="form.invoice_number_prefix"
                                                id="invoice_number_prefix"
                                                type="text"
                                                maxlength="10"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="INV-"
                                            />
                                            <p class="mt-1 text-xs text-gray-500">Shown before the invoice number.</p>
                                            <p v-if="form.errors.invoice_number_prefix" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.invoice_number_prefix }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="default_tax_rate">
                                                Default Tax Rate (%)
                                            </label>
                                            <input
                                                v-model.number="form.default_tax_rate"
                                                id="default_tax_rate"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p class="mt-1 text-xs text-gray-400">Used unless overridden per invoice.</p>
                                            <p v-if="form.errors.default_tax_rate" class="mt-1 text-xs text-red-600">
                                                {{ form.errors.default_tax_rate }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700" for="default_payment_terms">
                                            Default Payment Terms
                                        </label>
                                        <input
                                            v-model="form.default_payment_terms"
                                            id="default_payment_terms"
                                            type="text"
                                            maxlength="255"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Net 30"
                                        />
                                        <p v-if="form.errors.default_payment_terms" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.default_payment_terms }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Branding & Payment -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Invoice Branding & Payment Instructions</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Shown on invoices/PDFs for clients.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="invoice_company_name">
                                                Company Name
                                            </label>
                                            <input
                                                v-model="form.company_details.name"
                                                id="invoice_company_name"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="invoice_company_email">
                                                Billing Email
                                            </label>
                                            <input
                                                v-model="form.company_details.email"
                                                id="invoice_company_email"
                                                type="email"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="invoice_company_phone">
                                                Phone
                                            </label>
                                            <input
                                                v-model="form.company_details.phone"
                                                id="invoice_company_phone"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700" for="invoice_company_address">
                                                Address
                                            </label>
                                            <textarea
                                                v-model="form.company_details.address"
                                                id="invoice_company_address"
                                                rows="2"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            ></textarea>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700" for="bank_details">
                                            Bank / Payment Instructions
                                        </label>
                                        <textarea
                                            v-model="form.bank_details"
                                            id="bank_details"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Bank: Example Bank&#10;Account: 123456789&#10;Routing: 987654321"
                                        ></textarea>
                                        <p v-if="form.errors.bank_details" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.bank_details }}
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

                        <!-- API Access -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">API Access</h3>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Manage intake API access for external systems.
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                                        :class="form.api_enabled ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :class="form.api_enabled ? 'bg-green-500' : 'bg-slate-400'"
                                        ></span>
                                        {{ form.api_enabled ? "Enabled" : "Disabled" }}
                                    </span>
                                </div>
                            </div>
                            <div class="px-5 py-4 space-y-4">
                                <div>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            v-model="form.api_enabled"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Enable intake API
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Requests with a valid API key can create clients and orders when this is enabled.
                                    </p>
                                    <p v-if="form.errors.api_enabled" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.api_enabled }}
                                    </p>
                                </div>

                                <div class="rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                                    <p class="font-medium text-slate-900">Current key</p>
                                    <p v-if="apiKeyLastFour" class="mt-1">
                                        Last generated key ends with <span class="font-semibold">•••• {{ apiKeyLastFour }}</span>
                                    </p>
                                    <p v-else class="mt-1">
                                        No API key has been generated yet.
                                    </p>
                                    <p class="mt-2 text-slate-500">
                                        Keys are shown only once at generation. Regenerating will immediately revoke the previous key.
                                    </p>
                                </div>

                                <div
                                    v-if="apiKeyMessage"
                                    class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs text-indigo-900 break-all"
                                >
                                    <p class="font-semibold">New API Key</p>
                                    <p class="mt-1">{{ apiKeyMessage }}</p>
                                    <p class="mt-2 font-medium">Copy this key now. It will not be shown again.</p>
                                </div>

                                <button
                                    type="button"
                                    :disabled="!form.api_enabled"
                                    @click="generateApiKey"
                                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-white transition"
                                    :class="form.api_enabled ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-300 cursor-not-allowed'"
                                >
                                    {{ apiKeyLastFour ? "Regenerate API Key" : "Generate API Key" }}
                                </button>
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
                                    <h3 class="text-sm font-semibold text-gray-900">Workflow Automation</h3>
                                    <p class="mt-0.5 text-xs text-gray-500">Configure automatic status transitions.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <!-- Auto-assign on designer -->
                                    <div class="border-b border-gray-100 pb-4">
                                        <div class="flex items-start">
                                            <input
                                                v-model="form.auto_assign_on_designer"
                                                id="auto_assign_on_designer"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="auto_assign_on_designer">
                                                Auto-transition to ASSIGNED when designer is assigned
                                            </label>
                                        </div>
                                        <p class="mt-1 ml-6 text-xs text-gray-400">Orders will automatically change from RECEIVED to ASSIGNED when a designer is assigned.</p>
                                        <p v-if="form.errors.auto_assign_on_designer" class="mt-1 ml-6 text-xs text-red-600">
                                            {{ form.errors.auto_assign_on_designer }}
                                        </p>
                                    </div>

                                    <!-- Auto-submit on upload -->
                                    <div class="border-b border-gray-100 pb-4">
                                        <div class="flex items-start">
                                            <input
                                                v-model="form.auto_submit_on_upload"
                                                id="auto_submit_on_upload"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="auto_submit_on_upload">
                                                Auto-transition to SUBMITTED when designer uploads work
                                            </label>
                                        </div>
                                        <p class="mt-1 ml-6 text-xs text-gray-400">When designer uploads output files, automatically change status from IN_PROGRESS to SUBMITTED.</p>
                                        <p v-if="form.errors.auto_submit_on_upload" class="mt-1 ml-6 text-xs text-red-600">
                                            {{ form.errors.auto_submit_on_upload }}
                                        </p>
                                    </div>

                                    <!-- Auto-review on submit -->
                                    <div>
                                        <div class="flex items-start">
                                            <input
                                                v-model="form.auto_review_on_submit"
                                                id="auto_review_on_submit"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="auto_review_on_submit">
                                                Auto-transition to IN_REVIEW when work is submitted
                                            </label>
                                        </div>
                                        <p class="mt-1 ml-6 text-xs text-gray-400">Automatically change status from SUBMITTED to IN_REVIEW (requires auto-submit to be enabled).</p>
                                        <p v-if="form.errors.auto_review_on_submit" class="mt-1 ml-6 text-xs text-red-600">
                                            {{ form.errors.auto_review_on_submit }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Commissions -->
                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-900">Commissions</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700" for="sales_commission_earned_on">
                                            Sales Commission Earned On
                                        </label>
                                        <select
                                            v-model="form.sales_commission_earned_on"
                                            id="sales_commission_earned_on"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="approved">Approved</option>
                                            <option value="delivered">Delivered</option>
                                        </select>
                                        <p class="mt-1.5 text-xs text-gray-400">When sales commission is considered earned.</p>
                                        <p v-if="form.errors.sales_commission_earned_on" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.sales_commission_earned_on }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700" for="designer_bonus_earned_on">
                                            Designer Bonus Earned On
                                        </label>
                                        <select
                                            v-model="form.designer_bonus_earned_on"
                                            id="designer_bonus_earned_on"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="approved">Approved</option>
                                            <option value="delivered">Delivered</option>
                                        </select>
                                        <p class="mt-1.5 text-xs text-gray-400">When designer bonus is considered earned.</p>
                                        <p v-if="form.errors.designer_bonus_earned_on" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.designer_bonus_earned_on }}
                                        </p>
                                    </div>
                                    <div class="pt-2">
                                        <div class="flex items-start">
                                            <input
                                                v-model="form.enable_designer_tips"
                                                id="enable_designer_tips"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="ml-2 block text-sm text-gray-700" for="enable_designer_tips">
                                                Enable designer tips on delivery
                                            </label>
                                        </div>
                                        <p class="mt-1.5 text-xs text-gray-400">Allow admins to add optional tips for exceptional work when delivering orders.</p>
                                        <p v-if="form.errors.enable_designer_tips" class="mt-1 text-xs text-red-600">
                                            {{ form.errors.enable_designer_tips }}
                                        </p>
                                    </div>
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
