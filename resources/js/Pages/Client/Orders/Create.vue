<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    client: Object,
    allowedInputExtensions: Array,
    maxUploadMb: Number,
    fieldOptions: Object,
});

const form = ref({
    title: "",
    description: "",
    po_number: "",
    quantity: 1,
    priority: "normal",
    type: "digitizing",
    is_quote: false,
    width: "",
    height: "",
    placement: "",
    file_format: "",
    input_files: [],
});

const errors = ref({});
const processing = ref(false);

const showSizeFields = computed(() => form.value.type === "digitizing" || form.value.type === "patch");

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);
    form.value.input_files = files;
};

const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append("title", form.value.title);
    formData.append("description", form.value.description || "");
    formData.append("po_number", form.value.po_number || "");
    formData.append("quantity", form.value.quantity);
    formData.append("priority", form.value.priority);
    formData.append("type", form.value.type);
    formData.append("is_quote", form.value.is_quote ? "1" : "0");
    formData.append("width", form.value.width || "");
    formData.append("height", form.value.height || "");
    formData.append("placement", form.value.placement || "");
    formData.append("file_format", form.value.file_format || "");

    form.value.input_files.forEach((file, index) => {
        formData.append(`input_files[${index}]`, file);
    });

    router.post(route("client.orders.store"), formData, {
        preserveScroll: true,
        onError: (err) => {
            errors.value = err;
            processing.value = false;
        },
        onSuccess: () => {
            processing.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl font-semibold text-slate-900">Create Order</h2>
                <p class="text-sm text-slate-500">Submit a new order for processing.</p>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-8">
            <form
                @submit.prevent="submit"
                class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70 overflow-hidden"
            >
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Order Details</h3>
                </div>

                <div class="px-6 py-6 space-y-6">

                    <!-- Design Name + PO Number -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Design Name *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                                placeholder="e.g. Company Logo Left Chest"
                            />
                            <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">PO # <span class="font-normal text-slate-400">(optional)</span></label>
                            <input
                                v-model="form.po_number"
                                type="text"
                                placeholder="Your PO reference"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            />
                            <p v-if="errors.po_number" class="mt-1 text-sm text-red-600">{{ errors.po_number }}</p>
                        </div>
                    </div>

                    <!-- Order Type -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Order Type *</label>
                        <select
                            v-model="form.type"
                            required
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                        >
                            <option value="digitizing">Digitizing</option>
                            <option value="vector">Vector</option>
                            <option value="patch">Patch</option>
                        </select>
                        <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</p>
                    </div>

                    <!-- Width & Height -->
                    <div v-if="showSizeFields" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Width (inches)</label>
                            <input
                                v-model="form.width"
                                type="text"
                                placeholder='e.g. 4.0 or PROP'
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            />
                            <p v-if="errors.width" class="mt-1 text-sm text-red-600">{{ errors.width }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Height (inches)</label>
                            <input
                                v-model="form.height"
                                type="text"
                                placeholder='e.g. 3.5 or PROP'
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            />
                            <p v-if="errors.height" class="mt-1 text-sm text-red-600">{{ errors.height }}</p>
                        </div>
                        <p class="col-span-2 -mt-2 text-xs text-slate-400">Use PROP in one field for proportional sizing.</p>
                    </div>

                    <!-- Placement & File Format -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Design Placement</label>
                            <select
                                v-model="form.placement"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            >
                                <option value="">Select placement</option>
                                <option v-for="p in fieldOptions.placements" :key="p" :value="p">{{ p }}</option>
                            </select>
                            <p v-if="errors.placement" class="mt-1 text-sm text-red-600">{{ errors.placement }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">File Format Required</label>
                            <select
                                v-model="form.file_format"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            >
                                <option value="">Select format</option>
                                <option v-for="f in fieldOptions.fileFormats" :key="f" :value="f">{{ f }}</option>
                            </select>
                            <p v-if="errors.file_format" class="mt-1 text-sm text-red-600">{{ errors.file_format }}</p>
                        </div>
                    </div>

                    <!-- Quantity & Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Quantity *</label>
                            <input
                                v-model.number="form.quantity"
                                type="number"
                                required
                                min="1"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            />
                            <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Priority *</label>
                            <select
                                v-model="form.priority"
                                required
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            >
                                <option value="normal">Normal</option>
                                <option value="rush">Rush</option>
                            </select>
                            <p v-if="errors.priority" class="mt-1 text-sm text-red-600">{{ errors.priority }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Instructions <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-indigo-300 focus:ring focus:ring-indigo-200/50"
                            placeholder="Any special requirements, color details, or notes..."
                        ></textarea>
                        <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                    </div>

                    <!-- Is Quote -->
                    <div class="flex items-center">
                        <input
                            v-model="form.is_quote"
                            type="checkbox"
                            id="isthisquote"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="isthisquote">
                            This is a quote request
                        </label>
                    </div>

                    <!-- Input Files -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Artwork Files *</label>
                        <p class="mt-1 text-xs text-slate-500">
                            Allowed: {{ allowedInputExtensions.join(", ") }} &nbsp;|&nbsp; Max size: {{ maxUploadMb }}MB per file
                        </p>
                        <input
                            @change="handleFileChange"
                            type="file"
                            multiple
                            required
                            class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <p v-if="form.input_files.length > 0" class="mt-2 text-sm text-slate-600">
                            {{ form.input_files.length }} file(s) selected
                        </p>
                        <p v-if="errors.input_files" class="mt-1 text-sm text-red-600">{{ errors.input_files }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                    <Button :href="route('client.orders.index')">Cancel</Button>
                    <Button html-type="submit" primary :disabled="processing">
                        {{ processing ? "Creating..." : "Create Order" }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
