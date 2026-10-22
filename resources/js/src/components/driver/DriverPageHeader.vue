<template>
  <header
    class="sticky top-0 z-30 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md [-webkit-backdrop-filter:blur(12px)]"
    style="padding-top: max(0.625rem, env(safe-area-inset-top))"
  >
    <div class="flex min-h-[38px] items-center justify-between gap-3 px-3.5 pb-2.5 sm:px-5">
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-bold leading-tight tracking-tight text-driver-ink sm:text-xl">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="mt-0.5 truncate text-xs leading-snug text-driver-muted">
          {{ subtitle }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-1.5">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </header>
</template>

<script setup>
/** Header dùng chung cho các trang tab của driver shell (Lịch, Lịch đưa đón, Chi phí, Tài khoản). */
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>
