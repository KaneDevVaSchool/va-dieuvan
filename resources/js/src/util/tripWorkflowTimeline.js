const STATUS_TO_TIMELINE_KEY = {
  pending: 'created',
  approved: 'approved',
  assigned: 'assigned',
  driver_confirmed: 'assigned',
  in_progress: 'running',
  completed: 'completed',
  cancelled: 'created',
  incident: 'created',
}

export function tripStatusToTimelineKey(status) {
  return STATUS_TO_TIMELINE_KEY[String(status ?? '')] ?? 'created'
}

/**
 * @param {object|null|undefined} trip
 * @param {{ scheduleKey?: string|null, assignedActorName?: string }} [opts]
 * @returns {Array<{ status: string, created_at: string, actor_name: string }>}
 */
export function buildTripActivityTimelineLogs(trip, opts = {}) {
  const scheduleKey = opts.scheduleKey ?? null
  const assignedActorName = String(opts.assignedActorName ?? '').trim()
  const logs = []

  if (trip?.created_at) {
    logs.push({
      status: 'created',
      created_at: trip.created_at,
      actor_name: '',
    })
  }

  const leg =
    scheduleKey != null
      ? (trip?.schedule_legs ?? []).find((l) => l?.key === scheduleKey)
      : null

  for (const e of trip?.events ?? []) {
    if (e?.type !== 'status_change') continue
    const data = e.data ?? {}
    const eventScheduleKey = data.schedule_key ?? null

    if (scheduleKey != null) {
      if (eventScheduleKey === scheduleKey && data.leg_status) {
        logs.push({
          status: tripStatusToTimelineKey(data.leg_status),
          created_at: e.created_at,
          actor_name: e.creator?.name ?? '',
        })
        continue
      }
      if (eventScheduleKey != null) continue
    }

    if (data.to) {
      logs.push({
        status: tripStatusToTimelineKey(data.to),
        created_at: e.created_at,
        actor_name: e.creator?.name ?? '',
      })
    }
  }

  if (scheduleKey && leg) {
    const assign = leg.assignment
    const hasAssignment =
      leg.assigned === true ||
      assign?.driver_id != null ||
      assign?.vehicle_id != null ||
      String(assign?.external_driver_ref ?? '').trim() !== ''

    if (hasAssignment) {
      let assignedLog = logs.find((l) => l.status === 'assigned')
      if (!assignedLog) {
        assignedLog = {
          status: 'assigned',
          created_at: '',
          actor_name: assignedActorName,
        }
        logs.push(assignedLog)
      } else if (assignedActorName && !assignedLog.actor_name) {
        assignedLog.actor_name = assignedActorName
      }
    }

    if (leg.started_at && !logs.some((l) => l.status === 'running')) {
      logs.push({
        status: 'running',
        created_at: leg.started_at,
        actor_name: assignedActorName,
      })
    }
    if (leg.completed_at && !logs.some((l) => l.status === 'completed')) {
      logs.push({
        status: 'completed',
        created_at: leg.completed_at,
        actor_name: assignedActorName,
      })
    }
  }

  return logs
}
