<script setup>
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from '@inertiajs/vue3';

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
    clientEmails: {
        type: Array,
        default: () => [],
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

const isDraft = computed(() => props.invoice.status === "draft");
const canRecordPayment = computed(() =>
    ["sent", "partially_paid", "overdue"].includes(props.invoice.status)
);
const canMarkPaid = computed(() =>
    ["sent", "partially_paid", "overdue"].includes(props.invoice.status)
);
const canCancel = computed(() =>
    ["draft", "sent"].includes(props.invoice.status)
);
const canVoid = computed(() =>
    ["sent", "partially_paid", "overdue", "paid"].includes(
        props.invoice.status
    )
);
const paidAmount = computed(() => Number(props.invoice.paid_amount ?? 0));
const balanceDue = computed(() => Number(props.invoice.balance_due ?? 0));

const actionModal = ref(null);
const actionRouteMap = {
    cancel: "invoices.cancel",
    void: "invoices.void",
    markPaid: "invoices.mark-paid",
};

const openActionModal = (type) => {
    actionModal.value = type;
};

const closeActionModal = () => {
    actionModal.value = null;
};

const performAction = () => {
    if (!actionModal.value) return;
    const routeName = actionRouteMap[actionModal.value];
    router.post(route(routeName, props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            closeActionModal();
        },
    });
};

const actionMessages = {
    cancel: {
        title: "Cancel invoice",
        body: "This will cancel the invoice and release all associated orders.",
        confirm: "Cancel invoice",
    },
    void: {
        title: "Void invoice",
        body: "Voiding will keep a record but prevent further payments.",
        confirm: "Void invoice",
    },
    markPaid: {
        title: "Mark as paid",
        body: "Confirm that this invoice has been fully paid?",
        confirm: "Mark paid",
    },
};

const showPaymentModal = ref(false);
const showSendModal = ref(false);
const openPaymentModal = () => {
    paymentForm.reset();
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
    showPaymentModal.value = true;
};
const closePaymentModal = () => {
    showPaymentModal.value = false;
};

const openSendModal = () => {
    sendForm.reset();
    sendForm.attach_pdf = true;
    showSendModal.value = true;
};
const closeSendModal = () => {
    showSendModal.value = false;
};

const paymentForm = useForm({
    amount: "",
    payment_method: "",
    payment_date: new Date().toISOString().slice(0, 10),
    reference: "",
    notes: "",
});

const sendForm = useForm({
    message: "",
    attach_pdf: true,
    email_recipients: [],
});

// Pre-select primary email when opening send modal
const openSendModalWithRecipients = () => {
    sendForm.reset();
    sendForm.attach_pdf = true;
    const primary = props.clientEmails.find(e => e.is_primary);
    if (primary) {
        sendForm.email_recipients = [primary.email];
    } else if (props.clientEmails.length) {
        sendForm.email_recipients = [props.clientEmails[0].email];
    }
    showSendModal.value = true;
};

const toggleSendRecipient = (email) => {
    const idx = sendForm.email_recipients.indexOf(email);
    if (idx === -1) {
        sendForm.email_recipients.push(email);
    } else {
        sendForm.email_recipients.splice(idx, 1);
    }
};

const submitPayment = () => {
    paymentForm.post(route("invoices.payments.store", props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset();
            closePaymentModal();
        },
    });
};

