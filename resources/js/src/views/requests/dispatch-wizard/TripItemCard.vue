<template>
  <article class="rounded-lg bg-white/90 px-3.5 py-3 ring-1 ring-slate-200/60 sm:px-4 sm:py-3.5">
    <h4 class="border-b border-slate-200/70 pb-2 text-sm font-semibold text-slate-900">{{ heading }}</h4>
    <ul class="mt-2.5 divide-y divide-slate-100">
      <li
        v-for="(line, idx) in lines"
        :key="idx"
        class="grid grid-cols-1 gap-0.5 py-2 first:pt-0 last:pb-0 sm:grid-cols-[4.5rem_1fr] sm:gap-x-2"
      >
        <span v-if="line.label" class="text-[11px] font-medium text-slate-500 sm:text-xs">{{ line.label }}</span>
        <span class="min-w-0 text-xs leading-snug text-slate-800">{{ line.value }}</span>
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
