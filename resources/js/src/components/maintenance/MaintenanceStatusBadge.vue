<template>
  <span :class="badgeClass" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  status: { type: String, default: 'safe' }, // urgent | warning | safe
  daysRemaining: { type: Number, default: null },
})

const badgeClass = computed(() => {
  if (props.status === 'urgent') return 'bg-[#ff6b6b]/20 text-[#ff6b6b] ring-1 ring-[#ff6b6b]/40'
  if (props.status === 'warning') return 'bg-amber-400/20 text-amber-300 ring-1 ring-amber-400/40'
  return 'bg-[#7fdcc8]/15 text-[#7fdcc8] ring-1 ring-[#7fdcc8]/30'
})

const label = computed(() => {
  if (props.status === 'safe') return t('driver_maintenance.status_safe')
  if (props.daysRemaining != null) return `${props.daysRemaining} ${t('driver_maintenance.days_remaining')}`
  return props.status === 'urgent'
    ? t('driver_maintenance.status_urgent')
    : t('driver_maintenance.status_upcoming')
})
</script>
