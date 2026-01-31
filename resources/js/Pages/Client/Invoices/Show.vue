<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    invoice: Object,
    items: Array,
    payments: Array,
    companyDetails: Object,
    bankDetails: String,
});

const statusBadgeClass = (status) => {
    const classes = {
        sent: "bg-blue-100 text-blue-700",
        paid: "bg-green-100 text-green-700",
        overdue: "bg-red-100 text-red-700",
        partially_paid: "bg-amber-100 text-amber-700",
        cancelled: "bg-slate-100 text-slate-600",
        void: "bg-slate-100 text-slate-600",
    };
    return classes[status] || "bg-slate-100 text-slate-600";
};

const formatAmount = (amount) => {
    return parseFloat(amount || 0).toFixed(2);
};

const totalPaid = computed(() => {
    return props.payments?.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0) || 0;
});

const balanceDue = computed(() => {
    return parseFloat(props.invoice?.balance_due || 0);
});

const isPaid = computed(() => props.invoice?.status === "paid");
const isOverdue = computed(() => props.invoice?.status === "overdue");
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('client.invoices.index')"
                            class="text-slate-400 hover:text-slate-600"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <h2 class="text-2xl font-semibold text-slate-900">
                            Invoice {{ invoice.invoice_number }}
                        </h2>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusBadgeClass(invoice.status)"
                        >
                            {{ invoice.status_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Issued {{ formatDate(invoice.issue_date) }}
                    </p>
                </div>
                <a
                    :href="route('client.invoices.pdf', invoice.id)"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-8">
            <!-- Overdue Warning -->
            <div v-if="isOverdue" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">
                        This invoice is overdue. Due date was {{ formatDate(invoice.due_date) }}.
                    </p>
                </div>
            </div>

            <!-- Invoice Summary -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="grid grid-cols-1 divide-y divide-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                    <div class="p-6">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Invoice Details</h3>
                        <dl class="mt-4 space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-600">Invoice Number</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ invoice.invoice_number }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-600">Issue Date</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ formatDate(invoice.issue_date) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-600">Due Date</dt>
                                <dd class="text-sm font-medium" :class="isOverdue ? 'text-red-600' : 'text-slate-900'">
                                    {{ formatDate(invoice.due_date) }}
                                </dd>
                            </div>
                            <div v-if="invoice.payment_terms" class="flex justify-between">
                                <dt class="text-sm text-slate-600">Payment Terms</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ invoice.payment_terms }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Amount Summary</h3>
                        <dl class="mt-4 space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-600">Subtotal</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ invoice.currency }} {{ formatAmount(invoice.subtotal) }}</dd>
                            </div>
                            <div v-if="parseFloat(invoice.tax_amount) > 0" class="flex justify-between">
                                <dt class="text-sm text-slate-600">Tax ({{ invoice.tax_rate }}%)</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ invoice.currency }} {{ formatAmount(invoice.tax_amount) }}</dd>
                            </div>
                            <div v-if="parseFloat(invoice.discount_amount) > 0" class="flex justify-between">
                                <dt class="text-sm text-slate-600">Discount</dt>
                                <dd class="text-sm font-medium text-green-600">-{{ invoice.currency }} {{ formatAmount(invoice.discount_amount) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-200 pt-3">
                                <dt class="text-sm font-semibold text-slate-900">Total</dt>
                                <dd class="text-sm font-bold text-slate-900">{{ invoice.currency }} {{ formatAmount(invoice.total_amount) }}</dd>
                            </div>
                            <div v-if="!isPaid" class="flex justify-between">
                                <dt class="text-sm font-semibold" :class="isOverdue ? 'text-red-600' : 'text-slate-900'">Balance Due</dt>
                                <dd class="text-sm font-bold" :class="isOverdue ? 'text-red-600' : 'text-slate-900'">
                                    {{ invoice.currency }} {{ formatAmount(balanceDue) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Line Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Description</th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="item in items" :key="item.id">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">{{ item.description }}</div>
                                    <div v-if="item.order" class="text-xs text-slate-500">
                                        Order: {{ item.order.order_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-slate-900">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-right text-sm text-slate-900">{{ invoice.currency }} {{ formatAmount(item.unit_price) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-slate-900">{{ invoice.currency }} {{ formatAmount(item.amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div v-if="payments?.length > 0" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Payment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Reference</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="payment in payments" :key="payment.id">
                                <td class="px-6 py-4 text-sm text-slate-900">{{ formatDate(payment.payment_date) }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900 capitalize">{{ payment.payment_method || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ payment.reference || '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-green-600">{{ invoice.currency }} {{ formatAmount(payment.amount) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-slate-900">Total Paid</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-green-600">{{ invoice.currency }} {{ formatAmount(totalPaid) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="invoice.notes" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Notes</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ invoice.notes }}</p>
                </div>
            </div>

            <!-- Bank Details -->
            <div v-if="bankDetails && !isPaid" class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Payment Instructions</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ bankDetails }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
