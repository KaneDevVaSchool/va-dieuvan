<template>
  <span
    class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-va-50 font-bold text-va-800 ring-2 dark:bg-va-950/50 dark:text-va-200"
    :class="[sizeClass, ringVariant]"
    :title="title"
  >
    <img
      v-if="effectiveAvatar"
      :src="effectiveAvatar"
      alt=""
      class="h-full w-full object-cover"
      referrerpolicy="no-referrer"
      @error="onImgError"
    />
    <span v-else aria-hidden="true">{{ initials }}</span>
  </span>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { resolvePublicStorageUrl } from '../../util/publicStorageUrl.js'

const props = defineProps({
  name: { type: String, default: '' },
  email: { type: String, default: '' },
  avatarUrl: { type: String, default: null },
  title: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  /** Viền nhẹ (navbar mobile) */
  ringProminent: { type: Boolean, default: false },
})

const imgFailed = ref(false)

watch(
  () => props.avatarUrl,
  () => {
    imgFailed.value = false
  },
)

function onImgError() {
  imgFailed.value = true
}

const effectiveAvatar = computed(() => {
  if (imgFailed.value || !props.avatarUrl) return null
  return resolvePublicStorageUrl(props.avatarUrl)
})

const initials = computed(() => {
  const name = (props.name || '').trim()
  if (name) {
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length >= 2) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    }
    return name.slice(0, 2).toUpperCase()
  }
  const local = (props.email || '').split('@')[0] || ''
  return (local.slice(0, 2) || '?').toUpperCase()
})

const sizeClass = computed(() => {
  const map = {
    sm: 'h-8 w-8 text-[10px]',
    md: 'h-9 w-9 text-[11px] sm:h-10 sm:w-10 sm:text-xs',
    lg: 'h-11 w-11 text-sm',
  }
  return map[props.size] ?? map.md
})

const ringVariant = computed(() =>
  props.ringProminent
    ? 'ring-slate-200/90 shadow-sm dark:ring-slate-600'
    : 'ring-white dark:ring-slate-800',
)
</script>
