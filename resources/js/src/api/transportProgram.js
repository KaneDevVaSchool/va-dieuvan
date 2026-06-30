import { http } from './http'
import { normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

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

export async function bulkDeletePrograms(ids) {
  const { data } = await http.post('/tp-programs/bulk-delete', { ids })
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

export async function clearDayDriver(dayId, params = {}) {
  const { data } = await http.delete(`/tp-program-days/${dayId}/driver`, { params })
  return data.data
}

export async function assignDayBackupDriver(dayId, payload) {
  const { data } = await http.patch(`/tp-program-days/${dayId}/backup-driver`, payload)
  return data.data
}

export async function clearDayBackupDriver(dayId, params = {}) {
  const { data } = await http.delete(`/tp-program-days/${dayId}/backup-driver`, { params })
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

function attendanceShiftParams(shift) {
  return shift === 'morning' || shift === 'afternoon' ? { shift } : {}
}

export async function getDayAttendance(dayId, { shift } = {}) {
  const { data } = await http.get(`/tp-program-days/${dayId}/attendance`, { params: attendanceShiftParams(shift) })
  return data.data
}

export async function markDayAbsence(dayId, payload = {}) {
  const { shift, ...body } = payload
  const { data } = await http.post(`/tp-program-days/${dayId}/absences`, {
    ...body,
    ...attendanceShiftParams(shift),
  })
  return data.data
}

export async function unmarkDayAbsence(dayId, studentId, { shift } = {}) {
  const { data } = await http.delete(`/tp-program-days/${dayId}/absences/${studentId}`, {
    params: attendanceShiftParams(shift),
  })
  return data.data
}

export async function markDayPresent(dayId, payload = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/present`, payload)
  return data.data
}

export async function saveAttendanceDraft(dayId, { shift } = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/attendance/draft`, attendanceShiftParams(shift))
  return data.data
}

export async function confirmDayAttendance(dayId, attendanceLockVersion, { shift } = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/attendance/confirm`, {
    attendance_lock_version: attendanceLockVersion,
    ...attendanceShiftParams(shift),
  })
  return data.data
}

export async function reopenDayAttendance(dayId, { shift } = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/attendance/reopen`, attendanceShiftParams(shift))
  return data.data
}

export async function listAbsenceReasons() {
  const { data } = await http.get('/tp-absence-reasons')
  return data.data
}

export async function previewDayParentsNotify(dayId, body = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/notify-parents/preview`, body)
  return data.data
}

export async function notifyDayParents(dayId, body = {}) {
  const { data } = await http.post(`/tp-program-days/${dayId}/notify-parents`, body)
  return data.data
}

export async function listDayParentsNotifyLogs(dayId) {
  const { data } = await http.get(`/tp-program-days/${dayId}/notify-parents/logs`)
  return data.data
}

export async function downloadDayAttendanceExport(dayId, { shift } = {}) {
  const { data } = await http.get(`/tp-program-days/${dayId}/attendance/export`, {
    params: attendanceShiftParams(shift),
    responseType: 'blob',
  })
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `diem-danh-${dayId}.csv`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
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

export async function exportStudentsList(params = {}) {
  let res
  try {
    res = await http.get('/tp-students/export', {
      params,
      responseType: 'blob',
      headers: { Accept: '*/*' },
      timeout: 120_000,
    })
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
  const blob = res.data
  if (!(blob instanceof Blob) || blob.size === 0) throw new Error('empty_response')
  const stamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '').replace('T', '_')
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `danh-sach-hoc-sinh_${stamp}.xlsx`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
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

export async function bulkDeleteStudents(ids) {
  const { data } = await http.post('/tp-students/bulk-delete', { ids })
  return data.data
}

export async function getStudentPrograms(id) {
  const { data } = await http.get(`/tp-students/${id}/programs`)
  return data.data
}

// ── Import pipeline ──────────────────────────────────────────────────────────────

export async function uploadImport(file, targetProgramId = null) {
  const form = new FormData()
  form.append('file', file, file.name || 'import.xlsx')
  if (targetProgramId) form.append('target_program_id', String(targetProgramId))
  const { data } = await http.post('/tp-imports', form)
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

export async function updateImportRow(batchId, rowId, rowData) {
  const { data } = await http.patch(`/tp-imports/${batchId}/rows/${rowId}`, { data: rowData })
  return data.data
}

export async function skipImportRow(batchId, rowId, skip) {
  const { data } = await http.patch(`/tp-imports/${batchId}/rows/${rowId}`, { skip })
  return data.data
}

export async function listImportBatches(params = {}) {
  const { data } = await http.get('/tp-imports', { params })
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

export async function downloadImportErrorReport(id) {
  const { data } = await http.get(`/tp-imports/${id}/error-report`, {
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `bao-cao-loi-import-${id}.xlsx`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

export async function downloadImportSample() {
  const { data } = await http.get('/tp-imports/sample', {
    responseType: 'blob',
    headers: { Accept: '*/*' },
  })
  const url = window.URL.createObjectURL(new Blob([data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'mau-import-hoc-sinh.xlsx')
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

// ── Driver ──────────────────────────────────────────────────────────────────────

export async function driverListDays(params = {}) {
  const { data } = await http.get('/driver/tp-days', { params })
  return data.data
}

export async function driverGetDay(dayId, shift = null) {
  const params = shift ? { shift } : {}
  const { data } = await http.get(`/driver/tp-days/${dayId}`, { params })
  return data.data
}

export async function driverConfirmDay(dayId, shift = null) {
  const payload = shift ? { shift } : {}
  const { data } = await http.post(`/driver/tp-days/${dayId}/confirm`, payload)
  return data.data
}

export async function driverUnconfirmDay(dayId, shift = null) {
  const payload = shift ? { shift } : {}
  const { data } = await http.delete(`/driver/tp-days/${dayId}/confirm`, { data: payload })
  return data.data
}

/** Tài xế báo bận một ca: gỡ phân công + báo điều phối phân tài xế khác. */
export async function driverReportBusyDay(dayId, shift = null, reason = '') {
  const payload = {}
  if (shift) payload.shift = shift
  if (reason) payload.reason = reason
  const { data } = await http.post(`/driver/tp-days/${dayId}/report-busy`, payload)
  return data.data
}

export async function driverStartTrip(dayId, deviceId = null, shift = null) {
  const payload = {}
  if (deviceId) payload.device_id = deviceId
  if (shift) payload.shift = shift
  const { data } = await http.post(`/driver/tp-days/${dayId}/start`, payload)
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

export async function driverUpdateStudentNotes(executionId, studentId, driverNotes) {
  const { data } = await http.patch(
    `/driver/tp-executions/${executionId}/students/${studentId}/notes`,
    { driver_notes: driverNotes ?? null },
  )
  return data.data
}

export async function driverSync(executionId, actions) {
  const { data } = await http.post(`/driver/tp-executions/${executionId}/sync`, { actions })
  return data.data
}
