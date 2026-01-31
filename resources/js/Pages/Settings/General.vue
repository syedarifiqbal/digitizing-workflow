<script setup>
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import GeneralSettings from "@/Pages/Settings/Sections/GeneralSettings.vue";
import InvoicingSettings from "@/Pages/Settings/Sections/InvoicingSettings.vue";
import WorkflowSettings from "@/Pages/Settings/Sections/WorkflowSettings.vue";
import CommissionsSettings from "@/Pages/Settings/Sections/CommissionsSettings.vue";
import NotificationSettings from "@/Pages/Settings/Sections/NotificationSettings.vue";
import ApiSettings from "@/Pages/Settings/Sections/ApiSettings.vue";
import Button from "@/Components/Button.vue";

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
    enable_invoice_bulk_action: props.settings.enable_invoice_bulk_action ?? true,
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
    company_logo: null,
    remove_logo: false,
});

const resetTransform = () => {
    form.transform((data) => data);
};

const submit = () => {
    const needsMultipart = form.company_logo instanceof File;

    if (needsMultipart) {
        form.transform((data) => ({
            ...data,
            _method: "PUT",
        }));

        form.post(route("settings.update"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset("company_logo");
                form.remove_logo = false;
            },
            onFinish: resetTransform,
        });

        return;
    }

    form.put(route("settings.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.company_logo = null;
            form.remove_logo = false;
        },
    });
};

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
const apiKeyMessage = computed(() => page.props.flash?.api_key);
const apiKeyLastFour = computed(() => props.settings.api_key_last_four ?? null);

const generateApiKey = () => {
    router.post(route("settings.api-key.generate"), {}, { preserveScroll: true });
};

const tabs = [
    {
        id: "general",
        label: "General",
        description: "Company profile, uploads, currency.",
        component: GeneralSettings,
    },
    {
        id: "workflow",
        label: "Workflow",
        description: "Status automation across order stages.",
        component: WorkflowSettings,
    },
    {
        id: "commissions",
        label: "Commissions",
        description: "Sales & designer payouts, tips.",
        component: CommissionsSettings,
    },
    {
        id: "invoicing",
        label: "Invoicing",
        description: "Defaults, branding, payment instructions.",
        component: InvoicingSettings,
    },
    {
        id: "notifications",
        label: "Notifications",
        description: "Email alerts for your team.",
        component: NotificationSettings,
    },
    {
        id: "api",
        label: "API",
        description: "Programmatic access & key management.",
        component: ApiSettings,
    },
];

const activeTab = ref(tabs[0].id);

const activeComponent = computed(
    () => tabs.find((tab) => tab.id === activeTab.value)?.component ?? tabs[0].component,
);

const componentProps = computed(() => {
    if (activeTab.value === "api") {
        return {
            form,
            apiKeyLastFour: apiKeyLastFour.value,
            apiKeyMessage: apiKeyMessage.value,
        };
    }

    if (activeTab.value === "invoicing") {
        return {
            form,
            companyLogoUrl: props.settings.company_logo_url ?? null,
        };
    }

    return { form };
});
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
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200 h-max">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-900">Settings Sections</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Jump between focused groups of preferences.
                                </p>
                            </div>
                            <div class="space-y-2 p-3">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    type="button"
                                    class="w-full rounded-md border px-4 py-3 text-left transition"
                                    :class="
                                        activeTab === tab.id
                                            ? 'border-indigo-200 bg-indigo-50 text-indigo-700 shadow-sm'
                                            : 'border-transparent text-gray-700 hover:bg-slate-50'
                                    "
                                    @click="activeTab = tab.id"
                                >
                                    <p class="text-sm font-medium">{{ tab.label }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ tab.description }}
                                    </p>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-6 lg:col-span-3">
                            <component
                                :is="activeComponent"
                                v-bind="componentProps"
                                :key="activeTab"
                                @generate="generateApiKey"
                            />

                            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                                <div class="px-5 py-4 space-y-4">
                                    <p class="text-sm text-gray-600">
                                        Review your changes before saving. Updates apply across your workspace
                                        immediately.
                                    </p>
                                    <Button
                                        html-type="submit"
                                        type="submit"
                                        variant="primary"
                                        :disabled="form.processing"
                                        class="w-full inline-flex justify-center items-center"
                                    >
                                        {{ form.processing ? "Saving..." : "Save Settings" }}
                                    </Button>
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
