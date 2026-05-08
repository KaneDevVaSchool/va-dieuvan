/**
 * Nén ảnh JPEG/PNG/WebP trước khi upload (mobile/PWA).
 * @param {File} file
 * @param {{ maxEdge?: number, quality?: number, mime?: string }} opts
 * @returns {Promise<File>}
 */
export async function compressImageFile(file, opts = {}) {
  const maxEdge = opts.maxEdge ?? 1920
  const quality = opts.quality ?? 0.82
  const outMime = opts.mime ?? 'image/jpeg'

  if (!file?.type?.startsWith?.('image/')) return file
  if (file.type === 'image/gif') return file

  let bmp
  try {
    bmp = await createImageBitmap(file)
  } catch {
    return file
  }

  const { width: w0, height: h0 } = bmp
  const scale = Math.min(1, maxEdge / Math.max(w0, h0))
  const w = Math.max(1, Math.round(w0 * scale))
  const h = Math.max(1, Math.round(h0 * scale))

  const canvas = document.createElement('canvas')
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d')
  if (!ctx) {
    bmp.close?.()
    return file
  }
  ctx.drawImage(bmp, 0, 0, w, h)
  bmp.close?.()

  const blob = await new Promise((resolve) =>
    canvas.toBlob((b) => resolve(b || null), outMime, quality),
  )
  if (!blob || blob.size >= file.size) return file

  const base = file.name.replace(/\.[^.]+$/, '') || 'receipt'
  const ext = outMime === 'image/png' ? 'png' : 'jpg'
  return new File([blob], `${base}.${ext}`, { type: outMime })
}
