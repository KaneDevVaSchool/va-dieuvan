const COORDINATED_TRIP_STATUSES = new Set([
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
])

/**
 * @param {object|null|undefined} req
 * @returns {'not_updated'|'updated'|'submitted'|'coordinated'}
 */
export function extracurricularStudentCountTrackingKey(req) {
  if (!req?.dispatch_request_template_id) {
    return 'not_updated'
  }
  const tripSt = req?.trip?.status
  if (req?.status === 'approved' && tripSt && COORDINATED_TRIP_STATUSES.has(String(tripSt))) {
    return 'coordinated'
  }
  if (req?.student_count_submitted_at) {
    return 'submitted'
  }
  if (req?.student_count_actual != null && req?.student_count_actual !== '') {
    return 'updated'
  }
  return 'not_updated'
}
