import { http } from './http'

// ── Policy Trips ─────────────────────────────────────────────────────────────

/** GET /api/policy-trips */
export async function listPolicyTrips(params = {}) {
  const { data } = await http.get('/policy-trips', { params })
  return data.data
}

/** GET /api/policy-trips/:id */
export async function getPolicyTrip(id) {
  const { data } = await http.get(`/policy-trips/${id}`)
  return data.data
}

/** GET /api/policy-trips/:id/students */
export async function listPolicyTripStudents(id) {
  const { data } = await http.get(`/policy-trips/${id}/students`)
  return data.data
}

/** PATCH /api/policy-trips/:id/assign-driver */
export async function assignDriverToPolicyTrip(id, payload) {
  const { data } = await http.patch(`/policy-trips/${id}/assign-driver`, payload)
  return data.data
}

/** PATCH /api/policy-trips/:id/cancel */
export async function cancelPolicyTrip(id, reason) {
  const { data } = await http.patch(`/policy-trips/${id}/cancel`, { reason })
  return data.data
}

/** POST /api/policy-trips/generate — sinh chuyến theo lịch + HS policy (ngày học) */
export async function generatePolicyTripsForDate(date) {
  const { data } = await http.post('/policy-trips/generate', { date })
  return data.data
}

/** GET /api/policy-routes — tuyến + số HS policy */
export async function listPolicyRoutes() {
  const { data } = await http.get('/policy-routes')
  return data.data
}

// ── Student Policies ──────────────────────────────────────────────────────────

/** GET /api/student-policies */
export async function listStudentPolicies(params = {}) {
  const { data } = await http.get('/student-policies', { params })
  return data.data
}

/** POST /api/student-policies */
export async function createStudentPolicy(payload) {
  const { data } = await http.post('/student-policies', payload)
  return data.data
}

/** PATCH /api/student-policies/:id */
export async function updateStudentPolicy(id, payload) {
  const { data } = await http.patch(`/student-policies/${id}`, payload)
  return data.data
}

/** DELETE /api/student-policies/:id (soft delete) */
export async function deleteStudentPolicy(id) {
  const { data } = await http.delete(`/student-policies/${id}`)
  return data.data
}

// ── School Calendars ──────────────────────────────────────────────────────────

/** GET /api/school-calendars */
export async function listSchoolCalendars(params = {}) {
  const { data } = await http.get('/school-calendars', { params })
  return data.data
}

/** PATCH /api/school-calendars/:date */
export async function updateSchoolCalendarDay(date, payload) {
  const { data } = await http.patch(`/school-calendars/${date}`, payload)
  return data.data
}

/** POST /api/school-calendars/bulk */
export async function bulkImportSchoolCalendar(payload) {
  const { data } = await http.post('/school-calendars/bulk', payload)
  return data.data
}

/** POST /api/school-calendars/generate-month */
export async function generateSchoolCalendarMonth(payload) {
  const { data } = await http.post('/school-calendars/generate-month', payload)
  return data.data
}

/** GET /api/policy-students/search?q= */
export async function searchPolicyStudents(q = '') {
  const { data } = await http.get('/policy-students/search', { params: { q } })
  return data.data
}

// ── Dispatcher absence marking (§7.1 Kênh 1) ─────────────────────────────────

/**
 * Dispatcher đánh dấu vắng trước cho học sinh trong chuyến.
 * PATCH /api/driver/policy-trip-students/:id/absent
 * body: { absence_reason: 'absent_reported' | 'late_cancellation' }
 */
export async function markPolicyTripStudentAbsent(studentEntryId, absenceReason = 'absent_reported') {
  const { data } = await http.patch(`/driver/policy-trip-students/${studentEntryId}/absent`, {
    absence_reason: absenceReason,
  })
  return data.data
}

// ── Reports ───────────────────────────────────────────────────────────────────

/** GET /api/policy-trips/absence-report */
export async function getAbsenceReport(params = {}) {
  const { data } = await http.get('/policy-trips/absence-report', { params })
  return data.data
}
