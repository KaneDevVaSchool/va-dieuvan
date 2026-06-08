<template>
  <div class="animate-pulse space-y-6" role="status" :aria-label="ariaLabel || undefined">
    <div class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white p-5 shadow-sm sm:p-6">
      <div class="flex gap-3">
        <div class="h-11 w-11 shrink-0 rounded-2xl bg-slate-200" />
        <div class="min-w-0 flex-1 space-y-3">
          <div class="flex gap-2">
            <div class="h-7 w-20 rounded-lg bg-slate-200" />
            <div class="h-6 w-24 rounded-full bg-slate-200" />
          </div>
          <div class="h-4 w-full max-w-md rounded bg-slate-200" />
          <div class="h-3 w-48 rounded bg-slate-100" />
        </div>
      </div>
      <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="h-16 rounded-2xl bg-slate-100" />
        <div class="h-16 rounded-2xl bg-slate-100" />
      </div>
    </div>
    <div class="hidden rounded-2xl border border-slate-200 bg-white p-5 lg:block">
      <div class="h-3 w-32 rounded bg-slate-200" />
      <div class="mt-6 flex justify-between gap-2">
        <div v-for="i in 5" :key="i" class="flex flex-1 flex-col items-center gap-2">
          <div class="h-11 w-11 rounded-full bg-slate-200" />
          <div class="h-3 w-full max-w-[4rem] rounded bg-slate-100" />
        </div>
      </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <div class="flex gap-2">
        <div class="h-9 w-24 rounded-lg bg-slate-200" />
        <div class="h-9 w-28 rounded-lg bg-slate-100" />
      </div>
      <div class="mt-5 space-y-3">
        <div class="h-4 w-full rounded bg-slate-100" />
        <div class="h-4 w-[85%] rounded bg-slate-100" />
        <div class="h-32 rounded-xl bg-slate-50" />
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
