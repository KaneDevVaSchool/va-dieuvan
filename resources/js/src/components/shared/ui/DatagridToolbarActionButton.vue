<script setup>
import { computed } from 'vue'
import {
  ArrowDownTrayIcon,
  FunnelIcon,
  Squares2X2Icon,
  ViewColumnsIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  icon: {
    type: String,
    default: 'filter',
    validator: (v) => ['filter', 'columns', 'export'].includes(v),
  },
  active: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  testId: { type: String, default: '' },
})

defineEmits(['click'])

const IconComp = computed(() => {
  if (props.icon === 'columns') return ViewColumnsIcon
  if (props.icon === 'export') return ArrowDownTrayIcon
  return FunnelIcon
})
</script>

<template>
  <button
    type="button"
    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-sm font-medium shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-va-700/30 disabled:cursor-not-allowed disabled:opacity-50"
    :class="
      active
        ? 'border-va-800/40 bg-va-50 text-va-900 dark:border-va-600/50 dark:bg-va-950/40 dark:text-va-100'
        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'
    "
    :disabled="disabled"
    :data-testid="testId || `datagrid-toolbar-${icon}`"
    @click.stop="$emit('click', $event)"
  >
    <component :is="IconComp" class="h-[15px] w-[15px] shrink-0 opacity-80" aria-hidden="true" />
    <span><slot /></span>
  </button>
</template>
