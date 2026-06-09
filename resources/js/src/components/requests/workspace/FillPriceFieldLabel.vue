<template>
  <span class="mb-1 flex items-center gap-1 text-sm font-medium text-slate-600 dark:text-slate-300">
    <label v-if="forId" :for="forId" class="cursor-default">{{ label }}</label>
    <span v-else>{{ label }}</span>
    <span
      v-if="tooltip"
      :title="tooltip"
      class="inline-flex cursor-help text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
      @click.prevent
    >
      <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path
          fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z"
          clip-rule="evenodd"
        />
      </svg>
    </span>
  </span>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  tooltip: { type: String, default: '' },
  forId: { type: String, default: '' },
})
</script>
