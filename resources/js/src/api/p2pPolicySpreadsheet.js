import { saveAs } from 'file-saver'
import { http } from './http'
import { normalizeAxiosBlobError } from '../util/downloadPdfAttachment'

function filenameFromContentDisposition(header) {
  if (!header) return null
  const utf = /filename\*=UTF-8''([^;\s]+)/i.exec(header)
  if (utf?.[1]) {
    try {
      return decodeURIComponent(utf[1])
    } catch {
      return utf[1]
    }
  }
  const quoted = /filename="([^"]+)"/i.exec(header)
  if (quoted?.[1]) return quoted[1]
  const plain = /filename=([^;\s]+)/i.exec(header)
  return plain?.[1]?.replace(/"/g, '') ?? null
}

async function downloadSpreadsheet(path, params, fallbackName) {
  let res
  try {
    res = await http.get(path, {
      params,
      responseType: 'blob',
      headers: { Accept: '*/*' },
    })
  } catch (e) {
    await normalizeAxiosBlobError(e)
    throw e
  }
  const blob = res.data
  if (!(blob instanceof Blob)) {
    throw new Error('invalid_response')
  }
  const ct = String(res.headers['content-type'] || '').toLowerCase()
  if (ct.includes('application/json') || ct.includes('text/html')) {
    throw new Error('unexpected_body')
  }
  const name = filenameFromContentDisposition(res.headers['content-disposition']) || fallbackName
  saveAs(blob, name)
}

export async function downloadPolicyStudentsExport(params = {}) {
  await downloadSpreadsheet('/p2p-policy/students/export', params, 'p2p-policy-students.xlsx')
}

export async function downloadPolicyStudentsImportTemplate() {
  await downloadSpreadsheet('/p2p-policy/students/import-template', {}, 'p2p-policy-students-template.xlsx')
}

export async function previewPolicyStudentsImport(formData) {
  const { data } = await http.post('/p2p-policy/students/import/preview', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data
}

export async function commitPolicyStudentsImport(previewId) {
  const { data } = await http.post('/p2p-policy/students/import/commit', { preview_id: previewId })
  return data.data
}
