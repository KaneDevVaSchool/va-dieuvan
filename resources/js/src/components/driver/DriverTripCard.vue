<template>
  <div
    class="flex w-full min-w-0 flex-col gap-0 rounded-2xl text-left outline-none transition touch-manipulation"
    :class="rootClass"
    data-testid="driver-trip-card"
    @click="onRootClick"
  >
    <!-- Header -->
    <div
      class="flex gap-2 px-4 pt-4"
      :class="
        isStackedLayout
          ? 'flex-col items-stretch'
          : 'flex-col items-stretch sm:flex-row sm:items-start sm:justify-between'
      "
    >
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 sm:gap-2">
        <span
          class="inline-flex shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide"
          :style="typeBadgeStyle"
        >
          {{ typeLabel }}
        </span>
        <span
          v-if="shiftLabel"
          class="inline-flex shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold ring-1"
          :class="shiftBadgeClass"
        >
          {{ shiftLabel }}
        </span>
        <span
          class="min-w-0 max-w-full truncate text-xs font-medium text-[#94a3b8] sm:text-sm"
          :title="tripCode"
        >
          {{ tripCode }}
        </span>
      </div>
      <span
        class="inline-flex w-fit max-w-full items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold leading-tight"
        :class="[statusPillClass, isStackedLayout ? 'self-start' : 'shrink-0 sm:max-w-[11rem] sm:truncate']"
      >
        <component :is="statusIcon" v-if="statusIcon" class="h-3.5 w-3.5" />
        {{ statusLabelText }}
      </span>
    </div>

    <!-- Time -->
    <div class="mt-3 flex items-baseline gap-2 px-4">
      <span class="text-[22px] font-bold tabular-nums leading-none" :class="timeClass">
        {{ pickupTime }}
      </span>
      <span class="text-sm font-medium text-[#94a3b8]">
        {{ dayContextLabel }}
      </span>
    </div>

    <!-- Route -->
    <div class="mt-3 px-4">
      <div class="flex min-w-0 items-start gap-2.5">
        <span aria-hidden="true" class="mt-0.5 shrink-0 text-xs" :class="dotOriginClass">●</span>
        <p class="min-w-0 truncate text-[13px] font-medium leading-snug" :class="routeTextClass">
          {{ originLine }}
        </p>
      </div>
      <div class="ml-[9px] h-4 w-px shrink-0 bg-white/15" />
      <div class="flex min-w-0 items-start gap-2.5">
        <span aria-hidden="true" class="mt-0.5 shrink-0 text-xs text-rose-400">●</span>
        <p class="min-w-0 truncate text-[13px] font-medium leading-snug" :class="routeTextClass">
          {{ destLine }}
        </p>
      </div>
    </div>

    <!-- Footer -->
    <div
      class="mt-4 flex gap-2 px-4 pb-4"
      :class="footerLayoutClass"
    >
      <p
        class="min-w-0 text-sm text-[#94a3b8]"
        :class="cardMode === 'pending' && showPendingActions ? 'w-full' : ''"
      >
        <span v-if="passengerCount > 0">👥 {{ passengerMeta }}</span>
        <span v-if="passengerCount > 0 && kmLabel"> · </span>
        <span v-if="kmLabel">{{ kmLabel }}</span>
      </p>

      <template v-if="cardMode === 'in_progress'">
        <button
          type="button"
          class="inline-flex min-h-12 items-center justify-center gap-1 rounded-xl bg-[#22c55e] px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-black/25 active:scale-[0.98]"
          :class="primaryActionClass"
          data-testid="driver-trip-card-open"
          @click.stop="goDetail"
        >
          {{ t('driver_home.card_open_trip') }}
        </button>
      </template>
      <template v-else-if="cardMode === 'confirmed'">
        <button
          type="button"
          class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#22c55e] px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-black/20 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
          :class="primaryActionClass"
          :disabled="busy || !canStartAction"
          data-testid="driver-trip-card-start"
          @click.stop="onStart"
        >
          {{ t('driver_home.card_start') }}
        </button>
      </template>
      <template v-else-if="cardMode === 'pending'">
        <template v-if="showPendingActions">
          <div class="grid min-h-[48px] w-full shrink-0 grid-cols-2 gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-[#22c55e] px-3 py-2.5 text-sm font-bold text-white shadow-md shadow-black/25 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy || !canConfirmAction"
              data-testid="driver-trip-card-confirm"
              @click.stop="onConfirm"
            >
              {{ t('driver_home.btn_confirm') }}
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border-2 border-rose-500/70 bg-transparent px-3 py-2.5 text-sm font-bold text-rose-400 transition active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy"
              @click.stop="onDecline"
            >
              {{ t('driver_home.btn_decline') }}
            </button>
          </div>
        </template>
        <button
          v-else
          type="button"
          class="inline-flex shrink-0 items-center justify-center rounded-xl border-2 border-[#fbbf24] bg-transparent px-4 py-2 text-sm font-bold text-[#fbbf24]"
          @click.stop="goDetail"
        >
          {{ t('driver_home.card_view_detail') }}
        </button>
      </template>
      <template v-else-if="cardMode === 'completed'">
        <span class="shrink-0 text-sm font-semibold text-[#64748b]">
          {{ t('driver_home.card_done_label') }}
        </span>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  formatDepartForTrip,
  tripDepartIso,
  tripOutboundInboundTimeRange,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'
