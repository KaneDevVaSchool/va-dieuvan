<template>
  <button
    type="button"
    class="flex w-full min-w-0 gap-3 rounded-2xl border border-[rgba(255,255,255,0.06)] bg-[#0f2318] p-3 text-left shadow-md shadow-black/15 transition active:scale-[0.99]"
    style="border-radius: var(--radius-card, 16px)"
    @click="goDetail"
  >
    <!-- Time column -->
    <div class="w-[3.25rem] shrink-0 pt-0.5 text-center">
      <p class="text-lg font-bold tabular-nums leading-tight text-white">
        {{ trip.pickup_time || '—' }}
      </p>
      <p class="mt-1 text-[11px] tabular-nums text-[#94a3b8]">
        {{ trip.pickup_date || '' }}
      </p>
    </div>

    <!-- Middle -->
    <div class="min-w-0 flex-1 pt-0.5">
      <div class="flex flex-wrap items-center gap-1.5 gap-y-1">
        <span
          class="inline-flex rounded-lg bg-white/[0.06] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#94a3b8]"
          style="border-radius: var(--radius-badge, 8px)"
          :title="trip.type"
        >
          {{ trip.type || '—' }}
        </span>
        <span class="text-[11px] font-medium text-[#64748b]">
          {{ trip.trip_number || `#${trip.id}` }}
        </span>
      </div>
      <p class="mt-2 truncate text-[13px] font-medium text-white">
        <span aria-hidden="true" class="text-emerald-400">●</span>
        {{ trip.pickup_location || '—' }}
      </p>
      <p class="mt-1 truncate text-[13px] font-medium text-white">
        <span aria-hidden="true" class="text-[#3b82f6]">●</span>
        {{ trip.dropoff_location || '—' }}
      </p>
      <p class="mt-2 text-xs text-[#94a3b8]">
        <span v-if="trip.passenger_count > 0">👥 {{ trip.passenger_count }} {{ passengerLabel }}</span>
        <span v-if="kmPart">{{ trip.passenger_count > 0 ? ' · ' : '' }}{{ kmPart }}</span>
        <span v-if="minPart">{{ (trip.passenger_count > 0 || kmPart) ? ' · ' : '' }}{{ minPart }}</span>
      </p>
    </div>

    <!-- Status + chevron -->
    <div class="flex shrink-0 flex-col items-end gap-2 self-start pt-0.5">
      <span
        class="rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize"
        :style="{ backgroundColor: badgeStyle.bg, color: badgeStyle.fg }"
      >
        {{ badgeLabel }}
      </span>
      <span class="text-lg leading-none font-light text-[#7fdcc8]" aria-hidden="true">›</span>
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  trip: { type: Object, required: true },
})

const router = useRouter()
const { t } = useI18n()

const passengerLabel = computed(() =>
  props.trip.passenger_count === 1 ? t('trip_history_page.guest_one') : t('trip_history_page.guest_many'),
)

const kmPart = computed(() => {
  const km = props.trip.distance_km
  if (km == null || Number.isNaN(Number(km))) return ''
  return t('trip_history_page.km_short', { n: Number(km) })
})

const minPart = computed(() => {
  const m = props.trip.duration_minutes
  if (m == null || m === '') return ''
  return t('trip_history_page.min_short', { n: m })
})

const normalizedStatus = computed(() => String(props.trip.status ?? '').trim().toLowerCase())

/** @typedef {{ bg: string, fg: string }} BadgeStyle */

const badgeStyle = computed(() => {
  const s = normalizedStatus.value
  if (s === 'completed') return { bg: 'rgb(127 220 200 / 0.12)', fg: '#7fdcc8' }
  if (s === 'in_progress') return { bg: 'rgb(59 130 246 / 0.12)', fg: '#3b82f6' }
  if (s === 'cancelled') return { bg: 'rgb(244 63 94 / 0.12)', fg: '#f43f5e' }
  if (['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) {
    return { bg: 'rgb(245 158 11 / 0.12)', fg: '#f59e0b' }
  }
  return { bg: 'rgb(148 163 184 / 0.12)', fg: '#94a3b8' }
})

const badgeLabel = computed(() => {
  const s = normalizedStatus.value
  if (s === 'completed') return t('trip_history_page.status_completed')
  if (s === 'in_progress') return t('trip_history_page.status_running')
  if (s === 'cancelled') return t('trip_history_page.status_cancelled')
  if (['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) {
    return t('trip_history_page.status_pending')
  }
  return s || '—'
})

function goDetail() {
  router.push(`/driver/trips/${props.trip.id}`)
}
</script>
