<template>
  <div data-testid="staff-request-trip-tab-execution-timeline">
    <ol class="space-y-0 sm:hidden">
      <li
        v-for="(step, idx) in steps"
        :key="`${step.key}-m`"
        class="flex gap-3 pb-4 last:pb-0"
      >
        <div class="flex shrink-0 flex-col items-center">
          <span
            class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold"
            :class="circleClass(step.state)"
            :aria-current="step.state === 'current' ? 'step' : undefined"
          >
            <CheckIcon v-if="step.state === 'done'" class="h-3 w-3" />
            <span v-else class="tabular-nums">{{ idx + 1 }}</span>
          </span>
          <span
            v-if="idx < steps.length - 1"
            class="mt-0.5 w-px min-h-[1.25rem] flex-1"
            :class="verticalConnectorClass(step.state)"
            aria-hidden="true"
          />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold leading-snug" :class="labelClass(step.state)">{{ step.label }}</p>
          <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
            <span class="text-slate-400">{{ plannedAbbrev }}:</span>
            <span class="tabular-nums text-slate-600 dark:text-slate-300">{{ step.planned }}</span>
          </p>
          <p class="text-xs leading-relaxed">
            <span class="text-slate-400">{{ actualAbbrev }}:</span>
            <span class="font-medium tabular-nums text-slate-800 dark:text-slate-200">{{ step.actual }}</span>
          </p>
        </div>
      </li>
    </ol>

    <div class="hidden sm:block">
      <div class="flex w-full items-start">
        <template v-for="(step, idx) in steps" :key="`${step.key}-d`">
          <div
            class="flex min-w-0 flex-1 flex-col items-center px-0.5 text-center"
            :class="step.state === 'current' ? 'rounded bg-sky-50/80 py-1 dark:bg-sky-950/20' : ''"
          >
            <span
              class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
              :class="circleClass(step.state)"
              :aria-current="step.state === 'current' ? 'step' : undefined"
            >
              <CheckIcon v-if="step.state === 'done'" class="h-3 w-3" />
              <span v-else class="tabular-nums">{{ idx + 1 }}</span>
            </span>
            <p class="mt-1.5 line-clamp-2 text-xs font-semibold leading-snug" :class="labelClass(step.state)">
              {{ step.label }}
            </p>
            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
              <span class="text-slate-400">{{ plannedAbbrev }}</span>
              {{ step.planned }}
            </p>
            <p class="line-clamp-2 text-[11px] font-medium leading-relaxed tabular-nums text-slate-800 dark:text-slate-200">
              <span class="font-normal text-slate-400">{{ actualAbbrev }}</span>
              {{ step.actual }}
            </p>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mx-0.5 mt-3 h-px min-w-[0.2rem] flex-1 shrink"
            :class="connectorClass(step.state, steps[idx + 1]?.state)"
            aria-hidden="true"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  steps: { type: Array, required: true },
  plannedLabel: { type: String, required: true },
  actualLabel: { type: String, required: true },
  plannedShort: { type: String, default: '' },
  actualShort: { type: String, default: '' },
})

const plannedAbbrev = computed(() => props.plannedShort || props.plannedLabel)
const actualAbbrev = computed(() => props.actualShort || props.actualLabel)

function circleClass(state) {
  if (state === 'done') return 'border border-va-600 bg-va-600 text-white dark:border-va-500 dark:bg-va-500'
  if (state === 'current') {
    return 'border border-sky-500 bg-sky-50 text-sky-800 ring-1 ring-sky-300/60 dark:border-sky-400 dark:bg-sky-950/40 dark:text-sky-200'
  }
  return 'border border-dashed border-slate-300 bg-slate-50 text-slate-500 dark:border-slate-600 dark:bg-slate-800/40'
}

function labelClass(state) {
  if (state === 'current') return 'text-sky-800 dark:text-sky-200'
  if (state === 'pending') return 'text-slate-400 dark:text-slate-500'
  return 'text-slate-700 dark:text-slate-200'
}

function verticalConnectorClass(stepState) {
  if (stepState === 'done') return 'bg-va-500 dark:bg-va-400'
  return 'bg-slate-200 dark:bg-slate-700'
}

function connectorClass(stepState, nextState) {
  if (stepState === 'done' || nextState === 'done' || nextState === 'current') {
    return 'bg-va-500/60 dark:bg-va-600/50'
  }
  return 'bg-slate-200 dark:bg-slate-700'
}
</script>
