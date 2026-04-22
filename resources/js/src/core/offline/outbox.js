import { APP_CONFIG } from '../config/appConfig'
import { TOKEN_KEY } from '../config/authKeys'
import { buildAbsoluteApiUrl } from '../http/apiUrl'
import { openVaDb } from './idb'
import { logger } from '../monitoring/logger'

const MUTABLE = new Set(['post', 'put', 'patch', 'delete'])

function serializeHeaders(config) {
  const headers = {}
  const raw = config.headers || {}
  if (typeof raw.toJSON === 'function') {
    Object.assign(headers, raw.toJSON())
  } else if (typeof raw === 'object') {
    for (const [k, v] of Object.entries(raw)) {
      if (v == null) continue
      const key = String(k)
      if (key.toLowerCase() === 'authorization') continue
      headers[key] = typeof v === 'string' ? v : String(v)
    }
  }
  return headers
}

/**
 * @param {import('axios').InternalAxiosRequestConfig} config
 */
export async function enqueueOutboxRequest(config) {
  if (!config?.url || !MUTABLE.has(String(config.method || 'get').toLowerCase())) return
  if (config.headers?.['X-Skip-Outbox']) return
  if (typeof FormData !== 'undefined' && config.data instanceof FormData) return
  if (typeof Blob !== 'undefined' && config.data instanceof Blob) return

  const db = await openVaDb()
  const tx = db.transaction(APP_CONFIG.outboxStore, 'readwrite')
  const store = tx.objectStore(APP_CONFIG.outboxStore)

  const token = typeof localStorage !== 'undefined' ? localStorage.getItem(TOKEN_KEY) : null
  const record = {
    method: String(config.method || 'post').toUpperCase(),
    url: buildAbsoluteApiUrl(config),
    body:
      typeof config.data === 'string'
        ? config.data
        : config.data != null
          ? JSON.stringify(config.data)
          : null,
    headers: serializeHeaders(config),
    tokenSnapshot: token,
    createdAt: Date.now(),
  }

  await new Promise((resolve, reject) => {
    const r = store.add(record)
    r.onsuccess = () => resolve()
    r.onerror = () => reject(r.error)
  })

  logger.info('outbox.enqueued', { url: record.url, method: record.method })
  requestBackgroundSync()
}

async function requestBackgroundSync() {
  try {
    const reg = await navigator.serviceWorker?.ready
    if (reg && 'sync' in reg && reg.sync) {
      await reg.sync.register('va-outbox-sync')
    }
  } catch {
    /* ignore */
  }
}

export async function flushOutbox() {
  const db = await openVaDb()
  const items = await new Promise((resolve, reject) => {
    const tx = db.transaction(APP_CONFIG.outboxStore, 'readonly')
    const r = tx.objectStore(APP_CONFIG.outboxStore).getAll()
    r.onsuccess = () => resolve(r.result || [])
    r.onerror = () => reject(r.error)
  })

  for (const row of items) {
    const token = localStorage.getItem(TOKEN_KEY) || row.tokenSnapshot
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(row.headers && typeof row.headers === 'object' ? row.headers : {}),
    }
    if (token) headers.Authorization = `Bearer ${token}`

    try {
      const res = await fetch(row.url, {
        method: row.method,
        headers,
        body: row.method === 'GET' || row.method === 'HEAD' ? undefined : row.body,
      })
      if (res.ok) {
        await new Promise((resolve, reject) => {
          const tx2 = db.transaction(APP_CONFIG.outboxStore, 'readwrite')
          const d = tx2.objectStore(APP_CONFIG.outboxStore).delete(row.id)
          d.onsuccess = () => resolve()
          d.onerror = () => reject(d.error)
        })
        logger.info('outbox.flushed', { id: row.id })
      }
    } catch (e) {
      logger.warn('outbox.flush_failed', { id: row.id, message: String(e?.message || e) })
    }
  }
}

export function attachOnlineFlush() {
  if (typeof window === 'undefined') return
  window.addEventListener('online', () => {
    flushOutbox().catch(() => {})
  })
}
