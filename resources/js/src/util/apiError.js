/**
 * Lỗi API khiến phiên Bearer không còn hợp lệ — nên xóa token.
 * @param {unknown} err
 */
export function isUnauthorizedApiError(err) {
  return err?.response?.status === 401
}

/**
 * Lỗi tạm thời (mạng, timeout) — giữ token, thử khôi phục phiên sau.
 * @param {unknown} err
 */
export function isTransientSessionError(err) {
  if (err?.response) {
    return false
  }
  const code = err?.code
  if (code === 'ERR_NETWORK' || code === 'ECONNABORTED') {
    return true
  }
  if (typeof navigator !== 'undefined' && navigator.onLine === false) {
    return true
  }
  return false
}
