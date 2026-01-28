<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Workflow Automation</h3>
            <p class="mt-0.5 text-xs text-gray-500">Configure automatic status transitions.</p>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div class="border-b border-gray-100 pb-4">
                <div class="flex items-start">
                    <input
                        v-model="form.auto_assign_on_designer"
                        id="auto_assign_on_designer"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label class="ml-2 block text-sm text-gray-700" for="auto_assign_on_designer">
                        Auto-transition to ASSIGNED when designer is assigned
                    </label>
                </div>
                <p class="mt-1 ml-6 text-xs text-gray-400">
                    Orders change from RECEIVED to ASSIGNED automatically when a designer is assigned.
                </p>
                <p v-if="form.errors.auto_assign_on_designer" class="mt-1 ml-6 text-xs text-red-600">
                    {{ form.errors.auto_assign_on_designer }}
                </p>
            </div>

            <div class="border-b border-gray-100 pb-4">
                <div class="flex items-start">
                    <input
                        v-model="form.auto_submit_on_upload"
                        id="auto_submit_on_upload"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label class="ml-2 block text-sm text-gray-700" for="auto_submit_on_upload">
                        Auto-transition to SUBMITTED when designer uploads work
                    </label>
                </div>
                <p class="mt-1 ml-6 text-xs text-gray-400">
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
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label class="ml-2 block text-sm text-gray-700" for="auto_review_on_submit">
                        Auto-transition to IN_REVIEW when work is submitted
                    </label>
                </div>
                <p class="mt-1 ml-6 text-xs text-gray-400">
                    Automatically change status from SUBMITTED to IN_REVIEW (requires auto-submit to be enabled).
                </p>
                <p v-if="form.errors.auto_review_on_submit" class="mt-1 ml-6 text-xs text-red-600">
                    {{ form.errors.auto_review_on_submit }}
                </p>
            </div>
        </div>
    </div>
</template>
