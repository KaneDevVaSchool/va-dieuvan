<template>
  <div
    v-if="messages.length"
    class="rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 shadow-sm ring-1 ring-amber-900/[0.06] sm:px-5 sm:py-4"
    role="alert"
  >
    <p class="text-sm font-semibold text-amber-950">{{ title }}</p>
    <ul class="mt-2 list-inside list-disc space-y-1 text-xs font-medium text-amber-900/95">
      <li v-for="(msg, i) in messages" :key="i">{{ msg }}</li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  messages: { type: Array, default: () => [] },
})
</script>
