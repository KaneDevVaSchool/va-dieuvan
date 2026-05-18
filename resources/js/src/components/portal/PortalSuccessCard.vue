<template>
  <div
    role="status"
    class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-md"
  >
    <div class="flex items-start gap-4">
      <CheckCircleIcon class="h-10 w-10 shrink-0 text-emerald-600" aria-hidden="true" />
      <div class="min-w-0 flex-1 space-y-2">
        <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
        <slot />
        <div class="flex flex-wrap items-center gap-2">
          <span
            v-if="badge"
            class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
          >
            {{ badge }}
          </span>
        </div>
        <p class="font-mono text-2xl font-bold tracking-tight text-slate-900">#{{ requestId }}</p>
        <p v-if="hint" class="text-sm text-slate-500">{{ hint }}</p>
        <div v-if="$slots.actions" class="mt-4 flex flex-wrap gap-2">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CheckCircleIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, required: true },
  badge: { type: String, default: '' },
  requestId: { type: [String, Number], required: true },
  hint: { type: String, default: '' },
})
</script>
