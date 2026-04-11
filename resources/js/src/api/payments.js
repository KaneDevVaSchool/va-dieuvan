import { http } from './http'

export async function listReconciliationPeriods(params = {}) {
  const { data } = await http.get('/reconciliation-periods', { params })
  return data.data
}

export async function getReconciliationPeriod(id) {
  const { data } = await http.get(`/reconciliation-periods/${id}`)
  return data.data
}

export async function listPayments(params = {}) {
  const { data } = await http.get('/payments', { params })
  return data.data
}

export async function getPayment(id) {
  const { data } = await http.get(`/payments/${id}`)
  return data.data
}

export async function createReconciliationPeriod(payload) {
  const { data } = await http.post('/reconciliation-periods', payload)
  return data.data
}

export async function lockReconciliationPeriod(id) {
  const { data } = await http.post(`/reconciliation-periods/${id}/lock`)
  return data.data
}

export async function generatePayments(periodId, payload) {
  const { data } = await http.post(`/reconciliation-periods/${periodId}/generate-payments`, payload)
  return data.data
}

export async function executePayment(paymentId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/payments/${paymentId}/execute`, payload, { headers })
  return data.data
}
