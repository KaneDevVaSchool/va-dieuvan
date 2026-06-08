import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { addTripEvent, getTrip, updateTripStatus, upsertTripRecord } from '../api/trips'
import { submitStandaloneTripCost, submitTripCost } from '../api/costs'
import { isPassengerRowFilled, isBusinessRowFilled, isCargoRowFilled } from './dispatchWizardConstants'
import { useDriverWebPushBoot } from './useDriverWebPushBoot'
import { useDriverVisiblePoll } from './useDriverVisiblePoll'
import { useDriverDashboardStore } from '../store/driverDashboard'
import { isTripCompletedForDriver, isTripCostEditableStatus } from '../constants/tripStatus'
import {
  formatTripDayMonthAndHm24,
  formatTripTimeHm24,
  formatTripWeekdayDateLong,
  isSameVnCalendarDayAsNow,
  parseTripInstant,
} from '../util/tripDatetime'
import { dispatchRequestEffectivePassengerCount } from '../util/dispatchRequestPassengers'
import { buildDriverTripPaxList } from '../util/buildDriverTripPaxList'

const PASSENGER_PICKUP_EVENT = 'passenger_pickup'

/** Ẩn nhập KM trên màn tài xế; kết thúc chuyến không mở modal odometer */
export const DRIVER_KM_SECTION_ENABLED = false

