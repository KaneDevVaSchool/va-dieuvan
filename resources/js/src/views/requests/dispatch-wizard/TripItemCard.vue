<template>
  <article class="rounded-xl bg-white/90 px-4 py-3.5 shadow-sm shadow-slate-900/[0.04] sm:px-5 sm:py-4">
    <h4 class="text-sm font-semibold text-slate-900">{{ heading }}</h4>
    <ul class="mt-2 space-y-1.5 text-xs text-slate-700">
      <li v-for="(line, idx) in lines" :key="idx" class="flex gap-1.5 leading-snug">
        <span class="shrink-0 text-slate-400">-</span>
        <span>
          <span v-if="line.label" class="font-medium text-slate-600">{{ line.label }}:</span>
          {{ line.value }}
        </span>
      </li>
    </ul>
  </article>
</template>

<script setup>
defineProps({
  heading: { type: String, required: true },
  /** @type {{ label?: string, value: string }[]} */
  lines: { type: Array, default: () => [] },
})
</script>
