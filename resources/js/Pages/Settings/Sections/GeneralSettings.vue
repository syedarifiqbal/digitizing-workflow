<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const timeFormatOptions = [
    { value: 'h:mm A',    label: '12-hour  (e.g. 2:05 PM)' },
    { value: 'h:mm:ss A', label: '12-hour with seconds  (e.g. 2:05:30 PM)' },
    { value: 'HH:mm',     label: '24-hour  (e.g. 14:05)' },
    { value: 'HH:mm:ss',  label: '24-hour with seconds  (e.g. 14:05:30)' },
];

// Live preview using the currently selected formats
const previewTimestamp = computed(() => {
    const now = new Date();
    const day   = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year  = now.getFullYear();

    let datePart;
    switch (props.form.date_format) {
        case 'DD/MM/YYYY':  datePart = `${day}/${month}/${year}`; break;
        case 'YYYY-MM-DD':  datePart = `${year}-${month}-${day}`; break;
        case 'DD-MM-YYYY':  datePart = `${day}-${month}-${year}`; break;
        case 'DD.MM.YYYY':  datePart = `${day}.${month}.${year}`; break;
        default:            datePart = `${month}/${day}/${year}`;
    }

    const h24  = now.getHours();
    const h12  = h24 % 12 || 12;
    const ampm = h24 >= 12 ? 'PM' : 'AM';
    const mm   = String(now.getMinutes()).padStart(2, '0');
    const ss   = String(now.getSeconds()).padStart(2, '0');

    let timePart;
    switch (props.form.time_format) {
        case 'h:mm:ss A': timePart = `${h12}:${mm}:${ss} ${ampm}`; break;
        case 'HH:mm':     timePart = `${String(h24).padStart(2,'0')}:${mm}`; break;
        case 'HH:mm:ss':  timePart = `${String(h24).padStart(2,'0')}:${mm}:${ss}`; break;
        default:          timePart = `${h12}:${mm} ${ampm}`;
    }

    return `${datePart} ${timePart}`;
});
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">General</h3>
                <p class="mt-0.5 text-xs text-slate-500">Company info and dashboard preferences.</p>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="name">Company Name</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="date_format">Date Format</label>
                        <select
                            v-model="form.date_format"
                            id="date_format"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                            <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                            <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                            <option value="DD-MM-YYYY">DD-MM-YYYY</option>
                            <option value="DD.MM.YYYY">DD.MM.YYYY</option>
                        </select>
                        <p v-if="form.errors.date_format" class="mt-1 text-xs text-red-600">
                            {{ form.errors.date_format }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="time_format">Time Format</label>
                        <select
                            v-model="form.time_format"
                            id="time_format"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="opt in timeFormatOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.time_format" class="mt-1 text-xs text-red-600">
                            {{ form.errors.time_format }}
                        </p>
                    </div>
                </div>

                <!-- Live timestamp preview -->
                <div class="rounded-md bg-slate-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
                    <span class="text-xs font-medium text-slate-500">Preview:</span>
                    <span class="text-sm font-mono text-slate-800">{{ previewTimestamp }}</span>
                </div>

                <div class="space-y-3 pt-2">
                    <div class="flex items-center">
                        <input
                            v-model="form.email_verification_required"
                            id="email_verification_required"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="email_verification_required">
                            Require email verification for non-admin users
                        </label>
                    </div>
                    <p v-if="form.errors.email_verification_required" class="text-xs text-red-600">
                        {{ form.errors.email_verification_required }}
                    </p>

                    <div class="flex items-center">
                        <input
                            v-model="form.show_order_cards"
                            id="show_order_cards"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label class="ml-2 block text-sm text-slate-700" for="show_order_cards">
                            Show quick action cards on Orders dashboard
                        </label>
                    </div>
                    <p v-if="form.errors.show_order_cards" class="text-xs text-red-600">
                        {{ form.errors.show_order_cards }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">File Uploads</h3>
                <p class="mt-0.5 text-xs text-slate-500">Control allowed file types and size limits.</p>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="allowed_input_extensions">
                            Allowed Input Extensions
                        </label>
                        <input
                            v-model="form.allowed_input_extensions"
                            id="allowed_input_extensions"
                            type="text"
                            placeholder="jpg,jpeg,png,pdf"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated, no dots</p>
                        <p v-if="form.errors.allowed_input_extensions" class="mt-1 text-xs text-red-600">
                            {{ form.errors.allowed_input_extensions }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="allowed_output_extensions">
                            Allowed Output Extensions
                        </label>
                        <input
                            v-model="form.allowed_output_extensions"
                            id="allowed_output_extensions"
                            type="text"
                            placeholder="dst,emb,pes,exp,pdf"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated, no dots</p>
                        <p v-if="form.errors.allowed_output_extensions" class="mt-1 text-xs text-red-600">
                            {{ form.errors.allowed_output_extensions }}
                        </p>
                    </div>
                </div>
                <div class="sm:w-1/3">
                    <label class="block text-xs font-medium text-slate-700" for="max_upload_mb">
                        Max Upload Size (MB)
                    </label>
                    <input
                        v-model.number="form.max_upload_mb"
                        id="max_upload_mb"
                        type="number"
                        min="1"
                        max="100"
                        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.max_upload_mb" class="mt-1 text-xs text-red-600">
                        {{ form.errors.max_upload_mb }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Financial</h3>
                <p class="mt-0.5 text-xs text-slate-500">Currency and order numbering preferences.</p>
            </div>
            <div class="px-5 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="currency">Currency</label>
                        <input
                            v-model="form.currency"
                            id="currency"
                            type="text"
                            maxlength="3"
                            class="mt-1 block w-32 rounded-md border-slate-300 text-sm shadow-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">
                            {{ form.errors.currency }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700" for="order_number_prefix">
                            Order Number Prefix
                        </label>
                        <input
                            v-model="form.order_number_prefix"
                            id="order_number_prefix"
                            type="text"
                            maxlength="10"
                            placeholder="e.g. ORD-"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.order_number_prefix" class="mt-1 text-xs text-red-600">
                            {{ form.errors.order_number_prefix }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
