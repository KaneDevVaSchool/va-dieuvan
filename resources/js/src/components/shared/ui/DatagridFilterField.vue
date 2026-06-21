<script setup>
defineProps({
  label: { type: String, default: '' },
})
</script>

<template>
  <div class="min-w-0 w-full">
    <label v-if="label" class="mb-1 block text-xs font-medium text-slate-500">{{ label }}</label>
    <slot />
  </div>
</template>
