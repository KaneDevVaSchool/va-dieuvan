<template>
  <RouterLink
    :to="to"
    class="group flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="text-sm font-semibold text-slate-900 group-hover:text-slate-950">{{ title }}</div>
      <span
        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-600"
      >
        {{ tag }}
      </span>
    </div>
    <p class="mt-2 flex-1 text-xs leading-relaxed text-slate-600">{{ desc }}</p>
    <div class="mt-3 text-xs font-medium text-slate-900 underline-offset-2 group-hover:underline">Mở →</div>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  to: { type: String, required: true },
  title: { type: String, required: true },
  desc: { type: String, required: true },
  tag: { type: String, default: '' },
})
</script>
