import { reactive } from 'vue'

/** @typedef {'error' | 'success' | 'info'} AppMessageVariant */

export const appMessageState = reactive({
  open: false,
  title: '',
  body: '',
  /** @type {AppMessageVariant} */
  variant: 'error',
})

export function closeAppMessage() {
  appMessageState.open = false
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showAppError(message, title = 'Có lỗi') {
  appMessageState.title = title
  appMessageState.body = message || 'Có lỗi xảy ra.'
  appMessageState.variant = 'error'
  appMessageState.open = true
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showAppSuccess(message, title = 'Thành công') {
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
  appMessageState.title = title
  appMessageState.body = message || ''
  appMessageState.variant = 'info'
  appMessageState.open = true
}
