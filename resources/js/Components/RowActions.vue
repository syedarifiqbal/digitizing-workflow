<script setup>
import { Link } from "@inertiajs/vue3";
import {
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowsRightLeftIcon,
    CheckCircleIcon,
    DocumentIcon,
} from "@heroicons/vue/24/outline";

/**
 * Each action object:
 *   type     – 'view' | 'edit' | 'delete' | 'toggle' | 'custom'
 *   href     – Inertia Link destination (renders <Link>)
 *   url      – plain <a> href, opens in new tab (e.g. PDF download)
 *   action   – click handler (renders <button>)
 *   icon     – override Heroicon component
 *   label    – visible text (icon mode uses sr-only)
 *   title    – tooltip override (defaults to label)
 *   show     – boolean, default true
 */
const props = defineProps({
    actions: {
        type: Array,
        required: true,
    },
});

const defaultIcons = {
    view: EyeIcon,
    edit: PencilSquareIcon,
    delete: TrashIcon,
    toggle: ArrowsRightLeftIcon,
    download: DocumentIcon,
    confirm: CheckCircleIcon,
};

const defaultLabels = {
    view: "View",
    edit: "Edit",
    delete: "Delete",
    toggle: "Toggle",
    download: "Download",
    confirm: "Confirm",
};

const hoverColors = {
    view: "hover:text-slate-900",
    edit: "hover:text-indigo-600",
    delete: "hover:text-red-600",
    toggle: "hover:text-amber-500",
    download: "hover:text-slate-900",
    confirm: "hover:text-green-600",
    custom: "hover:text-indigo-600",
};

const getIcon = (action) => action.icon || defaultIcons[action.type] || null;
const getLabel = (action) => action.label || defaultLabels[action.type] || "";
const getTitle = (action) => action.title || getLabel(action);
const getHover = (action) => action.hoverColor || hoverColors[action.type] || hoverColors.custom;
const isVisible = (action) => action.show !== false;
</script>

<template>
    <div class="flex items-center justify-end gap-0.5">
        <template v-for="(act, i) in actions" :key="i">
            <template v-if="isVisible(act)">
                <!-- Inertia Link (view, edit, etc.) -->
                <Link
                    v-if="act.href"
                    :href="act.href"
                    class="inline-flex items-center rounded-full p-2 text-slate-400 transition-colors"
                    :class="getHover(act)"
                    :title="getTitle(act)"
                >
                    <span class="sr-only">{{ getLabel(act) }}</span>
                    <component :is="getIcon(act)" class="h-5 w-5" />
                </Link>

                <!-- External link (PDF, download, etc.) -->
                <a
                    v-else-if="act.url"
                    :href="act.url"
                    target="_blank"
                    class="inline-flex items-center rounded-full p-2 text-slate-400 transition-colors"
                    :class="getHover(act)"
                    :title="getTitle(act)"
                >
                    <span class="sr-only">{{ getLabel(act) }}</span>
                    <component :is="getIcon(act)" class="h-5 w-5" />
                </a>

                <!-- Button (delete, toggle, custom click) -->
                <button
                    v-else-if="act.action"
                    type="button"
                    class="inline-flex items-center rounded-full p-2 text-slate-400 transition-colors"
                    :class="getHover(act)"
                    :title="getTitle(act)"
                    @click="act.action"
                >
                    <span class="sr-only">{{ getLabel(act) }}</span>
                    <component :is="getIcon(act)" class="h-5 w-5" />
                </button>
            </template>
        </template>
    </div>
</template>
