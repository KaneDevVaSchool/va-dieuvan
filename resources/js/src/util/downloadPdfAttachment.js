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
/**
 * Chuẩn hóa URL /storage từ API (APP_URL) sang origin backend khi dev Vite.
 * @param {string} url
 */
export function resolveAttachmentAbsoluteUrl(url) {
  const u = url
  if (!u) return ''
  if (typeof window === 'undefined') return u
  const viteBackend =
    import.meta.env.DEV && import.meta.env.VITE_APP_URL
      ? String(import.meta.env.VITE_APP_URL).trim().replace(/\/$/, '')
      : ''
  try {
    const parsed = new URL(u, window.location.origin)
    if (parsed.pathname.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${parsed.pathname}${parsed.search}`
    }
    return parsed.href
  } catch {
    const path = u.startsWith('/') ? u : `/${u}`
    if (path.startsWith('/storage')) {
      const base = viteBackend || window.location.origin
      return `${base}${path}`
    }
    return `${window.location.origin}${path}`
  }
}

/**
 * Tải blob đính kèm qua Sanctum (xem ảnh/PDF trong SPA, không dùng thẻ img + /storage).
 * @param {number} attachmentId
 * @returns {Promise<Blob>}
 */
export async function fetchAttachmentBlob(attachmentId) {
  let res
  try {
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
  if (ct.includes('text/html') || ct.includes('application/json')) {
    throw new Error('unexpected_body')
  }

  return blob
}

async function blobLooksLikeZipOffice(blob) {
  const n = Math.min(4, blob.size)
  if (n < 2) return false
  const buf = new Uint8Array(await blob.slice(0, n).arrayBuffer())
  return buf[0] === 0x50 && buf[1] === 0x4b
}

/**
 * Reject blob responses that are JSON/HTML errors disguised as downloads.
 * @param {import('axios').AxiosResponse<Blob>} res
 */
export async function assertAxiosBlobIsOfficeZip(res) {
  const blob = res.data
  if (!(blob instanceof Blob) || blob.size === 0) {
    throw Object.assign(new Error('empty_response'), { code: 'EMPTY_BLOB' })
  }
  const ct = String(res.headers?.['content-type'] || '').toLowerCase()
  if (ct.includes('application/json') || ct.includes('text/html')) {
    try {
      const text = await blob.text()
      let message = 'Tải tệp thất bại.'
      try {
        const parsed = JSON.parse(text)
        if (typeof parsed?.message === 'string' && parsed.message) {
          message = parsed.message
        }
      } catch {
        /* ignore */
      }
      const err = new Error(message)
      err.response = { status: res.status, data: { message }, headers: res.headers, config: res.config }
      throw err
    } catch (e) {
      if (e?.response) throw e
      throw new Error('Tải tệp thất bại.')
    }
  }
  if (!(await blobLooksLikeZipOffice(blob))) {
    const err = new Error('Phản hồi không phải tệp Excel hợp lệ.')
    err.response = { status: res.status, data: { message: err.message }, headers: res.headers, config: res.config }
    throw err
  }
}

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
 * Lấy blob qua API (Sanctum) để xem trước trong iframe — không lưu file.
 * @param {number} attachmentId
 * @returns {Promise<{ ok: true, blob: Blob } | { ok: false, reason: 'html' | 'not_pdf' | 'bad_json' }>}
 */
export async function fetchPdfBlobForPreview(attachmentId) {
  let res
  try {
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

  if (blob.size === 0) {
    return { ok: false, reason: 'not_pdf' }
  }

  const ctOk = contentTypeLooksPdf(ct)
  const magicOk = await blobLooksLikePdf(blob)
  if (!ctOk && !magicOk) {
    return { ok: false, reason: 'not_pdf' }
  }

  return { ok: true, blob }
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
/**
 * Tải bất kỳ đính kèm nào (PDF, Excel, …) qua API đã xác thực.
 * @param {number} attachmentId
 * @param {string} [filename]
 */
export async function downloadBinaryAttachmentFromApi(attachmentId, filename) {
  let res
  try {
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
  if (ct.includes('text/html') || ct.includes('application/json')) {
    throw new Error('unexpected_body')
  }

  saveAs(blob, filename || 'download')
}

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
