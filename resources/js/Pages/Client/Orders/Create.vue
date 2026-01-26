<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
    client: Object,
    allowedInputExtensions: Array,
    maxUploadMb: Number,
});

const form = ref({
    title: "",
    description: "",
    quantity: 1,
    priority: "normal",
    type: "digitizing",
    is_quote: false,
    input_files: [],
});

const errors = ref({});
const processing = ref(false);

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
    formData.append("quantity", form.value.quantity);
    formData.append("priority", form.value.priority);
    formData.append("type", form.value.type);
    formData.append("is_quote", form.value.is_quote ? "1" : "0");

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
                <h2 class="text-lg font-semibold text-gray-900">
                    Create Order
                </h2>
                <p class="text-sm text-gray-500">
                    Submit a new order for processing.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form
                    @submit.prevent="submit"
                    class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden"
                >
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Order Details
                        </h3>
                    </div>

                    <div class="px-5 py-6 space-y-6">
                        <!-- Title -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Title *</label
                            >
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Brief description of the order"
                            />
                            <p
                                v-if="errors.title"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.title }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Description</label
                            >
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Detailed requirements and specifications"
                            ></textarea>
                            <p
                                v-if="errors.description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.description }}
                            </p>
                        </div>

                        <!-- Type -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Order Type *</label
                            >
                            <select
                                v-model="form.type"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="digitizing">Digitizing</option>
                                <option value="vector">Vector</option>
                                <option value="patch">Patch</option>
                            </select>
                            <p
                                v-if="errors.type"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.type }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Quantity -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Quantity *</label
                                >
                                <input
                                    v-model.number="form.quantity"
                                    type="number"
                                    required
                                    min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p
                                    v-if="errors.quantity"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.quantity }}
                                </p>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Priority *</label
                                >
                                <select
                                    v-model="form.priority"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="normal">Normal</option>
                                    <option value="rush">Rush</option>
                                </select>
                                <p
                                    v-if="errors.priority"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ errors.priority }}
                                </p>
                            </div>
                        </div>

                        <!-- Is Quote -->
                        <div class="flex items-center">
                            <input
                                v-model="form.is_quote"
                                type="checkbox"
                                id="isthisquote"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label
                                class="ml-2 block text-sm text-gray-700"
                                for="isthisquote"
                            >
                                This is a quote request
                            </label>
                        </div>

                        <!-- Input Files -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Artwork Files *</label
                            >
                            <p class="mt-1 text-xs text-gray-500">
                                Allowed extensions:
                                {{ allowedInputExtensions.join(", ") }} | Max
                                size: {{ maxUploadMb }}MB per file
                            </p>
                            <input
                                @change="handleFileChange"
                                type="file"
                                multiple
                                required
                                class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <p
                                v-if="form.input_files.length > 0"
                                class="mt-2 text-sm text-gray-600"
                            >
                                {{ form.input_files.length }} file(s) selected
                            </p>
                            <p
                                v-if="errors.input_files"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ errors.input_files }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3"
                    >
                        <a
                            :href="route('client.orders.index')"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ processing ? "Creating..." : "Create Order" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
