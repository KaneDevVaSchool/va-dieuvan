<template>
  <ol
    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4"
    data-testid="staff-request-trip-tab-execution-timeline"
  >
    <li
      v-for="(step, idx) in steps"
      :key="step.key"
      class="min-w-0 list-none"
      :aria-current="step.state === 'current' ? 'step' : undefined"
    >
      <article
        class="flex h-full flex-col overflow-hidden rounded-lg border bg-white dark:bg-slate-900/50"
        :class="cardClass(step.state)"
      >
        <header class="flex items-center gap-3 border-b border-inherit px-3.5 py-3 dark:border-slate-700/80">
          <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
            :class="circleClass(step.state)"
          >
            <CheckIcon v-if="step.state === 'done'" class="h-4 w-4" aria-hidden="true" />
            <span v-else class="tabular-nums">{{ idx + 1 }}</span>
          </span>
          <p class="min-w-0 text-base font-semibold leading-snug" :class="labelClass(step.state)">
            {{ step.label }}
          </p>
        </header>

        <dl class="flex flex-1 flex-col divide-y divide-slate-100 dark:divide-slate-800">
          <div class="flex flex-col gap-0.5 px-3.5 py-3 sm:min-h-[4.25rem]">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
              {{ plannedLabel }}
            </dt>
            <dd class="text-sm tabular-nums leading-snug" :class="valueClass(step.planned, true)">
              {{ step.planned }}
            </dd>
          </div>
          <div
            class="flex flex-col gap-0.5 px-3.5 py-3 sm:min-h-[4.25rem]"
            :class="step.state === 'current' ? 'bg-va-50/60 dark:bg-va-950/25' : 'bg-slate-50/80 dark:bg-slate-800/30'"
          >
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
              {{ actualLabel }}
            </dt>
            <dd
              class="text-sm tabular-nums leading-snug"
              :class="valueClass(step.actual)"
            >
              {{ step.actual }}
            </dd>
          </div>
        </dl>
      </article>
    </li>
  </ol>
</template>

<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  steps: { type: Array, required: true },
  plannedLabel: { type: String, required: true },
  actualLabel: { type: String, required: true },
  /** Giá trị placeholder khi chưa có dữ liệu (i18n). */
  emptyValue: { type: String, default: '' },
  plannedShort: { type: String, default: '' },
  actualShort: { type: String, default: '' },
})

function cardClass(state) {
  if (state === 'done') {
    return 'border-va-200/90 shadow-sm dark:border-va-800/60'
  }
  if (state === 'current') {
    return 'border-va-400 ring-2 ring-va-500/20 shadow-sm dark:border-va-600 dark:ring-va-500/25'
  }
  return 'border-slate-200 border-dashed dark:border-slate-700'
}

function circleClass(state) {
  if (state === 'done') {
    return 'border-2 border-va-600 bg-va-600 text-white dark:border-va-500 dark:bg-va-500'
  }
  if (state === 'current') {
    return 'border-2 border-va-600 bg-va-50 text-va-900 dark:border-va-500 dark:bg-va-950/50 dark:text-va-100'
  }
  return 'border-2 border-dashed border-slate-300 bg-slate-50 text-slate-500 dark:border-slate-600 dark:bg-slate-800/50'
}

function labelClass(state) {
  if (state === 'current') return 'text-va-900 dark:text-va-100'
  if (state === 'pending') return 'text-slate-500 dark:text-slate-400'
  return 'text-slate-900 dark:text-slate-100'
}

function valueClass(text, planned = false) {
  const empty = props.emptyValue
  const isEmpty =
    !text
    || text === '—'
    || text === '-'
    || (empty && text === empty)
  if (isEmpty) {
    return planned
      ? 'font-medium text-slate-500 dark:text-slate-400'
      : 'font-medium italic text-slate-400 dark:text-slate-500'
  }
  return 'font-semibold text-slate-900 dark:text-slate-100'
}
</script>
