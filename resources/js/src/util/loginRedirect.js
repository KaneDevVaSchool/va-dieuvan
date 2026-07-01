import { DISPATCH_WEB_BASE, isDispatchStaffHomePath } from '../config/dispatchWebBase'

/**
 * Post-login redirect từ query `redirect`, 401 interceptor, hoặc session Laravel.
 * Chặn lồng /auth/google (kể cả khi %-encode chồng như …/redirect=/auth/google?redirect=%252F).
 *
 * @param {unknown} raw
 * @returns {string}
 */
export function sanitizeLoginRedirect(raw) {
  let initial = ''

  if (raw == null) {
    initial = ''
  } else if (Array.isArray(raw)) {
    initial = raw.length ? String(raw[0]).trim() : ''
  } else if (typeof raw === 'string') {
    initial = raw.trim()
  } else {
    initial = String(raw).trim()
  }

  if (initial === '') {
    return '/'
  }
  if (initial.length > 512) {
    return '/'
  }

  /** @type {string} */
  let s = initial.replace(/\+/g, ' ')

  try {
    for (let i = 0; i < 14; i++) {
      const d = decodeURIComponent(s)
      if (d === s) break
      s = d
      if (s.length > 768) {
        return '/'
      }
    }
  } catch {
    return '/'
  }

  s = s.trim()
  if (s === '') {
    return '/'
  }

  if (/^https?:\/\//i.test(s)) {
    try {
      const u = new URL(s)
      if (typeof window !== 'undefined' && u.origin !== window.location.origin) {
        return '/'
      }
      s = `${u.pathname || '/'}${u.search || ''}`
    } catch {
      return '/'
    }
  }

  if (!s.startsWith('/')) {
    s = `/${s}`
  }

  const low = s.toLowerCase()
  if (
    low.includes('/auth/google') ||
    low.includes('%2fauth%2fgoogle') ||
    low.includes('%2fauth/')
  ) {
    return '/'
  }

  const qIndex = s.indexOf('?')
  if (qIndex !== -1) {
    const qs = s.slice(qIndex + 1)
    try {
      const sp = new URLSearchParams(qs)
      if (sp.has('code') || sp.has('state')) {
        return '/'
      }
    } catch {
      return '/'
    }
  }

  if (s.length > 512) {
    return '/'
  }

  return s || '/'
}

/**
 * @param {{ canAccessDispatchWebApp: () => boolean, canAccessDriverWebApp: () => boolean, isDeptHeadOnly: () => boolean }} auth
 */
function defaultPostLoginHome(auth) {
  if (!auth.canAccessDispatchWebApp() && !auth.canAccessDriverWebApp()) {
    return '/portal'
  }
  if (!auth.canAccessDispatchWebApp() && auth.canAccessDriverWebApp()) {
    return '/driver'
  }
  if (auth.isDeptHeadOnly()) {
    return '/dept'
  }
  return DISPATCH_WEB_BASE
}

/**
 * Đích sau OAuth / login: sanitize `redirect`, không để `/` hoặc `/login` (vẫn là màn đăng nhập).
 *
 * @param {{ canAccessDispatchWebApp: () => boolean, canAccessDriverWebApp: () => boolean, isDeptHeadOnly: () => boolean }} auth
 * @param {unknown} rawRedirect
 * @returns {string}
 */
export function resolvePostLoginTarget(auth, rawRedirect) {
  if (!auth.canAccessDispatchWebApp() && !auth.canAccessDriverWebApp()) {
    return '/portal'
  }

  let target = sanitizeLoginRedirect(
    rawRedirect != null && rawRedirect !== '' ? rawRedirect : '/',
  )

  if (target === '/' || target === '/login') {
    return defaultPostLoginHome(auth)
  }

  if (!auth.canAccessDispatchWebApp() && auth.canAccessDriverWebApp()) {
    if (target === '/profile' || target.startsWith('/profile/')) {
      return '/driver/account'
    }
    if (!target.startsWith('/driver')) {
      return '/driver'
    }
    return target
  }

  if (auth.canAccessDispatchWebApp() && auth.isDeptHeadOnly()) {
    if (target === '/mng' || isDispatchStaffHomePath(target)) {
      return '/dept'
    }
    if (target.startsWith('/mng/')) {
      const rm = target.match(/^\/mng\/requests\/(\d+)/)
      return rm ? `/dept/requests/${rm[1]}` : '/dept'
    }
  }

  return target
}

/**
 * Query chuẩn cho `/login`: chỉ token, error, redirect (đã sanitize).
 * @param {Record<string, unknown> | undefined} q
 */
export function normalizeLoginRouteQuery(q) {
  /** @type {Record<string, string>} */
  const o = {}
  if (!q || typeof q !== 'object') {
    return o
  }
  const t = q.token
  if (t != null && String(t).trim() !== '') {
    o.token = String(Array.isArray(t) ? t[0] : t).trim()
  }
  const c = q.code
  if (c != null && String(c).trim() !== '') {
    o.code = String(Array.isArray(c) ? c[0] : c).trim()
  }
  const e = q.error
  if (e != null && String(e).trim() !== '') {
    o.error = String(Array.isArray(e) ? e[0] : e).trim()
  }
  const redir = sanitizeLoginRedirect(q.redirect ?? '/')
  if (redir !== '/') {
    o.redirect = redir
  }
  return o
}

/**
 * `redirect` trong query đang không an toàn / bị encode chồng → cần replace URL.
 * @param {Record<string, unknown> | undefined} q
 */
export function loginRouteNeedsSanitizeReplace(q) {
  if (!q || typeof q !== 'object' || q.redirect == null) {
    return false
  }
  const raw = Array.isArray(q.redirect) ? q.redirect[0] : q.redirect
  const str = String(raw)
  if (str.trim() === '') {
    return false
  }
  return sanitizeLoginRedirect(str) !== str
}
