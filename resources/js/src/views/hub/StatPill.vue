<template>
  <div
    class="rounded-xl border p-4"
    :class="{
      'border-amber-200 bg-amber-50/80': tone === 'amber',
      'border-slate-200 bg-white': tone === 'slate',
      'border-emerald-200 bg-emerald-50/80': tone === 'emerald',
      'border-violet-200 bg-violet-50/80': tone === 'violet',
    }"
  >
    <div class="text-xs font-medium text-slate-600">{{ label }}</div>
    <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
      <span v-if="loading" class="text-sm font-normal text-slate-400">…</span>
      <span v-else-if="value === undefined || value === null" class="text-sm font-normal text-slate-400">—</span>
      <span v-else-if="typeof value === 'string'">{{ value }}</span>
      <span v-else>{{ value }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: undefined },
  loading: { type: Boolean, default: false },
  tone: { type: String, default: 'slate' },
})
</script>
