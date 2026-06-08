<template>
  <div class="animate-pulse space-y-4" role="status" :aria-label="ariaLabel || undefined">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex gap-3">
        <div class="h-10 w-10 rounded-lg bg-slate-200" />
        <div class="flex-1 space-y-3">
          <div class="h-7 w-28 rounded bg-slate-200" />
          <div class="h-4 w-64 max-w-full rounded bg-slate-100" />
        </div>
      </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
      <div class="h-4 w-full max-w-md rounded bg-slate-100" />
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-100 p-5">
      <div class="grid gap-3 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-10 rounded bg-slate-200/70" />
      </div>
    </div>
    <div class="grid gap-4 lg:grid-cols-12">
      <div class="space-y-4 lg:col-span-8">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="flex justify-between gap-2">
            <div v-for="i in 5" :key="i" class="h-10 w-10 rounded-full bg-slate-200" />
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <div class="h-9 w-32 rounded bg-slate-200" />
          <div class="mt-4 h-40 rounded-lg bg-slate-50" />
        </div>
      </div>
      <div class="space-y-4 lg:col-span-4">
        <div v-for="i in 3" :key="i" class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="h-4 w-24 rounded bg-slate-200" />
          <div class="mt-3 space-y-2">
            <div class="h-3 w-full rounded bg-slate-100" />
            <div class="h-3 w-4/5 rounded bg-slate-100" />
          </div>
        </div>
      </div>
    </div>
    <span class="sr-only">{{ ariaLabel }}</span>
  </div>
</template>

<script setup>
defineProps({
  ariaLabel: { type: String, default: '' },
})
</script>
