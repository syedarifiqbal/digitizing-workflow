<script setup>
import axios from "axios";
import { computed, reactive, ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    mode: {
        type: String,
        default: "create",
    },
    invoice: {
        type: [Object, null],
        default: null,
    },
    clients: {
        type: Array,
        default: () => [],
    },
    defaults: {
        type: Object,
        default: () => ({
            payment_terms: "Net 30",
            tax_rate: 0,
            currency: "USD",
        }),
    },
    initialClientId: {
        type: [String, Number],
        default: "",
    },
    initialOrders: {
        type: Array,
        default: () => [],
    },
    prefilledOrderIds: {
        type: Array,
        default: () => [],
    },
    prefilledOrders: {
        type: Array,
        default: () => [],
    },
    initialOrderNotes: {
        type: Object,
        default: () => ({}),
    },
    initialCustomItems: {
        type: Array,
        default: () => [],
    },
});

const isEditMode = computed(() => props.mode === "edit");
const initialClient =
    props.invoice?.client_id ||
    props.initialClientId ||
    props.clients[0]?.id ||
    "";

const form = useForm({
    client_id: initialClient,
    issue_date: props.invoice?.issue_date ?? "",
    due_date: props.invoice?.due_date ?? "",
    payment_terms: props.invoice?.payment_terms ?? props.defaults.payment_terms ?? "",
    tax_rate: props.invoice?.tax_rate ?? props.defaults.tax_rate ?? 0,
    discount_amount: props.invoice?.discount_amount ?? 0,
    currency: props.invoice?.currency ?? props.defaults.currency ?? "USD",
    notes: props.invoice?.notes ?? "",
    order_ids: [],
    order_notes: {},
    custom_items:
        props.initialCustomItems?.length > 0
            ? props.initialCustomItems.map((item) => ({
                  description: item.description ?? "",
                  quantity: item.quantity ?? 1,
                  unit_price: item.unit_price ?? 0,
              }))
            : [],
});

const availableOrders = ref(props.initialOrders ?? []);
const selectedOrderIds = ref(
    props.prefilledOrderIds?.slice() ??
        (props.invoice?.selected_order_ids ?? [])
);
const orderNotes = reactive({ ...props.initialOrderNotes });
const orderLookup = reactive({});
const loadingOrders = ref(false);
const fetchError = ref("");

const cacheOrders = (orders = []) => {
    orders.forEach((order) => {
        orderLookup[order.id] = order;
    });
};

cacheOrders(availableOrders.value);
cacheOrders(props.prefilledOrders ?? []);

selectedOrderIds.value.forEach((id) => {
    if (!orderNotes[id]) {
        orderNotes[id] = "";
    }
});

const selectedOrders = computed(() =>
    selectedOrderIds.value
        .map((id) => orderLookup[id])
        .filter((order) => Boolean(order)),
);

const orderSubtotal = computed(() =>
    selectedOrders.value.reduce((total, order) => total + Number(order.price ?? 0), 0),
);

const customSubtotal = computed(() =>
    form.custom_items.reduce((total, item) => {
        const quantity = Number(item.quantity ?? 0);
        const unit = Number(item.unit_price ?? 0);
        return total + quantity * unit;
    }, 0),
);

const subtotal = computed(() => orderSubtotal.value + customSubtotal.value);
const taxAmount = computed(
    () => subtotal.value * (Number(form.tax_rate ?? 0) / 100),
);
const total = computed(() =>
    Math.max(subtotal.value + taxAmount.value - Number(form.discount_amount ?? 0), 0),
);

const fetchOrders = async (clientId) => {
    if (!clientId) {
        availableOrders.value = [];
        return;
    }

    loadingOrders.value = true;
    fetchError.value = "";

    try {
        const response = await axios.get(
            route("invoices.eligible-orders", {
                client_id: clientId,
                selected: selectedOrderIds.value,
                invoice_id: isEditMode.value ? props.invoice?.id : null,
            })
        );
        availableOrders.value = response.data.orders ?? [];
        cacheOrders(response.data.orders ?? []);
    } catch (error) {
        fetchError.value = "Unable to load eligible orders. Please try again.";
    } finally {
        loadingOrders.value = false;
    }
};

let initializedClientWatcher = false;
watch(
    () => form.client_id,
    async (newClient, oldClient) => {
        if (!initializedClientWatcher) {
            initializedClientWatcher = true;
            return;
        }
        selectedOrderIds.value = [];
        Object.keys(orderNotes).forEach((key) => {
            delete orderNotes[key];
        });
        await fetchOrders(newClient);
    },
);

