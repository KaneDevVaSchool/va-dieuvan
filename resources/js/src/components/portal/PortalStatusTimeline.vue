<template>
  <section
    class="rounded-2xl border p-4 sm:p-5"
    :class="
      variant === 'staff'
        ? 'border-slate-200 bg-transparent dark:border-slate-800'
        : 'border-slate-200 bg-white'
    "
  >
    <h2
      v-if="title"
      class="text-sm font-bold uppercase tracking-wide"
      :class="variant === 'staff' ? 'text-slate-500 dark:text-slate-400' : 'font-semibold text-slate-900'"
    >
      {{ title }}
    </h2>

    <!-- Mobile: vertical -->
    <ol class="mt-4 md:hidden">
      <li v-for="(step, idx) in steps" :key="`${step.key}-m`" class="flex gap-3">
        <div class="flex w-9 shrink-0 flex-col items-center">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold"
            :class="circleClass(step.state)"
            :aria-current="step.state === 'current' ? 'step' : undefined"
          >
            <CheckIcon v-if="step.state === 'done'" class="h-4 w-4" />
            <span v-else-if="step.state === 'rejected'" class="text-xs font-bold">!</span>
            <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-va-800 dark:bg-va-400" />
            <span v-else class="text-[10px] text-slate-300 dark:text-slate-600">·</span>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mt-1 min-h-[1.75rem] w-px flex-1 bg-slate-200 dark:bg-slate-700"
            :class="step.state === 'done' ? '!bg-va-600 dark:!bg-va-500' : ''"
            aria-hidden="true"
          />
        </div>
        <div class="min-w-0 flex-1 pb-5 pt-0.5">
          <p class="text-sm font-semibold leading-snug" :class="labelClass(step.state)">
            {{ step.label }}
          </p>
          <p v-if="step.actor" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ step.actor }}</p>
          <p v-if="step.sub && step.sub !== '—'" class="mt-0.5 text-xs text-slate-500 dark:text-slate-500">
            {{ step.sub }}
          </p>
        </div>
      </li>
    </ol>

    <!-- Desktop: horizontal -->
    <div class="mt-4 hidden md:block">
      <div class="flex w-full items-start">
        <template v-for="(step, idx) in steps" :key="`${step.key}-d`">
          <div
            class="flex min-w-0 flex-1 flex-col items-center px-0.5 text-center sm:px-1"
            :class="
              step.state === 'current'
                ? 'rounded-lg bg-va-50/90 py-2 ring-1 ring-va-200/70 dark:bg-va-950/30 dark:ring-va-800/50'
                : ''
            "
          >
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 sm:h-10 sm:w-10"
              :class="circleClass(step.state)"
              :aria-current="step.state === 'current' ? 'step' : undefined"
            >
              <CheckIcon v-if="step.state === 'done'" class="h-4 w-4 sm:h-5 sm:w-5" />
              <span v-else-if="step.state === 'rejected'" class="text-sm font-bold">!</span>
              <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-va-800 dark:bg-va-400" />
              <span v-else class="text-xs text-slate-300 dark:text-slate-600">·</span>
            </div>
            <p class="mt-2 line-clamp-2 text-xs font-semibold leading-tight sm:text-sm" :class="labelClass(step.state)">
              {{ step.label }}
            </p>
            <p v-if="step.actor" class="mt-0.5 line-clamp-1 text-[11px] text-slate-600 dark:text-slate-400">
              {{ step.actor }}
            </p>
            <p
              v-if="step.sub && step.sub !== '—'"
              class="mt-0.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-500"
            >
              {{ step.sub }}
            </p>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mx-0.5 mt-[1.15rem] h-0.5 min-w-[0.25rem] flex-1 shrink self-start sm:mt-5"
            :class="connectorClass(step, steps[idx + 1])"
            aria-hidden="true"
          />
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, default: '' },
  steps: { type: Array, required: true },
  /** `staff` — dark shell on /mng request detail */
  variant: { type: String, default: 'portal' },
})

function circleClass(state) {
  if (state === 'done') return 'border-va-600 bg-va-600 text-white dark:border-va-500 dark:bg-va-500'
  if (state === 'current') return 'border-va-600 bg-white text-va-800 dark:border-va-400 dark:bg-slate-900'
  if (state === 'rejected') return 'border-rose-400 bg-white text-rose-600 dark:bg-slate-900'
  return 'border-slate-200 bg-white text-slate-300 dark:border-slate-700 dark:bg-slate-900'
}

function labelClass(state) {
  if (state === 'current') return 'text-va-800 dark:text-va-300'
  if (state === 'upcoming') return 'text-slate-400 dark:text-slate-500'
  if (state === 'rejected') return 'text-rose-700 dark:text-rose-400'
  return 'text-slate-800 dark:text-slate-200'
}

function connectorClass(step, nextStep) {
  if (step.state === 'done' || nextStep?.state === 'done') {
    return 'bg-va-500/70 dark:bg-va-600/60'
  }
  return 'bg-slate-200 dark:bg-slate-700'
}
</script>