const submitSend = () => {
    sendForm.post(route("invoices.send", props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeSendModal();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Invoice ${invoice.number}`" />
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
                    <Button
                        :href="route('invoices.pdf', invoice.id)"
                        target="_blank"
                        rel="noopener"
                        >
                        Download PDF
                    </Button>
                    <Button
                        :href="route('invoices.index')"
                    >
                        Back to list
                    </Button>
                    <Button
                        v-if="canEdit"
                        variant="primary"
                        :href="route('invoices.edit', invoice.id)"
                    >
                        Edit Invoice
                    </Button>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <Button
                    v-if="isDraft"
                    as="button"
                    @click="openSendModalWithRecipients"
                    variant="primary"
                >
                    Send Invoice
                </Button>
                <Button
                    v-if="canRecordPayment"
                    as="button"
                    class="!text-slate-700 hover:bg-slate-50"
                    @click="openPaymentModal"
                >
                    Record Payment
                </Button>
                <Button
                    v-if="canMarkPaid"
                    as="button"
                    class="!text-slate-700 hover:bg-slate-50"
                    @click="openActionModal('markPaid')"
                >
                    Mark as Paid
                </Button>
                <Button
                    v-if="canCancel"
                    as="button"
                    class="!text-red-600 hover:bg-red-50"
                    @click="openActionModal('cancel')"
                >
                    Cancel Invoice
                </Button>
                <Button
                    v-if="canVoid"
                    as="button"
                    class="!text-amber-600 hover:bg-amber-50"
                    @click="openActionModal('void')"
                >
                    Void Invoice
                </Button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="grid gap-6 p-6 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">
                                {{ invoice.currency }} {{ Number(invoice.total_amount ?? 0).toFixed(2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paid</p>
                            <p class="mt-2 text-2xl font-semibold text-green-600">
                                {{ invoice.currency }} {{ paidAmount.toFixed(2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Balance</p>
                            <p
                                class="mt-2 text-2xl font-semibold"
                                :class="balanceDue <= 0 ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ invoice.currency }} {{ balanceDue.toFixed(2) }}
                            </p>
                        </div>
                    </div>
                </div>

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

                <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Payments</h3>
                            <p class="text-xs text-slate-500">History of payments recorded for this invoice.</p>
                        </div>
                        <button
                            v-if="canRecordPayment"
                            type="button"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                            @click="openPaymentModal"
                        >
                            Record Payment
                        </button>
                    </div>
                    <div v-if="!invoice.payments?.length" class="px-6 py-5 text-sm text-slate-500">
                        No payments recorded yet.
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="payment in invoice.payments"
                            :key="payment.id"
                            class="px-6 py-4 flex flex-col gap-1"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <p class="font-semibold text-slate-900">
                                    {{ invoice.currency }} {{ Number(payment.amount ?? 0).toFixed(2) }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ payment.payment_date ?? '—' }}
                                </p>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ payment.payment_method }}
                                <span v-if="payment.reference"> • Ref: {{ payment.reference }}</span>
                            </p>
                            <p v-if="payment.notes" class="text-xs text-slate-400">
                                {{ payment.notes }}
                            </p>
                            <p v-if="payment.recorded_by" class="text-xs text-slate-400">
                                Recorded by {{ payment.recorded_by }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="!!actionModal"
            :title="actionMessages[actionModal]?.title ?? ''"
            :message="actionMessages[actionModal]?.body ?? ''"
            :confirm-label="actionMessages[actionModal]?.confirm ?? 'Confirm'"
            cancel-label="Keep Invoice"
            variant="primary"
            @close="closeActionModal"
            @confirm="performAction"
        />

        <Modal :show="showSendModal" max-width="lg" @close="closeSendModal">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Send Invoice</h3>
                    <button class="text-slate-400 hover:text-slate-600" @click="closeSendModal">&times;</button>
                </div>

                <!-- Email Recipients -->
                <div v-if="clientEmails && clientEmails.length" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Send To</label>
                    <div class="space-y-1.5">
                        <label
                            v-for="entry in clientEmails"
                            :key="entry.email"
                            class="flex items-center gap-3 cursor-pointer rounded-md px-2 py-1 hover:bg-slate-100"
                        >
                            <input
                                type="checkbox"
                                :checked="sendForm.email_recipients.includes(entry.email)"
                                @change="toggleSendRecipient(entry.email)"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-slate-800">{{ entry.email }}</span>
                            <span v-if="entry.label" class="rounded-full bg-slate-200 px-1.5 py-0.5 text-xs text-slate-600">{{ entry.label }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Message</label>
                    <textarea
                        v-model="sendForm.message"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Optional message to include in the email"
                    ></textarea>
                    <p v-if="sendForm.errors.message" class="mt-1 text-xs text-red-600">
                        {{ sendForm.errors.message }}
                    </p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="sendForm.attach_pdf"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    Attach PDF invoice
                </label>
                <p v-if="sendForm.errors.pdf" class="text-xs text-red-600">
                    {{ sendForm.errors.pdf }}
                </p>
                <div class="flex justify-end gap-3">
                    <Button
                        as="button"
                        @click="closeSendModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        as="button"
                        variant="primary"
                        :disabled="sendForm.processing"
                        @click="submitSend"
                    >
                        {{ sendForm.processing ? "Sending..." : "Send Invoice" }}
                    </Button>
                </div>
            </div>
        </Modal>

        <Modal :show="showPaymentModal" max-width="lg" @close="closePaymentModal">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Record Payment</h3>
                    <button class="text-slate-400 hover:text-slate-600" @click="closePaymentModal">&times;</button>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Amount</label>
                        <input
                            v-model.number="paymentForm.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">
                            {{ paymentForm.errors.amount }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Payment Date</label>
                        <input
                            v-model="paymentForm.payment_date"
                            type="date"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="paymentForm.errors.payment_date" class="mt-1 text-xs text-red-600">
                            {{ paymentForm.errors.payment_date }}
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Payment Method</label>
                    <input
                        v-model="paymentForm.payment_method"
                        type="text"
                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Wire transfer, cash, etc."
                    />
                    <p v-if="paymentForm.errors.payment_method" class="mt-1 text-xs text-red-600">
                        {{ paymentForm.errors.payment_method }}
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Reference</label>
                        <input
                            v-model="paymentForm.reference"
                            type="text"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional reference"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Notes</label>
                        <input
                            v-model="paymentForm.notes"
                            type="text"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional note"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <Button
                        as="button"
                        @click="closePaymentModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        as="button"
                        variant="primary"
                        :disabled="paymentForm.processing"
                        @click="submitPayment"
                    >
                        {{ paymentForm.processing ? "Saving..." : "Save Payment" }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
