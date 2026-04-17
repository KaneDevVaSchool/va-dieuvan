import { reactive } from 'vue'

export const confirmDialogState = reactive({
  open: false,
  title: 'Xác nhận',
  message: '',
  confirmLabel: 'Đồng ý',
  cancelLabel: 'Hủy',
  danger: false,
  /** @type {null | ((v: boolean) => void)} */
  resolve: null,
})

/**
 * @param {{ title?: string, message: string, confirmLabel?: string, cancelLabel?: string, danger?: boolean }} opts
 * @returns {Promise<boolean>}
 */
export function confirmAction(opts) {
  return new Promise((resolve) => {
    confirmDialogState.title = opts.title ?? 'Xác nhận'
    confirmDialogState.message = opts.message ?? ''
    confirmDialogState.confirmLabel = opts.confirmLabel ?? 'Đồng ý'
    confirmDialogState.cancelLabel = opts.cancelLabel ?? 'Hủy'
    confirmDialogState.danger = !!opts.danger
    confirmDialogState.resolve = resolve
    confirmDialogState.open = true
  })
}

export function resolveConfirm(ok) {
  const fn = confirmDialogState.resolve
  confirmDialogState.open = false
  confirmDialogState.resolve = null
  if (fn) fn(ok)
}
