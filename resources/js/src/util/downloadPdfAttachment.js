import axios from 'axios'
import { saveAs } from 'file-saver'
import { http } from '../api/http'

/**
 * Kiểm tra header PDF (%PDF-).
 * @param {Blob} blob
 */
async function blobLooksLikePdf(blob) {
  const n = Math.min(5, blob.size)
  if (n < 4) return false
  const buf = new Uint8Array(await blob.slice(0, n).arrayBuffer())
  let s = ''
  for (let i = 0; i < buf.length; i++) s += String.fromCharCode(buf[i])
  return s.startsWith('%PDF')
}

function contentTypeLooksPdf(ct) {
  const c = String(ct || '').toLowerCase()
  return c.includes('application/pdf') || c.includes('/pdf')
}

/**
 * Laravel trả lỗi JSON nhưng axios `responseType: 'blob'` nhận Blob — gắn lại object cho modal lỗi.
 * @param {unknown} err
 */
export async function normalizeAxiosBlobError(err) {
  const res = err?.response
  if (!res || !(res.data instanceof Blob)) return err
  const ct = String(res.headers?.['content-type'] || '').toLowerCase()
  if (!ct.includes('application/json')) return err
  try {
    const text = await res.data.text()
    res.data = JSON.parse(text)
  } catch {
    res.data = { message: res.status === 403 ? 'Không có quyền tải tệp.' : 'Tải tệp thất bại.' }
  }
  return err
}

/**
 * Tải qua API (Sanctum) — luôn dùng khi có `attachment.id` (không phụ thuộc /storage công khai).
 * @param {number} attachmentId
 * @param {string} filename
 * @returns {Promise<{ ok: true } | { ok: false, reason: 'html' | 'not_pdf' | 'bad_json' }>}
 */
export async function downloadPdfAttachmentFromApi(attachmentId, filename) {
  let res
  try {
    // Mặc định axios dùng Accept: application/json — có thể gây lệch thương lượng nội dung; tải file cần */*
    res = await http.get(`/attachments/${attachmentId}/download`, {
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
  if (ct.includes('text/html')) {
    return { ok: false, reason: 'html' }
  }
  if (ct.includes('application/json')) {
    return { ok: false, reason: 'bad_json' }
  }

  // Endpoint đã xác thực Sanctum — tin tưởng binary (PDF thường là application/octet-stream).
  if (blob.size === 0) {
    return { ok: false, reason: 'not_pdf' }
  }

  saveAs(blob, filename || 'document.pdf')
  return { ok: true }
}

/**
 * Fallback: URL trực tiếp (public disk) — axios + magic bytes.
 * @param {string} url URL tuyệt đối (đã chuẩn hóa origin).
 * @param {{ filename?: string, bearerToken?: string|null }} [options]
 * @returns {Promise<{ ok: true } | { ok: false, reason: 'html' | 'not_pdf' }>}
 */
export async function downloadPdfAttachmentFromUrl(url, options = {}) {
  const { filename = 'document.pdf', bearerToken = null } = options

  let sameOrigin = false
  try {
    sameOrigin =
      new URL(url, typeof window !== 'undefined' ? window.location.origin : undefined).origin ===
      (typeof window !== 'undefined' ? window.location.origin : '')
  } catch {
    sameOrigin = false
  }

  const headers = {}
  if (bearerToken) headers.Authorization = `Bearer ${bearerToken}`

  const res = await axios.get(url, {
    responseType: 'blob',
    headers,
    withCredentials: sameOrigin,
    timeout: 120_000,
  })

  if (res.status >= 400) {
    throw new Error(`HTTP ${res.status}`)
  }

  const blob = res.data
  if (!(blob instanceof Blob)) {
    throw new Error('invalid_response')
  }

  const ct = String(res.headers['content-type'] || '').toLowerCase()
  if (ct.includes('text/html')) {
    return { ok: false, reason: 'html' }
  }

  const byMagic = await blobLooksLikePdf(blob)
  const isPdf = contentTypeLooksPdf(ct) || byMagic
  if (!isPdf) {
    return { ok: false, reason: 'not_pdf' }
  }

  saveAs(blob, filename)
  return { ok: true }
}
