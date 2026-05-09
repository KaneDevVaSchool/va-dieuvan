<template>
  <div class="mt-2 space-y-1 text-center">
    <p class="text-sm font-medium leading-snug text-driver-ink/95 sm:text-base">{{ scheduleDateLine }}</p>
    <p class="whitespace-normal break-words text-base font-semibold tabular-nums text-[#7fdcc8] sm:text-lg">
      {{ scheduleTimeLine }}
    </p>
    <p class="text-[11px] leading-tight text-driver-muted/70 sm:text-xs">{{ t('driver_trip_detail.schedule_tz_note') }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  scheduleDateLine: { type: String, default: '' },
  scheduleTimeLine: { type: String, default: '' },
})

const { t } = useI18n()
</script>
