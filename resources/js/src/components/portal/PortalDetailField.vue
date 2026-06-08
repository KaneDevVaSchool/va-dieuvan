<template>
  <div class="grid grid-cols-1 gap-0.5 sm:grid-cols-[minmax(0,7rem)_1fr] sm:items-baseline sm:gap-x-4">
    <dt class="text-sm text-slate-500">{{ label }}</dt>
    <dd class="text-sm">
      <span v-if="hasValue" class="font-medium text-slate-900">{{ value }}</span>
      <span v-else class="italic text-slate-400">{{ emptyText }}</span>
    </dd>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '' },
  emptyText: { type: String, required: true },
})

const hasValue = computed(() => {
  const v = props.value
  if (v == null) return false
  const s = String(v).trim()
  return s.length > 0 && s !== '—'
})
</script>
