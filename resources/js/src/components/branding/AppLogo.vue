<template>
  <div :class="wrapperClass">
    <img
      :src="LOGO_URL"
      alt="VA Dispatch"
      :class="imgClass"
      class="h-auto w-auto max-w-full object-contain object-left"
      loading="lazy"
      decoding="async"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

/** Wordmark ngang — `public/images/logo/logo-2.png` */
const LOGO_URL = '/images/logo/logo-2.png'

const props = defineProps({
  /** sm: header / compact; md: sidebar; lg: drawer; xl: login */
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
  align: {
    type: String,
    default: 'left',
    validator: (v) => ['left', 'center'].includes(v),
  },
})

const wrapperClass = computed(() => {
  const a = props.align === 'center' ? 'items-center' : 'items-start'
  return ['flex flex-col', a].join(' ')
})

const imgClass = computed(() => {
  const map = {
    sm: 'max-h-8 w-auto max-w-[min(100%,200px)] sm:max-h-9 sm:max-w-[220px]',
    md: 'max-h-9 w-auto max-w-[min(100%,240px)] sm:max-h-10 sm:max-w-[260px]',
    lg: 'max-h-11 w-auto max-w-[min(100%,280px)] sm:max-h-12 sm:max-w-[300px]',
    xl: 'max-h-24 w-auto max-w-[min(100%,min(90vw,24rem))] sm:max-h-28',
  }
  return map[props.size] ?? map.md
})
</script>
