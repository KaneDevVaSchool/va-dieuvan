import { http } from './http'

export async function listTripCosts(params = {}) {
  const { data } = await http.get('/trip-costs', { params })
  return data.data
}

/** Dòng chi phí công tác theo nhân sự (từ wizard businessRows trên chuyến loại công tác). */
export async function listBusinessPersonnelCostLines(params = {}) {
  const { data } = await http.get('/trip-costs/business-personnel-lines', { params })
  return data.data
}

/** Dòng dự toán phiếu (wizard_snapshot) — chưa có trip_cost wizard_estimate. */
export async function listWizardEstimateLines(params = {}) {
  const { data } = await http.get('/trip-costs/wizard-estimate-lines', { params })
  return data.data
}

export async function listCostsForTrip(tripId, params = {}) {
  const { data } = await http.get(`/trips/${tripId}/costs`, { params })
  return data.data
}

export async function submitTripCost(tripId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/trips/${tripId}/costs`, payload, { headers })
  return data.data
}

/** Chi phí phát sinh — có thể gửi không gắn chuyến (trip_id tùy chọn). */
export async function submitStandaloneTripCost(payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post('/trip-costs', payload, { headers })
  return data.data
}

/**
 * @param {Record<string, unknown>} payload
 * @param {{ idempotencyKey?: string }} [opts]
 */
export async function decideTripCost(tripCostId, payload, opts = {}) {
  const idempotencyKey = opts.idempotencyKey ?? crypto.randomUUID()
  const { data } = await http.post(`/trip-costs/${tripCostId}/decision`, payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return data.data
}

export async function getTripCost(id) {
  const { data } = await http.get(`/trip-costs/${id}`)
  return data.data
}

export async function updateTripCost(id, payload) {
  const { data } = await http.patch(`/trip-costs/${id}`, payload)
  return data.data
}

export async function deleteTripCost(id) {
  const { data } = await http.delete(`/trip-costs/${id}`)
  return data.data
}

export async function bulkDeleteTripCosts(ids) {
  const { data } = await http.post('/trip-costs/bulk-delete', { ids })
  return data.data
}

/**
 * Xóa toàn bộ ghi chú chi phí khớp bộ lọc danh sách (tab Ghi chú / Phát sinh).
 * @param {Record<string, unknown> & { confirm_phrase: string, expected_count: number }} payload
 */
export async function purgeAllTripCosts(payload) {
  const { data } = await http.post('/trip-costs/purge-all', payload)
  return data.data
}

// ── Operational notes (tab Ghi chú vận hành) ─────────────────────

export async function listCostNotes(params = {}) {
  const { data } = await http.get('/trip-cost-notes', { params })
  return data.data
}

export async function addCostNote(payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post('/trip-cost-notes', payload, { headers })
  return data.data
}

export async function deleteCostNote(id) {
  const { data } = await http.delete(`/trip-cost-notes/${id}`)
  return data.data
}

export async function uploadTripCostReceipt(tripId, tripCostId, file, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const body = new FormData()
  body.append('file', file)
  const { data } = await http.post(`/trips/${tripId}/costs/${tripCostId}/receipt`, body, { headers })
  return data.data
}
