<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Invoice Defaults</h3>
                <p class="mt-0.5 text-xs text-gray-500">Used when creating new invoices.</p>
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
                        placeholder="Net 30"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.default_payment_terms" class="mt-1 text-xs text-red-600">
                        {{ form.errors.default_payment_terms }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">Branding & Payment Instructions</h3>
                <p class="mt-0.5 text-xs text-gray-500">Shown on invoices, PDFs, and emails.</p>
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
    </div>
</template>
