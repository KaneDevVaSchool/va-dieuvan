<template>
  <div
    class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80"
  >
    <div v-if="title" class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</div>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
})
</script>

