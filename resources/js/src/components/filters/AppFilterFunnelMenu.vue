<template>
  <details ref="root" class="group relative">
    <summary
      class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
    >
      <span class="relative inline-flex">
        <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
        <span
          v-if="badgeCount > 0"
          class="absolute -right-1.5 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-semibold leading-none text-white dark:bg-teal-500"
        >
          {{ badgeCount > 9 ? '9+' : badgeCount }}
        </span>
      </span>
      <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
    </summary>
    <div
      class="absolute left-0 top-[calc(100%+6px)] z-50 max-h-[min(70vh,24rem)] min-w-[260px] overflow-y-auto rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
    >
      <slot />
    </div>
  </details>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'

defineProps({
  badgeCount: { type: Number, default: 0 },
})

const root = ref(null)
useDetailsAutoClose(root)

function close() {
  const el = root.value
  if (el && 'open' in el) el.open = false
}

defineExpose({ close })
</script>
