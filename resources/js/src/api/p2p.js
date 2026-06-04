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

// ── Reports ───────────────────────────────────────────────────────────────────

/** GET /api/policy-trips/absence-report */
export async function getAbsenceReport(params = {}) {
  const { data } = await http.get('/policy-trips/absence-report', { params })
  return data.data
}
