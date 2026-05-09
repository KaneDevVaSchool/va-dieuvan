<template>
  <span class="rounded-full px-1.5 py-0.5 text-xs font-semibold sm:text-sm" :class="pillClass">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: '' },
  label: { type: String, default: '' },
})

const pillClass = computed(() => {
  const s = String(props.status ?? '').trim().toLowerCase()
  if (s === 'confirmed') return 'bg-emerald-500/20 text-emerald-300'
  if (s === 'rejected') return 'bg-rose-500/20 text-rose-300'
  if (s === 'draft') return 'bg-slate-500/25 text-slate-300'
  return 'bg-amber-500/20 text-amber-100'
})
</script>
