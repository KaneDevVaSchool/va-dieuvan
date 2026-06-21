import { computed, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  isBusinessRowFilled,
  isCargoRowFilled,
  isPassengerRowFilled,
} from './dispatchWizardConstants'
import { buildDriverTripPaxList } from '../util/buildDriverTripPaxList'
import { formatTripTimeHm24 } from '../util/tripDatetime'
import { labelTripStatus } from '../util/labels'
import { parseMoneyVnd, formatVndCurrency as formatVndMoney, VND_CURRENCY_SUFFIX } from '../util/money'

function nz(v) {
  return v == null ? '' : String(v).trim()
}

function fmtDateOnly(v, locale) {
  if (!v) return ''
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    const [y, m, d] = s.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return s
  const loc = locale === 'en' ? 'en-GB' : 'vi-VN'
  return d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function durationBetween(isoA, isoB, t) {
  if (!isoA || !isoB) return ''
  const ms = new Date(isoB).getTime() - new Date(isoA).getTime()
  if (!Number.isFinite(ms) || ms <= 0) return ''
  const h = Math.round((ms / 3_600_000) * 10) / 10
  return t('trip_detail.overview.duration_hours', { n: h })
}

function initialsFromName(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

/**
 * @param {import('vue').Ref|import('vue').ComputedRef|object} reqSource
 * @param {import('vue').Ref|import('vue').ComputedRef|null} costEstimateSource
 */
export function useStaffRequestTripDetailTab(reqSource, costEstimateSource) {
  const { t, locale } = useI18n()
  const req = toRef(reqSource)
  const costEstimate = toRef(costEstimateSource)

  const form = computed(() => req.value?.wizard_snapshot?.form ?? {})
  const snap = computed(() => req.value?.wizard_snapshot ?? {})
  const trip = computed(() => req.value?.trip ?? null)
  const isCargo = computed(() => req.value?.trip_type === 'cargo')
  const isBusiness = computed(() => req.value?.trip_type === 'business')

  const emptyLabel = computed(() => t('request_detail.ops_no_data'))

  function moneyDisplay(v) {
    const n = parseMoneyVnd(v)
    if (!n) return ''
    return formatVndMoney(n, VND_CURRENCY_SUFFIX)
  }

  const planDateNeeded = computed(() => fmtDateOnly(form.value.date_needed, locale.value) || emptyLabel.value)

  const planDuration = computed(() => {
    const fromTrip = durationBetween(req.value?.depart_at, req.value?.arrive_by, t)
    if (fromTrip) return fromTrip
    const row = isCargo.value
      ? (snap.value.cargoRows ?? []).find(isCargoRowFilled)
      : isBusiness.value
        ? (snap.value.businessRows ?? []).find(isBusinessRowFilled)
        : (snap.value.passengerRows ?? []).find(isPassengerRowFilled)
    if (!row) return emptyLabel.value
    const start = row.depart_at || row.pickup_at
    const end = row.return_at || row.delivery_at
    return durationBetween(start, end, t) || emptyLabel.value
  })

  const planVehicleType = computed(() => {
    const hint = nz(form.value.pricing_vehicle_hint)
    if (hint) return hint
    const seats = trip.value?.vehicle?.seat_count
    if (seats) return t('request_detail.trip_tab_seats_n', { n: seats })
    const est = costEstimate.value?.vehicleHint
    return est || emptyLabel.value
  })

  const planBudget = computed(() => {
    const total = costEstimate.value?.total
    if (total != null && total > 0) return moneyDisplay(total)
    const ev = moneyDisplay(form.value.estimated_vehicle_cost)
    if (ev) return ev
    const sp = moneyDisplay(req.value?.service_price)
    return sp || emptyLabel.value
  })

  const hasAssignment = computed(() => {
    const tr = trip.value
    if (!tr) return false
    return !!(tr.vehicle_id || tr.driver_id || tr.vehicle || tr.driver)
  })

  const assignmentVehicle = computed(() => {
    const v = trip.value?.vehicle
    if (!v) return emptyLabel.value
    const parts = [v.type, v.license_plate].map(nz).filter(Boolean)
    return parts.length ? parts.join(' · ') : emptyLabel.value
  })

  const assignmentDriver = computed(() => {
    const d = trip.value?.driver
    const name = d?.full_name || d?.name
    return nz(name) || emptyLabel.value
  })

  const assignmentPlate = computed(() => nz(trip.value?.vehicle?.license_plate) || emptyLabel.value)

  const assignmentOperator = computed(() => {
    const tp = trip.value?.transport_provider ?? trip.value?.transportProvider
    return nz(tp?.name) || emptyLabel.value
  })

  const assignmentStatus = computed(() => {
    const st = trip.value?.status
    return st ? labelTripStatus(st) : emptyLabel.value
  })

  const passengerRows = computed(() => {
    const dr = req.value
    if (!dr) return []
    if (isCargo.value) {
      const out = []
      let i = 0
      for (const r of snap.value.cargoRows ?? []) {
        if (!isCargoRowFilled(r)) continue
        i += 1
        const name = nz(r.name) || t('request_detail.trip_tab_cargo_item', { n: i })
        out.push({
          key: `c-${i}`,
          name,
          department: nz(r.dimensions) || emptyLabel.value,
          role: t('request_detail.trip_tab_role_cargo'),
          phone: nz(r.pickup_contact_phone) || nz(r.delivery_contact_phone) || emptyLabel.value,
          initials: initialsFromName(name),
        })
      }
      return out
    }
    if (isBusiness.value) {
      const out = []
      let i = 0
      for (const r of snap.value.businessRows ?? []) {
        if (!isBusinessRowFilled(r)) continue
        i += 1
        const name = nz(r.person_in_charge) || t('request_detail.trip_tab_business_leg', { n: i })
        out.push({
          key: `b-${i}`,
          name,
          department: nz(r.waypoint) || emptyLabel.value,
          role: t('request_detail.trip_tab_role_business'),
          phone: emptyLabel.value,
          initials: initialsFromName(name),
        })
      }
      return out
    }
    const list = buildDriverTripPaxList({
      dr,
      trip: trip.value,
      snap: snap.value,
      t,
      locale: locale.value,
      formatTripTimeHm24,
      isPassengerRowFilled,
      isBusinessRowFilled,
      isCargoRowFilled,
      classFromNotes: () => '',
    })
    return list.map((p, i) => ({
      key: `p-${i}`,
      name: p.name,
      department: nz(p.address) || emptyLabel.value,
      role: nz(p.subtitle) !== '—' ? p.subtitle : t('request_detail.trip_tab_role_passenger'),
      phone: p.phone || emptyLabel.value,
      initials: initialsFromName(p.name),
    }))
  })

  function fmtHm(iso) {
    if (!iso) return ''
    return formatTripTimeHm24(iso, { locale: locale.value })
  }

  function fmtDtLine(iso) {
    if (!iso) return ''
    const hm = fmtHm(iso)
    const d = fmtDateOnly(iso, locale.value)
    return hm && d ? `${hm} · ${d}` : hm || d
  }

  const executionSteps = computed(() => {
    const tr = trip.value
    const plannedDepart = req.value?.depart_at
    const plannedArrive = req.value?.arrive_by
    const actualDepart = tr?.started_at || tr?.depart_at
    const actualComplete = tr?.completed_at || tr?.arrive_by

    let pickupPlanned = ''
    let pickupActual = ''
    const checkIns = tr?.passenger_check_ins
    if (checkIns && typeof checkIns === 'object') {
      const times = Object.values(checkIns)
        .map((e) => e?.checked_in_at || e?.at)
        .filter(Boolean)
      if (times.length) {
        pickupActual = fmtDtLine(times.sort()[0])
      }
    }
    if (!pickupPlanned) {
      if (isCargo.value) {
        const cr = (snap.value.cargoRows ?? []).find(isCargoRowFilled)
        pickupPlanned = fmtDtLine(cr?.pickup_at)
      } else {
        const row = (snap.value.passengerRows ?? []).find(isPassengerRowFilled)
        pickupPlanned = fmtDtLine(row?.depart_at || plannedDepart)
      }
    }
    if (!pickupActual && pickupPlanned) pickupActual = emptyLabel.value

    const movePlanned =
      durationBetween(plannedDepart, plannedArrive, t) ||
      t('request_detail.trip_tab_move_window', {
        from: fmtHm(plannedDepart) || '—',
        to: fmtHm(plannedArrive) || '—',
      })
    const moveActual =
      actualDepart && actualComplete
        ? durationBetween(actualDepart, actualComplete, t) || fmtDtLine(actualComplete)
        : emptyLabel.value

    return [
      {
        key: 'depart',
        label: t('request_detail.trip_tab_step_depart'),
        planned: fmtDtLine(plannedDepart) || emptyLabel.value,
        actual: fmtDtLine(actualDepart) || emptyLabel.value,
        state: actualDepart ? 'done' : 'pending',
      },
      {
        key: 'pickup',
        label: t('request_detail.trip_tab_step_pickup'),
        planned: pickupPlanned || emptyLabel.value,
        actual: pickupActual || emptyLabel.value,
        state: pickupActual && pickupActual !== emptyLabel.value ? 'done' : 'pending',
      },
      {
        key: 'transit',
        label: t('request_detail.trip_tab_step_transit'),
        planned: movePlanned,
        actual: moveActual,
        state: tr?.started_at && !tr?.completed_at ? 'current' : tr?.completed_at ? 'done' : 'pending',
      },
      {
        key: 'complete',
        label: t('request_detail.trip_tab_step_complete'),
        planned: fmtDtLine(plannedArrive) || emptyLabel.value,
        actual: fmtDtLine(actualComplete) || emptyLabel.value,
        state: tr?.completed_at ? 'done' : 'pending',
      },
    ]
  })

  const purposeText = computed(() => nz(form.value.purpose))
  const purposeTargets = computed(() =>
    (Array.isArray(form.value.targets) ? form.value.targets : []).map(nz).filter(Boolean),
  )

  const basisText = computed(() => {
    const r = nz(form.value.basis_ref)
    if (r) return r
    const bf = nz(form.value.basisFileName)
    return bf ? t('request_detail.ops_basis_file', { name: bf }) : ''
  })

  const financeVehicle = computed(() => {
    const sp = moneyDisplay(req.value?.service_price)
    if (sp) return sp
    const ev = moneyDisplay(form.value.estimated_vehicle_cost)
    if (ev) return ev
    const rowTotal = costEstimate.value?.breakdown?.find((b) =>
      ['pass', 'bus', 'cargo'].includes(b.key),
    )
    return rowTotal ? moneyDisplay(rowTotal.amount) : emptyLabel.value
  })

  const financeExtras = computed(() => {
    const extras = (costEstimate.value?.breakdown ?? []).filter((b) =>
      ['porter', 'toll', 'e1', 'e2d', 'e2s', 'e2n'].includes(b.key),
    )
    const sum = extras.reduce((s, x) => s + (Number(x.amount) || 0), 0)
    return sum > 0 ? moneyDisplay(sum) : emptyLabel.value
  })

  const financeAdvance = computed(() => emptyLabel.value)

  const financeTotal = computed(() => {
    const total = costEstimate.value?.total
    if (total != null && total > 0) return moneyDisplay(total)
    return emptyLabel.value
  })

  const noteDispatcher = computed(() => nz(req.value?.notes))
  const noteDriver = computed(() => nz(trip.value?.record?.driver_notes))
  const noteExtra = computed(() => {
    const parts = [nz(form.value.free_notes), nz(form.value.cargo_extra_notes)].filter(Boolean)
    return parts.join('\n\n')
  })

  return {
    emptyLabel,
    planDateNeeded,
    planDuration,
    planVehicleType,
    planBudget,
    hasAssignment,
    assignmentVehicle,
    assignmentDriver,
    assignmentPlate,
    assignmentOperator,
    assignmentStatus,
    passengerRows,
    executionSteps,
    purposeText,
    purposeTargets,
    basisText,
    financeVehicle,
    financeExtras,
    financeAdvance,
    financeTotal,
    noteDispatcher,
    noteDriver,
    noteExtra,
    trip,
    isCargo,
  }
}
