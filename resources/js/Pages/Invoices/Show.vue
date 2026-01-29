<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    companyDetails: {
        type: Object,
        default: () => ({}),
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

const statusBadgeClass = (status) => {
    switch (status) {
        case "draft":
            return "bg-slate-100 text-slate-600";
        case "sent":
            return "bg-blue-100 text-blue-700";
        case "paid":
            return "bg-green-100 text-green-700";
        case "overdue":
            return "bg-red-100 text-red-700";
        case "partially_paid":
            return "bg-amber-100 text-amber-700";
        default:
            return "bg-slate-100 text-slate-600";
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">Invoice</p>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        {{ invoice.number }}
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                        :class="statusBadgeClass(invoice.status)"
                    >
                        {{ invoice.status_label }}
                    </span>
                    <Link
                        :href="route('invoices.index')"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to list
                    </Link>
                    <Link
                        v-if="canEdit"
                        :href="route('invoices.edit', invoice.id)"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700"
                    >
                        Edit Invoice
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">From</h3>
                            <p class="mt-2 text-sm text-slate-900">
                                {{ companyDetails?.name || "Your Company" }}
                            </p>
                            <p class="text-sm text-slate-500 whitespace-pre-line">
                                {{ companyDetails?.address }}
                            </p>
                            <p v-if="companyDetails?.phone" class="text-sm text-slate-500">
                                {{ companyDetails.phone }}
                            </p>
                            <p v-if="companyDetails?.email" class="text-sm text-slate-500">
                                {{ companyDetails.email }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Bill To</h3>
                            <p class="mt-2 text-sm text-slate-900">
                                {{ invoice.client?.name || "Client" }}
                            </p>
                            <p v-if="invoice.client?.company" class="text-sm text-slate-500">
                                {{ invoice.client.company }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Dates</h3>
                            <dl class="mt-2 space-y-2 text-sm text-slate-900">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Issue Date</dt>
                                    <dd>{{ invoice.issue_date ?? "—" }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Due Date</dt>
                                    <dd>{{ invoice.due_date ?? "—" }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Payment Terms</h3>
                            <p class="mt-2 text-sm text-slate-900">
                                {{ invoice.payment_terms || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Line Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-700">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide text-xs text-slate-500">Description</th>
                                    <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-xs text-slate-500">Qty</th>
                                    <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-xs text-slate-500">Unit</th>
                                    <th class="px-4 py-3 text-right font-semibold uppercase tracking-wide text-xs text-slate-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in invoice.items" :key="item.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900">
                                            {{ item.description }}
                                        </div>
                                        <div v-if="item.order_number" class="text-xs text-slate-500">
                                            Order: {{ item.order_number }}
                                        </div>
                                        <div v-if="item.note" class="text-xs text-slate-400">
                                            Note: {{ item.note }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ Number(item.quantity ?? 0).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        {{ invoice.currency }} {{ Number(item.unit_price ?? 0).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        {{ invoice.currency }} {{ Number(item.amount ?? 0).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100">
                        <dl class="space-y-2 text-sm text-slate-700">
                            <div class="flex justify-between">
                                <dt>Subtotal</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ invoice.currency }} {{ Number(invoice.subtotal ?? 0).toFixed(2) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Tax ({{ Number(invoice.tax_rate ?? 0).toFixed(2) }}%)</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ invoice.currency }} {{ Number(invoice.tax_amount ?? 0).toFixed(2) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Discount</dt>
                                <dd class="font-medium text-slate-900">
                                    - {{ invoice.currency }} {{ Number(invoice.discount_amount ?? 0).toFixed(2) }}
                                </dd>
                            </div>
                            <div class="flex justify-between text-base font-semibold text-slate-900">
                                <dt>Total</dt>
                                <dd>
                                    {{ invoice.currency }} {{ Number(invoice.total_amount ?? 0).toFixed(2) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div v-if="invoice.notes" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Notes</h3>
                    </div>
                    <div class="px-6 py-4 text-sm text-slate-700 whitespace-pre-line">
                        {{ invoice.notes }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
