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
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Tenant Settings
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Configure company-wide preferences for workflows, uploads,
                    and billing.
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div
                            v-if="successMessage"
                            class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-700"
                        >
                            {{ successMessage }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    General
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="name"
                                        >
                                            Company Name
                                        </label>
                                        <input
                                            v-model="form.name"
                                            id="name"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="form.errors.name"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            v-model="
                                                form.email_verification_required
                                            "
                                            id="email_verification_required"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label
                                            class="ml-2 block text-sm text-gray-700"
                                            for="email_verification_required"
                                        >
                                            Require email verification for
                                            non-admin users
                                        </label>
                                    </div>
                                    <p
                                        v-if="
                                            form.errors
                                                .email_verification_required
                                        "
                                        class="text-sm text-red-600"
                                    >
                                        {{
                                            form.errors
                                                .email_verification_required
                                        }}
                                    </p>

                                    <div class="flex items-center">
                                        <input
                                            v-model="form.show_order_cards"
                                            id="show_order_cards"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label
                                            class="ml-2 block text-sm text-gray-700"
                                            for="show_order_cards"
                                        >
                                            Show quick action cards on Orders
                                            dashboard
                                        </label>
                                    </div>
                                    <p
                                        v-if="form.errors.show_order_cards"
                                        class="text-sm text-red-600"
                                    >
                                        {{ form.errors.show_order_cards }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Workflow
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="commission_earned_on"
                                        >
                                            Commission earned on
                                        </label>
                                        <select
                                            v-model="form.commission_earned_on"
                                            id="commission_earned_on"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="approved">
                                                Approved
                                            </option>
                                            <option value="delivered">
                                                Delivered
                                            </option>
                                        </select>
                                        <p
                                            v-if="
                                                form.errors.commission_earned_on
                                            "
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{
                                                form.errors.commission_earned_on
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Notifications
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="flex items-center">
                                        <input
                                            v-model="form.notify_on_assignment"
                                            id="notify_on_assignment"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label
                                            class="ml-2 block text-sm text-gray-700"
                                            for="notify_on_assignment"
                                        >
                                            Send email notification when an order is assigned to a designer
                                        </label>
                                    </div>
                                    <p
                                        v-if="form.errors.notify_on_assignment"
                                        class="text-sm text-red-600"
                                    >
                                        {{ form.errors.notify_on_assignment }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    File Uploads
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="allowed_input_extensions"
                                        >
                                            Allowed input extensions
                                        </label>
                                        <input
                                            v-model="
                                                form.allowed_input_extensions
                                            "
                                            id="allowed_input_extensions"
                                            type="text"
                                            placeholder="jpg,jpeg,png,pdf"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .allowed_input_extensions
                                            "
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{
                                                form.errors
                                                    .allowed_input_extensions
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="allowed_output_extensions"
                                        >
                                            Allowed output extensions
                                        </label>
                                        <input
                                            v-model="
                                                form.allowed_output_extensions
                                            "
                                            id="allowed_output_extensions"
                                            type="text"
                                            placeholder="dst,emb,pes,exp,pdf,ai,psd,png,jpg"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .allowed_output_extensions
                                            "
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{
                                                form.errors
                                                    .allowed_output_extensions
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="max_upload_mb"
                                        >
                                            Max upload size (MB)
                                        </label>
                                        <input
                                            v-model.number="form.max_upload_mb"
                                            id="max_upload_mb"
                                            type="number"
                                            min="1"
                                            max="100"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="form.errors.max_upload_mb"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.max_upload_mb }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Financial
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="currency"
                                        >
                                            Currency
                                        </label>
                                        <input
                                            v-model="form.currency"
                                            id="currency"
                                            type="text"
                                            maxlength="3"
                                            class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="form.errors.currency"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.currency }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            for="order_number_prefix"
                                        >
                                            Order number prefix
                                        </label>
                                        <input
                                            v-model="form.order_number_prefix"
                                            id="order_number_prefix"
                                            type="text"
                                            maxlength="10"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p
                                            v-if="
                                                form.errors.order_number_prefix
                                            "
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{
                                                form.errors.order_number_prefix
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    Save settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
