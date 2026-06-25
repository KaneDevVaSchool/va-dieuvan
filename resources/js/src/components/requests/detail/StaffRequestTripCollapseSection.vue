<template>
  <section
    :class="rootClass"
    :aria-label="title"
    :data-testid="`staff-request-trip-section-${sectionKey}`"
  >
    <header
      class="flex items-center gap-2"
      :class="headerClass"
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
      <h3 :class="titleClass">
        {{ title }}
      </h3>
      <span
        v-if="badge"
        class="max-w-[8rem] shrink-0 truncate rounded px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-600 dark:text-slate-300"
        :class="variant === 'stack' ? badgeClass : 'bg-slate-100 dark:bg-slate-800'"
      >
        {{ badge }}
      </span>
      <slot name="header-actions" />
    </header>
    <div v-show="!collapsible || open" :class="bodyClass">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { RD_SECTION_EYEBROW } from '../../../util/requestDetailTypography'

const props = defineProps({
  sectionKey: { type: String, required: true },
  title: { type: String, required: true },
  badge: { type: String, default: '' },
  tone: {
    type: String,
    default: 'slate',
    validator: (v) => ['brand', 'sky', 'emerald', 'amber', 'violet', 'rose', 'slate'].includes(v),
  },
  /** `embedded` — single card interior (overview-style). `stack` — separate bordered cards. */
  variant: {
    type: String,
    default: 'embedded',
    validator: (v) => ['embedded', 'stack'].includes(v),
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

const rootClass = computed(() =>
  props.variant === 'stack'
    ? 'overflow-hidden rounded-lg border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900/90'
    : 'border-b border-slate-100 last:border-b-0 dark:border-slate-800',
)

const headerClass = computed(() =>
  props.variant === 'stack'
    ? ['border-b border-slate-100 px-4 py-3 dark:border-slate-800', toneMap[props.tone].header]
    : 'border-b border-slate-100 px-4 py-2.5 dark:border-slate-800 sm:px-5',
)

const titleClass = computed(() =>
  props.variant === 'stack'
    ? 'min-w-0 flex-1 truncate text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200'
    : `min-w-0 flex-1 truncate ${RD_SECTION_EYEBROW}`,
)

const bodyClass = computed(() =>
  props.variant === 'stack' ? 'px-4 py-4' : 'px-4 py-3 sm:px-5 sm:py-4',
)

const badgeClass = computed(() => toneMap[props.tone].badge)
</script>
