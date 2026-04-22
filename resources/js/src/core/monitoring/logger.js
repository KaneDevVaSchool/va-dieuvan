import { APP_CONFIG } from '../config/appConfig'

function shouldLog(level) {
  if (import.meta.env.DEV) return true
  return level === 'warn' || level === 'error'
}

function sendRemote(level, msg, context) {
  if (!import.meta.env.PROD || typeof fetch === 'undefined') return
  try {
    const body = JSON.stringify({
      level,
      message: msg,
      context: context || {},
      t: Date.now(),
      href: typeof location !== 'undefined' ? location.href : '',
    })
    fetch(APP_CONFIG.telemetryEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body,
      keepalive: true,
    }).catch(() => {})
  } catch {
    /* ignore */
  }
}

export const logger = {
  /**
   * @param {string} msg
   * @param {Record<string, unknown>} [ctx]
   */
  debug(msg, ctx) {
    if (shouldLog('debug')) console.debug(`[va-dispatch] ${msg}`, ctx || '')
  },
  info(msg, ctx) {
    if (shouldLog('info')) console.info(`[va-dispatch] ${msg}`, ctx || '')
  },
  warn(msg, ctx) {
    console.warn(`[va-dispatch] ${msg}`, ctx || '')
    sendRemote('warn', msg, ctx)
  },
  error(msg, ctx) {
    console.error(`[va-dispatch] ${msg}`, ctx || '')
    sendRemote('error', msg, ctx)
  },
}

/** Gắn Sentry / OpenTelemetry tại đây khi deploy */
export function initIntegrationsFromEnv() {}
