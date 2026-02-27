<script setup>
import { computed, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    clients: Array,
    priorityOptions: Array,
    typeOptions: Array,
    currency: String,
    defaultType: {
        type: String,
        default: "digitizing",
    },
    isQuote: {
        type: Boolean,
        default: false,
    },
    fieldOptions: Object,
});

const form = useForm({
    client_id: "",
    title: "",
    po_number: "",
    instructions: "",
    type: props.defaultType ?? props.typeOptions?.[0]?.value ?? "digitizing",
    priority: "normal",
    due_at: "",
    price_amount: "",
    currency: props.currency ?? "USD",
    source: "",
    attachments: [],
    quote: props.isQuote ? 1 : 0,
    // Shared
    height: "",
    width: "",
    placement: "",
    num_colors: "",
    // Digitizing
    file_format: "",
    // Patch
    patch_type: "",
    quantity: "",
    backing: "",
    merrow_border: "",
    fabric: "",
    shipping_address: "",
    need_by: "",
    // Vector
    color_type: "",
    vector_order_type: "",
    required_format: "",
});

const isDigitizing = computed(() => form.type === "digitizing");
const isPatch = computed(() => form.type === "patch");
const isVector = computed(() => form.type === "vector");

const attachmentFiles = ref([]);
const fileInputRef = ref(null);

