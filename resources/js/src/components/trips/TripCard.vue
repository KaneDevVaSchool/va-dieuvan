<template>
  <button
    type="button"
    class="flex w-full min-w-0 flex-col gap-0 rounded-2xl border border-[rgba(255,255,255,0.06)] text-left shadow-md shadow-black/15 transition active:scale-[0.99]"
    :class="[
      comfortable ? 'bg-[#0f2318] p-1' : 'bg-[#0f2318]',
    ]"
    style="border-radius: var(--radius-card, 16px)"
    @click="goDetail"
  >
    <!-- Card top row: time + badges + status -->
    <div class="flex items-start justify-between gap-2" :class="comfortable ? 'px-4 pt-4' : 'px-3 pt-3'">
      <!-- Time & date -->
      <div class="shrink-0 text-center">
        <p
          class="font-bold tabular-nums leading-tight text-white"
          :class="comfortable ? 'text-2xl' : 'text-lg'"
        >
          {{ trip.pickup_time || '—' }}
        </p>
        <p
          class="mt-0.5 tabular-nums text-[#64748b]"
          :class="comfortable ? 'text-xs' : 'text-[10px]'"
        >
          {{ trip.pickup_date || trip.depart_date || '' }}
        </p>
      </div>

      <!-- Type badge + trip number -->
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 pt-0.5">
        <span
          class="inline-flex shrink-0 rounded-lg px-2 py-0.5 font-bold uppercase tracking-wide"
          :class="comfortable ? 'text-xs' : 'text-[11px]'"
          :style="typeBadgeStyle"
        >
          {{ typeLabel }}
        </span>
        <span
          class="font-medium text-[#64748b]"
          :class="comfortable ? 'text-xs' : 'text-[11px]'"
        >
          {{ trip.trip_number || `#${trip.id}` }}
        </span>
      </div>

      <!-- Status badge -->
      <span
        class="shrink-0 rounded-full px-2.5 py-1 font-semibold"
        :class="comfortable ? 'text-xs' : 'text-[11px]'"
        :style="{ backgroundColor: statusStyle.bg, color: statusStyle.fg }"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Divider -->
    <div class="border-t border-white/[0.05]" :class="comfortable ? 'mx-4 mt-3' : 'mx-3 mt-2.5'" />

    <!-- Origin / Destination -->
    <div :class="comfortable ? 'px-4 pt-3' : 'px-3 pt-2.5'">
      <div class="flex min-w-0 items-start gap-2">
        <span aria-hidden="true" class="mt-0.5 shrink-0 text-[10px] text-emerald-400">●</span>
        <p
          class="min-w-0 truncate font-medium text-white"
          :class="comfortable ? 'text-base' : 'text-[13px]'"
        >
          {{ trip.pickup_location || trip.origin || '—' }}
        </p>
      </div>
      <!-- Connector line -->
      <div
        class="ml-[7px] border-l border-dashed border-white/[0.12]"
        :class="comfortable ? 'h-4' : 'h-3'"
      />
      <div class="flex min-w-0 items-start gap-2">
        <span aria-hidden="true" class="mt-0.5 shrink-0 text-[10px] text-blue-400">●</span>
        <p
          class="min-w-0 truncate font-medium text-white"
          :class="comfortable ? 'text-base' : 'text-[13px]'"
        >
          {{ trip.dropoff_location || trip.destination || '—' }}
        </p>
      </div>
    </div>

    <!-- Footer: passengers + km + chevron -->
    <div
      class="flex items-center justify-between pt-2"
      :class="comfortable ? 'px-4 pb-4' : 'px-3 pb-3'"
    >
      <p :class="comfortable ? 'text-sm text-[#94a3b8]' : 'text-xs text-[#94a3b8]'">
        <template v-if="passengerCount > 0">
          <span>👥 {{ passengerCount }} {{ passengerLabel }}</span>
          <span v-if="kmPart"> · {{ kmPart }}</span>
        </template>
        <template v-else>
          <span v-if="kmPart">{{ kmPart }}</span>
        </template>
      </p>
      <span
        class="font-light leading-none text-[#7fdcc8]"
        :class="comfortable ? 'text-2xl' : 'text-lg'"
        aria-hidden="true"
      >›</span>
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  trip: { type: Object, required: true },
  comfortable: { type: Boolean, default: false },
})

