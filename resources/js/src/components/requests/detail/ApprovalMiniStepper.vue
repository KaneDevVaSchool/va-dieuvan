<template>
  <div
    class="flex flex-wrap items-center gap-x-3 gap-y-1.5"
    role="list"
    :aria-label="ariaLabel"
    data-testid="approval-mini-stepper"
  >
    <template v-for="(step, idx) in steps" :key="step.key">
      <div class="flex min-w-0 items-center gap-1.5" role="listitem">
        <span
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold tabular-nums"
          :class="dotClass(step.state)"
          :aria-current="step.state === 'current' ? 'step' : undefined"
        >
          <CheckIcon v-if="step.state === 'done'" class="h-3 w-3" aria-hidden="true" />
          <span v-else-if="step.state === 'rejected'" aria-hidden="true">!</span>
          <span v-else-if="step.state === 'current'" class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
          <span v-else class="h-1.5 w-1.5 rounded-full bg-current opacity-40" aria-hidden="true" />
        </span>
        <span
          class="truncate text-[11px] font-medium leading-none"
          :class="labelClass(step.state)"
        >
          {{ step.label }}
        </span>
      </div>
      <span
        v-if="idx < steps.length - 1"
        class="hidden h-px w-3 shrink-0 bg-slate-200 dark:bg-slate-700 sm:block"
        aria-hidden="true"
      />
    </template>
  </div>
</template>

<script setup>
import { CheckIcon } from '@heroicons/vue/24/solid'

defineProps({
  steps: { type: Array, default: () => [] },
  ariaLabel: { type: String, default: '' },
})

function dotClass(state) {
  if (state === 'done') return 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
  if (state === 'current') return 'bg-va-600 text-white ring-2 ring-va-600/20 dark:bg-va-500'
  if (state === 'rejected') return 'bg-rose-600 text-white'
  return 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
}

function labelClass(state) {
  if (state === 'current') return 'text-slate-900 dark:text-slate-100'
  if (state === 'done') return 'text-slate-500 dark:text-slate-400'
  if (state === 'rejected') return 'text-rose-700 dark:text-rose-300'
  return 'text-slate-400 dark:text-slate-500'
}
</script>