const handleFiles = (event) => {
    const newFiles = Array.from(event.target.files ?? []);
    attachmentFiles.value.push(...newFiles);
    form.attachments = [...attachmentFiles.value];
    // Reset the input so the same file can be re-added if removed
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const removeAttachment = (index) => {
    attachmentFiles.value.splice(index, 1);
    form.attachments = [...attachmentFiles.value];
};

const formatAttachmentSize = (size) => {
    if (!size) return '0 KB';
    const kb = size / 1024;
    if (kb < 1024) return `${kb.toFixed(1)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

const attachmentError = computed(() => {
    const entries = Object.entries(form.errors ?? {});
    const match = entries.find(([key]) => key.startsWith("attachments"));
    return match ? match[1] : null;
});

const submit = () => {
    form.post(route("orders.store"), {
        forceFormData: true,
    });
};

const selectedTypeLabel = computed(() => {
    const option = props.typeOptions?.find((opt) => opt.value === form.type);
    return option?.label ?? (form.type ?? "").split("_").join(" ");
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold text-slate-900">
                    New {{ selectedTypeLabel }}
                    {{ props.isQuote ? "Quote" : "Order" }}
                </h2>
                <p class="text-sm text-slate-500">
                    {{
                        props.isQuote
                            ? "Create a quote for the selected service type."
                            : "Capture client intake details and upload artwork."
                    }}
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">

                            <!-- Basic Info -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Basic Information</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="type">Order Type</label>
                                        <div class="mt-1 flex gap-2">
                                            <button
                                                v-for="option in typeOptions"
                                                :key="option.value"
                                                type="button"
                                                :class="[
                                                    'inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium transition',
                                                    form.type === option.value
                                                        ? 'bg-indigo-600 text-white shadow-sm'
                                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                                ]"
                                                @click="form.type = option.value"
                                            >
                                                {{ option.label }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="title">Design Name *</label>
                                            <input
                                                v-model="form.title"
                                                id="title"
                                                type="text"
                                                placeholder="Enter design name"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="po_number">Purchase Order #</label>
                                            <input
                                                v-model="form.po_number"
                                                id="po_number"
                                                type="text"
                                                placeholder="PO number"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.po_number" class="mt-1 text-xs text-red-600">{{ form.errors.po_number }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="client">Client *</label>
                                        <select
                                            v-model="form.client_id"
                                            id="client"
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Select client</option>
                                            <option
                                                v-for="client in clients"
                                                :key="client.id"
                                                :value="client.id"
                                            >
                                                {{ client.name }} ({{ client.email ?? "no email" }})
                                            </option>
                                        </select>
                                        <p v-if="form.errors.client_id" class="mt-1 text-xs text-red-600">{{ form.errors.client_id }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Digitizing Fields -->
                            <div v-if="isDigitizing" class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Digitizing Details</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">Write PROP in height or width for proportional sizing.</p>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="height">Height (inches)</label>
                                            <input
                                                v-model="form.height"
                                                id="height"
                                                type="text"
                                                placeholder="e.g. 3.5 or PROP"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.height" class="mt-1 text-xs text-red-600">{{ form.errors.height }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="width">Width (inches)</label>
                                            <input
                                                v-model="form.width"
                                                id="width"
                                                type="text"
                                                placeholder="e.g. 4.0 or PROP"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.width" class="mt-1 text-xs text-red-600">{{ form.errors.width }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="placement">Design Placement</label>
                                            <select
                                                v-model="form.placement"
                                                id="placement"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select placement</option>
                                                <option v-for="p in fieldOptions.placements" :key="p" :value="p">{{ p }}</option>
                                            </select>
                                            <p v-if="form.errors.placement" class="mt-1 text-xs text-red-600">{{ form.errors.placement }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="file_format">File Format Required</label>
                                            <select
                                                v-model="form.file_format"
                                                id="file_format"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select format</option>
                                                <option v-for="f in fieldOptions.fileFormats" :key="f" :value="f">{{ f }}</option>
                                            </select>
                                            <p v-if="form.errors.file_format" class="mt-1 text-xs text-red-600">{{ form.errors.file_format }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Patch Fields -->
                            <div v-if="isPatch" class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Patch Details</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="patch_type">Patch Type</label>
                                            <select
                                                v-model="form.patch_type"
                                                id="patch_type"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select patch type</option>
                                                <option v-for="pt in fieldOptions.patchTypes" :key="pt" :value="pt">{{ pt }}</option>
                                            </select>
                                            <p v-if="form.errors.patch_type" class="mt-1 text-xs text-red-600">{{ form.errors.patch_type }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="quantity">Quantity</label>
                                            <input
                                                v-model.number="form.quantity"
                                                id="quantity"
                                                type="number"
                                                min="1"
                                                placeholder="Number of patches"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.quantity" class="mt-1 text-xs text-red-600">{{ form.errors.quantity }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="patch_height">Height (inches)</label>
                                            <input
                                                v-model="form.height"
                                                id="patch_height"
                                                type="text"
                                                placeholder="e.g. 3.5"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.height" class="mt-1 text-xs text-red-600">{{ form.errors.height }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="patch_width">Width (inches)</label>
                                            <input
                                                v-model="form.width"
                                                id="patch_width"
                                                type="text"
                                                placeholder="e.g. 4.0"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.width" class="mt-1 text-xs text-red-600">{{ form.errors.width }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="backing">Backing</label>
                                            <select
                                                v-model="form.backing"
                                                id="backing"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select backing</option>
                                                <option v-for="b in fieldOptions.backings" :key="b" :value="b">{{ b }}</option>
                                            </select>
                                            <p v-if="form.errors.backing" class="mt-1 text-xs text-red-600">{{ form.errors.backing }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="patch_placement">Placement</label>
                                            <select
                                                v-model="form.placement"
                                                id="patch_placement"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select placement</option>
                                                <option v-for="p in fieldOptions.placements" :key="p" :value="p">{{ p }}</option>
                                            </select>
                                            <p v-if="form.errors.placement" class="mt-1 text-xs text-red-600">{{ form.errors.placement }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="merrow_border">Merrow Border</label>
                                            <select
                                                v-model="form.merrow_border"
                                                id="merrow_border"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select</option>
                                                <option v-for="m in fieldOptions.merrowBorders" :key="m" :value="m">{{ m }}</option>
                                            </select>
                                            <p v-if="form.errors.merrow_border" class="mt-1 text-xs text-red-600">{{ form.errors.merrow_border }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="num_colors">No. of Colors</label>
                                            <input
                                                v-model.number="form.num_colors"
                                                id="num_colors"
                                                type="number"
                                                min="0"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.num_colors" class="mt-1 text-xs text-red-600">{{ form.errors.num_colors }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="fabric">Fabric</label>
                                            <select
                                                v-model="form.fabric"
                                                id="fabric"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select fabric</option>
                                                <option v-for="f in fieldOptions.fabrics" :key="f" :value="f">{{ f }}</option>
                                            </select>
                                            <p v-if="form.errors.fabric" class="mt-1 text-xs text-red-600">{{ form.errors.fabric }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="shipping_address">Shipping Address (Optional)</label>
                                        <textarea
                                            v-model="form.shipping_address"
                                            id="shipping_address"
                                            rows="2"
                                            placeholder="Enter shipping address if needed"
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        ></textarea>
                                        <p v-if="form.errors.shipping_address" class="mt-1 text-xs text-red-600">{{ form.errors.shipping_address }}</p>
                                    </div>
                                    <div class="sm:w-1/2">
                                        <label class="block text-xs font-medium text-slate-700" for="need_by">Patches Need By</label>
                                        <DatePicker
                                            v-model="form.need_by"
                                            id="need_by"
                                            placeholder="Select date"
                                        />
                                        <p v-if="form.errors.need_by" class="mt-1 text-xs text-red-600">{{ form.errors.need_by }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Vector Fields -->
                            <div v-if="isVector" class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Vector Details</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="color_type">Color Type</label>
                                            <select
                                                v-model="form.color_type"
                                                id="color_type"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select color type</option>
                                                <option v-for="c in fieldOptions.colorTypes" :key="c" :value="c">{{ c }}</option>
                                            </select>
                                            <p v-if="form.errors.color_type" class="mt-1 text-xs text-red-600">{{ form.errors.color_type }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="vector_num_colors">Number of Colors</label>
                                            <input
                                                v-model.number="form.num_colors"
                                                id="vector_num_colors"
                                                type="number"
                                                min="0"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <p v-if="form.errors.num_colors" class="mt-1 text-xs text-red-600">{{ form.errors.num_colors }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="vector_order_type">Order Type</label>
                                            <select
                                                v-model="form.vector_order_type"
                                                id="vector_order_type"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select order type</option>
                                                <option v-for="v in fieldOptions.vectorOrderTypes" :key="v" :value="v">{{ v }}</option>
                                            </select>
                                            <p v-if="form.errors.vector_order_type" class="mt-1 text-xs text-red-600">{{ form.errors.vector_order_type }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-700" for="required_format">Required Format</label>
                                            <select
                                                v-model="form.required_format"
                                                id="required_format"
                                                class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select format</option>
                                                <option v-for="r in fieldOptions.requiredFormats" :key="r" :value="r">{{ r }}</option>
                                            </select>
                                            <p v-if="form.errors.required_format" class="mt-1 text-xs text-red-600">{{ form.errors.required_format }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructions + Files -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Instructions & Files</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="instructions">
                                            {{ isVector ? 'Vector Instructions' : 'Design Instructions' }}
                                        </label>
                                        <textarea
                                            v-model="form.instructions"
                                            id="instructions"
                                            rows="4"
                                            placeholder="Write design instructions here"
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        ></textarea>
                                        <p v-if="form.errors.instructions" class="mt-1 text-xs text-red-600">{{ form.errors.instructions }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="attachments">Input Artwork</label>
                                        <p class="mt-0.5 text-xs text-slate-400">Upload up to 10 files. Click "Add Files" to select and add multiple files one batch at a time.</p>
                                        <input
                                            ref="fileInputRef"
                                            id="attachments"
                                            type="file"
                                            multiple
                                            @change="handleFiles"
                                            class="mt-1 block w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        />
                                        <p v-if="attachmentError" class="mt-1 text-xs text-red-600">{{ attachmentError }}</p>

                                        <!-- Selected file list -->
                                        <div
                                            v-if="attachmentFiles.length"
                                            class="mt-2 rounded-md border border-slate-200 divide-y divide-slate-100"
                                        >
                                            <div
                                                v-for="(file, index) in attachmentFiles"
                                                :key="index"
                                                class="flex items-center justify-between px-3 py-2"
                                            >
                                                <div>
                                                    <p class="text-xs font-medium text-slate-900">{{ file.name }}</p>
                                                    <p class="text-xs text-slate-500">{{ formatAttachmentSize(file.size) }}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="text-xs text-red-500 hover:text-red-700"
                                                    @click="removeAttachment(index)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">

                            <!-- Priority -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Priority</h3>
                                </div>
                                <div class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <label
                                            v-for="option in priorityOptions"
                                            :key="option.value"
                                            :class="[
                                                'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium cursor-pointer border transition',
                                                form.priority === option.value
                                                    ? (option.value === 'rush' ? 'border-red-300 bg-red-50 text-red-700' : 'border-indigo-300 bg-indigo-50 text-indigo-700')
                                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.priority"
                                                :value="option.value"
                                                class="sr-only"
                                            />
                                            {{ option.value === 'rush' ? 'Super Urgent' : option.label }}
                                        </label>
                                    </div>
                                    <p v-if="form.errors.priority" class="mt-1 text-xs text-red-600">{{ form.errors.priority }}</p>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Pricing</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="price_amount">Quoted Price</label>
                                        <input
                                            v-model="form.price_amount"
                                            id="price_amount"
                                            type="number"
                                            step="0.01"
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.price_amount" class="mt-1 text-xs text-red-600">{{ form.errors.price_amount }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="currency">Currency</label>
                                        <input
                                            v-model="form.currency"
                                            id="currency"
                                            type="text"
                                            maxlength="3"
                                            class="mt-1 block w-32 rounded-md border-slate-300 text-sm shadow-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">{{ form.errors.currency }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 border-b border-slate-100">
                                    <h3 class="text-sm font-semibold text-slate-900">Schedule</h3>
                                </div>
                                <div class="px-5 py-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="due_at">Due Date</label>
                                        <DatePicker
                                            v-model="form.due_at"
                                            id="due_at"
                                            placeholder="Select due date"
                                        />
                                        <p v-if="form.errors.due_at" class="mt-1 text-xs text-red-600">{{ form.errors.due_at }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700" for="source">Source</label>
                                        <input
                                            v-model="form.source"
                                            id="source"
                                            type="text"
                                            placeholder="Website, phone, API..."
                                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.source" class="mt-1 text-xs text-red-600">{{ form.errors.source }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                                <div class="px-5 py-4 space-y-3">
                                    <input type="hidden" name="quote" :value="form.quote" />
                                    <Button
                                        as="button"
                                        html-type="submit"
                                        variant="primary"
                                        :disabled="form.processing"
                                        extra-class="w-full justify-center"
                                    >
                                        {{ form.processing ? 'Creating...' : (props.isQuote ? 'Create Quote' : 'Create Order') }}
                                    </Button>
                                    <Button
                                        :href="route('orders.index')"
                                        extra-class="w-full justify-center"
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
