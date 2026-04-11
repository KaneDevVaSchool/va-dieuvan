<template>
  <div class="rounded-xl border bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md">
    <div v-if="title" class="mb-3 text-sm font-semibold text-slate-900">{{ title }}</div>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
})
</script>

