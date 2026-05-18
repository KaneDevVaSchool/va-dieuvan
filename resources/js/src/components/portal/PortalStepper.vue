<template>
  <nav
    class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/80 to-white p-4 shadow-md shadow-slate-900/[0.06] ring-1 ring-slate-900/[0.04] backdrop-blur-sm sm:p-5"
    :aria-label="stepsNavLabel"
  >
    <ol class="flex items-center gap-0">
      <li v-for="(s, i) in steps" :key="s.key" class="flex min-w-0 flex-1 items-center">
        <div
          class="flex min-w-0 flex-1 flex-col items-center gap-2 text-center sm:flex-row sm:gap-3 sm:text-left"
          :class="current === i ? 'sm:ring-4 sm:rounded-2xl sm:bg-white/90 sm:px-3 sm:py-2 sm:ring-va-800/15 sm:shadow-sm' : ''"
        >
          <span
            class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold shadow-md transition-all duration-200"
            :class="
              current === i
                ? 'bg-va-800 text-white ring-4 ring-va-800/25 shadow-va-900/25'
                : current > i
                  ? 'bg-emerald-600 text-white shadow-emerald-900/20'
                  : 'bg-slate-200 text-slate-600 shadow-inner'
            "
          >
            <CheckIcon v-if="current > i" class="h-5 w-5 stroke-[2.5]" aria-hidden="true" />
            <span v-else>{{ i + 1 }}</span>
          </span>
          <span
            class="min-w-0 max-w-full truncate text-xs font-semibold sm:flex-1 sm:overflow-visible sm:whitespace-normal sm:text-sm"
            :class="current === i ? 'text-slate-900' : current > i ? 'text-emerald-800' : 'text-slate-400'"
          >
            {{ s.label }}
          </span>
        </div>
        <div
          v-if="i < steps.length - 1"
          class="mx-1 hidden h-0.5 min-w-[0.75rem] flex-1 rounded-full bg-gradient-to-r sm:block md:mx-3"
          :class="
            current > i
              ? 'from-emerald-400 via-emerald-300 to-slate-200'
              : 'from-slate-200 via-slate-200 to-slate-200'
          "
          aria-hidden="true"
        />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline'

defineProps({
  steps: { type: Array, required: true },
  current: { type: Number, default: 0 },
  stepsNavLabel: { type: String, default: '' },
})
</script>
