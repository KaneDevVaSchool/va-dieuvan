import { http } from './http'

/**
 * Client API cho module Transport Program redesign (tp_*).
 * Mọi hàm trả về `data.data` đã bóc lớp envelope { message, data }.
 */

// ── Programs ─────────────────────────────────────────────────────────────────

export async function listPrograms(params = {}) {
  const { data } = await http.get('/tp-programs', { params })
  return data.data
}

export async function getProgram(id) {
  const { data } = await http.get(`/tp-programs/${id}`)
  return data.data
}

export async function createProgram(payload) {
  const { data } = await http.post('/tp-programs', payload)
  return data.data
}

export async function updateProgram(id, payload) {
  const { data } = await http.patch(`/tp-programs/${id}`, payload)
  return data.data
}

export async function deleteProgram(id) {
  const { data } = await http.delete(`/tp-programs/${id}`)
  return data.data
}

export async function activateProgram(id) {
  const { data } = await http.post(`/tp-programs/${id}/activate`)
  return data.data
}

export async function pauseProgram(id) {
  const { data } = await http.post(`/tp-programs/${id}/pause`)
  return data.data
}

export async function cancelProgram(id, reason) {
  const { data } = await http.post(`/tp-programs/${id}/cancel`, { reason })
  return data.data
}

// ── Days ─────────────────────────────────────────────────────────────────────

export async function listProgramDays(id, params = {}) {
  const { data } = await http.get(`/tp-programs/${id}/days`, { params })
  return data.data
}

export async function getProgramDay(dayId) {
  const { data } = await http.get(`/tp-program-days/${dayId}`)
  return data.data
}

export async function updateProgramDay(dayId, payload) {
  const { data } = await http.patch(`/tp-program-days/${dayId}`, payload)
  return data.data
}

export async function assignDayDriver(dayId, payload) {
  const { data } = await http.patch(`/tp-program-days/${dayId}/assign-driver`, payload)
  return data.data
}

export async function clearDayDriver(dayId) {
  const { data } = await http.delete(`/tp-program-days/${dayId}/driver`)
  return data.data
}

// ── Enrollments ───────────────────────────────────────────────────────────────

export async function listEnrollments(id) {
  const { data } = await http.get(`/tp-programs/${id}/enrollments`)
  return data.data
}

export async function enrollStudents(id, studentIds) {
  const { data } = await http.post(`/tp-programs/${id}/enrollments`, { student_ids: studentIds })
  return data.data
}

export async function unenrollStudent(id, studentId, reason) {
  const { data } = await http.delete(`/tp-programs/${id}/enrollments/${studentId}`, {
    data: { reason },
  })
  return data.data
}

// ── Attendance & Absences ──────────────────────────────────────────────────────

export async function getDayAttendance(dayId) {
  const { data } = await http.get(`/tp-program-days/${dayId}/attendance`)
  return data.data
}

export async function markDayAbsence(dayId, payload) {
  const { data } = await http.post(`/tp-program-days/${dayId}/absences`, payload)
  return data.data
}

export async function unmarkDayAbsence(dayId, studentId) {
  const { data } = await http.delete(`/tp-program-days/${dayId}/absences/${studentId}`)
  return data.data
}

// ── Reports & Audit ─────────────────────────────────────────────────────────────

export async function getAbsenceReport(id, params = {}) {
  const { data } = await http.get(`/tp-programs/${id}/reports/absence`, { params })
  return data.data
}

export async function getCostReport(id, params = {}) {
  const { data } = await http.get(`/tp-programs/${id}/reports/cost`, { params })
  return data.data
}

export async function getProgramAudit(id, params = {}) {
  const { data } = await http.get(`/tp-programs/${id}/audit`, { params })
  return data.data
}

export async function updateExecutionCost(executionId, payload) {
  const { data } = await http.patch(`/tp-executions/${executionId}/cost`, payload)
  return data.data
}

// ── Students ────────────────────────────────────────────────────────────────────

export async function listStudents(params = {}) {
  const { data } = await http.get('/tp-students', { params })
  return data.data
}

export async function getStudent(id) {
  const { data } = await http.get(`/tp-students/${id}`)
  return data.data
}

export async function createStudent(payload) {
  const { data } = await http.post('/tp-students', payload)
  return data.data
}

export async function updateStudent(id, payload) {
  const { data } = await http.patch(`/tp-students/${id}`, payload)
  return data.data
}

export async function deleteStudent(id) {
  const { data } = await http.delete(`/tp-students/${id}`)
  return data.data
}

export async function getStudentPrograms(id) {
  const { data } = await http.get(`/tp-students/${id}/programs`)
  return data.data
}

// ── Import pipeline ──────────────────────────────────────────────────────────────

export async function uploadImport(file, targetProgramId = null) {
  const form = new FormData()
  form.append('file', file)
  if (targetProgramId) form.append('target_program_id', targetProgramId)
  const { data } = await http.post('/tp-imports', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data
}

export async function getImport(id) {
  const { data } = await http.get(`/tp-imports/${id}`)
  return data.data
}

export async function saveImportMapping(id, columnMapping) {
  const { data } = await http.patch(`/tp-imports/${id}/mapping`, { column_mapping: columnMapping })
  return data.data
}

export async function listImportRows(id, params = {}) {
  const { data } = await http.get(`/tp-imports/${id}/rows`, { params })
  return data.data
}

export async function applyImportFixes(id, rules) {
  const { data } = await http.post(`/tp-imports/${id}/apply-fixes`, { rules })
  return data.data
}

export async function executeImport(id, options = {}) {
  const { data } = await http.post(`/tp-imports/${id}/execute`, options)
  return data.data
}

export function importErrorReportUrl(id) {
  return `/api/tp-imports/${id}/error-report`
}

// ── Driver ──────────────────────────────────────────────────────────────────────

export async function driverListDays(params = {}) {
  const { data } = await http.get('/driver/tp-days', { params })
  return data.data
}

export async function driverGetDay(dayId) {
  const { data } = await http.get(`/driver/tp-days/${dayId}`)
  return data.data
}

export async function driverStartTrip(dayId, deviceId = null) {
  const { data } = await http.post(`/driver/tp-days/${dayId}/start`, { device_id: deviceId })
  return data.data
}

export async function driverCompleteTrip(executionId, confirmPendingBoard = false) {
  const { data } = await http.post(`/driver/tp-executions/${executionId}/complete`, {
    confirm_pending_board: confirmPendingBoard,
  })
  return data.data
}

export async function driverBoard(executionId, studentId, clientTimestamp = null) {
  const { data } = await http.patch(
    `/driver/tp-executions/${executionId}/students/${studentId}/board`,
    { client_timestamp: clientTimestamp },
  )
  return data.data
}

export async function driverAlight(executionId, studentId, clientTimestamp = null) {
  const { data } = await http.patch(
    `/driver/tp-executions/${executionId}/students/${studentId}/alight`,
    { client_timestamp: clientTimestamp },
  )
  return data.data
}

export async function driverAbsent(executionId, studentId, payload) {
  const { data } = await http.patch(
    `/driver/tp-executions/${executionId}/students/${studentId}/absent`,
    payload,
  )
  return data.data
}

export async function driverUndoAbsent(executionId, studentId) {
  const { data } = await http.patch(
    `/driver/tp-executions/${executionId}/students/${studentId}/undo-absent`,
  )
  return data.data
}

export async function driverSync(executionId, actions) {
  const { data } = await http.post(`/driver/tp-executions/${executionId}/sync`, { actions })
  return data.data
}
