<template>
  <div class="space-y-4" role="status" :aria-label="ariaLabel || undefined">
    <div
      v-for="i in count"
      :key="i"
      class="animate-pulse rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1 space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <div class="h-4 w-14 rounded-md bg-slate-200" />
            <div class="h-6 w-24 rounded-full bg-slate-200" />
          </div>
          <div class="h-4 max-w-full rounded-md bg-slate-200 sm:max-w-md" />
          <div class="flex flex-wrap gap-2">
            <div class="h-3 w-28 rounded bg-slate-100" />
            <div class="h-3 w-20 rounded bg-slate-100" />
          </div>
        </div>
        <div class="mt-1 h-5 w-5 shrink-0 rounded bg-slate-200" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  count: { type: Number, default: 3 },
  ariaLabel: { type: String, default: '' },
})
</script>
