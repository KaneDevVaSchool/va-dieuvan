import { computed, onUnmounted, ref, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripType } from '../util/labels'
import { isEmptyDisplay } from '../util/displayValue'

/** Trip payload from GET /trips/:id — kept loose to match existing JS view. */
export type TripDetail = Record<string, any> | null

function scheduleSame(isoA: string | null | undefined, isoB: string | null | undefined) {
  if (!isoA || !isoB) return true
  return new Date(isoA).getTime() === new Date(isoB).getTime()
}

export function useTripDetail(trip: Ref<TripDetail>, workflowStatus?: Ref<string | null | undefined>) {
  const { t, locale } = useI18n()

  const tick = ref(0)
  const intervalId = setInterval(() => {
    tick.value += 1
  }, 60_000)
  onUnmounted(() => clearInterval(intervalId))

  function fmtTime(v: string | null | undefined) {
    const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
    if (!v) return ''
    return new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit', hour12: false })
  }

  function fmtTimeDisplay(v: string | null | undefined) {
    const s = fmtTime(v)
    return s || t('trip_detail.empty.time')
  }

  function fmtDateLong(v: string | null | undefined) {
    const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
    if (!v) return ''
    return new Date(v).toLocaleDateString(l, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  }

  function fmtDateCompact(v: string | null | undefined) {
    const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return v
      ? new Date(v).toLocaleDateString(l, { weekday: 'short', day: 'numeric', month: 'short' })
      : ''
  }

  const countdown = computed(() => {
    void tick.value
    const dep = trip.value?.depart_at
    if (!dep) return null
    const diffMin = Math.round((new Date(dep).getTime() - Date.now()) / 60_000)
    if (diffMin <= 0) return null
    if (diffMin >= 24 * 60) {
      const days = Math.floor(diffMin / (24 * 60))
      return t('trip_detail.overview.countdown_days', { n: days })
    }
    const h = Math.floor(diffMin / 60)
    const m = diffMin % 60
    if (h > 0) return t('trip_detail.overview.countdown_hours', { h, m })
    return t('trip_detail.overview.countdown_minutes', { m })
  })

  const tripTypeLabel = computed(() => labelTripType(trip.value?.dispatch_request?.trip_type))

  const slaBanner = computed(() => {
    void tick.value
    const tr = trip.value
    if (!tr?.arrive_by) return null
    if (['completed', 'cancelled'].includes(tr.status)) return null
    const end = new Date(tr.arrive_by).getTime()
    if (!Number.isFinite(end)) return null
    const min = Math.round((end - Date.now()) / 60_000)
    if (min < 0) return { kind: 'overdue' as const, text: t('trip_detail.sla.overdue_detail', { n: Math.abs(min) }) }
    return { kind: 'ok' as const, text: t('trip_detail.sla.remaining_minutes', { n: min }) }
  })

  const requesterName = computed(() => trip.value?.dispatch_request?.requester?.name?.trim() || '')

  const requesterSubtitle = computed(() => {
    const s = trip.value?.dispatch_request?.wizard_snapshot
    const u = s?.form?.requester_unit
    if (u?.trim()) return u.trim()
    const code = trip.value?.dispatch_request?.requester?.employee_code
    if (code) return t('trip_detail.overview.employee_code', { code })
    return trip.value?.dispatch_request?.requester?.email?.trim() || ''
  })

  const requesterInitials = computed(() => {
    const name = requesterName.value.trim()
    if (!name) return '?'
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  })

  const scheduleDateLong = computed(() => fmtDateLong(trip.value?.depart_at))
  const scheduleDepartTime = computed(() => fmtTime(trip.value?.depart_at))
  const scheduleArriveTime = computed(() => fmtTime(trip.value?.arrive_by))

  const scheduleArriveDateShort = computed(() => {
    const a = trip.value?.depart_at
    const b = trip.value?.arrive_by
    if (!a || !b) return ''
    const da = new Date(a)
    const db = new Date(b)
    if (da.toDateString() === db.toDateString()) return ''
    return fmtDateCompact(b)
  })

  const scheduleTimeRange = computed(() => {
    const a = trip.value?.depart_at
    const b = trip.value?.arrive_by
    if (!a) return ''
    if (!b) return scheduleDepartTime.value
    const end =
      scheduleArriveDateShort.value !== ''
        ? `${scheduleArriveTime.value} (${scheduleArriveDateShort.value})`
        : scheduleArriveTime.value
    if (!scheduleDepartTime.value && !end) return ''
    if (!end) return scheduleDepartTime.value
    return `${scheduleDepartTime.value} → ${end}`
  })

  const scheduleDuration = computed(() => {
    const a = trip.value?.depart_at
    const b = trip.value?.arrive_by
    if (!a || !b) return ''
    const ms = new Date(b).getTime() - new Date(a).getTime()
    if (!Number.isFinite(ms) || ms <= 0) return ''
    const h = Math.round((ms / 3_600_000) * 10) / 10
    return t('trip_detail.overview.duration_hours', { n: h })
  })

  const scheduleMismatchNotes = computed(() => {
    const dr = trip.value?.dispatch_request
    const tr = trip.value
    if (!dr || !tr) return []
    const out: string[] = []
    if (dr.depart_at && tr.depart_at && !scheduleSame(dr.depart_at, tr.depart_at)) {
      out.push(t('trip_detail.overview.depart_vs_request', { req: fmtTimeDisplay(dr.depart_at), trip: fmtTimeDisplay(tr.depart_at) }))
    }
    if (dr.arrive_by && tr.arrive_by && !scheduleSame(dr.arrive_by, tr.arrive_by)) {
      out.push(t('trip_detail.overview.arrive_vs_request', { req: fmtTimeDisplay(dr.arrive_by), trip: fmtTimeDisplay(tr.arrive_by) }))
    }
    return out
  })

  const originLabel = computed(() => {
    const o = trip.value?.dispatch_request?.origin
    return isEmptyDisplay(o) ? '' : String(o).trim()
  })
  const destinationLabel = computed(() => {
    const d = trip.value?.dispatch_request?.destination
    return isEmptyDisplay(d) ? '' : String(d).trim()
  })
  const statusForWorkflow = computed(() => workflowStatus?.value ?? trip.value?.status)

  const stepPickup = computed(() => {
    const s = statusForWorkflow.value
    if (s === 'completed') return { state: 'done' as const, label: t('trip_detail.step.completed') }
    if (s === 'in_progress') return { state: 'done' as const, label: t('trip_detail.step.completed') }
    if (s === 'cancelled') return { state: 'blocked' as const, label: t('trip_detail.step.cancelled') }
    return { state: 'active' as const, label: t('trip_detail.step.pending') }
  })

  const stepDropoff = computed(() => {
    const s = statusForWorkflow.value
    if (s === 'completed') return { state: 'done' as const, label: t('trip_detail.step.completed') }
    if (s === 'cancelled') return { state: 'blocked' as const, label: t('trip_detail.step.cancelled') }
    if (s === 'in_progress') return { state: 'active' as const, label: t('trip_detail.step.pending') }
    return { state: 'pending' as const, label: t('trip_detail.step.pending') }
  })

  return {
    countdown,
    tripTypeLabel,
    slaBanner,
    requesterName,
    requesterSubtitle,
    requesterInitials,
    scheduleDateLong,
    scheduleDepartTime,
    scheduleArriveTime,
    scheduleArriveDateShort,
    scheduleTimeRange,
    scheduleDuration,
    scheduleMismatchNotes,
    originLabel,
    destinationLabel,
    stepPickup,
    stepDropoff,
  }
}
