<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    // 'button' | 'link'
    as: { type: String, default: "button" },

    // used when as === 'link'
    href: { type: String, default: null },

    // used when as === 'button'
    type: { type: String, default: "button" }, // button | submit | reset
    disabled: { type: Boolean, default: false },

    // 'plain' | 'primary'
    variant: { type: String, default: "plain" },

    // optional semantic style hint (you can expand these mappings)
    // 'default' | 'create' | 'save' | 'danger'
    directive: { type: String, default: "default" },

    extraClass: { type: String, default: "" },
});

const component = computed(() => (props.as === "link" ? Link : "button"));

const variantClasses = {
    plain: "inline-flex justify-center items-center text-capitalize rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50",
    primary:
        "inline-flex justify-center items-center text-capitalize rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110",
};

// Optional “directive” tweaks.
// Keep these light so it doesn’t get messy.
const directiveClasses = {
    default: "",
    create: "",
    save: "",
    danger: "border-red-300 text-red-700 hover:bg-red-50",
};

const disabledClasses = "disabled:opacity-50 disabled:cursor-not-allowed";

const classes = computed(() => {
    const base = variantClasses[props.variant] ?? variantClasses.plain;

    // If variant is plain, allow directive to tweak it a bit (like danger).
    // For primary, ignore directive by default to keep it consistent.
    const directive =
        props.variant === "plain"
            ? directiveClasses[props.directive] ?? ""
            : "";

    return [base, directive, disabledClasses, props.extraClass]
        .filter(Boolean)
        .join(" ");
});
</script>

<template>
    <component
        :is="component"
        :href="props.as === 'link' ? props.href : undefined"
        :type="props.as === 'button' ? props.type : undefined"
        :disabled="props.as === 'button' ? props.disabled : undefined"
        :class="classes"
    >
        <slot />
    </component>
</template>
