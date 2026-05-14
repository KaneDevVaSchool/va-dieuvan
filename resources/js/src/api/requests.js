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
  return p
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
 * @param {number} passenger_count
 */
export async function patchPassengerCount(dispatchRequestId, passenger_count) {
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/passenger-count`, {
    passenger_count,
  })
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
 * @param {{ service_price: number|string }} payload
 */
export async function fillPriceDispatchRequest(dispatchRequestId, payload) {
  const { data } = await http.patch(`/dispatch-requests/${dispatchRequestId}/fill-price`, payload)
  return data.data
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

