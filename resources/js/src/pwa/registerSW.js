import { registerSW } from 'virtual:pwa-register'
import { Workbox } from 'workbox-window'

/** @type {(reloadPage?: boolean) => Promise<void>} */
let updateSW = async () => {}

let pwaUpdatePending = false

const DESKTOP_AUTO_UPDATE_MQ = '(min-width: 1024px)'

export function takePendingPwaUpdate() {
  const v = pwaUpdatePending
  pwaUpdatePending = false
  return v
}

function isDesktopViewport() {
  return typeof window !== 'undefined' && window.matchMedia(DESKTOP_AUTO_UPDATE_MQ).matches
}

function notifyPwaUpdateAvailable() {
  pwaUpdatePending = true
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('pwa:update-available'))
  }
}

async function tryAutoApplyPendingUpdate() {
  try {
    await applyServiceWorkerUpdate()
  } catch {
    if (isDesktopViewport()) {
      window.location.reload()
    }
  }
}

function onServiceWorkerUpdateDetected() {
  notifyPwaUpdateAvailable()
  if (isDesktopViewport()) {
    void tryAutoApplyPendingUpdate()
  }
}

/** @param {ServiceWorkerRegistration | undefined | null} reg */
function inspectRegistrationForWaiting(reg) {
  if (reg?.waiting) {
    onServiceWorkerUpdateDetected()
  }
}

async function pollServiceWorkerUpdate() {
  if (!('serviceWorker' in navigator)) return
  try {
    const reg = await navigator.serviceWorker.getRegistration('/')
    await reg?.update()
    inspectRegistrationForWaiting(reg)
  } catch {
    /* ignore */
  }
}

function attachRegistrationUpdateListener(reg) {
  if (!reg) return
  reg.addEventListener('updatefound', () => {
    const installing = reg.installing
    if (!installing) return
    installing.addEventListener('statechange', () => {
      if (installing.state === 'installed' && navigator.serviceWorker.controller) {
        inspectRegistrationForWaiting(reg)
      }
    })
  })
}

function attachVisibilityUpdateHooks() {
  if (typeof document === 'undefined') return

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void pollServiceWorkerUpdate()
    }
  })

  window.addEventListener('focus', () => {
    void pollServiceWorkerUpdate()
  })

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      void pollServiceWorkerUpdate()
    }
  })
}

/**
 * Đăng ký service worker (prod + dev khi bật devOptions.enabled).
 * Trả về hàm áp dụng bản cập nhật Workbox (gọi sau khi user xác nhận).
 */
export function setupServiceWorker() {
  attachVisibilityUpdateHooks()

  if (import.meta.env.DEV) {
    updateSW = registerSW({
      onNeedRefresh() {
        onServiceWorkerUpdateDetected()
      },
      onOfflineReady() {
        console.info('[PWA] App ready for offline use')
      },
      onRegisteredSW(_swUrl, registration) {
        inspectRegistrationForWaiting(registration)
        attachRegistrationUpdateListener(registration)
        if (registration) {
          setInterval(() => {
            registration.update().catch(() => {})
          }, 1000 * 60 * 15)
        }
      },
    })
    void pollServiceWorkerUpdate()
    return { updateSW }
  }

  if (!('serviceWorker' in navigator)) {
    return { updateSW }
  }

  const wb = new Workbox('/sw.js', { scope: '/', type: 'module' })

  wb.addEventListener('waiting', () => {
    onServiceWorkerUpdateDetected()
  })

  wb.addEventListener('controlling', (event) => {
    if (event.isUpdate) {
      window.location.reload()
    }
  })

  wb.addEventListener('activated', (event) => {
    if (!event.isUpdate) {
      console.info('[PWA] App ready for offline use')
    }
  })

  wb.register()
    .then((registration) => {
      inspectRegistrationForWaiting(registration)
      attachRegistrationUpdateListener(registration)
      if (registration) {
        setInterval(() => {
          registration.update().catch(() => {})
        }, 1000 * 60 * 15)
      }
    })
    .catch((err) => {
      console.warn('[PWA] Service worker registration failed', err)
    })

  void pollServiceWorkerUpdate()

  updateSW = async (reloadPage = true) => {
    if (reloadPage) {
      wb.messageSkipWaiting()
    }
  }

  return { updateSW }
}

/** Kích hoạt SW chờ và tải lại (workbox-window / virtual:pwa-register). */
export function applyServiceWorkerUpdate() {
  return updateSW(true)
}