export function useDriverTripDetailPage() {
  const { t, te, locale } = useI18n()
  const route = useRoute()
  const driverDashboardStore = useDriverDashboardStore()
  const { bootDriverOutboundNotifications } = useDriverWebPushBoot()
  const { start: startTripDetailVisiblePoll } = useDriverVisiblePoll(() => refresh(), { intervalMs: 55_000 })

  const trip = ref(null)
  const loading = ref(true)
  const loadError = ref('')
  const moreOpen = ref(false)
  const eventPosting = ref(false)
  const actionBusy = ref(false)

  const kmModalOpen = ref(false)
  const kmAfterSave = ref(null)
  const kmSaving = ref(false)
  const endKmModel = ref('')
  const kmNoteModel = ref('')

  const costModalOpen = ref(false)
  const costSaving = ref(false)
  const costError = ref('')
  const costForm = ref({ type: 'fuel', amount: '', description: '' })

  const expandedStudentIdx = ref(null)
  const isPaused = ref(false)

  const studentFilterStatus = ref('all')
  const studentSortOrder = ref('default')

  function costTypeLabel(type) {
    const k = `driver_trip_detail.cost_type_${String(type || 'other')}`
    if (te(k)) return t(k)
    return type
  }

  function costStatusLabel(status) {
    const slug = String(status ?? '').trim().toLowerCase()
    const map = {
      draft: 'cost_status_draft',
      submitted: 'cost_status_submitted',
      confirmed: 'cost_status_confirmed',
      rejected: 'cost_status_rejected',
    }
    const suffix = map[slug]
    if (!suffix) return status ?? '—'
    const k = `driver_trip_detail.${suffix}`
    return te(k) ? t(k) : String(status ?? '')
  }

  const costTypes = computed(() => [
    { value: 'fuel', label: costTypeLabel('fuel') },
    { value: 'toll', label: costTypeLabel('toll') },
    { value: 'parking', label: costTypeLabel('parking') },
    { value: 'meal', label: costTypeLabel('meal') },
    { value: 'wash', label: costTypeLabel('wash') },
    { value: 'fine', label: costTypeLabel('fine') },
    { value: 'repair', label: costTypeLabel('repair') },
    { value: 'other', label: costTypeLabel('other') },
  ])

  onMounted(() => {
    void bootDriverOutboundNotifications()
    void load()
    startTripDetailVisiblePoll()
  })

  const tripId = computed(() => {
    const id = route.params.id
    const n = Number(id)
    return Number.isFinite(n) && n > 0 ? n : null
  })

  watch(
    () => route.params.id,
    () => {
      void load()
    },
  )

  const dr = computed(() => trip.value?.dispatch_request ?? null)
  const snap = computed(() => dr.value?.wizard_snapshot ?? null)

  function statusLabelTr(st) {
    const k = `trips_page.trip_status.${st}`
    const tr = t(k)
    return tr === k ? st : tr
  }

  const statusBadgeClass = computed(() => {
    const s = trip.value?.status
    if (s === 'in_progress') return 'bg-teal-500/20 text-teal-300 ring-1 ring-teal-400/30'
    if (s === 'completed') return 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30'
    if (s === 'cancelled') return 'bg-rose-500/20 text-rose-300 ring-1 ring-rose-400/30'
    if (s === 'driver_confirmed') return 'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/30'
    return 'bg-sky-500/20 text-sky-200 ring-1 ring-sky-400/30'
  })

  const headerStatusText = computed(() => {
    if (!trip.value) return '—'
    if (trip.value.status === 'in_progress') return t('driver_trip_detail.status_running')
    return statusLabelTr(trip.value.status)
  })

  const scheduleDateLine = computed(() => {
    const tr = trip.value
    if (!tr?.depart_at) return '—'
    return formatTripWeekdayDateLong(tr.depart_at, { locale: locale.value })
  })

  const scheduleTimeLine = computed(() => {
    const tr = trip.value
    if (!tr?.depart_at) return '—'
    const loc = locale.value
    const start = formatTripTimeHm24(tr.depart_at, { locale: loc })
    const endIso = tr.arrive_by || dr.value?.arrive_by
    if (!endIso) return t('driver_trip_detail.schedule_start_only', { start })
    const end = formatTripTimeHm24(endIso, { locale: loc })
    return t('driver_trip_detail.schedule_time_range', { start, end })
  })

  function formatCostTime(iso) {
    if (!iso) return '—'
    const loc = locale.value === 'en' ? 'en' : 'vi'
    const time = formatTripTimeHm24(iso, { locale: loc })
    if (isSameVnCalendarDayAsNow(iso)) return t('driver_trip_detail.today_time', { t: time })
    return formatTripDayMonthAndHm24(iso, { locale: loc })
  }

  const dispatcherPhone = import.meta.env.VITE_DISPATCHER_PHONE || null

  function splitAddress(s) {
    const t0 = (s || '').trim()
    if (!t0) return { main: '—', sub: '' }
    const i = t0.indexOf(',')
    if (i === -1) return { main: t0, sub: '' }
    return { main: t0.slice(0, i).trim(), sub: t0.slice(i + 1).trim() }
  }

  const primaryDriverLeg = computed(() => {
    const all = trip.value?.schedule_legs ?? []
    if (!Array.isArray(all) || !all.length) return null
    const did = myDriverId.value
    if (did) {
      const mine = all.filter((l) => Number(l.assignment?.driver_id) === did)
      if (mine.length === 1) return mine[0]
    }
    if (all.length === 1) return all[0]
    return null
  })

  const originLines = computed(() => {
    const leg = primaryDriverLeg.value
    if (leg?.pickup) return splitAddress(leg.pickup)
    return splitAddress(dr.value?.origin)
  })
  const destLines = computed(() => {
    const leg = primaryDriverLeg.value
    if (leg?.dropoff) return splitAddress(leg.dropoff)
    return splitAddress(dr.value?.destination)
  })
  const originMain = computed(() => originLines.value.main)
  const originSub = computed(() => originLines.value.sub)
  const destMain = computed(() => destLines.value.main)
  const destSub = computed(() => destLines.value.sub)

  const mapUrl = computed(() => {
    const a = (dr.value?.origin || '').trim()
    const b = (dr.value?.destination || '').trim()
    if (a && b) return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(a)}&destination=${encodeURIComponent(b)}&travelmode=driving`
    if (a) return `https://maps.google.com/maps?q=${encodeURIComponent(a)}`
    if (b) return `https://maps.google.com/maps?q=${encodeURIComponent(b)}`
    return ''
  })

  function mapUrlForPair(a, b) {
    const o = (a || '').trim()
    const d = (b || '').trim()
    if (o && d) {
      return `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(o)}&destination=${encodeURIComponent(d)}&travelmode=driving`
    }
    if (o) return `https://maps.google.com/maps?q=${encodeURIComponent(o)}`
    if (d) return `https://maps.google.com/maps?q=${encodeURIComponent(d)}`
    return ''
  }

  const myDriverId = computed(() => {
    const fromTrip = trip.value?.driver?.id ?? trip.value?.driver_id
    const n = Number(fromTrip)
    return Number.isFinite(n) && n > 0 ? n : null
  })

  const multiScheduleLegTrip = computed(() => (trip.value?.schedule_legs?.length ?? 0) > 1)

  const driverOperationalLeg = computed(() => {
    const all = trip.value?.schedule_legs ?? []
    if (!Array.isArray(all) || !all.length) return null
    const did = myDriverId.value
    let pool = all
    if (did) {
      const mine = all.filter((l) => Number(l.assignment?.driver_id) === did)
      if (mine.length) pool = mine
    } else if (all.length === 1) {
      pool = all
    } else {
      return null
    }

    const inProgress = pool.find((l) => l.status === 'in_progress')
    if (inProgress) return inProgress

    const waiting = pool.find(
      (l) => !['completed', 'cancelled', 'incident'].includes(String(l.status ?? '')),
    )
    return waiting ?? pool[0] ?? null
  })

  function buildStatusPayload(status) {
    const payload = { status }
    if (multiScheduleLegTrip.value && driverOperationalLeg.value?.key) {
      payload.schedule_key = driverOperationalLeg.value.key
    }
    return payload
  }

  const driverLegStatus = computed(
    () => driverOperationalLeg.value?.status ?? trip.value?.status,
  )

  const driverRouteLegs = computed(() => {
    const all = trip.value?.schedule_legs ?? []
    if (!Array.isArray(all) || !all.length) return []
    const did = myDriverId.value
    let legs = all
    if (did) {
      const mine = all.filter((l) => Number(l.assignment?.driver_id) === did)
      if (mine.length) legs = mine
    }
    if (legs.length <= 1) return []
    return legs.map((leg, idx) => {
      const o = splitAddress(leg.pickup || dr.value?.origin)
      const d = splitAddress(leg.dropoff || dr.value?.destination)
      return {
        key: leg.key,
        label: t('driver_trip_detail.schedule_leg', { n: leg.label_seq ?? idx + 1 }),
        originMain: o.main,
        originSub: o.sub,
        destMain: d.main,
        destSub: d.sub,
        mapUrl: mapUrlForPair(leg.pickup, leg.dropoff),
      }
    })
  })

  const tripCosts = computed(() => {
    const c = trip.value?.costs
    if (!Array.isArray(c)) return []
    return c.slice().sort((a, b) => (b.id || 0) - (a.id || 0))
  })

  function costAmountNum(c) {
    return Number(c.amount) || 0
  }

  const costsApprovedTotal = computed(() =>
    tripCosts.value.filter((c) => String(c.status).toLowerCase() === 'confirmed').reduce((s, c) => s + costAmountNum(c), 0),
  )

  const costsPendingTotal = computed(() =>
    tripCosts.value
      .filter((c) => {
        const s = String(c.status).toLowerCase()
        return s === 'submitted' || s === 'draft'
      })
      .reduce((s, c) => s + costAmountNum(c), 0),
  )

  const canAddCost = computed(() => isTripCostEditableStatus(trip.value?.status))

  const canAddPostTripCost = computed(
    () => isTripCompletedForDriver(trip.value?.status) && !canAddCost.value,
  )

  const canOpenCostModal = computed(() => canAddCost.value || canAddPostTripCost.value)

  const startKmModel = computed(() => {
    const r = trip.value?.record
    if (r?.start_odometer_km != null) return r.start_odometer_km
    const v = trip.value?.vehicle?.odometer_km
    if (v != null) return v
    const d0 = trip.value?.driver?.odometer_km
    if (d0 != null) return d0
    return null
  })

  const hasEndOdometer = computed(() => trip.value?.record?.end_odometer_km != null)

  function formatKm(n) {
    if (n == null || n === '') return '—'
    return Number(n).toLocaleString('vi-VN')
  }

  function formatKmInput(n) {
    if (n == null || n === '') return ''
    return Number(n).toLocaleString('vi-VN')
  }

  const distancePreview = computed(() => {
    const s = startKmModel.value
    const e = parseDigits(endKmModel.value)
    if (s == null || e == null) return t('driver_trip_detail.km_dash')
    const d = e - Number(s)
    if (Number.isNaN(d) || d < 0) return t('driver_trip_detail.km_dash')
    return `${d.toLocaleString('vi-VN')} km`
  })

  const canSubmitKm = computed(() => {
    const e = parseDigits(endKmModel.value)
    if (e == null) return false
    const s = startKmModel.value
    if (s != null && e < Number(s)) return false
    return true
  })

  function parseDigits(v) {
    const d = String(v || '').replace(/\D/g, '')
    if (d === '') return null
    return parseInt(d, 10)
  }

  function onEndKmInput() {
    const p = parseDigits(endKmModel.value)
    if (p != null) endKmModel.value = p.toLocaleString('vi-VN')
  }

  function openKmModal(completeAfter) {
    kmAfterSave.value = completeAfter === 'complete' ? 'complete' : null
    const e = trip.value?.record?.end_odometer_km
    if (e != null) {
      endKmModel.value = String(e)
      onEndKmInput()
    } else {
      endKmModel.value = ''
    }
    kmNoteModel.value = (trip.value?.record?.driver_notes || '').trim()
    kmModalOpen.value = true
  }

  async function submitKmModal() {
    const id = tripId.value
    if (id == null || !canSubmitKm.value || kmSaving.value) return
    const e = parseDigits(endKmModel.value)
    if (e == null) return
    const doComplete = kmAfterSave.value === 'complete'
    kmSaving.value = true
    try {
      const s = startKmModel.value
      const payload = { end_odometer_km: e }
      const note = kmNoteModel.value?.trim()
      if (note) payload.driver_notes = note
      if (s != null && trip.value?.record?.start_odometer_km == null) {
        payload.start_odometer_km = Number(s)
      }
      await upsertTripRecord(id, payload)
      await refresh()
      kmModalOpen.value = false
      if (doComplete) await doCompleteTrip()
    } catch {
      loadError.value = t('driver_trip_detail.km_err')
    } finally {
      kmSaving.value = false
      kmAfterSave.value = null
    }
  }

  function openCostModal() {
    if (!canOpenCostModal.value) return
    costError.value = ''
    costForm.value = { type: 'fuel', amount: '', description: '' }
    costModalOpen.value = true
  }

  async function submitCost() {
    const id = tripId.value
    if (id == null || costSaving.value || !canOpenCostModal.value) return
    const a = String(costForm.value.amount || '').replace(/\D/g, '')
    const num = a === '' ? NaN : parseInt(a, 10)
    if (!Number.isFinite(num) || num < 0) {
      costError.value = t('driver_trip_detail.cost_err_amount')
      return
    }
    const payload = {
      type: costForm.value.type,
      amount: num,
      description: costForm.value.description?.trim() || null,
      currency: 'VND',
    }
    const idem = `driver-cost-${id}-${Date.now()}`
    costSaving.value = true
    costError.value = ''
    try {
      if (canAddPostTripCost.value) {
        await submitStandaloneTripCost({ ...payload, trip_id: id }, { idempotencyKey: idem })
      } else {
        await submitTripCost(id, payload, { idempotencyKey: idem })
      }
      costModalOpen.value = false
      await refresh()
    } catch {
      costError.value = t('driver_trip_detail.cost_err_submit')
    } finally {
      costSaving.value = false
    }
  }

  const warningBanner = computed(() => {
    const tr = trip.value
    if (!tr) return null
    if (!isTripCostEditableStatus(tr.status)) return null
    const endIso = tr.arrive_by || dr.value?.arrive_by
    if (!endIso) return null
    const end = new Date(endIso).getTime()
    if (!Number.isFinite(end)) return null
    const min = Math.round((end - Date.now()) / 60000)
    if (min < 0 || min > 10) return null
    const firstDrop = (dr.value?.destination || '').trim() || '—'
    return { minutes: min, body: t('driver_trip_detail.warn_body', { place: firstDrop }) }
  })

  const pickupStateByIndex = computed(() => {
    const evs = trip.value?.events
    if (!Array.isArray(evs) || !evs.length) return new Map()
    const sorted = [...evs].sort((a, b) => (b.id || 0) - (a.id || 0))
    const m = new Map()
    for (const e of sorted) {
      if (e.type !== PASSENGER_PICKUP_EVENT) continue
      const d = e.data
      if (!d || d.row_index == null) continue
      const idx = Number(d.row_index)
      if (Number.isNaN(idx) || m.has(idx)) continue
      const st = d.state
      if (st === 'picked_up' || st === 'absent') m.set(idx, st)
    }
    return m
  })

  function rowState(i) {
    const fromEvent = pickupStateByIndex.value.get(i)
    if (fromEvent) return fromEvent
    const row = paxList.value[i]
    if (row?.policyCheckInKey && trip.value?.passenger_check_ins) {
      const entry = trip.value.passenger_check_ins[row.policyCheckInKey]
      if (entry?.checked_in_at || row.policyCheckedInAt) return 'picked_up'
    }
    return null
  }

  const paxKind = computed(() => {
    const tt = dr.value?.trip_type
    if (tt === 'cargo') return 'cargo'
    if (tt === 'door_to_door' || tt === 'point_to_point') return 'student'
    return 'other'
  })

  function classFromNotes(notes) {
    const s = (notes || '').trim()
    if (!s) return ''
    const m = s.match(/lớp\s*([0-9A-Za-z]+)/i) || s.match(/Lớp\s*([0-9A-Za-z.]+)/)
    if (m) return `Lớp ${m[1]}`
    if (s.length < 40) return s
    return `${s.slice(0, 36)}…`
  }

  const paxList = computed(() =>
    buildDriverTripPaxList({
      dr: dr.value,
      trip: trip.value,
      snap: snap.value,
      t,
      locale: locale.value,
      formatTripTimeHm24,
      isPassengerRowFilled,
      isBusinessRowFilled,
      isCargoRowFilled,
      classFromNotes,
    }),
  )

  const indexedPaxList = computed(() => paxList.value.map((p, i) => ({ ...p, _origIndex: i })))

  const displayedPaxList = computed(() => {
    let list = indexedPaxList.value

    if (studentFilterStatus.value !== 'all') {
      list = list.filter((p) => {
        const st = rowState(p._origIndex)
        if (studentFilterStatus.value === 'waiting') return st == null
        return st === studentFilterStatus.value
      })
    }

    if (studentSortOrder.value === 'time_asc') {
      list = [...list].sort((a, b) => (a.time || '').localeCompare(b.time || ''))
    } else if (studentSortOrder.value === 'time_desc') {
      list = [...list].sort((a, b) => (b.time || '').localeCompare(a.time || ''))
    }

    return list
  })

  function cycleFilter() {
    const cycle = ['all', 'waiting', 'picked_up', 'absent']
    const idx = cycle.indexOf(studentFilterStatus.value)
    studentFilterStatus.value = cycle[(idx + 1) % cycle.length]
  }

  function cycleSort() {
    const cycle = ['default', 'time_asc', 'time_desc']
    const idx = cycle.indexOf(studentSortOrder.value)
    studentSortOrder.value = cycle[(idx + 1) % cycle.length]
  }

  function toggleStudent(origIdx) {
    expandedStudentIdx.value = expandedStudentIdx.value === origIdx ? null : origIdx
  }

  function studentInitials(name) {
    const n = (name || '').trim() || '?'
    const p = n.split(/\s+/)
    if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
    return n.slice(0, 2).toUpperCase()
  }

  const effectivePassengerCount = computed(() => dispatchRequestEffectivePassengerCount(dr.value))

  /** Số khách hiển thị (thống kê, tiêu đề, thanh đón) — ưu tiên số trên yêu cầu khi danh sách chi tiết chưa đủ. */
  const paxDisplayTotal = computed(() => {
    const listed = paxList.value.length
    const eff = effectivePassengerCount.value
    return Math.max(listed, eff) || 0
  })

  const statsStudentCount = computed(() => paxDisplayTotal.value)

  const statsDistance = computed(() => {
    const rec = trip.value?.record
    if (rec?.distance_km != null) return `${Number(rec.distance_km).toLocaleString('vi-VN')} km`
    const dr2 = dr.value
    if (dr2?.estimated_distance_km != null) return `${Number(dr2.estimated_distance_km).toLocaleString('vi-VN')} km`
    return '— km'
  })

  const statsDuration = computed(() => {
    const tr = trip.value
    if (!tr?.depart_at) return `— ${t('driver_trip_detail.stats_duration_unit')}`
    const end = tr.arrive_by || dr.value?.arrive_by
    if (!end) return `— ${t('driver_trip_detail.stats_duration_unit')}`
    const d0 = parseTripInstant(tr.depart_at)
    const d1 = parseTripInstant(end)
    if (!d0 || !d1) return `— ${t('driver_trip_detail.stats_duration_unit')}`
    const mins = Math.round((d1.getTime() - d0.getTime()) / 60000)
    if (!Number.isFinite(mins) || mins < 0) return `— ${t('driver_trip_detail.stats_duration_unit')}`
    return `${mins} ${t('driver_trip_detail.stats_duration_unit')}`
  })

  const showPickupBar = computed(() => paxKind.value === 'student' && paxList.value.length > 0)

  const pickedCount = computed(() => {
    if (paxKind.value !== 'student') return 0
    let c = 0
    for (let i = 0; i < paxList.value.length; i += 1) {
      if (rowState(i) === 'picked_up') c += 1
    }
    return c
  })

  function isNextIndex(i) {
    if (paxKind.value !== 'student' || !canMarkPickup.value) return false
    for (let j = 0; j < paxList.value.length; j += 1) {
      const st = rowState(j)
      if (st === 'picked_up' || st === 'absent') continue
      return j === i
    }
    return false
  }

  const canMarkPickup = computed(
    () => trip.value?.status === 'in_progress' && paxKind.value === 'student' && paxList.value.length > 0,
  )

  const canStart = computed(() => {
    if (!trip.value) return false
    const st = driverLegStatus.value
    return ['assigned', 'driver_confirmed', 'pending', 'approved'].includes(st)
  })

  const canEndTrip = computed(() => driverLegStatus.value === 'in_progress')

  async function onEndTrip() {
    if (!canEndTrip.value) return
    if (!hasEndOdometer.value && DRIVER_KM_SECTION_ENABLED) {
      openKmModal('complete')
      return
    }
    await doCompleteTrip()
  }

  async function doCompleteTrip() {
    const id = tripId.value
    if (id == null || actionBusy.value) return
    actionBusy.value = true
    try {
      await updateTripStatus(id, buildStatusPayload('completed'))
      await refresh()
      void driverDashboardStore.refreshTripsQuiet()
    } catch {
      loadError.value = t('driver_trip_detail.status_err')
    } finally {
      actionBusy.value = false
    }
  }

  async function load() {
    const id = tripId.value
    if (id == null) {
      loadError.value = t('driver_trip_detail.err_bad_id')
      loading.value = false
      return
    }
    loading.value = true
    loadError.value = ''
    try {
      trip.value = await getTrip(id)
    } catch {
      loadError.value = t('driver_home.load_error')
      trip.value = null
    } finally {
      loading.value = false
    }
  }

  async function refresh() {
    const id = tripId.value
    if (id == null) return
    try {
      trip.value = await getTrip(id)
    } catch {
      /* keep stale data */
    }
  }

  async function setRowState(i, state) {
    const id = tripId.value
    if (id == null || eventPosting.value) return
    eventPosting.value = true
    try {
      await addTripEvent(id, {
        type: PASSENGER_PICKUP_EVENT,
        data: { row_index: i, state, at: new Date().toISOString() },
      })
      await refresh()
    } catch {
      loadError.value = t('driver_trip_detail.event_err')
    } finally {
      eventPosting.value = false
    }
  }

  async function startTrip() {
    const id = tripId.value
    if (id == null || actionBusy.value) return
    actionBusy.value = true
    try {
      await updateTripStatus(id, buildStatusPayload('in_progress'))
      await refresh()
      void driverDashboardStore.refreshTripsQuiet()
    } catch {
      loadError.value = t('driver_trip_detail.status_err')
    } finally {
      actionBusy.value = false
    }
  }

  function onRefreshMenu() {
    void refresh()
    moreOpen.value = false
  }

  return {
    trip,
    loading,
    loadError,
    moreOpen,
    eventPosting,
    actionBusy,
    kmModalOpen,
    kmSaving,
    endKmModel,
    kmNoteModel,
    costModalOpen,
    costSaving,
    costError,
    costForm,
    expandedStudentIdx,
    isPaused,
    studentFilterStatus,
    studentSortOrder,
    costTypes,
    tripId,
    dr,
    statusBadgeClass,
    headerStatusText,
    scheduleDateLine,
    scheduleTimeLine,
    dispatcherPhone,
    originMain,
    originSub,
    destMain,
    destSub,
    mapUrl,
    driverRouteLegs,
    tripCosts,
    costsApprovedTotal,
    costsPendingTotal,
    canAddCost,
    canAddPostTripCost,
    startKmModel,
    hasEndOdometer,
    distancePreview,
    canSubmitKm,
    warningBanner,
    rowState,
    paxKind,
    paxList,
    displayedPaxList,
    statsStudentCount,
    paxDisplayTotal,
    statsDistance,
    statsDuration,
    showPickupBar,
    pickedCount,
    canMarkPickup,
    canStart,
    canEndTrip,
    DRIVER_KM_SECTION_ENABLED,
    costTypeLabel,
    costStatusLabel,
    formatKm,
    formatKmInput,
    formatCostTime,
    onEndKmInput,
    openKmModal,
    submitKmModal,
    openCostModal,
    submitCost,
    cycleFilter,
    cycleSort,
    toggleStudent,
    studentInitials,
    isNextIndex,
    onEndTrip,
    startTrip,
    setRowState,
    onRefreshMenu,
    refresh,
  }
}
