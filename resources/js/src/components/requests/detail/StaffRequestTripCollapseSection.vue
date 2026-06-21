<template>
  <section
    class="overflow-hidden rounded-lg border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900/90"
    :class="accentClass"
    :aria-label="title"
    :data-testid="`staff-request-trip-section-${sectionKey}`"
  >
    <header
      class="flex items-center gap-1 border-b border-slate-100 px-2 py-1.5 dark:border-slate-800"
      :class="headerTintClass"
    >
      <button
        type="button"
        class="shrink-0 rounded p-0.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
        :aria-expanded="open"
        :aria-label="open ? collapseLabel : expandLabel"
        :data-testid="`staff-request-trip-section-toggle-${sectionKey}`"
        @click="open = !open"
      >
        <ChevronDownIcon
          :class="['h-4 w-4 transition-transform duration-150', open ? 'rotate-0' : '-rotate-90']"
          aria-hidden="true"
        />
      </button>
      <h3 class="min-w-0 flex-1 truncate text-[11px] font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">
        {{ title }}
      </h3>
      <span
        v-if="badge"
        class="max-w-[8rem] shrink-0 truncate rounded px-1.5 py-px text-[9px] font-semibold uppercase tracking-wide"
        :class="badgeClass"
      >
        {{ badge }}
      </span>
      <slot name="header-actions" />
    </header>
    <div v-show="open" class="px-2 py-2">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  sectionKey: { type: String, required: true },
  title: { type: String, required: true },
  badge: { type: String, default: '' },
  tone: {
    type: String,
    default: 'slate',
    validator: (v) => ['brand', 'sky', 'emerald', 'amber', 'violet', 'rose', 'slate'].includes(v),
  },
  defaultOpen: { type: Boolean, default: true },
  expandLabel: { type: String, required: true },
  collapseLabel: { type: String, required: true },
})

const open = ref(props.defaultOpen)

const toneMap = {
  brand: {
    accent: 'border-l-[3px] border-l-va-500 dark:border-l-va-400',
    header: 'bg-va-50/50 dark:bg-va-950/20',
    badge: 'bg-va-100 text-va-800 dark:bg-va-950/50 dark:text-va-300',
  },
  sky: {
    accent: 'border-l-[3px] border-l-sky-500 dark:border-l-sky-400',
    header: 'bg-sky-50/40 dark:bg-sky-950/15',
    badge: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300',
  },
  emerald: {
    accent: 'border-l-[3px] border-l-emerald-500 dark:border-l-emerald-400',
    header: 'bg-emerald-50/40 dark:bg-emerald-950/15',
    badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
  },
  amber: {
    accent: 'border-l-[3px] border-l-amber-500 dark:border-l-amber-400',
    header: 'bg-amber-50/40 dark:bg-amber-950/15',
    badge: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
  },
  violet: {
    accent: 'border-l-[3px] border-l-violet-500 dark:border-l-violet-400',
    header: 'bg-violet-50/40 dark:bg-violet-950/15',
    badge: 'bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-300',
  },
  rose: {
    accent: 'border-l-[3px] border-l-rose-500 dark:border-l-rose-400',
    header: 'bg-rose-50/40 dark:bg-rose-950/15',
    badge: 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300',
  },
  slate: {
    accent: 'border-l-[3px] border-l-slate-300 dark:border-l-slate-600',
    header: '',
    badge: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  },
}

const accentClass = computed(() => toneMap[props.tone].accent)
const headerTintClass = computed(() => toneMap[props.tone].header)
const badgeClass = computed(() => toneMap[props.tone].badge)
</script>
