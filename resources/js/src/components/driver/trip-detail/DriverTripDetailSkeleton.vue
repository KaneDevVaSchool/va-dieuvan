<template>
  <div class="mt-3 space-y-4">
    <!-- Header-like shimmer -->
    <div class="animate-pulse overflow-hidden rounded-2xl border border-white/5 bg-driver-card p-4 ring-1 ring-white/[0.06]">
      <div class="mx-auto h-6 w-40 rounded-lg bg-white/10" />
      <div class="mx-auto mt-3 h-4 w-56 rounded bg-white/[0.07]" />
      <div class="mt-4 grid grid-cols-3 gap-2">
        <div v-for="n in 3" :key="n" class="rounded-xl bg-white/10 py-3">
          <div class="mx-auto h-4 w-10 rounded bg-[#7fdcc8]/20" />
          <div class="mx-auto mt-2 h-3 w-16 rounded bg-white/[0.06]" />
          <div class="mx-auto mt-2 h-5 w-12 rounded bg-white/10" />
        </div>
      </div>
    </div>
    <!-- Route card -->
    <div class="animate-pulse overflow-hidden rounded-2xl border border-white/5 bg-driver-card p-4 ring-1 ring-white/[0.06]">
      <div class="flex gap-3">
        <div class="h-11 w-11 shrink-0 rounded-xl bg-emerald-500/30" />
        <div class="min-w-0 flex-1 space-y-2 pt-1">
          <div class="h-3 w-20 rounded bg-white/[0.08]" />
          <div class="h-5 w-full rounded bg-white/10" />
              <div class="h-4 max-w-[85%] rounded bg-white/[0.06]" />
        </div>
      </div>
      <div class="mt-4 flex gap-3">
        <div class="h-11 w-11 shrink-0 rounded-xl bg-rose-500/30" />
        <div class="min-w-0 flex-1 space-y-2 pt-1">
          <div class="h-3 w-24 rounded bg-white/[0.08]" />
          <div class="h-5 w-full rounded bg-white/10" />
        </div>
      </div>
      <div class="mt-4 h-12 w-full rounded-xl bg-sky-600/40" />
    </div>
    <!-- List card -->
    <div class="animate-pulse overflow-hidden rounded-2xl border border-white/5 bg-driver-card ring-1 ring-white/[0.06]">
      <div class="flex items-center justify-between px-4 py-3.5">
        <div class="h-5 w-40 rounded bg-white/10" />
        <div class="flex gap-2">
          <div class="h-10 w-20 rounded-full bg-white/[0.08]" />
          <div class="h-10 w-24 rounded-full bg-white/[0.08]" />
        </div>
      </div>
      <div v-for="n in 2" :key="n" class="border-t border-white/[0.06] px-4 py-3">
        <div class="flex gap-3">
          <div class="h-12 w-12 shrink-0 rounded-full bg-white/10" />
          <div class="min-w-0 flex-1 space-y-2">
            <div class="h-4 w-36 rounded bg-white/10" />
            <div class="h-3 w-full rounded bg-white/[0.06]" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