import { driverTripDisplayPassengerCount } from '../../util/dispatchRequestPassengers'
import { useHaptics } from '../../composables/useHaptics'
import { resolveDispatchTripApiId } from '../../util/driverScheduleLeg'
import { tpDriverTripCanConfirm, tpDriverTripCanStart } from '../../composables/useTpDriverSlotActions'

const props = defineProps({
  trip: { type: Object, required: true },
  /** Hiển thị nút Xác nhận / Từ chối (banner chờ xác nhận). */
  showPendingActions: { type: Boolean, default: false },
  busy: { type: Boolean, default: false },
  /** `stacked` = full-width dashboard; `carousel` = horizontal snap strip */
  layout: { type: String, default: 'carousel' },
})

const emit = defineEmits(['start', 'confirm', 'decline'])

const router = useRouter()
const { locale, t } = useI18n()
const haptics = useHaptics()

function onStart() {
  haptics.impact()
  emit('start', props.trip)
}
function onConfirm() {
  haptics.success()
  emit('confirm', props.trip)
}
function onDecline() {
  haptics.warn()
  emit('decline', props.trip)
}

const tripRaw = computed(() => props.trip)

const isStackedLayout = computed(() => props.layout === 'stacked')

function drOf(t) {
  return t?.dispatch_request ?? t?.dispatchRequest ?? {}
}

const pickupTime = computed(() => {
  const tr = tripRaw.value
  const tag = locale.value === 'vi' ? 'vi' : 'en'
  const range = tripOutboundInboundTimeRange(tr, tag, t)
  if (range) return range
  if (tripDepartIso(tr)) return formatDepartForTrip(tr, tag).time
  if (tr.pickup_time) return tr.pickup_time
  return '—'
})

const tripCode = computed(() => {
  const tr = tripRaw.value
  return tr.request_code || tr.trip_number || `REQ-${tr.id}`
})

const typeLabel = computed(() => tripTypeBadgeText(tripRaw.value))

const isMultiSlotTp = computed(() => !!tripRaw.value._tp?.multi_slot)

const shiftLabel = computed(() => {
  if (!isMultiSlotTp.value) return ''
  const s = tripRaw.value._tp?.shift || tripRaw.value.calendar_shift
  if (s === 'morning') return t('driver_home.shift_morning')
  if (s === 'afternoon') return t('driver_home.shift_afternoon')
  return ''
})

const shiftBadgeClass = computed(() => {
  const s = tripRaw.value._tp?.shift || tripRaw.value.calendar_shift
  if (s === 'afternoon') {
    return 'bg-amber-500/20 text-amber-200 ring-amber-400/35'
  }
  return 'bg-sky-500/20 text-sky-200 ring-sky-400/35'
})

const typeBadgeStyle = computed(() => {
  const lbl = typeLabel.value
  if (lbl === 'P2P') return { backgroundColor: 'rgb(59 130 246 / 0.22)', color: '#93c5fd' }
  if (lbl === 'ĐĐ' || lbl === 'D2D') return { backgroundColor: 'rgb(168 85 247 / 0.22)', color: '#d8b4fe' }
  if (lbl === 'CT') return { backgroundColor: 'rgb(245 158 11 / 0.22)', color: '#fcd34d' }
  if (lbl === 'CG') return { backgroundColor: 'rgb(249 115 22 / 0.22)', color: '#fdba74' }
  return { backgroundColor: 'rgb(148 163 184 / 0.15)', color: '#94a3b8' }
})

