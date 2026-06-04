import { http } from './http'

/**
 * Client API module P2P — Học sinh chính sách (docs/p2p.md).
 * Mọi hàm trả về `data.data` đã bóc lớp envelope { message, data }.
 */

// ── Lookups (dùng cho filter & modal) ────────────────────────────────────────

/** GET /policy-routes — chỉ tuyến loại P2P (type=policy), không gồm D2D. */
export async function listPolicyRoutes() {
  const { data } = await http.get('/policy-routes')
  return data.data
}

/** POST /policy-routes { name } — tạo tuyến P2P. */
export async function createPolicyRoute(payload) {
  const { data } = await http.post('/policy-routes', payload)
  return data.data
}

/** GET /drivers — danh sách tài xế (gán chuyến). */
export async function listDrivers(params = {}) {
  const { data } = await http.get('/drivers', { params })
  return data.data
}

/** GET /vehicles — danh sách xe (gán chuyến). */
export async function listVehicles(params = {}) {
  const { data } = await http.get('/vehicles', { params })
  return data.data
}

/** GET /policy-students/search?q= — tìm HS để tạo policy. */
export async function searchPolicyStudents(q = '') {
  const { data } = await http.get('/policy-students/search', { params: { q } })
  return data.data
}

// ── Policy Trips (dashboard điều vận §8) ──────────────────────────────────────

/** GET /policy-trips?date=&time_slot=&route_id=&status= */
export async function listPolicyTrips(params = {}) {
  const { data } = await http.get('/policy-trips', { params })
  return data.data
}

/** GET /policy-trips/:id/students → { trip, items }. */
export async function getPolicyTripStudents(id) {
  const { data } = await http.get(`/policy-trips/${id}/students`)
  return data.data
}

/** POST /policy-trips/generate { date } — sinh chuyến theo lịch (idempotent). */
export async function generatePolicyTrips(date) {
  const { data } = await http.post('/policy-trips/generate', { date })
  return data.data
}

/** PATCH /policy-trips/:id/assign-driver { driver_id, vehicle_id }. */
export async function assignDriverToPolicyTrip(id, payload) {
  const { data } = await http.patch(`/policy-trips/${id}/assign-driver`, payload)
  return data.data
}

/** PATCH /policy-trips/:id/cancel { reason }. */
export async function cancelPolicyTrip(id, reason) {
  const { data } = await http.patch(`/policy-trips/${id}/cancel`, { reason })
  return data.data
}

/**
 * Điều vận đánh dấu vắng trước (§7.1 Kênh 1).
 * PATCH /driver/policy-trip-students/:entryId/absent { absence_reason }.
 */
export async function markPolicyTripStudentAbsent(entryId, absenceReason = 'absent_reported') {
  const { data } = await http.patch(`/driver/policy-trip-students/${entryId}/absent`, {
    absence_reason: absenceReason,
  })
  return data.data
}

// ── Student Policies (master list §8.3) ──────────────────────────────────────

/** GET /student-policies?status=&time_slot=&semester=&route_id= */
export async function listStudentPolicies(params = {}) {
  const { data } = await http.get('/student-policies', { params })
  return data.data
}

/** POST /student-policies */
export async function createStudentPolicy(payload) {
  const { data } = await http.post('/student-policies', payload)
  return data.data
}

/** PATCH /student-policies/:id */
export async function updateStudentPolicy(id, payload) {
  const { data } = await http.patch(`/student-policies/${id}`, payload)
  return data.data
}

/** DELETE /student-policies/:id (soft delete) → { id, affected_trips }. */
export async function deleteStudentPolicy(id) {
  const { data } = await http.delete(`/student-policies/${id}`)
  return data.data
}

/** GET /student-policies/:id/impact → { affected_trips } (cảnh báo trước khi ngưng). */
export async function getStudentPolicyImpact(id) {
  const { data } = await http.get(`/student-policies/${id}/impact`)
  return data.data
}

// ── School Calendars (§8 / §3.4) ─────────────────────────────────────────────

/** GET /school-calendars?year=&month= */
export async function listSchoolCalendars(params = {}) {
  const { data } = await http.get('/school-calendars', { params })
  return data.data
}

/** PATCH /school-calendars/:date */
export async function updateSchoolCalendarDay(date, payload) {
  const { data } = await http.patch(`/school-calendars/${date}`, payload)
  return data.data
}

/** POST /school-calendars/bulk */
export async function bulkImportSchoolCalendar(payload) {
  const { data } = await http.post('/school-calendars/bulk', payload)
  return data.data
}

/** POST /school-calendars/generate-month */
export async function generateSchoolCalendarMonth(payload) {
  const { data } = await http.post('/school-calendars/generate-month', payload)
  return data.data
}

// ── Reports (§8.4) ───────────────────────────────────────────────────────────

/** GET /policy-trips/absence-report?week_start=&time_slot=&route_id=&class= */
export async function getAbsenceReport(params = {}) {
  const { data } = await http.get('/policy-trips/absence-report', { params })
  return data.data
}
