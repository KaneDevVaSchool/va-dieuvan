<template>
  <span :class="classes">
    <slot>{{ labelRequestStatus(status) }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { labelRequestStatus } from '../../util/labels'
import { requestStatusBadgeClass } from '../../util/requestStatusBadge'

const props = defineProps({
  /** dispatch_requests.status */
  status: { type: String, default: '' },
  /** md: text-xs px-2.5 py-0.5; sm: table / compact */
  size: { type: String, default: 'md' },
})

const classes = computed(() => {
  const base = 'inline-flex items-center rounded-full font-medium'
  const sizing =
    props.size === 'sm'
      ? 'px-2 py-0.5 text-[11px]'
      : 'px-2.5 py-0.5 text-xs'
  return [base, sizing, requestStatusBadgeClass(props.status)].filter(Boolean).join(' ')
})
</script>
