<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="pillClass"
  >
    <slot>{{ text }}</slot>
  </span>
</template>

<script setup>
defineProps({
  text: { type: [String, Number], default: '' },
  /** Lớp Tailwind nền/chữ — lấy từ constants/policyTripStatus.js */
  pillClass: { type: String, default: 'bg-slate-100 text-slate-700' },
})
</script>
