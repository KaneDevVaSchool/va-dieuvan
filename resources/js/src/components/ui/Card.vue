<template>
  <div
    class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80"
  >
    <div v-if="title || hint" class="mb-3 flex items-start justify-between gap-2">
      <div v-if="title" class="min-w-0 flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ title }}
      </div>
      <button
        v-if="hint"
        type="button"
        class="shrink-0 rounded-md p-0.5 text-slate-400 outline-none ring-offset-2 transition hover:bg-slate-100 hover:text-teal-600 focus-visible:ring-2 focus-visible:ring-teal-500 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-teal-400"
        :title="hint"
        :aria-label="hint"
        @click.stop
      >
        <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
      </button>
    </div>
    <slot />
  </div>
</template>

<script setup>
import { InformationCircleIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, default: '' },
  hint: { type: String, default: '' },
})
</script>

