<template>
  <input
    type="text"
    readonly
    tabindex="-1"
    class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1 text-[11px] leading-snug text-slate-900"
    :class="alignRight ? 'text-right tabular-nums' : ''"
    :value="display"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  alignRight: { type: Boolean, default: false },
})

const display = computed(() => {
  const v = props.modelValue
  const s = v == null ? '' : String(v).trim()
  return s
})
</script>
