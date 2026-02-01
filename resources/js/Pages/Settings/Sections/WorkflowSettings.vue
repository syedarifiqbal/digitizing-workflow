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
        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Workflow Automation</h3>
                <p class="mt-0.5 text-xs text-slate-500">Configure automatic status transitions.</p>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-start">
                        <input
                            v-model="form.auto_assign_on_designer"
                            id="auto_assign_on_designer"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="auto_assign_on_designer">
                            Auto-transition to ASSIGNED when designer is assigned
                        </label>
                    </div>
                    <p class="mt-1 ml-6 text-xs text-slate-400">
                        Orders change from RECEIVED to ASSIGNED automatically when a designer is assigned.
                    </p>
                    <p v-if="form.errors.auto_assign_on_designer" class="mt-1 ml-6 text-xs text-red-600">
                        {{ form.errors.auto_assign_on_designer }}
                    </p>
                </div>

                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-start">
                        <input
                            v-model="form.auto_submit_on_upload"
                            id="auto_submit_on_upload"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="auto_submit_on_upload">
                            Auto-transition to SUBMITTED when designer uploads work
                        </label>
                    </div>
                    <p class="mt-1 ml-6 text-xs text-slate-400">
                        When designer uploads output files, automatically change status from IN_PROGRESS to SUBMITTED.
                    </p>
                    <p v-if="form.errors.auto_submit_on_upload" class="mt-1 ml-6 text-xs text-red-600">
                        {{ form.errors.auto_submit_on_upload }}
                    </p>
                </div>

                <div>
                    <div class="flex items-start">
                        <input
                            v-model="form.auto_review_on_submit"
                            id="auto_review_on_submit"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="auto_review_on_submit">
                            Auto-transition to IN_REVIEW when work is submitted
                        </label>
                    </div>
                    <p class="mt-1 ml-6 text-xs text-slate-400">
                        Automatically change status from SUBMITTED to IN_REVIEW (requires auto-submit to be enabled).
                    </p>
                    <p v-if="form.errors.auto_review_on_submit" class="mt-1 ml-6 text-xs text-red-600">
                        {{ form.errors.auto_review_on_submit }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Bulk Actions</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    Decide which one-click tools appear on the Orders table.
                </p>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div class="flex items-start">
                    <input
                        v-model="form.enable_invoice_bulk_action"
                        id="enable_invoice_bulk_action"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label class="ml-2 block text-sm text-slate-700" for="enable_invoice_bulk_action">
                        Show “Create Invoice” bulk action when eligible delivered orders (for one client) are selected
                    </label>
                </div>
                <p class="text-xs text-slate-500">
                    Only appears when the Orders list is filtered to a single client and the selection includes delivered/closed, uninvoiced orders.
                </p>
                <p v-if="form.errors.enable_invoice_bulk_action" class="text-xs text-red-600">
                    {{ form.errors.enable_invoice_bulk_action }}
                </p>
            </div>
        </div>
    </div>
</template>
