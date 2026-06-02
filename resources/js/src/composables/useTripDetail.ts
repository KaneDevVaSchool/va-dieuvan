import { computed, onUnmounted, ref, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripType } from '../util/labels'

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
    return v ? new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '-'
  }

  function fmtDateLong(v: string | null | undefined) {
    const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return v
      ? new Date(v).toLocaleDateString(l, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
      : '—'
  }

  const countdown = computed(() => {
    void tick.value
    const dep = trip.value?.depart_at
    if (!dep) return null
    const diffMin = Math.round((new Date(dep).getTime() - Date.now()) / 60_000)
    if (diffMin <= 0) return null
    const h = Math.floor(diffMin / 60)
    const m = diffMin % 60
    return h > 0 ? `${h}h ${m}p` : `${m}p`
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

  const requesterName = computed(() => trip.value?.dispatch_request?.requester?.name ?? '—')

  const requesterSubtitle = computed(() => {
    const s = trip.value?.dispatch_request?.wizard_snapshot
    const u = s?.form?.requester_unit
    if (u?.trim()) return u.trim()
    const code = trip.value?.dispatch_request?.requester?.employee_code
    if (code) return t('trip_detail.overview.employee_code', { code })
    return trip.value?.dispatch_request?.requester?.email ?? '—'
  })

  const requesterInitials = computed(() => {
    const name = requesterName.value.trim()
    if (!name || name === '—') return '?'
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  })

  const scheduleDateLong = computed(() => fmtDateLong(trip.value?.depart_at))
  const scheduleTimeRange = computed(() => {
    const a = trip.value?.depart_at
    const b = trip.value?.arrive_by
    if (!a) return '—'
    if (!b) return fmtTime(a)
    return `${fmtTime(a)} – ${fmtTime(b)}`
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
      out.push(t('trip_detail.overview.depart_vs_request', { req: fmtTime(dr.depart_at), trip: fmtTime(tr.depart_at) }))
    }
    if (dr.arrive_by && tr.arrive_by && !scheduleSame(dr.arrive_by, tr.arrive_by)) {
      out.push(t('trip_detail.overview.arrive_vs_request', { req: fmtTime(dr.arrive_by), trip: fmtTime(tr.arrive_by) }))
    }
    return out
  })

  const originLabel = computed(() => trip.value?.dispatch_request?.origin ?? '—')
  const destinationLabel = computed(() => trip.value?.dispatch_request?.destination ?? '—')
  const currentLabel = computed(() => {
    if (statusForWorkflow.value === 'in_progress') return t('trip_detail.current_location.en_route')
    return '—'
  })

  const statusForWorkflow = computed(() => workflowStatus?.value ?? trip.value?.status)

  const stepPickup = computed(() => {
    const s = statusForWorkflow.value
    if (s === 'completed') return { state: 'done' as const, label: t('trip_detail.step.completed') }
    if (s === 'in_progress') return { state: 'done' as const, label: t('trip_detail.step.completed') }
    if (s === 'cancelled') return { state: 'blocked' as const, label: t('trip_detail.step.cancelled') }
    return { state: 'active' as const, label: t('trip_detail.step.pending') }
  })

  const stepCurrent = computed(() => {
    const s = statusForWorkflow.value
    if (s === 'in_progress') return { state: 'active' as const, label: t('trip_detail.step.en_route') }
    if (s === 'completed') return { state: 'done' as const, label: t('trip_detail.step.arrived') }
    if (s === 'cancelled') return { state: 'blocked' as const, label: t('trip_detail.step.cancelled') }
    return { state: 'pending' as const, label: t('trip_detail.step.waiting') }
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
    scheduleTimeRange,
    scheduleDuration,
    scheduleMismatchNotes,
    originLabel,
    destinationLabel,
    currentLabel,
    stepPickup,
    stepCurrent,
    stepDropoff,
  }
}
