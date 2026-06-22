import { computed } from 'vue'
import { labelTripStatus } from '../util/labels'
import { portalRequestPassengerCount } from '../util/portalRequestFormat.js'

/**
 * Meta for portal request detail — waiting party, SLA, next action copy.
 * @param {import('vue').Ref<object|null>} reqRef
 * @param {(key: string, ...args: unknown[]) => string} t
 */
export function usePortalRequestActionCenter(reqRef, t) {
  const hintKey = computed(() => {
    const r = reqRef.value
    if (!r) return 'portal.next_step_hint.generic'
    const st = r.status
    const tripSt = r.trip?.status
    if (st === 'pending') return 'portal.next_step_hint.pending'
    if (st === 'price_filled') return 'portal.next_step_hint.price_filled'
    if (st === 'rejected') return 'portal.next_step_hint.rejected'
    if (st === 'cancelled') return 'portal.next_step_hint.cancelled'
    if (st === 'draft') return 'portal.next_step_hint.draft'
    if (st === 'approved') {
      if (!tripSt || tripSt === 'approved') return 'portal.next_step_hint.approved_dispatch'
      if (tripSt === 'assigned' || tripSt === 'driver_confirmed') return 'portal.next_step_hint.dispatched'
      if (tripSt === 'in_progress') return 'portal.next_step_hint.running'
      if (tripSt === 'completed') return 'portal.next_step_hint.completed'
      return 'portal.next_step_hint.approved_dispatch'
    }
    return 'portal.next_step_hint.generic'
  })

  const nextActionText = computed(() => t(hintKey.value))

  const waitingParty = computed(() => resolveWaitingParty(reqRef.value, t))

  /** @deprecated Prefer waitingParty — kept for callers that only need a single line */
  const waitingOnLabel = computed(() => {
    const p = waitingParty.value
    if (p.name) return p.name
    return p.roleLabel
  })

  const waitingRoleLabel = computed(() => waitingParty.value.roleLabel)
  const waitingPersonName = computed(() => waitingParty.value.name)
  const waitingPersonDetail = computed(() => waitingParty.value.detail)

  const hoursUntilDepart = computed(() => {
    const iso = reqRef.value?.depart_at
    if (!iso) return null
    try {
      const d = new Date(iso)
      if (Number.isNaN(d.getTime())) return null
      return (d.getTime() - Date.now()) / 3600000
    } catch {
      return null
    }
  })

  const slaLabel = computed(() => {
    const h = hoursUntilDepart.value
    if (h == null) return ''
    if (h < 0) return t('portal.action_center.sla_departed')
    if (h < 1) return t('portal.action_center.sla_under_hour')
    const rounded = Math.round(h)
    return t('portal.action_center.sla_hours', { hours: rounded })
  })

  const dispatchStrip = computed(() => {
    const r = reqRef.value
    const trip = r?.trip
    if (!r || r.status !== 'approved' || !trip) return null

    const driver = driverDisplayName(r)
    const vehicle = (trip.vehicle?.type || trip.external_vehicle_ref || '').trim()
    const plate = (trip.vehicle?.license_plate || '').trim()
    const passengers = portalRequestPassengerCount(r)
    const tripSt = trip.status || ''

    if (!driver && !vehicle && !plate && passengers <= 0 && !tripSt) return null

    return {
      driver,
      driverPhone: (trip.driver?.phone || '').trim(),
      vehicle,
      plate,
      passengers: passengers > 0 ? passengers : null,
      tripStatus: tripSt,
      tripStatusLabel: tripSt ? labelTripStatus(tripSt) : '',
    }
  })

  const priorityLabel = computed(() => {
    const r = reqRef.value
    if (!r) return ''
    if (r.is_urgent) return t('portal.badge_urgent')
    return t('portal.action_center.priority_normal')
  })

  const priorityTone = computed(() => (reqRef.value?.is_urgent ? 'urgent' : 'normal'))

  return {
    nextActionText,
    waitingOnLabel,
    waitingRoleLabel,
    waitingPersonName,
    waitingPersonDetail,
    slaLabel,
    priorityLabel,
    priorityTone,
    hoursUntilDepart,
    dispatchStrip,
  }
}

/**
 * @param {object|null|undefined} r
 * @param {(key: string, ...args: unknown[]) => string} t
 */
function resolveWaitingParty(r, t) {
  const empty = { roleLabel: '', name: '', detail: '' }
  if (!r) return empty

  const st = r.status
  if (st === 'pending') {
    return {
      roleLabel: t('portal.action_center.waiting_role_dispatch'),
      name: '',
      detail: t('portal.action_center.waiting_detail_dispatch_queue'),
    }
  }
  if (st === 'price_filled') {
    const head = r.assigned_dept_head?.name
    return {
      roleLabel: t('portal.action_center.waiting_role_dept_head'),
      name: head ? String(head).trim() : '',
      detail: head ? t('portal.action_center.waiting_detail_approval') : t('portal.action_center.waiting_dept_head'),
    }
  }
  if (st === 'rejected' || st === 'cancelled') {
    return {
      roleLabel: t('portal.action_center.waiting_role_none'),
      name: '',
      detail: t('portal.action_center.waiting_none'),
    }
  }
  if (st === 'draft') {
    return {
      roleLabel: t('portal.action_center.waiting_role_requester'),
      name: (r.requester?.name || r.requester_name || '').trim(),
      detail: t('portal.action_center.waiting_requester'),
    }
  }
  if (st === 'approved') {
    const tripSt = r.trip?.status
    if (!tripSt || tripSt === 'approved') {
      const dispatcher = (r.trip?.dispatcher?.name || '').trim()
      return {
        roleLabel: t('portal.action_center.waiting_role_dispatch'),
        name: dispatcher,
        detail: dispatcher
          ? t('portal.action_center.waiting_detail_dispatcher')
          : t('portal.action_center.waiting_detail_dispatch_assign'),
      }
    }
    if (['assigned', 'driver_confirmed', 'in_progress'].includes(tripSt)) {
      const driver = driverDisplayName(r)
      const phone = (r.trip?.driver?.phone || '').trim()
      return {
        roleLabel: t('portal.action_center.waiting_role_driver'),
        name: driver,
        detail: phone,
      }
    }
    if (tripSt === 'completed') {
      return {
        roleLabel: t('portal.action_center.waiting_role_none'),
        name: '',
        detail: t('portal.action_center.waiting_none'),
      }
    }
  }

  return {
    roleLabel: t('portal.action_center.waiting_role_dispatch'),
    name: '',
    detail: t('portal.action_center.waiting_dispatch'),
  }
}

function driverDisplayName(r) {
  const trip = r?.trip
  if (!trip) return ''
  const d = trip.driver
  if (d?.full_name) return String(d.full_name).trim()
  if (trip.external_driver_ref) return String(trip.external_driver_ref).trim()
  return ''
}
