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
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <p class="mt-0.5 text-xs text-gray-500">Choose how we notify your team about order activity.</p>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div class="flex items-start">
                <input
                    v-model="form.notify_on_assignment"
                    id="notify_on_assignment"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label class="ml-2 block text-sm text-gray-700" for="notify_on_assignment">
                    Email designers when orders are assigned
                </label>
            </div>
            <p class="mt-1.5 text-xs text-gray-400">
                Send an immediate notification whenever a designer is assigned to an order.
            </p>
            <p v-if="form.errors.notify_on_assignment" class="mt-1 text-xs text-red-600">
                {{ form.errors.notify_on_assignment }}
            </p>

            <div class="flex items-start">
                <input
                    v-model="form.notify_on_comment"
                    id="notify_on_comment"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label class="ml-2 block text-sm text-gray-700" for="notify_on_comment">
                    Email on new order comments
                </label>
            </div>
            <p class="mt-1.5 text-xs text-gray-400">
                Send an email notification whenever a new comment is added to an order. Bell notifications always appear regardless of this setting.
            </p>
            <p v-if="form.errors.notify_on_comment" class="mt-1 text-xs text-red-600">
                {{ form.errors.notify_on_comment }}
            </p>
        </div>
    </div>
</template>
