import { TOKEN_KEY } from '../core/config/authKeys'

const ORIGINAL_TOKEN_KEY = 'va_impersonator_token'
const RETURN_PATH_KEY = 'va_impersonator_return_path'
const DEFAULT_RETURN_PATH = '/mng/system/user-roles'
let restoring = false

export function getImpersonatorToken() {
  return localStorage.getItem(ORIGINAL_TOKEN_KEY)
}

export function stashImpersonatorToken(token, returnPath) {
  if (token) {
    localStorage.setItem(ORIGINAL_TOKEN_KEY, token)
    localStorage.setItem(RETURN_PATH_KEY, returnPath || DEFAULT_RETURN_PATH)
  } else {
    localStorage.removeItem(ORIGINAL_TOKEN_KEY)
    localStorage.removeItem(RETURN_PATH_KEY)
  }
}

export function getImpersonatorReturnPath() {
  const path = localStorage.getItem(RETURN_PATH_KEY)
  if (!path?.startsWith('/')) return DEFAULT_RETURN_PATH
  try {
    const url = new URL(path, window.location.origin)
    if (url.origin === window.location.origin) return `${url.pathname}${url.search}${url.hash}`
  } catch { /* Fall back for invalid stored paths. */ }
  return DEFAULT_RETURN_PATH
}

export function clearIdentityCache() {
  localStorage.removeItem('vas_user_hint')
  try {
    sessionStorage.removeItem('va_driver_dash_snap_v1')
    sessionStorage.removeItem('va_driver_pending_seen_init')
    sessionStorage.removeItem('va_driver_pending_ids_seen')
  } catch { /* Storage unavailable. */ }
}

export function isRestoringImpersonator() {
  return restoring
}

export function restoreImpersonatorSession() {
  const original = getImpersonatorToken()
  if (!original) return false
  const returnPath = getImpersonatorReturnPath()
  restoring = true
  localStorage.setItem(TOKEN_KEY, original)
  stashImpersonatorToken(null)
  clearIdentityCache()
  window.location.replace(returnPath)
  return true
}
