<template>
  <details ref="detailsEl" class="group relative min-w-0" :class="rootClass">
    <summary
      class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
      :class="[fullWidthSummary ? 'max-w-full' : '', summaryClass]"
    >
      <span
        class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100"
        :class="summaryTextClass"
        :title="summaryTitle || undefined"
        >{{ summaryText }}</span
      >
      <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
    </summary>
    <div
      class="absolute left-0 top-[calc(100%+6px)] z-50 rounded-xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
      :class="panelClass"
    >
      <slot />
    </div>
  </details>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'

defineProps({
  /** Kept for backward compatibility; not rendered on the chip. */
  label: { type: String, default: '' },
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
})

const detailsEl = ref(null)
useDetailsAutoClose(detailsEl)

function close() {
  const el = detailsEl.value
  if (el && 'open' in el) el.open = false
}

defineExpose({ close })
</script>
