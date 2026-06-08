/**
 * Tiến trình yêu cầu điều xe (staff) — cùng logic domain với usePortalTimelineSteps.
 * @param {import('vue').Ref<object|null>|import('vue').ComputedRef<object|null>} reqRef
 * @param {(key: string, ...args: unknown[]) => string} t
 * @param {import('vue').Ref<string>|import('vue').ComputedRef<string>} localeRef
 */
import { computed } from 'vue'

export function useRequestTimelineSteps(reqRef, t, localeRef) {
  function localeTag() {
    return localeRef.value === 'en' ? 'en-GB' : 'vi-VN'
  }

  function fmtStepDetail(v) {
    if (!v) return '—'
    try {
      const hour12 = localeRef.value === 'en'
      return new Date(v).toLocaleString(localeTag(), {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12,
      })
    } catch {
      return '—'
    }
  }

  return computed(() => {
    const r = reqRef.value
    if (!r) return []

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
        { key: 'created', label: t('request_detail.step_label_created'), sub: fmtStepDetail(r.created_at), state: 'upcoming' },
        { key: 'pending', label: t('request_detail.step_label_pending'), sub: '', state: 'upcoming' },
        { key: 'approved', label: t('request_detail.step_label_approved_short'), sub: '', state: 'upcoming' },
        { key: 'dispatch', label: t('request_detail.step_label_dispatch'), sub: '', state: 'upcoming' },
        { key: 'running', label: t('request_detail.step_label_running'), sub: '', state: 'upcoming' },
        { key: 'done', label: t('request_detail.step_label_done'), sub: '', state: 'upcoming' },
      ]

      steps[1].sub =
        st === 'pending' || st === 'rejected'
          ? fmtStepDetail(r.created_at)
          : pendingEndAt
            ? fmtStepDetail(pendingEndAt)
            : '—'

      steps[2].sub = approvedAt ? fmtStepDetail(approvedAt) : '—'
      steps[3].sub =
        tripSt === 'approved' ? '—' : dispatchDoneAt ? fmtStepDetail(dispatchDoneAt) : '—'
      steps[4].sub =
        tripSt === 'in_progress' || tripSt === 'completed'
          ? fmtStepDetail(runningAt)
          : ['assigned', 'driver_confirmed'].includes(tripSt)
            ? fmtStepDetail(trip.updated_at)
            : '—'
      steps[5].sub = tripSt === 'completed' && completedAt ? fmtStepDetail(completedAt) : '—'

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

      return steps
    }

    const steps = [
      { key: 'created', label: t('request_detail.step_label_created'), sub: fmtStepDetail(r.created_at), state: 'upcoming' },
      { key: 'price_pending', label: t('request_detail.step_price_pending'), sub: '', state: 'upcoming' },
      { key: 'dept_pending', label: t('request_detail.step_dept_pending'), sub: '', state: 'upcoming' },
      { key: 'approved', label: t('request_detail.step_label_approved_short'), sub: '', state: 'upcoming' },
      { key: 'dispatch', label: t('request_detail.step_label_dispatch'), sub: '', state: 'upcoming' },
      { key: 'running', label: t('request_detail.step_label_running'), sub: '', state: 'upcoming' },
      { key: 'done', label: t('request_detail.step_label_done'), sub: '', state: 'upcoming' },
    ]

    steps[1].sub = st === 'pending' ? fmtStepDetail(r.created_at) : '—'
    steps[2].sub =
      r.price_filled_at && (st === 'price_filled' || st === 'approved' || st === 'rejected')
        ? fmtStepDetail(r.price_filled_at)
        : st === 'price_filled'
          ? fmtStepDetail(r.updated_at)
          : '—'
    steps[3].sub = approvedAt ? fmtStepDetail(approvedAt) : '—'
    steps[4].sub =
      tripSt === 'approved' ? '—' : dispatchDoneAt ? fmtStepDetail(dispatchDoneAt) : '—'
    steps[5].sub =
      tripSt === 'in_progress' || tripSt === 'completed'
        ? fmtStepDetail(runningAt)
        : ['assigned', 'driver_confirmed'].includes(tripSt)
          ? fmtStepDetail(trip.updated_at)
          : '—'
    steps[6].sub = tripSt === 'completed' && completedAt ? fmtStepDetail(completedAt) : '—'

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

    return steps
  })
}
