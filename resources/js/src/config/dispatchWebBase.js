/** Tiền tố URL cho ứng dụng điều vận (admin / dispatcher). Đăng nhập và tài xế giữ `/login`, `/driver`. */
export const DISPATCH_WEB_BASE = '/mng'

/** Các đường dẫn SPA điều vận (trước đây ở gốc /) — dùng chuyển sang `/mng/...`. */
const LEGACY_STAFF_PREFIXES = [
  '/',
  '/dispatcher',
  '/profile',
  '/requests',
  '/dispatch-requests',
  '/trips',
  '/costs',
  '/cargo',
  '/routes',
  '/resources',
  '/reports',
  '/pricing',
  '/audit-logs',
  '/system',
]

/** Hồ sơ: nhân viên điều vận → dưới /mng; chỉ tài xế → /profile. */
export function profilePathForAuth(canAccessDispatchWebApp) {
  return canAccessDispatchWebApp ? `${DISPATCH_WEB_BASE}/profile` : '/profile'
}

/** true nếu URL gốc (không có /mng) là màn điều vận — cần rewrite cho tài khoản nhân viên. */
export function shouldRewriteLegacyStaffPath(path) {
  if (path === '/' || path === '') return true
  return LEGACY_STAFF_PREFIXES.some((p) => {
    if (p === '/') return false
    return path === p || path.startsWith(`${p}/`)
  })
}

/** `/mng` + `/foo` → `/mng/foo`; `/` → `/mng`. */
export function buildStaffPrefixedPath(path) {
  if (path === '/' || path === '') return DISPATCH_WEB_BASE
  return `${DISPATCH_WEB_BASE}${path}`
}

/** `/mng` hoặc `/mng/`. */
export function isDispatchStaffHomePath(path) {
  return path === DISPATCH_WEB_BASE || path === `${DISPATCH_WEB_BASE}/`
}
