<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Commissions & Rewards</h3>
            <p class="mt-0.5 text-xs text-slate-500">Control when teams earn commissions, bonuses, and tips.</p>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700" for="sales_commission_earned_on">
                    Sales Commission Earned On
                </label>
                <select
                    v-model="form.sales_commission_earned_on"
                    id="sales_commission_earned_on"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="approved">Approved</option>
                    <option value="delivered">Delivered</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-400">Choose when sales commission is considered earned.</p>
                <p v-if="form.errors.sales_commission_earned_on" class="mt-1 text-xs text-red-600">
                    {{ form.errors.sales_commission_earned_on }}
                </p>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700" for="designer_bonus_earned_on">
                    Designer Bonus Earned On
                </label>
                <select
                    v-model="form.designer_bonus_earned_on"
                    id="designer_bonus_earned_on"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="approved">Approved</option>
                    <option value="delivered">Delivered</option>
                </select>
                <p class="mt-1.5 text-xs text-slate-400">Define when designer bonuses are payable.</p>
                <p v-if="form.errors.designer_bonus_earned_on" class="mt-1 text-xs text-red-600">
                    {{ form.errors.designer_bonus_earned_on }}
                </p>
            </div>
            <div class="pt-2">
                <div class="flex items-start">
                    <input
                        v-model="form.enable_designer_tips"
                        id="enable_designer_tips"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label class="ml-2 block text-sm text-slate-700" for="enable_designer_tips">
                        Enable designer tips on delivery
                    </label>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">
                    Allow admins to add optional tips for exceptional work when delivering orders.
                </p>
                <p v-if="form.errors.enable_designer_tips" class="mt-1 text-xs text-red-600">
                    {{ form.errors.enable_designer_tips }}
                </p>
            </div>
        </div>
    </div>
</template>
