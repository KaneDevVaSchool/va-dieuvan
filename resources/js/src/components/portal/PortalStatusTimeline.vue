<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ title }}</h2>

    <!-- Mobile / tablet: vertical timeline -->
    <ol class="mt-6 md:hidden">
      <li v-for="(step, idx) in steps" :key="`${step.key}-m`" class="flex gap-3">
        <div class="flex w-11 shrink-0 flex-col items-center">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors"
            :class="circleClass(step.state)"
            :aria-current="step.state === 'current' ? 'step' : undefined"
          >
            <CheckIcon v-if="step.state === 'done'" class="h-5 w-5" />
            <span v-else-if="step.state === 'rejected'" class="text-sm font-bold">!</span>
            <span v-else-if="step.state === 'current'" class="h-2.5 w-2.5 rounded-full bg-teal-600" />
            <span v-else class="text-xs text-slate-300">·</span>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mt-1 min-h-[2.25rem] w-0.5 flex-1 rounded-full"
            :class="step.state === 'done' ? 'bg-teal-500' : 'bg-slate-200'"
            aria-hidden="true"
          />
        </div>
        <div class="min-w-0 flex-1 pb-8 pt-1">
          <p class="text-sm font-semibold leading-snug text-slate-900">{{ step.label }}</p>
          <p v-if="step.sub && step.sub !== '—'" class="mt-1 text-xs leading-snug text-slate-500">{{ step.sub }}</p>
        </div>
      </li>
    </ol>

    <!-- Desktop: horizontal scroll -->
    <div class="-mx-2 mt-6 hidden px-2 pb-2 md:block md:overflow-x-auto">
      <div class="flex min-w-min items-start">
        <template v-for="(step, idx) in steps" :key="`${step.key}-d`">
          <div class="flex min-w-[5rem] max-w-[7rem] flex-1 flex-col items-center px-1 text-center lg:min-w-[6rem] lg:max-w-none xl:flex-none xl:flex-1">
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors"
              :class="circleClass(step.state)"
              :aria-current="step.state === 'current' ? 'step' : undefined"
            >
              <CheckIcon v-if="step.state === 'done'" class="h-6 w-6" />
              <span v-else-if="step.state === 'rejected'" class="text-sm font-bold">!</span>
              <span v-else-if="step.state === 'current'" class="h-2.5 w-2.5 rounded-full bg-teal-600" />
              <span v-else class="text-xs text-slate-300">·</span>
            </div>
            <p class="mt-2 line-clamp-3 text-xs font-semibold leading-tight text-slate-800 lg:text-sm">
              {{ step.label }}
            </p>
            <p v-if="step.sub && step.sub !== '—'" class="mt-0.5 text-xs leading-tight text-slate-500 lg:text-xs">
              {{ step.sub }}
            </p>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mx-1 mt-[1.375rem] h-0.5 min-w-[1rem] flex-1 max-w-[2.5rem] shrink self-start xl:max-w-none xl:flex-1"
            :class="step.state === 'done' ? 'bg-teal-500' : 'bg-slate-200'"
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
  title: { type: String, required: true },
  steps: { type: Array, required: true },
})

function circleClass(state) {
  if (state === 'done') return 'border-teal-500 bg-teal-500 text-white'
  if (state === 'current') return 'border-teal-500 bg-white text-teal-600 ring-4 ring-teal-500/10'
  if (state === 'rejected') return 'border-rose-400 bg-white text-rose-500'
  return 'border-slate-200 bg-white text-slate-300'
}
</script>
