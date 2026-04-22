import { APP_CONFIG } from '../config/appConfig'

/**
 * @returns {Promise<IDBDatabase>}
 */
export function openVaDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(APP_CONFIG.idbName, APP_CONFIG.idbVersion)
    req.onerror = () => reject(req.error)
    req.onupgradeneeded = () => {
      const db = req.result
      if (!db.objectStoreNames.contains(APP_CONFIG.outboxStore)) {
        db.createObjectStore(APP_CONFIG.outboxStore, { keyPath: 'id', autoIncrement: true })
      }
    }
    req.onsuccess = () => resolve(req.result)
  })
}
