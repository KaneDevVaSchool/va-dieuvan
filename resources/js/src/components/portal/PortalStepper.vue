<template>
  <nav class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4" :aria-label="stepsNavLabel">
    <ol class="flex gap-1 sm:gap-2 md:justify-center md:gap-4">
      <li
        v-for="(s, i) in steps"
        :key="s.key"
        class="flex min-w-0 flex-1 items-center gap-1.5 rounded-xl px-2 py-2 text-xs font-medium sm:gap-2 sm:text-sm md:max-w-[10rem] md:flex-none"
        :class="
          current === i
            ? 'bg-slate-50 text-slate-900 ring-2 ring-va-800/25'
            : current > i
              ? 'text-emerald-800'
              : 'text-slate-400'
        "
      >
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold sm:h-8 sm:w-8"
          :class="
            current === i ? 'bg-va-800 text-white' : current > i ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'
          "
        >
          <CheckIcon v-if="current > i" class="h-4 w-4 stroke-[2.5]" aria-hidden="true" />
          <span v-else>{{ i + 1 }}</span>
        </span>
        <span class="min-w-0 truncate md:whitespace-normal md:text-center">{{ s.label }}</span>
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