const normalizedStatus = computed(() =>
  String(tripRaw.value.status ?? '').trim().toLowerCase(),
)

const cardMode = computed(() => {
  const s = normalizedStatus.value
  if (s === 'in_progress') return 'in_progress'
  if (s === 'completed') return 'completed'
  if (s === 'cancelled') return 'cancelled'
  if (['driver_confirmed', 'approved'].includes(s)) return 'confirmed'
  if (['pending', 'assigned'].includes(s)) return 'pending'
  if (s === 'incident') return 'pending'
  return 'pending'
})

const footerLayoutClass = computed(() => {
  if (cardMode.value === 'pending' && props.showPendingActions) return 'flex-col'
  if (isStackedLayout.value) return 'flex-col items-stretch gap-3'
  return 'flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'
})

const primaryActionClass = computed(() =>
  isStackedLayout.value ? 'w-full shrink-0' : 'w-full shrink-0 sm:w-auto',
)

const statusLabelText = computed(() => {
  const m = cardMode.value
  let base = ''
  if (m === 'in_progress') base = t('driver_home.card_status_in_progress')
  else if (m === 'confirmed') base = t('driver_home.card_status_confirmed')
  else if (m === 'pending') base = t('driver_home.card_status_pending')
  else if (m === 'completed') base = t('driver_home.card_status_completed')
  else if (m === 'cancelled') base = t('driver_home.card_status_cancelled')
  else base = tripRaw.value.status || '—'

  if (isMultiSlotTp.value && shiftLabel.value) {
    return `${shiftLabel.value} · ${base}`
  }
  return base
})

const PlayIcon = {
  name: 'PlayIcon',
  props: { class: String },
  setup(icProps) {
    return () =>
      h(
        'svg',
        { class: icProps.class, xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20', fill: 'currentColor' },
        h('path', {
          d: 'M6.3 2.841A1.5 1.5 0 0 0 4 4.11V15.89a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z',
        }),
      )
  },
}

const ClockMini = {
  name: 'ClockMini',
  props: { class: String },
  setup(icProps) {
    return () =>
      h(
        'svg',
        { class: icProps.class, xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20', fill: 'currentColor' },
        h('path', {
          'fill-rule': 'evenodd',
          d: 'M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5a.75.75 0 0 0 .471.696l3 1.25a.75.75 0 1 0 .558-1.392l-2.53-1.054V5Z',
          'clip-rule': 'evenodd',
        }),
      )
  },
}

const CheckMini = {
  name: 'CheckMini',
  props: { class: String },
  setup(icProps) {
    return () =>
      h(
        'svg',
        { class: icProps.class, xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20', fill: 'currentColor' },
        h('path', {
          'fill-rule': 'evenodd',
          d: 'M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z',
          'clip-rule': 'evenodd',
        }),
      )
  },
}

const canConfirmAction = computed(() => tpDriverTripCanConfirm(tripRaw.value))

const canStartAction = computed(() => tpDriverTripCanStart(tripRaw.value))

const statusIcon = computed(() => {
  const m = cardMode.value
  if (m === 'in_progress') return PlayIcon
  if (m === 'pending') return ClockMini
  if (m === 'completed') return CheckMini
  return null
})

const statusPillClass = computed(() => {
  const m = cardMode.value
  if (m === 'in_progress') return 'bg-emerald-500/25 text-emerald-300'
  if (m === 'confirmed') return 'bg-[#7fdcc8]/15 text-[#7fdcc8]'
  if (m === 'pending') return 'bg-[#fbbf24]/20 text-[#fbbf24]'
  if (m === 'completed') return 'bg-white/10 text-[#64748b]'
  if (m === 'cancelled') return 'bg-rose-500/15 text-rose-300/90'
  return 'bg-white/10 text-[#94a3b8]'
})

const rootClass = computed(() => {
  const m = cardMode.value
  const base = 'shadow-lg shadow-black/20 ring-1'
  if (m === 'cancelled') {
    return `${base} cursor-default border border-rose-900/40 bg-[#0c1412]/90 opacity-50 ring-rose-900/20`
  }
  if (m === 'completed') {
    return `${base} cursor-pointer border border-white/10 bg-[#0f1816]/90 opacity-[0.65] ring-white/[0.06] active:scale-[0.99]`
  }
  if (m === 'in_progress') {
    return `${base} cursor-pointer border-2 border-[#4ade80] bg-[#0a1f14]/95 ring-[#4ade80]/30 active:scale-[0.99]`
  }
  if (m === 'confirmed') {
    return `${base} cursor-pointer border border-[#7fdcc8]/45 bg-[#0c1815]/95 ring-[#7fdcc8]/15 active:scale-[0.99]`
  }
  if (m === 'pending') {
    return `${base} cursor-pointer border border-[#fbbf24]/50 bg-[#1a1810]/95 ring-[#fbbf24]/15 active:scale-[0.99]`
  }
  return `${base} cursor-pointer border border-white/10 bg-[#0f1816] ring-white/[0.06] active:scale-[0.99]`
})

const timeClass = computed(() => {
  if (cardMode.value === 'completed') return 'text-[#64748b]'
  return 'text-white'
})

const routeTextClass = computed(() => {
  if (cardMode.value === 'completed') return 'text-[#64748b]'
  return 'text-white'
})

const dotOriginClass = computed(() => {
  if (cardMode.value === 'completed') return 'text-emerald-600/60'
  return 'text-emerald-400'
})

function departDateLocal() {
  const tr = tripRaw.value
  if (tr.depart_at) return new Date(tr.depart_at)
  if (tr.depart_date && /^\d{4}-\d{2}-\d{2}/.test(tr.depart_date)) {
    const [y, m, d] = tr.depart_date.slice(0, 10).split('-').map(Number)
    return new Date(y, m - 1, d, 12, 0, 0, 0)
  }
  return null
}

const dayContextLabel = computed(() => {
  const d = departDateLocal()
  if (!d || Number.isNaN(d.getTime())) return ''
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tripDay = new Date(d)
  tripDay.setHours(0, 0, 0, 0)
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)

  if (tripDay.getTime() === today.getTime()) {
    const h = d.getHours()
    if (h < 12) return t('driver_home.day_this_morning')
    return t('driver_home.day_today')
  }
  if (tripDay.getTime() === tomorrow.getTime()) return t('driver_home.day_tomorrow')
  return d.toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    day: '2-digit',
    month: '2-digit',
  })
})

