/**
 * Post-login redirect từ query `redirect` hoặc 401 interceptor.
 * Chặn lồng URL OAuth / callback Google (hay gặp khi SW intercept navigation sai).
 *
 * @param {unknown} raw
 * @returns {string}
 */
export function sanitizeLoginRedirect(raw) {
  if (raw == null || typeof raw !== 'string') {
    return '/'
  }
  const s = raw.trim()
  if (s === '') {
    return '/'
  }
  if (s.length > 512) {
    return '/'
  }

  /** @type {string} */
  let pathWithQuery

  if (/^https?:\/\//i.test(s)) {
    try {
      const u = new URL(s)
      if (typeof window !== 'undefined' && u.origin !== window.location.origin) {
        return '/'
      }
      pathWithQuery = u.pathname + u.search
    } catch {
      return '/'
    }
  } else {
    pathWithQuery = s.startsWith('/') ? s : `/${s}`
  }

  if (pathWithQuery.startsWith('/auth/google')) {
    return '/'
  }

  const qIndex = pathWithQuery.indexOf('?')
  if (qIndex !== -1) {
    const qs = pathWithQuery.slice(qIndex + 1)
    try {
      const sp = new URLSearchParams(qs)
      if (sp.has('code') || sp.has('state')) {
        return '/'
      }
    } catch {
      return '/'
    }
  }

  return pathWithQuery || '/'
}
