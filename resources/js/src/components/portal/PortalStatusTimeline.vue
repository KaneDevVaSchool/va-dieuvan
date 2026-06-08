<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
    <h2 v-if="title" class="text-sm font-semibold text-slate-900">{{ title }}</h2>

    <!-- Mobile / tablet: vertical -->
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
            <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-va-800" />
            <span v-else class="text-[10px] text-slate-300">·</span>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mt-1 min-h-[1.75rem] w-px flex-1 bg-slate-200"
            :class="step.state === 'done' ? '!bg-va-700' : ''"
            aria-hidden="true"
          />
        </div>
        <div class="min-w-0 flex-1 pb-6 pt-0.5">
          <p
            class="text-sm font-semibold leading-snug"
            :class="step.state === 'current' ? 'text-va-900' : 'text-slate-900'"
          >
            {{ step.label }}
          </p>
          <p v-if="step.actor" class="mt-0.5 text-xs text-slate-600">{{ step.actor }}</p>
          <p v-if="step.sub && step.sub !== '—'" class="mt-0.5 text-xs text-slate-500">{{ step.sub }}</p>
        </div>
      </li>
    </ol>

    <!-- Desktop: horizontal -->
    <div class="mt-5 hidden md:block">
      <div class="flex w-full items-start">
        <template v-for="(step, idx) in steps" :key="`${step.key}-d`">
          <div
            class="flex min-w-0 flex-1 flex-col items-center px-1 text-center"
            :class="step.state === 'current' ? 'rounded-lg bg-va-50/80 py-2 ring-1 ring-va-200/60' : ''"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2"
              :class="circleClass(step.state)"
              :aria-current="step.state === 'current' ? 'step' : undefined"
            >
              <CheckIcon v-if="step.state === 'done'" class="h-5 w-5" />
              <span v-else-if="step.state === 'rejected'" class="text-sm font-bold">!</span>
              <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-va-800" />
              <span v-else class="text-xs text-slate-300">·</span>
            </div>
            <p
              class="mt-2 line-clamp-2 text-xs font-semibold leading-tight lg:text-sm"
              :class="step.state === 'current' ? 'text-va-900' : 'text-slate-800'"
            >
              {{ step.label }}
            </p>
            <p v-if="step.actor" class="mt-0.5 line-clamp-1 text-[11px] text-slate-600">{{ step.actor }}</p>
            <p v-if="step.sub && step.sub !== '—'" class="mt-0.5 text-[11px] text-slate-500">{{ step.sub }}</p>
          </div>
          <div
            v-if="idx < steps.length - 1"
            class="mx-0.5 mt-5 h-px min-w-[0.35rem] flex-1 shrink self-start"
            :class="step.state === 'done' ? 'bg-va-700' : 'bg-slate-200'"
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
  if (state === 'done') return 'border-va-700 bg-va-800 text-white'
  if (state === 'current') return 'border-va-800 bg-white text-va-800'
  if (state === 'rejected') return 'border-rose-400 bg-white text-rose-600'
  return 'border-slate-200 bg-white text-slate-300'
}
</script>