const router = useRouter()
const { t } = useI18n()

// ── Trip type badge ──────────────────────────────────────────────────

const rawType = computed(() =>
  (props.trip.trip_type_label || props.trip.type || '').trim().toLowerCase(),
)

const typeLabel = computed(() => {
  const t0 = rawType.value
  if (t0 === 'p2p' || t0 === 'point_to_point') return 'P2P'
  if (t0 === 'd2d' || t0 === 'door_to_door') return 'D2D'
  if (t0 === 'ct' || t0 === 'business' || t0 === 'cong_tac') return 'CT'
  if (t0 === 'hh' || t0 === 'delivery' || t0 === 'cargo' || t0 === 'hang_hoa') return 'HH'
  return (props.trip.trip_type_label || props.trip.type || '—').toUpperCase()
})

const typeBadgeStyle = computed(() => {
  const lbl = typeLabel.value
  if (lbl === 'P2P') return { backgroundColor: 'rgb(59 130 246 / 0.18)', color: '#60a5fa' }
  if (lbl === 'D2D') return { backgroundColor: 'rgb(168 85 247 / 0.18)', color: '#c084fc' }
  if (lbl === 'CT') return { backgroundColor: 'rgb(245 158 11 / 0.18)', color: '#fbbf24' }
  if (lbl === 'HH') return { backgroundColor: 'rgb(249 115 22 / 0.18)', color: '#fb923c' }
  return { backgroundColor: 'rgb(148 163 184 / 0.12)', color: '#94a3b8' }
})

// ── Status badge ──────────────────────────────────────────────────────

const normalizedStatus = computed(() => String(props.trip.status ?? '').trim().toLowerCase())

function todayYmdLocal() {
  const x = new Date()
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const d = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

/** Scheduled day passed (local calendar) and trip not completed/cancelled */
const isOverdue = computed(() => {
  const day = props.trip.depart_date
  if (!day || typeof day !== 'string') return false
  const ymd = day.slice(0, 10)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return false
  const s = normalizedStatus.value
  if (s === 'completed' || s === 'cancelled') return false
  return ymd < todayYmdLocal()
})

const statusStyle = computed(() => {
  if (isOverdue.value) return { bg: 'rgb(245 158 11 / 0.2)', fg: '#fbbf24' }
  const s = normalizedStatus.value
  if (s === 'in_progress') return { bg: 'rgb(20 184 166 / 0.18)', fg: '#2dd4bf' }
  if (s === 'completed') return { bg: 'rgb(34 197 94 / 0.15)', fg: '#4ade80' }
  if (['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) {
    return { bg: 'rgb(148 163 184 / 0.12)', fg: '#94a3b8' }
  }
  if (s === 'cancelled') return { bg: 'rgb(244 63 94 / 0.15)', fg: '#f43f5e' }
  return { bg: 'rgb(148 163 184 / 0.10)', fg: '#94a3b8' }
})

const statusLabel = computed(() => {
  if (isOverdue.value) return t('trip_history_page.status_overdue')
  const s = normalizedStatus.value
  if (s === 'in_progress') return t('trip_history_page.status_running')
  if (s === 'completed') return t('trip_history_page.status_completed')
  if (s === 'cancelled') return t('trip_history_page.status_cancelled')
  if (['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) {
    return t('trip_history_page.status_pending')
  }
  return props.trip.status || '—'
})

// ── Passenger + km ────────────────────────────────────────────────────

const passengerCount = computed(() => Number(props.trip.passenger_count) || 0)

const passengerLabel = computed(() =>
  passengerCount.value === 1
    ? t('trip_history_page.guest_one')
    : t('trip_history_page.guest_many'),
)

const kmPart = computed(() => {
  const km = props.trip.actual_km ?? props.trip.distance_km
  if (km == null || Number.isNaN(Number(km))) return ''
  return t('trip_history_page.km_short', { n: Number(km).toFixed(1) })
})

function goDetail() {
  router.push(`/driver/trips/${props.trip.id}`)
}
</script>
