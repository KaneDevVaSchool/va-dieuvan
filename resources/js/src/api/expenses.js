import { saveAs } from 'file-saver'
import { http } from './http'
import { assertAxiosBlobIsOfficeZip, normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

/* ───────────── Người lập (tài xế) ───────────── */

export async function listMyExpenses(params = {}) {
  const { data } = await http.get('/driver/expenses', { params })
  return data.data
}

/** Người đề nghị, đơn vị, người ký mặc định, danh sách xe, hồ sơ căn cứ. */
export async function getExpenseDefaults() {
  const { data } = await http.get('/driver/expenses/defaults')
  return data.data
}

export async function listExpenseCostCandidates(params) {
  const { data } = await http.get('/driver/expenses/cost-candidates', { params })
  return data.data
}

export async function getMyExpense(id) {
  const { data } = await http.get(`/driver/expenses/${id}`)
  return data.data
}

export async function createExpense(payload) {
  const { data } = await http.post('/driver/expenses', payload, {
    headers: { 'Idempotency-Key': `expense-create-${crypto.randomUUID()}` },
  })
  return data.data
}

export async function updateExpense(id, payload) {
  const { data } = await http.patch(`/driver/expenses/${id}`, payload)
  return data.data
}

export async function submitExpense(id, lockVersion) {
  const { data } = await http.post(
    `/driver/expenses/${id}/submit`,
    { lock_version: lockVersion },
    { headers: { 'Idempotency-Key': `expense-submit-${id}-${lockVersion}` } },
  )
  return data.data
}

export async function cancelMyExpense(id) {
  const { data } = await http.post(`/driver/expenses/${id}/cancel`)
  return data.data
}

/** @param {'driver'|'staff'} scope */
export async function uploadExpenseScan(id, file, scope = 'driver') {
  const fd = new FormData()
  fd.append('file', file)
  const base = scope === 'driver' ? '/driver/expenses' : '/expenses'
  const { data } = await http.post(`${base}/${id}/signed-scan`, fd)
  return data.data
}

/* ───────────── Back-office ───────────── */

export async function listExpenses(params = {}) {
  const { data } = await http.get('/expenses', { params })
  return data.data
}

export async function getExpense(id) {
  const { data } = await http.get(`/expenses/${id}`)
  return data.data
}

export async function rejectExpense(id, reason) {
  const { data } = await http.post(`/expenses/${id}/reject`, { reason })
  return data.data
}

export async function markExpensePaid(id, paidAt = null) {
  const { data } = await http.post(
    `/expenses/${id}/mark-paid`,
    { paid_at: paidAt },
    { headers: { 'Idempotency-Key': `expense-paid-${id}-${crypto.randomUUID()}` } },
  )
  return data.data
}

export async function cancelExpense(id) {
  const { data } = await http.post(`/expenses/${id}/cancel`)
  return data.data
}

export async function getExpenseSettings() {
  const { data } = await http.get('/expenses/settings')
  return data.data
}

export async function updateExpenseSettings(payload) {
  const { data } = await http.put('/expenses/settings', payload)
  return data.data
}

export async function getVehicleCostLedgerSummary(params) {
  const { data } = await http.get('/vehicle-cost-ledger', { params })
  return data.data
}

export async function getVehicleCostLedger(vehicleId, params) {
  const { data } = await http.get(`/vehicle-cost-ledger/${vehicleId}`, { params })
  return data.data
}

/* ───────────── Tải file ───────────── */

async function downloadBlob(url, filename, { office = false, params } = {}) {
  let res
  try {
    res = await http.get(url, { params, responseType: 'blob', headers: { Accept: '*/*' }, timeout: 90_000 })
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
  if (office) await assertAxiosBlobIsOfficeZip(res)
  const blob = res.data
  if (!(blob instanceof Blob) || blob.size === 0) throw new Error('empty_response')
  saveAs(blob, filename)
}

/** @param {{ id: number, code: string }} doc @param {'pdf'|'xlsx'} format @param {'driver'|'staff'} scope */
export function downloadExpenseFile(doc, format, scope = 'driver') {
  const base = scope === 'driver' ? '/driver/expenses' : '/expenses'
  return downloadBlob(`${base}/${doc.id}/${format}`, `${doc.code}.${format}`, { office: format === 'xlsx' })
}

export function downloadVehicleCostLedger(vehicleId, plate, params) {
  const slug = String(plate || 'xe').replace(/[^A-Za-z0-9]+/g, '')
  return downloadBlob(`/vehicle-cost-ledger/${vehicleId}/export`, `so-chi-phi_${slug}.xlsx`, { office: true, params })
}
