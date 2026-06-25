<template>
  <nav
    class="rounded-xl border border-slate-200/80 bg-white/95 p-2 shadow-sm sm:p-2.5"
    :aria-label="stepsNavLabel"
  >
    <p class="mb-2 text-center text-[11px] font-medium text-slate-500 sm:hidden">
      {{ steps[current]?.label }}
    </p>
    <ol
      class="flex items-stretch gap-0"
    >
      <li
        v-for="(s, i) in steps"
        :key="s.key"
        class="flex min-w-0 flex-1 flex-col"
      >
        <div class="flex items-center">
          <button
            v-if="interactive"
            type="button"
            class="group flex w-full items-center justify-center gap-1.5 rounded-lg px-1 py-1.5 text-left antialiased outline-none transition focus:outline-none focus-visible:outline-none sm:flex-col sm:items-center sm:gap-1.5 sm:px-2 sm:py-2"
            :class="
              i > maxReachedStep
                ? 'cursor-not-allowed opacity-45'
                : current === i
                  ? 'border border-va-200/50 bg-va-50/70 text-slate-900 shadow-sm'
                  : current > i
                    ? 'border border-transparent text-slate-800 hover:bg-emerald-50'
                    : 'border border-transparent text-slate-500 hover:bg-slate-50'
            "
            :disabled="i > maxReachedStep"
            :aria-current="current === i ? 'step' : undefined"
            @click="i <= maxReachedStep && $emit('select', i)"
          >
            <span
              class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold shadow-sm sm:h-8 sm:w-8 sm:text-xs"
              :class="
                current === i
                  ? 'bg-va-800 text-white ring-4 ring-va-100'
                  : current > i
                    ? 'bg-emerald-500 text-white'
                    : i <= maxReachedStep
                      ? 'bg-slate-200 text-slate-600 group-hover:bg-slate-300'
                      : 'bg-slate-200 text-slate-400'
              "
            >
              <CheckIcon v-if="current > i" class="h-4 w-4" aria-hidden="true" />
              <span v-else>{{ i + 1 }}</span>
            </span>
            <span
              class="hidden min-w-0 flex-1 text-xs font-semibold leading-snug sm:block sm:text-center sm:text-[13px] sm:leading-snug"
              :class="current === i ? 'text-slate-900' : current > i ? 'text-slate-800' : 'text-slate-500'"
            >
              {{ s.label }}
            </span>
          </button>
          <div
            v-else
            class="flex w-full items-center justify-center gap-1.5 rounded-lg px-1 py-1.5 text-left antialiased sm:flex-col sm:items-center sm:gap-1.5 sm:px-2 sm:py-2"
            :class="
              current === i
                ? 'border border-va-200/50 bg-va-50/70 text-slate-900 shadow-sm'
                : current > i
                  ? 'border border-transparent text-slate-800'
                  : 'border border-transparent text-slate-500'
            "
            role="presentation"
          >
            <span
              class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold shadow-sm sm:h-8 sm:w-8 sm:text-xs"
              :class="
                current === i
                  ? 'bg-va-800 text-white ring-4 ring-va-100'
                  : current > i
                    ? 'bg-emerald-500 text-white'
                    : 'bg-slate-200 text-slate-400'
              "
            >
              <CheckIcon v-if="current > i" class="h-4 w-4" aria-hidden="true" />
              <span v-else>{{ i + 1 }}</span>
            </span>
            <span
              class="hidden min-w-0 flex-1 text-xs font-semibold leading-snug sm:block sm:text-center sm:text-[13px] sm:leading-snug"
              :class="current === i ? 'text-slate-900' : current > i ? 'text-slate-800' : 'text-slate-500'"
            >
              {{ s.label }}
            </span>
          </div>
          <div
            v-if="i < steps.length - 1"
            class="mx-0.5 h-0.5 w-3 shrink-0 rounded-full sm:w-5 md:w-8 lg:w-10"
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
  /** Khi bật, cho phép nhảy tới các bước đã đi qua (`maxReachedStep`). */
  interactive: { type: Boolean, default: false },
  maxReachedStep: { type: Number, default: 0 },
})

defineEmits(['select'])
</script>
