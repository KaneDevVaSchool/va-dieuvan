import { computed, toValue } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CubeIcon,
  ExclamationTriangleIcon,
  TruckIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import { tripStatusAdminPillClass } from '../constants/tripStatus'
import { labelTripStatus, labelTripType } from '../util/labels'

export const timelineTripCardHoverClass =
  'transition-all duration-150 hover:z-20 hover:-translate-y-px hover:shadow-lg focus-visible:z-20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/45 dark:hover:brightness-105'

export const timelineTripCardSelectedClass =
  'ring-2 ring-teal-500 ring-offset-2 shadow-md dark:ring-offset-slate-900'

const BAR = {
  completed: '#639922',
  in_progress: '#378ADD',
  pending: '#888780',
  late: '#D85A30',
  cargo: '#BA7517',
}

const CARD = {
  completed:
    'bg-green-50 border-green-200 text-green-900 dark:bg-green-950/40 dark:border-green-800/60 dark:text-green-100',
  in_progress:
    'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/40 dark:border-blue-800/60 dark:text-blue-100',
  pending:
    'bg-white border-slate-200 text-slate-800 dark:bg-slate-900/80 dark:border-slate-600 dark:text-slate-100',
  late:
    'bg-orange-50 border-orange-200 text-orange-900 dark:bg-orange-950/40 dark:border-orange-800/60 dark:text-orange-100',
}

function dispatchRequest(trip) {
  return trip?.dispatch_request ?? trip?.dispatchRequest ?? null
}

export function tripEndAt(trip) {
  const r = dispatchRequest(trip)
  const end = r?.arrive_by ?? trip?.arrive_by
  if (end) return new Date(end)
  const s = new Date(trip.depart_at)
  return new Date(s.getTime() + 60 * 60 * 1000)
}

export function tripTypeOf(trip) {
  return dispatchRequest(trip)?.trip_type ?? null
}

export function isTimelineTripLate(trip, now = new Date()) {
  if (!trip?.depart_at) return false
  if (['completed', 'cancelled'].includes(trip.status)) return false
  const n = now.getTime()
  if (n > tripEndAt(trip).getTime()) return true
  if (
    trip.status !== 'in_progress' &&
    n > new Date(trip.depart_at).getTime()
  ) {
    return true
  }
  return false
}

export function timelineCardVisualState(trip, now = new Date()) {
  const late = isTimelineTripLate(trip, now)
  if (late) {
    return { key: 'late', barColor: BAR.late, cardClass: CARD.late }
  }
  if (trip.status === 'completed') {
    return {
      key: 'completed',
      barColor: BAR.completed,
      cardClass: CARD.completed,
    }
  }
  if (trip.status === 'in_progress') {
    return {
      key: 'in_progress',
      barColor: BAR.in_progress,
      cardClass: CARD.in_progress,
    }
  }
  const tt = tripTypeOf(trip)
  return {
    key: 'pending',
    barColor: tt === 'cargo' ? BAR.cargo : BAR.pending,
    cardClass: CARD.pending,
  }
}

function passengerTripTypes() {
  return new Set(['point_to_point', 'business'])
}

export function timelineTripTypeIcon(trip, late) {
  if (late) return ExclamationTriangleIcon
  const tt = tripTypeOf(trip)
  if (tt === 'cargo') return CubeIcon
  if (tt === 'door_to_door') return UsersIcon
  if (passengerTripTypes().has(tt)) return TruckIcon
  return TruckIcon
}

export function useDispatchTimelineTripCard(tripSource) {
  const { t, locale } = useI18n()

  const trip = computed(() => toValue(tripSource))

  const visual = computed(() =>
    timelineCardVisualState(trip.value, new Date()),
  )

  const late = computed(() => isTimelineTripLate(trip.value, new Date()))

  const typeIcon = computed(() => timelineTripTypeIcon(trip.value, late.value))

  function fmtTime(v) {
    if (!v) return '—'
    const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
    return new Date(v).toLocaleTimeString(loc, {
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const timeRange = computed(() => {
    const tr = trip.value
    if (!tr) return '—'
    return `${fmtTime(tr.depart_at)}–${fmtTime(tripEndAt(tr))}`
  })

  const originText = computed(
    () => dispatchRequest(trip.value)?.origin?.trim() || '—',
  )
  const destinationText = computed(
    () => dispatchRequest(trip.value)?.destination?.trim() || '—',
  )

  const routeLine = computed(() => {
    const a = originText.value
    const b = destinationText.value
    if (a !== '—' && b !== '—') return `${a} → ${b}`
    if (a !== '—') return a
    if (b !== '—') return b
    return t('dispatcher_board.untitled_trip')
  })

  const tripTypeLabel = computed(() => {
    const tt = tripTypeOf(trip.value)
    return tt ? labelTripType(tt) : '—'
  })

  const statusLabel = computed(() => {
    if (late.value) return t('resources_dashboard.timeline_status_late')
    return labelTripStatus(trip.value?.status)
  })

  const statusPillClass = computed(() => {
    if (late.value) {
      return 'bg-orange-100 text-orange-900 dark:bg-orange-950/50 dark:text-orange-100'
    }
    return tripStatusAdminPillClass(trip.value?.status)
  })

  const driverLabel = computed(() => {
    const tr = trip.value
    if (!tr) return ''
    if (tr.driver?.full_name) return tr.driver.full_name
    const tp =
      tr.transport_provider?.name ?? tr.transportProvider?.name ?? null
    if (tp) return `${tp} (NCC)`
    return ''
  })

  return {
    visual,
    late,
    typeIcon,
    timeRange,
    routeLine,
    originText,
    destinationText,
    tripTypeLabel,
    statusLabel,
    statusPillClass,
    driverLabel,
    cardHoverClass: timelineTripCardHoverClass,
    cardSelectedClass: timelineTripCardSelectedClass,
  }
}
