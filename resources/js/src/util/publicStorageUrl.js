/**
 * Chuẩn hóa URL public disk (/storage/...) từ API: APP_URL có thể khác host trang hiện tại.
 * @param {string|null|undefined} url
 * @returns {string|null}
 */
export function resolvePublicStorageUrl(url) {
  const u = String(url ?? '').trim()
  if (!u) return null
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
    return u
  }
}
