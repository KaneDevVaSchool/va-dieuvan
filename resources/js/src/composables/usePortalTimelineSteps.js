/**
 * Luồng tiến độ hiển thị cho portal — D2D vs có phòng ban (parity với timeline dept).
 * @param {import('vue').Ref<object|null>|import('vue').ComputedRef<object|null>} reqRef
 * @param {(key: string, ...args: unknown[]) => string} t
 */
import { computed } from 'vue'

export function usePortalTimelineSteps(reqRef, t) {
  return computed(() => {
    const r = reqRef.value
    if (!r) return []

    const fmt = (iso) => {
      if (!iso) return '—'
      try {
        return new Date(iso).toLocaleString('vi-VN', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })
      } catch {
        return '—'
      }
    }

    const trip = r.trip
    const tripSt = trip?.status
    const st = r.status

    const approvedAt = trip?.created_at
    const pendingEndAt = st === 'rejected' ? r.updated_at : approvedAt
    const isAssignedOrMore =
      trip && ['assigned', 'driver_confirmed', 'in_progress', 'completed'].includes(tripSt)
    const dispatchDoneAt =
      tripSt === 'approved'
        ? null
        : isAssignedOrMore
          ? ['assigned', 'driver_confirmed'].includes(tripSt)
            ? trip.updated_at
            : trip.started_at || trip.updated_at
          : null
    const runningAt = trip?.started_at
    const completedAt = trip?.completed_at

    const deptFlow = r.trip_type !== 'door_to_door'

    if (!deptFlow) {
      const steps = [
        { key: 'created', labelKey: 'portal.timeline.created', sub: fmt(r.created_at), state: 'upcoming' },
        { key: 'pending', labelKey: 'portal.timeline.pending', sub: '', state: 'upcoming' },
        { key: 'approved', labelKey: 'portal.timeline.approved', sub: '', state: 'upcoming' },
        { key: 'dispatch', labelKey: 'portal.timeline.dispatch', sub: '', state: 'upcoming' },
        { key: 'running', labelKey: 'portal.timeline.running', sub: '', state: 'upcoming' },
        { key: 'done', labelKey: 'portal.timeline.done', sub: '', state: 'upcoming' },
      ]

      steps[1].sub =
        st === 'pending' || st === 'rejected'
          ? fmt(r.created_at)
          : pendingEndAt
            ? fmt(pendingEndAt)
            : '—'

      steps[2].sub = approvedAt ? fmt(approvedAt) : '—'
      steps[3].sub =
        tripSt === 'approved' ? '—' : dispatchDoneAt ? fmt(dispatchDoneAt) : '—'
      steps[4].sub =
        tripSt === 'in_progress' || tripSt === 'completed'
          ? fmt(runningAt)
          : ['assigned', 'driver_confirmed'].includes(tripSt)
            ? fmt(trip.updated_at)
            : '—'
      steps[5].sub = tripSt === 'completed' && completedAt ? fmt(completedAt) : '—'

      let active = 1
      if (st === 'draft') active = 0
      else if (st === 'pending' || st === 'rejected') active = 1
      else if (st === 'cancelled') active = 1
      else if (st === 'approved') {
        if (!trip) active = 2
        else if (tripSt === 'approved') active = 3
        else if (['assigned', 'driver_confirmed'].includes(tripSt)) active = 4
        else if (tripSt === 'in_progress') active = 4
        else if (tripSt === 'completed') active = 5
        else active = 2
      }

      steps[0].state = st === 'draft' ? 'current' : 'done'
      for (let i = 1; i < steps.length; i++) {
        if (i < active) steps[i].state = 'done'
        else if (i === active) {
          if (st === 'rejected' && i === 1) steps[i].state = 'rejected'
          else if (st === 'cancelled' && i === 1) steps[i].state = 'current'
          else steps[i].state = 'current'
        } else steps[i].state = 'upcoming'
      }

      if (st === 'rejected') for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
      if (st === 'draft') for (let i = 1; i < steps.length; i++) steps[i].state = 'upcoming'
      if (st === 'cancelled') for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'

      return enrichPortalTimelineSteps(
        steps.map((s) => ({ ...s, label: t(s.labelKey) })),
        r,
        t,
      )
    }

    const steps = [
      { key: 'created', labelKey: 'portal.timeline.created', sub: fmt(r.created_at), state: 'upcoming' },
      {
        key: 'price_pending',
        labelKey: 'portal.timeline.price_pending',
        sub: '',
        state: 'upcoming',
      },
      {
        key: 'dept_pending',
        labelKey: 'portal.timeline.dept_pending',
        sub: '',
        state: 'upcoming',
      },
      { key: 'approved', labelKey: 'portal.timeline.approved', sub: '', state: 'upcoming' },
      { key: 'dispatch', labelKey: 'portal.timeline.dispatch', sub: '', state: 'upcoming' },
      { key: 'running', labelKey: 'portal.timeline.running', sub: '', state: 'upcoming' },
      { key: 'done', labelKey: 'portal.timeline.done', sub: '', state: 'upcoming' },
    ]

    steps[1].sub = st === 'pending' ? fmt(r.created_at) : '—'
    steps[2].sub =
      r.price_filled_at && (st === 'price_filled' || st === 'approved' || st === 'rejected')
        ? fmt(r.price_filled_at)
        : st === 'price_filled'
          ? fmt(r.updated_at)
          : '—'

    steps[3].sub = approvedAt ? fmt(approvedAt) : '—'
    steps[4].sub = tripSt === 'approved' ? '—' : dispatchDoneAt ? fmt(dispatchDoneAt) : '—'
    steps[5].sub =
      tripSt === 'in_progress' || tripSt === 'completed'
        ? fmt(runningAt)
        : ['assigned', 'driver_confirmed'].includes(tripSt)
          ? fmt(trip.updated_at)
          : '—'
    steps[6].sub = tripSt === 'completed' && completedAt ? fmt(completedAt) : '—'

    let active = 1
    if (st === 'draft') active = 0
    else if (st === 'pending') active = 1
    else if (st === 'price_filled') active = 2
    else if (st === 'rejected') active = 2
    else if (st === 'cancelled') active = 2
    else if (st === 'approved') {
      if (!trip) active = 3
      else if (tripSt === 'approved') active = 4
      else if (['assigned', 'driver_confirmed'].includes(tripSt)) active = 5
      else if (tripSt === 'in_progress') active = 5
      else if (tripSt === 'completed') active = 6
      else active = 3
    }

    steps[0].state = st === 'draft' ? 'current' : 'done'
    for (let i = 1; i < steps.length; i++) {
      if (i < active) steps[i].state = 'done'
      else if (i === active) {
        if (st === 'rejected' && i === 2) steps[i].state = 'rejected'
        else if (st === 'cancelled' && i === 2) steps[i].state = 'current'
        else steps[i].state = 'current'
      } else steps[i].state = 'upcoming'
    }

    if (st === 'rejected') for (let i = 3; i < steps.length; i++) steps[i].state = 'upcoming'
    if (st === 'draft') for (let i = 1; i < steps.length; i++) steps[i].state = 'upcoming'
    if (st === 'cancelled') for (let i = 3; i < steps.length; i++) steps[i].state = 'upcoming'

    return enrichPortalTimelineSteps(
      steps.map((s) => ({ ...s, label: t(s.labelKey) })),
      r,
      t,
    )
  })
}

function personName(user) {
  if (!user) return ''
  const n = user.name || user.full_name
  return typeof n === 'string' ? n.trim() : ''
}

function enrichPortalTimelineSteps(steps, r, t) {
  const requester = personName(r.requester) || (r.requester_name || '').trim()
  const approver = personName(r.approver)
  const priceFiller = personName(r.price_filled_by_user) || personName(r.price_filler)
  const deptHead = personName(r.assigned_dept_head)
  const dispatcher = personName(r.trip?.dispatcher)
  const driver =
    personName(r.trip?.driver) ||
    (r.trip?.external_driver_ref ? String(r.trip.external_driver_ref).trim() : '')

  const actorByKey = {
    created: requester || t('portal.timeline_actor.requester'),
    pending: t('portal.timeline_actor.dispatch_team'),
    price_pending: t('portal.timeline_actor.dispatch_team'),
    dept_pending: deptHead || t('portal.timeline_actor.dept_head'),
    approved: approver || t('portal.timeline_actor.approver'),
    dispatch: dispatcher || t('portal.timeline_actor.dispatch_team'),
    running: driver || t('portal.timeline_actor.driver'),
    done: '',
  }

  return steps.map((s) => ({
    ...s,
    actor: actorByKey[s.key] || '',
  }))
}
