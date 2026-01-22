<script setup>
import { computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowKey: {
        type: String,
        default: 'id',
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    selectedIds: {
        type: Array,
        default: () => [],
    },
    emptyText: {
        type: String,
        default: 'No records found.',
    },
});

const emit = defineEmits(['update:selectedIds', 'rowClick']);

const hasRows = computed(() => props.rows.length > 0);

const isSelected = (id) => props.selectedIds.includes(id);

const toggleSelectAll = (checked) => {
    if (!props.selectable) {
        return;
    }

    if (checked) {
        emit(
            'update:selectedIds',
            props.rows.map((row) => row?.[props.rowKey]).filter((value) => value !== undefined && value !== null)
        );
    } else {
        emit('update:selectedIds', []);
    }
};

const toggleRow = (id, checked) => {
    if (!props.selectable) {
        return;
    }

    const next = new Set(props.selectedIds);
    if (checked) {
        next.add(id);
    } else {
        next.delete(id);
    }

    emit('update:selectedIds', Array.from(next));
};

const defaultCellValue = (row, column) => {
    const value = row?.[column.key];
    if (value === undefined || value === null || value === '') {
        return '—';
    }

    return value;
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th v-if="selectable" class="px-4 py-3">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            :checked="rows.length > 0 && selectedIds.length === rows.length"
                            @change="toggleSelectAll($event.target.checked)"
                        />
                    </th>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500',
                            column.headerClass,
                        ]"
                    >
                        <slot :name="`head-${column.key}`" :column="column">
                            {{ column.label }}
                        </slot>
                    </th>
                </tr>
            </thead>
            <tbody v-if="hasRows">
                <tr
                    v-for="(row, rowIndex) in rows"
                    :key="row?.[rowKey] ?? `row-${rowIndex}`"
                    class="hover:bg-gray-50"
                    @click="$emit('rowClick', row)"
                >
                    <td v-if="selectable" class="px-4 py-3">
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            :checked="isSelected(row?.[rowKey])"
                            :value="row?.[rowKey]"
                            @change="toggleRow(row?.[rowKey], $event.target.checked)"
                            @click.stop
                        />
                    </td>
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        :class="['px-4 py-3 text-sm text-gray-900', column.cellClass]"
                    >
                        <slot :name="`cell-${column.key}`" :row="row" :column="column">
                            {{ defaultCellValue(row, column) }}
                        </slot>
                    </td>
                </tr>
            </tbody>
            <tbody v-else>
                <tr>
                    <td
                        :colspan="columns.length + (selectable ? 1 : 0)"
                        class="px-4 py-6 text-center text-sm text-gray-500"
                    >
                        <slot name="empty">
                            {{ emptyText }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
