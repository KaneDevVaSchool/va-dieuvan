import { registerSW } from 'virtual:pwa-register'

/** @type {(reloadPage?: boolean) => Promise<void>} */
let updateSW = async () => {}

/**
 * Đăng ký service worker (prod + dev khi bật devOptions.enabled).
 * Trả về hàm áp dụng bản cập nhật Workbox (gọi sau khi user xác nhận).
 */
export function setupServiceWorker() {
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

/** Kích hoạt SW chờ và tải lại (workbox-window trong virtual:pwa-register). */
export function applyServiceWorkerUpdate() {
  return updateSW(true)
}
