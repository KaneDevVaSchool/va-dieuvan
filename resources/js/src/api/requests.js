import { http } from './http'

export async function listRequests(params = {}) {
  const { data } = await http.get('/requests', { params })
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

export async function markPaperReceived(dispatchRequestId, payload) {
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/paper-received`, payload)
  return data.data
}

export async function decideDispatchRequest(dispatchRequestId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/decision`, payload, { headers })
  return data.data
}

