import { computed } from 'vue'

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

  const waitingOnLabel = computed(() => {
    const r = reqRef.value
    if (!r) return ''
    const st = r.status
    if (st === 'pending') return t('portal.action_center.waiting_dispatch')
    if (st === 'price_filled') {
      const head = r.assigned_dept_head?.name
      return head ? t('portal.action_center.waiting_person', { name: head }) : t('portal.action_center.waiting_dept_head')
    }
    if (st === 'rejected' || st === 'cancelled') return t('portal.action_center.waiting_none')
    if (st === 'draft') return t('portal.action_center.waiting_requester')
    if (st === 'approved') {
      const tripSt = r.trip?.status
      if (!tripSt || tripSt === 'approved') return t('portal.action_center.waiting_dispatch')
      if (['assigned', 'driver_confirmed', 'in_progress'].includes(tripSt)) {
        const driver = driverDisplayName(r)
        return driver ? t('portal.action_center.waiting_person', { name: driver }) : t('portal.action_center.waiting_driver')
      }
      if (tripSt === 'completed') return t('portal.action_center.waiting_none')
    }
    return t('portal.action_center.waiting_dispatch')
  })

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

  const dueLabel = computed(() => {
    const iso = reqRef.value?.depart_at
    if (!iso) return ''
    try {
      return new Date(iso).toLocaleString('vi-VN', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      })
    } catch {
      return ''
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
    slaLabel,
    dueLabel,
    priorityLabel,
    priorityTone,
    hoursUntilDepart,
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
