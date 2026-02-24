<script setup>
import { computed } from "vue";
import { VueDatePicker } from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import { useDateFormat } from "@/Composables/useDateFormat";

const { getFormat } = useDateFormat();

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Select date',
    },
    id: {
        type: String,
        default: '',
    },
    enableTime: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

/**
 * Convert tenant format to vue-datepicker format tokens.
 * Tenant uses: MM/DD/YYYY, DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY, DD.MM.YYYY
 * Vue-datepicker uses: MM/dd/yyyy, dd/MM/yyyy, yyyy-MM-dd, dd-MM-yyyy, dd.MM.yyyy
 */
const pickerFormat = computed(() => {
    const tenantFormat = getFormat();
    return tenantFormat
        .replace('YYYY', 'yyyy')
        .replace('DD', 'dd');
});

const handleUpdate = (value) => {
    if (!value) {
        emit('update:modelValue', '');
        return;
    }

    // Always emit YYYY-MM-DD for backend compatibility
    const d = new Date(value);
    if (isNaN(d.getTime())) {
        emit('update:modelValue', '');
        return;
    }

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    emit('update:modelValue', `${year}-${month}-${day}`);
};
</script>

<template>
    <VueDatePicker
        :model-value="modelValue"
        @update:model-value="handleUpdate"
        :format="pickerFormat"
        :enable-time-picker="enableTime"
        auto-apply
        :placeholder="placeholder"
        :uid="id"
        input-class-name="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
    />
</template>