const toggleOrderSelection = (orderId, checked) => {
    if (checked) {
        if (!selectedOrderIds.value.includes(orderId)) {
            selectedOrderIds.value.push(orderId);
            if (!orderNotes[orderId]) {
                orderNotes[orderId] = "";
            }
        }
    } else {
        selectedOrderIds.value = selectedOrderIds.value.filter((id) => id !== orderId);
        delete orderNotes[orderId];
    }
};

const addCustomItem = () => {
    form.custom_items.push({
        description: "",
        quantity: 1,
        unit_price: 0,
    });
};

const removeCustomItem = (index) => {
    form.custom_items.splice(index, 1);
};

const submit = () => {
    const cleanedNotes = {};
    selectedOrderIds.value.forEach((id) => {
        if (orderNotes[id] && orderNotes[id].trim().length) {
            cleanedNotes[id] = orderNotes[id].trim();
        }
    });

    const cleanedCustomItems = form.custom_items
        .map((item) => ({
            description: item.description?.trim() ?? "",
            quantity: Number(item.quantity ?? 0),
            unit_price: Number(item.unit_price ?? 0),
        }))
        .filter(
            (item) =>
                item.description.length ||
                item.quantity > 0 ||
                item.unit_price > 0,
        );

    if (isEditMode.value && props.invoice) {
        form.transform((data) => ({
            ...data,
            order_ids: [...selectedOrderIds.value],
            order_notes: cleanedNotes,
            custom_items: cleanedCustomItems,
        })).put(
            route("invoices.update", props.invoice.id),
            {
                preserveScroll: true,
                onFinish: () => {
                    form.transform((data) => data);
                },
            },
        );
    } else {
        form.transform((data) => ({
            ...data,
            order_ids: [...selectedOrderIds.value],
            order_notes: cleanedNotes,
            custom_items: cleanedCustomItems,
        })).post(route("invoices.store"), {
            preserveScroll: true,
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="`${isEditMode? 'Edit': 'Create'} Invoice`" />

        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        {{ isEditMode ? "Edit Invoice" : "New Invoice" }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        <template v-if="isEditMode">
                            Update draft invoices before sending them to your client.
                        </template>
                        <template v-else>
                            Select a client, pick delivered orders, and we’ll calculate totals automatically.
                        </template>
                    </p>
                </div>
                <Button :href="route('invoices.index')">
                    Back to Invoices
                </Button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-slate-900">Invoice Details</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700" for="client_id">
                                        Client
                                    </label>
                                    <select
                                        v-model="form.client_id"
                                        id="client_id"
                                        :disabled="isEditMode"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="" disabled>Select client</option>
                                        <option
                                            v-for="client in clients"
                                            :key="client.id"
                                            :value="client.id"
                                        >
                                            {{ client.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.client_id" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.client_id }}
                                    </p>
                                    <p v-if="isEditMode" class="mt-1 text-xs text-slate-500">
                                        Client cannot be changed after invoice creation.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700" for="payment_terms">
                                        Payment Terms
                                    </label>
                                    <input
                                        v-model="form.payment_terms"
                                        id="payment_terms"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. Net 30"
                                    />
                                    <p v-if="form.errors.payment_terms" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.payment_terms }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">
                                        Issue Date
                                    </label>
                                    <DatePicker v-model="form.issue_date" id="issue_date" placeholder="Select issue date" />
                                    <p v-if="form.errors.issue_date" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.issue_date }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">
                                        Due Date
                                    </label>
                                    <DatePicker v-model="form.due_date" id="due_date" placeholder="Select due date" />
                                    <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.due_date }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700" for="notes">
                                    Notes
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    id="notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Visible to client on the invoice"
                                ></textarea>
                                <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Eligible Orders</h3>
                                <p class="text-xs text-slate-500">
                                    Delivered/closed orders that haven’t been invoiced yet.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                @click="fetchOrders(form.client_id)"
                            >
                                Refresh
                            </button>
                        </div>
                        <div class="px-5 py-4">
                            <div v-if="loadingOrders" class="rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                Loading orders…
                            </div>
                            <div v-else-if="fetchError" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-600">
                                {{ fetchError }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-if="availableOrders.length === 0"
                                    class="rounded-md border border-slate-200 px-4 py-3 text-sm text-slate-500"
                                >
                                    No delivered orders available for the selected client.
                                </div>
                                <div
                                    v-else
                                    v-for="order in availableOrders"
                                    :key="order.id"
                                    class="rounded-md border border-slate-200 px-4 py-3 text-sm text-slate-700 flex flex-col gap-1"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                {{ order.order_number }} <span class="text-xs font-normal text-slate-400">({{ order.currency }} {{ Number(order.price ?? 0).toFixed(2) }})</span>
                                            </p>
                                            <p class="text-xs text-slate-500">{{ order.title }}</p>
                                            <p class="text-xs text-slate-400">
                                                Delivered {{ order.delivered_at ? new Date(order.delivered_at).toLocaleDateString() : "—" }}
                                            </p>
                                        </div>
                                        <div>
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="selectedOrderIds.includes(order.id)"
                                                @change="toggleOrderSelection(order.id, $event.target.checked)"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.order_ids" class="text-xs text-red-600">
                                    {{ form.errors.order_ids }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-slate-900">Selected Orders</h3>
                            <p class="text-xs text-slate-500">
                                Add optional notes for each order (shown on the invoice line item).
                            </p>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div
                                v-if="selectedOrders.length === 0"
                                class="rounded-md border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500"
                            >
                                No orders selected yet.
                            </div>
                            <div
                                v-else
                                v-for="order in selectedOrders"
                                :key="'selected-' + order.id"
                                class="rounded-md border border-slate-200 px-4 py-3 space-y-2"
                            >
                                <div class="flex items-center justify-between text-sm text-slate-900">
                                    <div>
                                        {{ order.order_number }}
                                        <span class="text-xs text-slate-500">{{ order.title }}</span>
                                    </div>
                                    <div class="font-semibold">
                                        {{ order.currency }} {{ Number(order.price ?? 0).toFixed(2) }}
                                    </div>
                                </div>
                                <textarea
                                    v-model="orderNotes[order.id]"
                                    rows="2"
                                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Optional note for this order (visible to client)"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Custom Adjustments</h3>
                                <p class="text-xs text-slate-500">
                                    Optional line items for fees, credits, or adjustments.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                @click="addCustomItem"
                            >
                                Add custom line
                            </button>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div
                                v-if="form.custom_items.length === 0"
                                class="rounded-md border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500"
                            >
                                No custom adjustments added yet.
                            </div>
                            <div
                                v-for="(item, index) in form.custom_items"
                                :key="'custom-' + index"
                                class="rounded-md border border-slate-200 p-4 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-slate-500">
                                        Custom Line {{ index + 1 }}
                                    </p>
                                    <button
                                        type="button"
                                        class="text-xs text-red-500 hover:text-red-700"
                                        @click="removeCustomItem(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700">
                                            Description
                                        </label>
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="e.g. Rush fee"
                                        />
                                        <p v-if="form.errors[`custom_items.${index}.description`]" class="mt-1 text-xs text-red-600">
                                            {{ form.errors[`custom_items.${index}.description`] }}
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700">
                                                Quantity
                                            </label>
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors[`custom_items.${index}.quantity`]" class="mt-1 text-xs text-red-600">
                                                {{ form.errors[`custom_items.${index}.quantity`] }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700">
                                                Unit Price ({{ form.currency }})
                                            </label>
                                            <input
                                                v-model.number="item.unit_price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors[`custom_items.${index}.unit_price`]" class="mt-1 text-xs text-red-600">
                                                {{ form.errors[`custom_items.${index}.unit_price`] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-slate-900">Totals</h3>
                        </div>
                        <div class="px-5 py-4 space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Tax Rate (%)</label>
                                    <input
                                        v-model.number="form.tax_rate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.tax_rate" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.tax_rate }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Discount ({{ form.currency }})</label>
                                    <input
                                        v-model.number="form.discount_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.discount_amount" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.discount_amount }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">Currency</label>
                                    <input
                                        v-model="form.currency"
                                        type="text"
                                        maxlength="3"
                                        class="mt-1 block w-full rounded-md border-slate-300 text-sm uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.currency }}
                                    </p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm text-slate-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span class="font-semibold">{{ form.currency }} {{ subtotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Tax</span>
                                    <span class="font-semibold">{{ form.currency }} {{ taxAmount.toFixed(2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Discount</span>
                                    <span class="font-semibold">- {{ form.currency }} {{ Number(form.discount_amount ?? 0).toFixed(2) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-base font-semibold text-slate-900">
                                    <span>Total</span>
                                    <span>{{ form.currency }} {{ total.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                        <div class="px-5 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-600">
                                You can send the invoice or record payments after saving this draft.
                            </p>
                            <div class="flex items-center gap-3">
                                <Button :href="route('invoices.index')">
                                    Cancel
                                </Button>
                                <Button
                                    as="button"
                                    html-type="submit"
                                    variant="primary"
                                    :disabled="form.processing"
                                >
                                    {{
                                        form.processing
                                            ? "Saving..."
                                            : isEditMode
                                            ? "Save Changes"
                                            : "Create Invoice"
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
