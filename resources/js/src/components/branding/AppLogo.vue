<template>
  <div :class="wrapperClass">
    <img
      :src="LOGO_URL"
      alt="Vietnam America Schools"
      :class="imgClass"
      class="h-auto w-auto max-w-full object-contain object-center"
      loading="lazy"
      decoding="async"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

/** Public asset — served from `public/images/logo/logo.png` */
const LOGO_URL = '/images/logo/logo.png'

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
    sm: 'max-h-9 max-w-[136px]',
    md: 'max-h-11 max-w-[160px]',
    lg: 'max-h-14 max-w-[200px]',
    xl: 'max-h-32 max-w-[min(100%,280px)]',
  }
  return map[props.size] ?? map.md
})
</script>
