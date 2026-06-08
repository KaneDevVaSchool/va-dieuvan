<template>
  <details ref="detailsEl" class="group relative min-w-0" :class="rootClass">
    <summary
      class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border px-2 py-1.5 shadow-sm transition [&::-webkit-details-marker]:hidden"
      :class="[
        fullWidthSummary ? 'max-w-full' : 'max-w-[min(100%,11rem)] sm:max-w-[13rem]',
        summaryClass,
        active
          ? 'border-teal-300/90 bg-teal-50/90 text-teal-950 ring-1 ring-teal-200/70 hover:bg-teal-50 dark:border-teal-700/60 dark:bg-teal-950/40 dark:text-teal-100 dark:ring-teal-800/50'
          : 'border-white/80 bg-white/90 text-slate-700 hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800',
      ]"
      :aria-label="ariaLabel || panelTitle || summaryText"
    >
      <span
        v-if="showChipLabel && label"
        class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400"
      >{{ label }}:</span>
      <span
        class="min-w-0 flex-1 truncate"
        :class="[
          active
            ? 'text-sm font-semibold text-teal-950 dark:text-teal-50'
            : 'text-xs font-medium text-slate-600 sm:text-sm dark:text-slate-400',
          summaryTextClass,
        ]"
        :title="summaryTitle || summaryText"
        >{{ summaryText }}</span
      >
      <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
    </summary>
    <div
      class="absolute left-0 top-[calc(100%+6px)] z-[100] w-[min(calc(100vw-2rem),20rem)] rounded-xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950 sm:w-auto"
      :class="panelClass"
    >
      <p
        v-if="panelTitle"
        class="border-b border-slate-100 bg-slate-50/80 px-3 py-2 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-200"
      >
        {{ panelTitle }}
      </p>
      <slot />
    </div>
  </details>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'

defineProps({
  label: { type: String, default: '' },
  /** When true, shows `label` before the summary value on the chip. */
  showChipLabel: { type: Boolean, default: false },
  summaryText: { type: String, required: true },
  /** Tailwind classes for the dropdown panel (width, padding, overflow, …) */
  panelClass: { type: String, default: 'min-w-[220px] py-1' },
  /** Extra classes on the summary value span (e.g. max-w) */
  summaryTextClass: { type: String, default: '' },
  /** Native title on the value (e.g. full trip label when truncated) */
  summaryTitle: { type: String, default: '' },
  fullWidthSummary: { type: Boolean, default: false },
  rootClass: { type: String, default: '' },
  summaryClass: { type: String, default: '' },
  /** Panel header (field name); chip uses summaryText only. */
  panelTitle: { type: String, default: '' },
  /** Highlight chip when a non-default filter value is applied. */
  active: { type: Boolean, default: false },
  /** Accessible name for the filter control when summary is a field label. */
  ariaLabel: { type: String, default: '' },
})

const detailsEl = ref(null)
useDetailsAutoClose(detailsEl)

function close() {
  const el = detailsEl.value
  if (el && 'open' in el) el.open = false
}

defineExpose({ close })
</script>
