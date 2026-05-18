<template>
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-6 py-12 text-center shadow-sm">
    <ClipboardDocumentIcon
      class="mx-auto h-12 w-12 text-slate-300"
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
