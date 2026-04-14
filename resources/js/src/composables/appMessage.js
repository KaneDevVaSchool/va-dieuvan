import { reactive } from 'vue'
import { buildApiErrorPresentation } from '../api/http'

/** @typedef {'error' | 'success' | 'info'} AppMessageVariant */

export const appMessageState = reactive({
  open: false,
  title: '',
  body: '',
  /** @type {AppMessageVariant} */
  variant: 'error',
  /** Chi tiết kỹ thuật (HTTP, endpoint) — chỉ dùng khi variant === 'error' */
  /** @type {null | { status: number|null, method: string, path: string, retryAfter: string|null, serverRaw: string, networkHint: string, axiosCode?: string|null, axiosMessage?: string|null, configMissing?: boolean }} */
  apiDetails: null,
})

export function closeAppMessage() {
  appMessageState.open = false
  appMessageState.apiDetails = null
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showAppError(message, title = 'Có lỗi') {
  appMessageState.apiDetails = null
  appMessageState.title = title
  appMessageState.body = message || 'Có lỗi xảy ra.'
  appMessageState.variant = 'error'
  appMessageState.open = true
}

/**
 * Hiển thị lỗi API trong modal: tiêu đề theo mã HTTP, nội dung thân thiện + khối chi tiết (method, path, Retry-After).
 * @param {unknown} err
 * @param {string} [fallback]
 */
export function showAppErrorFromApi(err, fallback = 'Đã xảy ra lỗi.') {
  const p = buildApiErrorPresentation(err, fallback)
  appMessageState.title = p.title
  appMessageState.body = p.friendly
  appMessageState.apiDetails = p.details
  appMessageState.variant = 'error'
  appMessageState.open = true
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showAppSuccess(message, title = 'Thành công') {
  appMessageState.apiDetails = null
  appMessageState.title = title
  appMessageState.body = message || 'Đã xử lý.'
  appMessageState.variant = 'success'
  appMessageState.open = true
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showAppInfo(message, title = 'Thông báo') {
  appMessageState.apiDetails = null
  appMessageState.title = title
  appMessageState.body = message || ''
  appMessageState.variant = 'info'
  appMessageState.open = true
}
