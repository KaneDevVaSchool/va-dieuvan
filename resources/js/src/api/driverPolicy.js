import { http } from './http'

/**
 * Client API luồng tài xế cho chuyến policy (§5, §10.2).
 * Các PATCH/POST khi mất mạng được http interceptor đẩy vào outbox và phát lại
 * khi online (server ghi timestamp lúc xử lý — server-wins, E9).
 */

/** GET /driver/policy-trips?date=today → { date, items }. */
export async function listDriverPolicyTrips(date = 'today') {
  const { data } = await http.get('/driver/policy-trips', { params: { date } })
  return data.data
}

/** GET /driver/policy-trips/:id/students → { trip, items }. */
export async function getDriverPolicyTripStudents(id) {
  const { data } = await http.get(`/driver/policy-trips/${id}/students`)
  return data.data
}

/** POST /driver/policy-trips/:id/start → trip. */
export async function startDriverPolicyTrip(id) {
  const { data } = await http.post(`/driver/policy-trips/${id}/start`)
  return data.data
}

/**
 * POST /driver/policy-trips/:id/complete { confirm } → trip.
 * 422 code=unhandled_students (block) hoặc boarded_not_alighted (cần confirm=true).
 */
export async function completeDriverPolicyTrip(id, confirm = false) {
  const { data } = await http.post(`/driver/policy-trips/${id}/complete`, { confirm })
  return data.data
}

/** PATCH /driver/policy-trip-students/:id/board → student row. */
export async function boardPolicyTripStudent(entryId) {
  const { data } = await http.patch(`/driver/policy-trip-students/${entryId}/board`)
  return data.data
}

/** PATCH /driver/policy-trip-students/:id/alight → student row. */
export async function alightPolicyTripStudent(entryId) {
  const { data } = await http.patch(`/driver/policy-trip-students/${entryId}/alight`)
  return data.data
}

/** PATCH /driver/policy-trip-students/:id/absent { absence_reason } → student row. */
export async function absentPolicyTripStudent(entryId, absenceReason) {
  const { data } = await http.patch(`/driver/policy-trip-students/${entryId}/absent`, {
    absence_reason: absenceReason,
  })
  return data.data
}
