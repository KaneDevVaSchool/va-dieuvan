import { registerSW } from 'virtual:pwa-register'
import { Workbox } from 'workbox-window'

/** @type {(reloadPage?: boolean) => Promise<void>} */
let updateSW = async () => {}

/**
 * Đăng ký service worker (prod + dev khi bật devOptions.enabled).
 * Trả về hàm áp dụng bản cập nhật Workbox (gọi sau khi user xác nhận).
 */
export function setupServiceWorker() {
  if (import.meta.env.DEV) {
    updateSW = registerSW({
      onNeedRefresh() {
        window.dispatchEvent(new CustomEvent('pwa:update-available'))
      },
      onOfflineReady() {
        console.info('[PWA] App ready for offline use')
      },
      onRegisteredSW(_swUrl, registration) {
        if (registration) {
          setInterval(() => {
            registration.update().catch(() => {})
          }, 1000 * 60 * 60)
        }
      },
    })
    return { updateSW }
  }

  if (!('serviceWorker' in navigator)) {
    return { updateSW }
  }

  const wb = new Workbox('/sw.js', { scope: '/', type: 'module' })

  wb.addEventListener('waiting', () => {
    window.dispatchEvent(new CustomEvent('pwa:update-available'))
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

  wb.register().then((registration) => {
    if (registration) {
      setInterval(() => {
        registration.update().catch(() => {})
      }, 1000 * 60 * 60)
    }
  })

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
