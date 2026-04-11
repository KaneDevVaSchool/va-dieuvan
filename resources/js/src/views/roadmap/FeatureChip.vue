<template>
  <div
    class="flex flex-col rounded-lg border p-3 text-sm"
    :class="status === 'live' ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-slate-50'"
  >
    <div class="flex items-center gap-2">
      <span
        class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
        :class="status === 'live' ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-200 text-slate-700'"
      >
        {{ status === 'live' ? 'Live' : 'Plan' }}
      </span>
      <span class="font-medium text-slate-900">{{ title }}</span>
    </div>
    <p class="mt-1 text-xs text-slate-600">{{ detail }}</p>
  </div>
</template>

<script setup>
defineProps({
  status: { type: String, default: 'live' },
  title: { type: String, required: true },
  detail: { type: String, required: true },
})
</script>
