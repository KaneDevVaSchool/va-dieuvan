<template>
  <div :class="wrapperClass">
    <img
      :src="logoUrl"
      alt="VA Dispatch"
      :class="['h-auto w-auto max-w-full object-contain', imgAlignClass, imgClass]"
      loading="lazy"
      decoding="async"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

/** Wordmark ngang — `public/images/logo/logo-2.png` */
const LOGO_WORDMARK_URL = '/images/logo/logo-2.png'
/** Icon vuông PWA — sidebar thu gọn, manifest */
const LOGO_ICON_URL = '/images/logo/logo_pwa_v1.png'

const props = defineProps({
  /** wordmark: logo ngang; icon: logo vuông PWA */
  variant: {
    type: String,
    default: 'wordmark',
    validator: (v) => ['wordmark', 'icon'].includes(v),
  },
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

const imgAlignClass = computed(() => (props.align === 'center' ? 'object-center' : 'object-left'))

const logoUrl = computed(() => (props.variant === 'icon' ? LOGO_ICON_URL : LOGO_WORDMARK_URL))

const imgClass = computed(() => {
  if (props.variant === 'icon') {
    const iconMap = {
      sm: 'h-8 w-8 max-h-8 max-w-8 sm:h-9 sm:w-9 sm:max-h-9 sm:max-w-9',
      md: 'h-9 w-9 max-h-9 max-w-9 sm:h-10 sm:w-10 sm:max-h-10 sm:max-w-10',
      lg: 'h-10 w-10 max-h-10 max-w-10 sm:h-11 sm:w-11 sm:max-h-11 sm:max-w-11',
      xl: 'h-24 w-24 max-h-24 max-w-24 sm:h-28 sm:w-28 sm:max-h-28 sm:max-w-28',
    }
    return iconMap[props.size] ?? iconMap.sm
  }
  const map = {
    sm: 'max-h-8 w-auto max-w-[min(100%,200px)] sm:max-h-9 sm:max-w-[220px]',
    md: 'max-h-9 w-auto max-w-[min(100%,240px)] sm:max-h-10 sm:max-w-[260px]',
    lg: 'max-h-11 w-auto max-w-[min(100%,280px)] sm:max-h-12 sm:max-w-[300px]',
    xl: 'max-h-24 w-auto max-w-[min(100%,min(90vw,24rem))] sm:max-h-28',
  }
  return map[props.size] ?? map.md
})
</script>
