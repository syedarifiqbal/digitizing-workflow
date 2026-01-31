<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();

const props = defineProps({
  // 'auto' | 'button' | 'link' | 'a'
  as: { type: String, default: 'auto' },

  href: { type: String, default: null },

  htmlType: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },

  // styling
  variant: { type: String, default: 'plain' }, // plain | primary | danger
  primary: { type: Boolean, default: false },

  extraClass: { type: String, default: '' },
});

const variantClasses = {
  plain:
    'inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50',
  primary:
    'inline-flex items-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:brightness-110',
  danger:
    'inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition',
};

const disabledClasses = 'disabled:opacity-50 disabled:cursor-not-allowed';

const resolvedVariant = computed(() => (props.primary ? 'primary' : props.variant));

const classes = computed(() => {
  const base = variantClasses[resolvedVariant.value] ?? variantClasses.plain;
  return [base, disabledClasses, props.extraClass].filter(Boolean).join(' ');
});

// If target=_blank or download is present, use native <a> so Inertia doesn't intercept.
const wantsNativeAnchor = computed(() => {
  return attrs.target === '_blank' || Object.prototype.hasOwnProperty.call(attrs, 'download');
});

const component = computed(() => {
  if (props.as === 'button') return 'button';
  if (props.as === 'a') return 'a';
  if (props.as === 'link') return Link;

  // auto
  if (props.href) return wantsNativeAnchor.value ? 'a' : Link;
  return 'button';
});
</script>

<template>
  <component
    :is="component"
    v-bind="attrs"
    :href="component !== 'button' ? props.href : undefined"
    :type="component === 'button' ? props.htmlType : undefined"
    :disabled="component === 'button' ? props.disabled : undefined"
    :class="classes"
  >
    <slot />
  </component>
</template>
