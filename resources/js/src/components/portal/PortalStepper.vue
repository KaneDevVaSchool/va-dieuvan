<template>
  <nav
    class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:p-3"
    :aria-label="stepsNavLabel"
  >
    <ol
      class="flex snap-x snap-mandatory items-stretch gap-0 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:gap-0 [&::-webkit-scrollbar]:hidden"
    >
      <li
        v-for="(s, i) in steps"
        :key="s.key"
        class="flex min-w-[44%] shrink-0 snap-start flex-col sm:min-w-0 sm:flex-1"
      >
        <div class="flex items-center">
          <div
            class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left antialiased sm:flex-col sm:items-center sm:gap-2 sm:px-2 sm:py-2"
            :class="
              current === i
                ? 'border border-indigo-200/50 bg-indigo-50/70 text-slate-900 shadow-sm'
                : current > i
                  ? 'border border-transparent text-slate-800'
                  : 'border border-transparent text-slate-500'
            "
            role="presentation"
          >
            <span
              class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold shadow-sm sm:h-8 sm:w-8 sm:text-[11px]"
              :class="
                current === i
                  ? 'bg-indigo-600 text-white ring-4 ring-indigo-100'
                  : current > i
                    ? 'bg-emerald-500 text-white'
                    : 'bg-slate-200 text-slate-400'
              "
            >
              <CheckIcon v-if="current > i" class="h-3 w-3 sm:h-3.5 sm:w-3.5" aria-hidden="true" />
              <span v-else>{{ i + 1 }}</span>
            </span>
            <span
              class="min-w-0 flex-1 text-[11px] font-semibold leading-snug sm:text-center sm:text-xs sm:leading-snug"
              :class="current === i ? 'text-slate-900' : current > i ? 'text-slate-800' : 'text-slate-500'"
            >
              {{ s.label }}
            </span>
          </div>
          <div
            v-if="i < steps.length - 1"
            class="mx-0.5 hidden h-0.5 w-5 shrink-0 rounded-full sm:block md:w-8 lg:w-11"
            :class="current > i ? 'bg-gradient-to-r from-emerald-400 to-slate-200' : 'bg-slate-200'"
            aria-hidden="true"
          />
        </div>
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
