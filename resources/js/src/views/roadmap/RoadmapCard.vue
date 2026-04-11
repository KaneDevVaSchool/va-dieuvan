<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between gap-2">
      <span
        class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
        :class="{
          'bg-rose-100 text-rose-800': priority === 'P1',
          'bg-amber-100 text-amber-900': priority === 'P2',
          'bg-slate-100 text-slate-700': priority === 'P3',
        }"
      >
        {{ priority }}
      </span>
      <span class="text-[11px] text-slate-400">{{ tags }}</span>
    </div>
    <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ title }}</h3>
    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ body }}</p>
  </div>
</template>

<script setup>
defineProps({
  priority: { type: String, required: true },
  title: { type: String, required: true },
  body: { type: String, required: true },
  tags: { type: String, default: '' },
})
</script>
