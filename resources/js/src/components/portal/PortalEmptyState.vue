<template>
  <div class="rounded-2xl border border-dashed border-indigo-200/70 bg-white/85 px-6 py-12 text-center shadow-md shadow-indigo-900/[0.04] ring-1 ring-indigo-900/[0.03] backdrop-blur-sm">
    <ClipboardDocumentIcon
      class="mx-auto h-12 w-12 text-indigo-300"
      :class="{ 'animate-pulse': pulseIcon }"
      aria-hidden="true"
    />
    <h2 class="mt-4 text-base font-semibold text-slate-900">{{ title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ description }}</p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
import { ClipboardDocumentIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  /** Pulse animation on icon (e.g. transitional loading affordance) */
  pulseIcon: { type: Boolean, default: false },
})
</script>
