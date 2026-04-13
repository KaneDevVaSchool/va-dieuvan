<template>
  <label class="flex items-baseline gap-1 text-xs font-medium text-slate-600 dark:text-slate-300">
    <span>{{ label }}</span>
    <span
      v-if="important"
      class="text-rose-600 dark:text-rose-400"
      title="Nên điền đầy đủ"
      aria-label="trường nên điền"
      >*</span
    >
  </label>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  important: { type: Boolean, default: false },
})
</script>