const originLine = computed(() => {
  const tr = tripRaw.value
  const dr = drOf(tr)
  return (tr.pickup_location || tr.origin || dr.origin || '—').toString()
})

const destLine = computed(() => {
  const tr = tripRaw.value
  const dr = drOf(tr)
  return (tr.dropoff_location || tr.destination || dr.destination || '—').toString()
})

const passengerCount = computed(() => driverTripDisplayPassengerCount(tripRaw.value))

const passengerMeta = computed(() => {
  const n = passengerCount.value
  if (typeLabel.value === 'ĐĐ' || typeLabel.value === 'D2D' || tripRaw.value._tp) {
    return t('driver_home.meta_students', { n })
  }
  return `${n} ${n === 1 ? t('trip_history_page.guest_one') : t('trip_history_page.guest_many')}`
})

const kmLabel = computed(() => {
  const tr = tripRaw.value
  const rec = tr.record ?? {}
  const completed = cardMode.value === 'completed'
  const actual = rec.distance_km
  if (completed && actual != null && !Number.isNaN(Number(actual))) {
    return t('driver_home.km_actual', { n: Number(actual).toFixed(1) })
  }
  const dr = drOf(tr)
  const est = dr.estimated_distance_km
  if (est != null && !Number.isNaN(Number(est))) {
    return t('driver_home.km_est', { n: Number(est).toFixed(1) })
  }
  const flat = tr.distance_km ?? tr.actual_km
  if (flat != null && !Number.isNaN(Number(flat))) {
    return t('driver_home.km_est', { n: Number(flat).toFixed(1) })
  }
  return ''
})

function goDetail() {
  const tp = tripRaw.value._tp
  if (tp?.day_id) {
    const query = tp.shift ? { shift: tp.shift } : {}
    router.push({ path: `/driver/tp-days/${tp.day_id}`, query })
    return
  }
  router.push(`/driver/trips/${resolveDispatchTripApiId(tripRaw.value) ?? tripRaw.value.id}`)
}

function onRootClick() {
  if (cardMode.value === 'cancelled') return
  goDetail()
}
</script>
