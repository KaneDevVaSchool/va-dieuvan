/** Tránh reload vòng lặp khi chunk/build vẫn lỗi sau một lần thử. */
export const CHUNK_RELOAD_SESSION_KEY = 'va-dieuvan:chunk-reload-once'
export const BOOTSTRAP_RELOAD_SESSION_KEY = 'va-dieuvan:bootstrap-reload-once'

export function isStaleChunkMessage(message, error) {
  const msg = message ?? ''
  return (
    /Failed to fetch dynamically imported module/i.test(msg) ||
    /Loading chunk [\w-]+ failed/i.test(msg) ||
    /Importing a module script failed/i.test(msg) ||
    error?.name === 'ChunkLoadError'
  )
}

export async function clearPwaRuntimeCaches() {
  if (!('caches' in window)) return
  const names = await caches.keys()
  await Promise.all(names.map((name) => caches.delete(name)))
}

/**
 * Xóa cache SW, gỡ đăng ký SW (nếu có), tải lại trang — một lần mỗi phiên tab.
 * @param {string} [targetUrl]
 * @returns {Promise<boolean>} true nếu đã kích hoạt reload
 */
export async function reloadOnceForStaleAssets(targetUrl) {
  if (sessionStorage.getItem(CHUNK_RELOAD_SESSION_KEY)) return false
  sessionStorage.setItem(CHUNK_RELOAD_SESSION_KEY, '1')

  await clearPwaRuntimeCaches()

  if ('serviceWorker' in navigator) {
    try {
      const regs = await navigator.serviceWorker.getRegistrations()
      await Promise.all(regs.map((reg) => reg.unregister()))
    } catch {
      /* ignore */
    }
  }

  window.location.assign(targetUrl || window.location.href)
  return true
}

/** Bắt 404 script `/build/assets/*` trước khi Vue boot (entry Vite lỗi thời). */
export function installBootstrapStaleAssetListener() {
  if (typeof window === 'undefined') return

  window.addEventListener(
    'error',
    (event) => {
      const el = event.target
      if (!el || el.tagName !== 'SCRIPT') return
      const src = el.src || ''
      if (!src.includes('/build/assets/')) return
      if (sessionStorage.getItem(BOOTSTRAP_RELOAD_SESSION_KEY)) return
      sessionStorage.setItem(BOOTSTRAP_RELOAD_SESSION_KEY, '1')
      void reloadOnceForStaleAssets(window.location.href)
    },
    true,
  )
}
