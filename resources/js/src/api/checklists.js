import { saveAs } from 'file-saver'
import { http } from './http'
import { assertAxiosBlobIsOfficeZip, normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

/* ───────────── Tài xế ───────────── */

/** Kỳ hiện tại Daily/Weekly + xe có NG hôm nay (banner). */
export async function getChecklistOverview() {
  const { data } = await http.get('/driver/checklists/overview')
  return data.data
}

/** Template, xe, chuyến / ngày TP hôm nay để gắn vào phiếu. */
export async function getChecklistOptions() {
  const { data } = await http.get('/driver/checklists/options')
  return data.data
}

export async function listMyChecklists(params = {}) {
  const { data } = await http.get('/driver/checklists', { params })
  return data.data
}

export async function getMyChecklist(id) {
  const { data } = await http.get(`/driver/checklists/${id}`)
  return data.data
}

export async function startChecklist(payload) {
  const { data } = await http.post('/driver/checklists', payload, {
    headers: { 'Idempotency-Key': `checklist-start-${crypto.randomUUID()}` },
  })
  return data.data
}

export async function saveChecklistDraft(id, payload) {
  const { data } = await http.patch(`/driver/checklists/${id}`, payload)
  return data.data
}

export async function submitChecklist(id, payload) {
  const { data } = await http.post(`/driver/checklists/${id}/submit`, payload, {
    headers: { 'Idempotency-Key': `checklist-submit-${id}-${payload.lock_version}` },
  })
  return data.data
}

export async function discardChecklist(id) {
  const { data } = await http.delete(`/driver/checklists/${id}`)
  return data.data
}

export async function uploadChecklistEvidence(id, itemId, file) {
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await http.post(`/driver/checklists/${id}/items/${itemId}/evidence`, fd)
  return data.data
}

export async function deleteChecklistEvidence(id, itemId, attachmentId) {
  const { data } = await http.delete(`/driver/checklists/${id}/items/${itemId}/evidence/${attachmentId}`)
  return data.data
}

/* ───────────── Điều phối ───────────── */

export async function getChecklistCompliance(params) {
  const { data } = await http.get('/checklists/compliance', { params })
  return data.data
}

export async function listChecklists(params = {}) {
  const { data } = await http.get('/checklists', { params })
  return data.data
}

export async function getChecklist(id) {
  const { data } = await http.get(`/checklists/${id}`)
  return data.data
}

export async function reviewChecklist(id, reviewNote) {
  const { data } = await http.post(`/checklists/${id}/review`, { review_note: reviewNote || null })
  return data.data
}

export async function getChecklistSettings() {
  const { data } = await http.get('/checklist-settings')
  return data.data
}

export async function updateChecklistTemplate(templateId, payload) {
  const { data } = await http.patch(`/checklist-settings/templates/${templateId}`, payload)
  return data.data
}

export async function createChecklistItem(templateId, payload) {
  const { data } = await http.post(`/checklist-settings/templates/${templateId}/items`, payload)
  return data.data
}

export async function updateChecklistItem(templateId, itemId, payload) {
  const { data } = await http.patch(`/checklist-settings/templates/${templateId}/items/${itemId}`, payload)
  return data.data
}

export async function deleteChecklistItem(templateId, itemId) {
  const { data } = await http.delete(`/checklist-settings/templates/${templateId}/items/${itemId}`)
  return data.data
}

export async function reorderChecklistItems(templateId, ids) {
  const { data } = await http.post(`/checklist-settings/templates/${templateId}/items/reorder`, { ids })
  return data.data
}

export async function createReminderSlot(payload) {
  const { data } = await http.post('/checklist-settings/slots', payload)
  return data.data
}

export async function updateReminderSlot(id, payload) {
  const { data } = await http.patch(`/checklist-settings/slots/${id}`, payload)
  return data.data
}

export async function deleteReminderSlot(id) {
  const { data } = await http.delete(`/checklist-settings/slots/${id}`)
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

/** @param {{ id: number, code: string }} sub @param {'driver'|'staff'} scope */
export function downloadChecklistPdf(sub, scope = 'driver') {
  const base = scope === 'driver' ? '/driver/checklists' : '/checklists'
  return downloadBlob(`${base}/${sub.id}/pdf`, `${sub.code}.pdf`)
}

export function downloadChecklistXlsx(sub) {
  return downloadBlob(`/checklists/${sub.id}/xlsx`, `${sub.code}.xlsx`, { office: true })
}

export function exportChecklists(params = {}) {
  return downloadBlob('/checklists/export', `checklist_${new Date().toISOString().slice(0, 10)}.xlsx`, { office: true, params })
}
