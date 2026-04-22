import { logger } from './logger'

export function bootstrapMonitoring() {
  if (typeof window === 'undefined') return

  window.addEventListener('error', (ev) => {
    logger.error('window.error', {
      message: ev.message,
      filename: ev.filename,
      lineno: ev.lineno,
      colno: ev.colno,
    })
  })

  window.addEventListener('unhandledrejection', (ev) => {
    const r = ev.reason
    logger.error('unhandledrejection', {
      message: r && typeof r === 'object' && 'message' in r ? String(r.message) : String(r),
    })
  })
}
