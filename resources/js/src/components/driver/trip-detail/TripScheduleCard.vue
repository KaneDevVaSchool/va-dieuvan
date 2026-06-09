<template>
  <div class="mt-2 flex justify-center px-1">
    <p
      class="inline-flex max-w-full flex-wrap items-center justify-center gap-x-2 gap-y-0.5 text-center text-sm leading-snug sm:text-base"
    >
      <span class="font-medium text-driver-ink/95">{{ scheduleDateLine }}</span>
      <span class="text-driver-muted/35" aria-hidden="true">·</span>
      <span class="font-semibold tabular-nums text-[#7fdcc8]">{{ scheduleTimeLine }}</span>
    </p>
  </div>
</template>

<script setup>
defineProps({
  scheduleDateLine: { type: String, default: '' },
  scheduleTimeLine: { type: String, default: '' },
})
</script>
