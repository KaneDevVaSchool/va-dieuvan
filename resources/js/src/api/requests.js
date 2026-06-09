import { http } from './http'
import { normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

/**
 * Laravel 10 `boolean` rule accepts true/false/0/1/'0'/'1' — not the string "true" from query strings.
 * Always send 0/1 for boolean query params so production works even without server-side normalization.
 * @param {Record<string, unknown>} params
 */
export function normalizeRequestListParams(params) {
  const p = { ...params }
  if (p.only_trashed === true) p.only_trashed = 1
  if (p.only_trashed === false) delete p.only_trashed
  if (p.is_urgent === true) p.is_urgent = 1
  if (p.is_urgent === false) delete p.is_urgent
  if (p.sla_risk_only === true) p.sla_risk_only = 1
  if (p.sla_risk_only === false) delete p.sla_risk_only
  if (p.recurring_only === true) p.recurring_only = 1
  if (p.recurring_only === false) delete p.recurring_only
  if (p.extracurricular_only === true) p.extracurricular_only = 1
  if (p.extracurricular_only === false) delete p.extracurricular_only
  if (p.student_count_submitted === true) p.student_count_submitted = 1
  if (p.student_count_submitted === false) p.student_count_submitted = 0
  return p
}

export async function getDeptSummary() {
  const { data } = await http.get('/dept/summary')
  return data.data
}

export async function listRequests(params = {}) {
  const { data } = await http.get('/requests', { params: normalizeRequestListParams(params) })
  return data.data
}

/**
 * @param {{ ids: number[] }} payload
 */
export async function bulkSoftDeleteRequests(payload) {
  const { data } = await http.post('/requests/bulk-delete', payload)
  return data.data
}

/**
 * @param {{ ids: number[] }} payload
 */
export async function bulkRestoreRequests(payload) {
  const { data } = await http.post('/requests/bulk-restore', payload)
  return data.data
}

/**
 * Permanently delete soft-deleted requests (trash).
 * @param {{ ids: number[] }} payload
 */
export async function bulkForceDeleteRequests(payload) {
  const { data } = await http.post('/requests/bulk-force-delete', payload)
  return data.data
}

export async function getDispatchRequest(id) {
  const { data } = await http.get(`/dispatch-requests/${id}`)
  return data.data
}

export async function getDispatchRequestAuditLogs(id) {
  const { data } = await http.get(`/dispatch-requests/${id}/audit-logs`)
  return data.data.items ?? []
}

/**
 * @param {Record<string, unknown>} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function createPortalDispatchRequest(payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post('/portal/dispatch-requests', payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

/** Tìm nhân sự (form portal — không cần dispatch.staff). @param {string} q */
export async function searchUsersForPortalForm(q) {
  const { data } = await http.get('/portal/users/for-dispatch-form', { params: { q } })
  const rows = data?.data
  return Array.isArray(rows) ? rows : []
}

/** Trưởng đơn vị (portal tạo phiếu). @param {{ q?: string, pick?: number|string }} opts */
export async function searchPortalDeptHeads(opts = {}) {
  const params = {}
  if (opts.q != null && String(opts.q).trim() !== '') params.q = String(opts.q).trim()
  if (opts.pick != null && opts.pick !== '') params.pick = opts.pick
  const { data } = await http.get('/portal/users/dept-heads', { params })
  return data.data ?? []
}

/**
 * Portal dashboard KPI counts (scoped to current requester).
 * @returns {Promise<{ processing: number, pending: number, completed_this_month: number, rejected: number }>}
 */
export async function getPortalRequestsSummary() {
  const { data } = await http.get('/portal/dispatch-requests/summary')
  return data.data
}

/**
 * @param {{ per_page?: number, page?: number, q?: string, sort?: 'depart_desc'|'depart_asc'|'created_desc'|'created_asc', filter?: 'all'|'pending'|'approved'|'rejected'|'returned', trip_type?: 'business'|'cargo'|'door_to_door'|'point_to_point', is_urgent?: 0|1, extracurricular_only?: 0|1, date_from?: string, date_to?: string }} [params]
 */
export async function listPortalRequests(params = {}) {
  const { data } = await http.get('/portal/dispatch-requests', { params: normalizeRequestListParams(params) })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 */
export async function getPortalDispatchRequest(dispatchRequestId) {
  const { data } = await http.get(`/portal/dispatch-requests/${dispatchRequestId}`)
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {File} file
 * @param {(pct: number) => void} [onProgress]
 */
export async function uploadPortalSignedPaper(dispatchRequestId, file, onProgress) {
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await http.post(`/portal/dispatch-requests/${dispatchRequestId}/signed-paper`, fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {string} status
 */
export async function patchPortalSigningWorkflow(dispatchRequestId, status) {
  const { data } = await http.patch(`/portal/dispatch-requests/${dispatchRequestId}/signing-workflow`, { status })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 */
export async function getPortalSignedDocuments(dispatchRequestId) {
  const { data } = await http.get(`/portal/dispatch-requests/${dispatchRequestId}/signed-documents`, {
    params: { include_history: 1 },
  })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {File} file
 * @param {(pct: number) => void} [onProgress]
 */
export async function uploadPortalProposalBasis(dispatchRequestId, file, onProgress) {
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await http.post(`/portal/dispatch-requests/${dispatchRequestId}/proposal-basis`, fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {number} attachmentId
 * @returns {Promise<Blob>}
 */
export async function downloadPortalAttachmentBlob(dispatchRequestId, attachmentId) {
  try {
    const res = await http.get(`/portal/dispatch-requests/${dispatchRequestId}/attachments/${attachmentId}/download`, {
      responseType: 'blob',
      headers: { Accept: '*/*' },
    })
    return res.data
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
}

// ---------------------------------------------------------------------------
// Portal form templates (biểu mẫu đã lưu)
// ---------------------------------------------------------------------------

/**
 * @returns {Promise<Array<{ id: number, name: string, trip_type: string, updated_at: string }>>}
 */
export async function getPortalFormTemplates() {
  const { data } = await http.get('/portal/form-templates')
  return data.data
}

/**
 * @param {number} id
 * @returns {Promise<{ id: number, name: string, trip_type: string, wizard_snapshot: object|null, updated_at: string }>}
 */
export async function getPortalFormTemplate(id) {
  const { data } = await http.get(`/portal/form-templates/${id}`)
  return data.data
}

/**
 * @param {{ name: string, trip_type: string, wizard_snapshot?: object }} payload
 */
export async function createPortalFormTemplate(payload) {
  const { data } = await http.post('/portal/form-templates', payload)
  return data.data
}

/**
 * @param {number} id
 * @param {{ name: string }} payload
 */
export async function updatePortalFormTemplate(id, payload) {
  const { data } = await http.patch(`/portal/form-templates/${id}`, payload)
  return data.data
}

/**
 * @param {number} id
 */
export async function deletePortalFormTemplate(id) {
  const { data } = await http.delete(`/portal/form-templates/${id}`)
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @returns {Promise<Blob>}
 */
export async function exportPortalDispatchRequestPdf(dispatchRequestId) {
  try {
    const res = await http.get(`/portal/dispatch-requests/${dispatchRequestId}/export-pdf`, {
      responseType: 'blob',
      headers: { Accept: 'application/pdf' },
    })
    return res.data
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
}

/**
 * @param {Record<string, unknown>} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function createDispatchRequest(payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post('/dispatch-requests', payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

/**
 * @param {Record<string, unknown>} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function createDispatchRequestTemplate(payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post('/dispatch-request-templates', payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

/** Lịch lặp — portal (không cần dispatch.web / request.create). */
export async function createPortalDispatchRequestTemplate(payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post('/portal/dispatch-request-templates', payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

export async function updatePortalDispatchRequestTemplate(templateId, payload) {
  const { data } = await http.patch(`/portal/dispatch-request-templates/${templateId}`, payload)
  return data.data
}

/** Đổi tên phiếu / nhóm kế hoạch (lịch CLB portal). */
export async function updatePortalDispatchPlanLabel(templateId, planLabel) {
  const { data } = await http.patch(
    `/portal/dispatch-request-templates/${templateId}/plan-label`,
    { plan_label: planLabel },
  )
  return data.data
}

/** Sửa phiếu instance định kỳ CLB (portal). */
export async function patchPortalRecurringInstance(dispatchRequestId, payload) {
  const { data } = await http.patch(
    `/portal/dispatch-requests/${dispatchRequestId}/recurring-instance`,
    payload,
  )
  return data.data
}

export async function submitPortalRecurringInstance(dispatchRequestId) {
  const { data } = await http.post(`/portal/dispatch-requests/${dispatchRequestId}/submit-recurring`)
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function cloneDispatchRequest(dispatchRequestId, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post(
    `/dispatch-requests/${dispatchRequestId}/clone`,
    {},
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {Record<string, unknown>} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function patchDispatchRequestWizard(dispatchRequestId, payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/wizard`, payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 * @param {number} student_count_actual
 */
export async function patchPassengerCount(dispatchRequestId, student_count_actual) {
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/passenger-count`, {
    student_count_actual,
  })
  return data.data
}

/**
 * Chốt số HS thực tế và gửi thông tin tới điều vận (chuyến định kỳ).
 * @param {number} dispatchRequestId
 */
export async function submitStudentCount(dispatchRequestId) {
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/submit-student-count`)
  return data.data
}

export async function markPaperReceived(dispatchRequestId, payload) {
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/paper-received`, payload)
  return data.data
}

export async function revertPaperReceived(dispatchRequestId) {
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/paper-revert`)
  return data.data
}

export async function decideDispatchRequest(dispatchRequestId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/decision`, payload, { headers })
  return data.data
}

/**
 * @param {{
 *   service_price: number|string,
 *   rows?: Array<{ unit_price?: number, extra_fee?: number, notes?: string|null }>,
 *   dept_head_user_id?: number|null,
 * }} payload
 */
/** @param {number} dispatchRequestId @param {Record<string, unknown>} payload */
export async function applyDispatchRequestPricingHints(dispatchRequestId, payload) {
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/pricing-hints`, payload)
  return data.data
}

export async function fillPriceDispatchRequest(dispatchRequestId, payload) {
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/fill-price`, payload)
  return data.data
}

/**
 * Trưởng BP cùng phòng với người đề xuất (`q`: lọc; `pick`: luôn trả user ID nếu hợp lệ).
 * @returns {Promise<Array<{ id: number, name: string, employee_code: string|null, email?: string|null }>>}
 */
export async function getAvailableDeptHeads(dispatchRequestId, opts = {}) {
  const params = {}
  if (opts.q != null && String(opts.q).trim() !== '') params.q = String(opts.q).trim()
  if (opts.pick != null && opts.pick !== '') params.pick = opts.pick

  const { data } = await http.get(`/dispatch-requests/${dispatchRequestId}/available-dept-heads`, { params })
  return data.data ?? []
}

/**
 * @param {{ decision: 'approve'|'reject', rejection_reason?: string|null }} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function deptDecideDispatchRequest(dispatchRequestId, payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/dept-decision`, payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

/**
 * Xuất PDF biểu mẫu BM.03 (inline preview).
 * @param {number} dispatchRequestId
 * @returns {Promise<Blob>}
 */
export async function exportDispatchRequestPdf(dispatchRequestId) {
  try {
    const res = await http.get(`/dispatch-requests/${dispatchRequestId}/export-pdf`, {
      responseType: 'blob',
      headers: { Accept: 'application/pdf' },
    })
    return res.data
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
}

