<template>
  <div
    class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80"
  >
    <div
      class="flex items-start gap-1 border-b border-slate-100/90 px-2 py-2 dark:border-slate-800 sm:gap-2 sm:px-3 sm:py-2.5"
    >
      <button
        type="button"
        class="mt-0.5 shrink-0 rounded-lg p-1 text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-800"
        :aria-expanded="open"
        :aria-label="open ? collapseLabel : expandLabel"
        @click="open = !open"
      >
        <ChevronDownIcon
          :class="['h-5 w-5 transition-transform duration-200', open ? 'rotate-0' : '-rotate-90']"
          aria-hidden="true"
        />
      </button>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h3>
          <span v-if="badge" class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
            {{ badge }}
          </span>
        </div>
        <p v-if="hint" class="mt-0.5 text-xs leading-snug text-slate-500 dark:text-slate-400">
          {{ hint }}
        </p>
      </div>
    </div>
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-0.5"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-show="open" class="border-t border-slate-50 p-2 dark:border-slate-800/80 sm:p-3">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  title: { type: String, required: true },
  hint: { type: String, default: '' },
  badge: { type: String, default: '' },
  defaultOpen: { type: Boolean, default: true },
  expandLabel: { type: String, default: 'Expand' },
  collapseLabel: { type: String, default: 'Collapse' },
  /** When set, restore open state from sessionStorage */
  persistKey: { type: String, default: '' },
})

function readPersisted() {
  if (!props.persistKey || typeof sessionStorage === 'undefined') return props.defaultOpen
  try {
    const v = sessionStorage.getItem(`dash-chart:${props.persistKey}`)
    if (v === '0') return false
    if (v === '1') return true
  } catch {
    /* ignore */
  }
  return props.defaultOpen
}

const open = ref(readPersisted())

watch(
  () => props.persistKey,
  () => {
    open.value = readPersisted()
  },
)

watch(open, (v) => {
  if (!props.persistKey || typeof sessionStorage === 'undefined') return
  try {
    sessionStorage.setItem(`dash-chart:${props.persistKey}`, v ? '1' : '0')
  } catch {
    /* ignore */
  }
})
</script>
