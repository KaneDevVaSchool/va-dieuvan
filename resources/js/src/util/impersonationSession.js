import { TOKEN_KEY } from '../core/config/authKeys'

const ORIGINAL_TOKEN_KEY = 'va_impersonator_token'
let restoring = false

export function getImpersonatorToken() {
  return localStorage.getItem(ORIGINAL_TOKEN_KEY)
}

export function stashImpersonatorToken(token) {
  if (token) localStorage.setItem(ORIGINAL_TOKEN_KEY, token)
  else localStorage.removeItem(ORIGINAL_TOKEN_KEY)
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
  restoring = true
  localStorage.setItem(TOKEN_KEY, original)
  stashImpersonatorToken(null)
  clearIdentityCache()
  window.location.replace('/mng/system/user-roles')
  return true
}
