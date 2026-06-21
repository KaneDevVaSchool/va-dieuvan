<template>
  <section
    class="overflow-hidden rounded-lg border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900/90"
    :aria-label="title"
    :data-testid="`staff-request-trip-section-${sectionKey}`"
  >
    <header
      class="flex items-center gap-1.5 border-b border-slate-100 px-3 py-2 dark:border-slate-800"
      :class="headerTintClass"
    >
      <button
        v-if="collapsible"
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
      <h3 class="min-w-0 flex-1 truncate text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">
        {{ title }}
      </h3>
      <span
        v-if="badge"
        class="max-w-[8rem] shrink-0 truncate rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
        :class="badgeClass"
      >
        {{ badge }}
      </span>
      <slot name="header-actions" />
    </header>
    <div v-show="!collapsible || open" class="px-3 py-3">
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
  collapsible: { type: Boolean, default: false },
  expandLabel: { type: String, default: '' },
  collapseLabel: { type: String, default: '' },
})

const open = ref(props.defaultOpen)

const toneMap = {
  brand: {
    header: 'bg-va-50/50 dark:bg-va-950/20',
    badge: 'bg-va-100 text-va-800 dark:bg-va-950/50 dark:text-va-300',
  },
  sky: {
    header: 'bg-sky-50/40 dark:bg-sky-950/15',
    badge: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300',
  },
  emerald: {
    header: 'bg-emerald-50/40 dark:bg-emerald-950/15',
    badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
  },
  amber: {
    header: 'bg-amber-50/40 dark:bg-amber-950/15',
    badge: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
  },
  violet: {
    header: 'bg-violet-50/40 dark:bg-violet-950/15',
    badge: 'bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-300',
  },
  rose: {
    header: 'bg-rose-50/40 dark:bg-rose-950/15',
    badge: 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300',
  },
  slate: {
    header: '',
    badge: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  },
}

const headerTintClass = computed(() => toneMap[props.tone].header)
const badgeClass = computed(() => toneMap[props.tone].badge)
</script>
