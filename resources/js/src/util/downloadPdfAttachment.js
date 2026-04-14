import axios from 'axios'
import { saveAs } from 'file-saver'
import { fileTypeFromBlob } from 'file-type'

/**
 * Kiểm tra nhanh header PDF (%PDF-) khi server gửi application/octet-stream.
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

/**
 * Tải URL dạng blob qua axios (cùng cơ chế header Bearer như API), xác thực PDF bằng `file-type`,
 * lưu bằng FileSaver.js.
 *
 * @param {string} url URL tuyệt đối (đã chuẩn hóa origin).
 * @param {{ filename?: string, bearerToken?: string|null }} [options]
 * @returns {Promise<{ ok: true } | { ok: false, reason: 'html' | 'not_pdf', detected?: { ext: string, mime: string } }>}
 */
export async function downloadPdfAttachmentFromUrl(url, options = {}) {
  const { filename = 'document.pdf', bearerToken = null } = options

  let sameOrigin = false
  try {
    sameOrigin = new URL(url, typeof window !== 'undefined' ? window.location.origin : undefined).origin ===
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

  const detected = await fileTypeFromBlob(blob)
  const byMagic = await blobLooksLikePdf(blob)
  const isPdf =
    detected?.mime === 'application/pdf' || detected?.ext === 'pdf' || byMagic

  if (!isPdf) {
    return { ok: false, reason: 'not_pdf', detected }
  }

  saveAs(blob, filename)
  return { ok: true }
}
