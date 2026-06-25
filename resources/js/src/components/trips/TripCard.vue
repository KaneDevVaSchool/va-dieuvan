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
    <!-- Card top: comfortable = 2 rows (time+status, then badges); compact = wrapped row -->
    <div
      v-if="comfortable"
      class="flex flex-col gap-2 px-4 pt-4"
    >
      <div class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0 shrink text-left">
          <p class="text-2xl font-bold tabular-nums leading-tight text-white sm:text-3xl">
            {{ displayPickupTime }}
          </p>
          <p
            v-if="arriveTimeLabel"
            class="mt-0.5 text-sm font-medium tabular-nums text-[#7fdcc8]"
          >
            {{ arriveTimeLabel }}
          </p>
          <p class="mt-0.5 text-sm tabular-nums text-[#64748b]">
            {{ trip.pickup_date || trip.depart_date || '' }}
          </p>
        </div>
        <span
          class="max-w-[11rem] shrink-0 rounded-full px-2.5 py-1 text-center text-xs font-semibold leading-snug sm:max-w-none sm:text-sm"
          :style="{ backgroundColor: statusStyle.bg, color: statusStyle.fg }"
        >
          {{ statusLabel }}
        </span>
      </div>
      <div class="flex min-w-0 flex-col gap-1">
        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
          <span
            class="inline-flex max-w-full shrink-0 rounded-lg px-2 py-0.5 text-xs font-bold uppercase tracking-wide sm:text-sm"
            :style="typeBadgeStyle"
          >
            {{ typeLabel }}
          </span>
          <span
            v-if="trip.is_urgent"
            class="inline-flex shrink-0 rounded-lg bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-300 sm:text-xs"
          >
            {{ t('trip_history_page.urgent_badge') }}
          </span>
        </div>
        <p
          class="min-w-0 break-all text-sm font-semibold text-[#7fdcc8] sm:truncate sm:text-base"
          :title="tripCode"
        >
          {{ tripCode }}
        </p>
      </div>
    </div>
    <div
      v-else
      class="flex flex-wrap items-start gap-x-2 gap-y-1.5 px-3 pt-3"
    >
      <div class="shrink-0 text-center">
        <p class="text-lg font-bold tabular-nums leading-tight text-white">
          {{ displayPickupTime }}
        </p>
        <p class="mt-0.5 text-[10px] tabular-nums text-[#64748b]">
          {{ trip.pickup_date || trip.depart_date || '' }}
        </p>
      </div>
      <div class="flex min-w-0 flex-1 basis-[calc(100%-5rem)] flex-col gap-1 pt-0.5 sm:basis-auto">
        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
          <span
            class="inline-flex shrink-0 rounded-lg px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide"
            :style="typeBadgeStyle"
          >
            {{ typeLabel }}
          </span>
          <span
            v-if="trip.is_urgent"
            class="inline-flex shrink-0 rounded-lg bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-300"
          >
            {{ t('trip_history_page.urgent_badge') }}
          </span>
        </div>
        <p class="min-w-0 truncate text-xs font-semibold text-[#7fdcc8]">
          {{ tripCode }}
        </p>
      </div>
      <span
        class="ml-auto shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
        :style="{ backgroundColor: statusStyle.bg, color: statusStyle.fg }"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Divider -->
    <div class="border-t border-white/[0.05]" :class="comfortable ? 'mx-4 mt-3' : 'mx-3 mt-2.5'" />

    <!-- Origin / Destination -->
    <div :class="comfortable ? 'px-4 pt-3' : 'px-3 pt-2.5'">
      <div class="flex min-w-0 items-start gap-2.5">
        <span aria-hidden="true" class="mt-1 shrink-0 text-xs text-emerald-400">●</span>
        <div class="min-w-0 flex-1">
          <p
            v-if="comfortable"
            class="text-xs font-semibold uppercase tracking-wide text-emerald-400/80"
          >
            {{ t('driver_trip_detail.point_start') }}
          </p>
          <p
            class="min-w-0 font-medium text-white"
            :class="comfortable ? 'line-clamp-2 text-lg leading-snug' : 'truncate text-[13px]'"
          >
            {{ trip.pickup_location || trip.origin || '—' }}
          </p>
        </div>
      </div>
      <!-- Connector line -->
      <div
        class="ml-[9px] border-l border-dashed border-white/[0.12]"
        :class="comfortable ? 'h-5' : 'h-3'"
      />
      <div class="flex min-w-0 items-start gap-2.5">
        <span aria-hidden="true" class="mt-1 shrink-0 text-xs text-blue-400">●</span>
        <div class="min-w-0 flex-1">
          <p
            v-if="comfortable"
            class="text-xs font-semibold uppercase tracking-wide text-blue-400/80"
          >
            {{ t('driver_trip_detail.point_end') }}
          </p>
          <p
            class="min-w-0 font-medium text-white"
            :class="comfortable ? 'line-clamp-2 text-lg leading-snug' : 'truncate text-[13px]'"
          >
            {{ trip.dropoff_location || trip.destination || '—' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Extra details (comfortable) -->
    <div
      v-if="comfortable && (notesPreview || scheduleLegCount > 1)"
      class="space-y-1 px-4 pt-2"
    >
      <p v-if="scheduleLegCount > 1" class="text-sm font-medium text-[#94a3b8]">
        {{ t('trip_history_page.schedule_legs', { n: scheduleLegCount }) }}
      </p>
      <p v-if="notesPreview" class="line-clamp-2 text-sm italic text-[#64748b]">
        {{ notesPreview }}
      </p>
    </div>

    <!-- Footer: passengers + km + chevron -->
    <div
      class="flex items-center justify-between pt-2"
      :class="comfortable ? 'px-4 pb-4 pt-3' : 'px-3 pb-3'"
    >
      <p :class="comfortable ? 'text-base font-semibold text-[#cbd5e1]' : 'text-xs text-[#94a3b8]'">
        <template v-if="passengerCount > 0">
          <span>👥 {{ passengerCount }} {{ passengerLabel }}</span>
          <span v-if="kmPart"> · {{ kmPart }}</span>
          <span v-if="comfortable && durationPart"> · {{ durationPart }}</span>
        </template>
        <template v-else>
          <span v-if="kmPart">{{ kmPart }}</span>
          <span v-if="!kmPart && durationPart">{{ durationPart }}</span>
        </template>
      </p>
      <span
        class="font-light leading-none text-[#7fdcc8]"
        :class="comfortable ? 'text-3xl' : 'text-lg'"
        aria-hidden="true"
      >›</span>
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { dispatchRequestDisplayPassengerCount } from '../../util/dispatchRequestPassengers'
import { tripOutboundInboundTimeRange } from '../../composables/useDriverTripDisplay'

const props = defineProps({
  trip: { type: Object, required: true },
  comfortable: { type: Boolean, default: false },
})

const router = useRouter()
const { locale, t } = useI18n()

function drOf() {
  return props.trip?.dispatch_request ?? props.trip?.dispatchRequest ?? null
}

// ── Trip type badge ──────────────────────────────────────────────────

const rawType = computed(() =>
  (props.trip.trip_type_label || props.trip.type || drOf()?.trip_type || '').trim().toLowerCase(),
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

const tripCode = computed(() =>
  props.trip.request_code || props.trip.trip_number || `REQ-${props.trip.id}`,
)

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

// ── Time display ──────────────────────────────────────────────────────

const displayPickupTime = computed(() => {
  const tag = locale.value === 'vi' ? 'vi' : 'en'
  const range = tripOutboundInboundTimeRange(props.trip, tag, t)
  if (props.comfortable && range) return range
  return props.trip.pickup_time || '—'
})

const arriveTimeLabel = computed(() => {
  if (displayPickupTime.value.includes('\u00a0')) return ''
  const at = props.trip.arrive_time
  if (!at) return ''
  return t('trip_history_page.arrive_by_short', { time: at })
})

// ── Passenger + km ────────────────────────────────────────────────────

const passengerCount = computed(() => {
  const dr = drOf()
  if (dr) return dispatchRequestDisplayPassengerCount(dr)
  const n = Number(props.trip.passenger_count)
  return Number.isFinite(n) && n > 0 ? n : 0
})

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

const durationPart = computed(() => {
  const m = props.trip.duration_minutes
  if (m == null || Number.isNaN(Number(m)) || Number(m) <= 0) return ''
  return t('trip_history_page.min_short', { n: Math.round(Number(m)) })
})

const notesPreview = computed(() => {
  const n = props.trip.notes_preview || drOf()?.notes
  if (!n || typeof n !== 'string') return ''
  const trimmed = n.trim()
  if (!trimmed) return ''
  return trimmed.length > 80 ? `${trimmed.slice(0, 78)}…` : trimmed
})

const scheduleLegCount = computed(() => {
  const legs = props.trip.schedule_legs
  return Array.isArray(legs) ? legs.length : 0
})

function goDetail() {
  router.push(`/driver/trips/${props.trip.id}`)
}
</script>
