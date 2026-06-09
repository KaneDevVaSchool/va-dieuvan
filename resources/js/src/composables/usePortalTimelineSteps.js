/**
 * Luồng tiến độ hiển thị (portal + staff) — D2D vs có phòng ban, dạng gọn (gộp bước liên quan).
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

    const steps = deptFlow
      ? buildDeptCompactSteps(r, fmt, { st, trip, tripSt, approvedAt, dispatchDoneAt, runningAt, completedAt })
      : buildD2dCompactSteps(r, fmt, {
          st,
          trip,
          tripSt,
          pendingEndAt,
          approvedAt,
          dispatchDoneAt,
          runningAt,
          completedAt,
        })

    return enrichPortalTimelineSteps(
      steps.map((s) => ({ ...s, label: t(s.labelKey) })),
      r,
      t,
      { deptFlow, st },
    )
  })
}

/** @param {object} r @param {Function} fmt */
function buildD2dCompactSteps(r, fmt, ctx) {
  const { st, trip, tripSt, pendingEndAt, approvedAt, dispatchDoneAt, runningAt, completedAt } = ctx

  const steps = [
    { key: 'created', labelKey: 'portal.timeline.created', sub: fmt(r.created_at), state: 'upcoming' },
    { key: 'pending', labelKey: 'portal.timeline.pending', sub: '', state: 'upcoming' },
    { key: 'trip_ops', labelKey: 'portal.timeline.trip_ops', sub: '', state: 'upcoming' },
    { key: 'done', labelKey: 'portal.timeline.done', sub: '', state: 'upcoming' },
  ]

  steps[1].sub =
    st === 'pending' || st === 'rejected'
      ? fmt(r.created_at)
      : pendingEndAt
        ? fmt(pendingEndAt)
        : '—'

  steps[2].sub =
    tripSt === 'approved'
      ? '—'
      : dispatchDoneAt
        ? fmt(dispatchDoneAt)
        : tripSt === 'in_progress' || tripSt === 'completed'
          ? fmt(runningAt)
          : ['assigned', 'driver_confirmed'].includes(tripSt)
            ? fmt(trip.updated_at)
            : approvedAt
              ? fmt(approvedAt)
              : '—'

  steps[3].sub = tripSt === 'completed' && completedAt ? fmt(completedAt) : '—'

  let active = 1
  if (st === 'draft') active = 0
  else if (st === 'pending' || st === 'rejected' || st === 'cancelled') active = 1
  else if (st === 'approved') {
    if (!trip || tripSt === 'approved') active = 2
    else if (['assigned', 'driver_confirmed', 'in_progress'].includes(tripSt)) active = 2
    else if (tripSt === 'completed') active = 4
    else active = 2
  }

  applyTimelineStates(steps, active, { st, rejectIndex: 1, cancelIndex: 1, draftIndex: 0 })
  if (tripSt === 'completed') markAllDone(steps)

  return steps
}

/** @param {object} r @param {Function} fmt */
function buildDeptCompactSteps(r, fmt, ctx) {
  const { st, trip, tripSt, approvedAt, dispatchDoneAt, runningAt, completedAt } = ctx

  const steps = [
    { key: 'created', labelKey: 'portal.timeline.created', sub: fmt(r.created_at), state: 'upcoming' },
    {
      key: 'internal_review',
      labelKey: 'portal.timeline.internal_review',
      sub: '',
      state: 'upcoming',
    },
    { key: 'approved', labelKey: 'portal.timeline.approved', sub: '', state: 'upcoming' },
    { key: 'trip_ops', labelKey: 'portal.timeline.trip_ops', sub: '', state: 'upcoming' },
    { key: 'done', labelKey: 'portal.timeline.done', sub: '', state: 'upcoming' },
  ]

  if (st === 'pending') {
    steps[1].sub = fmt(r.created_at)
  } else if (r.price_filled_at && ['price_filled', 'approved', 'rejected'].includes(st)) {
    steps[1].sub = fmt(r.price_filled_at)
  } else if (st === 'price_filled') {
    steps[1].sub = fmt(r.updated_at)
  } else {
    steps[1].sub = '—'
  }

  steps[2].sub = approvedAt ? fmt(approvedAt) : '—'
  steps[3].sub =
    tripSt === 'approved'
      ? '—'
      : dispatchDoneAt
        ? fmt(dispatchDoneAt)
        : tripSt === 'in_progress' || tripSt === 'completed'
          ? fmt(runningAt)
          : ['assigned', 'driver_confirmed'].includes(tripSt)
            ? fmt(trip.updated_at)
            : '—'
  steps[4].sub = tripSt === 'completed' && completedAt ? fmt(completedAt) : '—'

  let active = 1
  if (st === 'draft') active = 0
  else if (st === 'pending' || st === 'price_filled') active = 1
  else if (st === 'rejected' || st === 'cancelled') active = 1
  else if (st === 'approved') {
    if (!trip) active = 2
    else if (tripSt === 'approved') active = 3
    else if (['assigned', 'driver_confirmed', 'in_progress'].includes(tripSt)) active = 3
    else if (tripSt === 'completed') active = 5
    else active = 2
  }

  applyTimelineStates(steps, active, { st, rejectIndex: 1, cancelIndex: 1, draftIndex: 0 })
  if (st === 'rejected') {
    for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
  }
  if (st === 'cancelled') {
    for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
  }
  if (tripSt === 'completed') markAllDone(steps)

  return steps
}

function applyTimelineStates(steps, active, { st, rejectIndex, cancelIndex, draftIndex }) {
  steps[draftIndex].state = st === 'draft' ? 'current' : 'done'

  for (let i = 1; i < steps.length; i++) {
    if (active >= steps.length) {
      steps[i].state = 'done'
      continue
    }
    if (i < active) steps[i].state = 'done'
    else if (i === active) {
      if (st === 'rejected' && i === rejectIndex) steps[i].state = 'rejected'
      else if (st === 'cancelled' && i === cancelIndex) steps[i].state = 'current'
      else steps[i].state = 'current'
    } else steps[i].state = 'upcoming'
  }

  if (st === 'draft') {
    for (let i = 1; i < steps.length; i++) steps[i].state = 'upcoming'
  }
}

function markAllDone(steps) {
  steps.forEach((s) => {
    if (s.state !== 'rejected') s.state = 'done'
  })
}

function personName(user) {
  if (!user) return ''
  const n = user.name || user.full_name
  return typeof n === 'string' ? n.trim() : ''
}

function enrichPortalTimelineSteps(steps, r, t, { deptFlow, st }) {
  const requester = personName(r.requester) || (r.requester_name || '').trim()
  const approver = personName(r.approver)
  const deptHead = personName(r.assigned_dept_head)
  const dispatcher = personName(r.trip?.dispatcher)
  const driver =
    personName(r.trip?.driver) ||
    (r.trip?.external_driver_ref ? String(r.trip.external_driver_ref).trim() : '')

  const actorByKey = {
    created: requester || t('portal.timeline_actor.requester'),
    pending: t('portal.timeline_actor.dispatch_team'),
    internal_review:
      st === 'pending'
        ? t('portal.timeline_actor.dispatch_team')
        : deptHead || t('portal.timeline_actor.dept_head'),
    approved: approver || t('portal.timeline_actor.approver'),
    trip_ops:
      r.trip?.status === 'in_progress'
        ? driver || t('portal.timeline_actor.driver')
        : dispatcher || t('portal.timeline_actor.dispatch_team'),
    done: '',
  }

  if (!deptFlow && st === 'pending') {
    actorByKey.pending = t('portal.timeline_actor.dispatch_team')
  }

  return steps.map((s) => ({
    ...s,
    actor: actorByKey[s.key] || '',
  }))
}
