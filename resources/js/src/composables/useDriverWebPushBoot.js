import { useNotificationStore } from '../store/notificationCenter'

/**
 * Đăng ký Web Push (prod) sau khi có quyền thông báo — gọi từ onMounted các màn driver (PWA).
 */
export function useDriverWebPushBoot() {
  const notifStore = useNotificationStore()

  async function bootDriverOutboundNotifications() {
    await notifStore.requestBrowserNotificationPermission()
    if (
      typeof Notification !== 'undefined' &&
      Notification.permission === 'granted' &&
      import.meta.env.PROD
    ) {
      await notifStore.registerWebPush()
    }
  }

  /**
   * Gọi từ nút bấm (user gesture) — trên iOS thường cần để xin quyền thông báo.
   * @returns {Promise<{ ok: boolean, reason?: string }>}
   */
  async function enableDriverPushFromButton() {
    await notifStore.requestBrowserNotificationPermission()
    if (typeof Notification === 'undefined') {
      return { ok: false, reason: 'api' }
    }
    if (Notification.permission === 'denied') {
      return { ok: false, reason: 'denied' }
    }
    if (Notification.permission === 'granted' && import.meta.env.PROD) {
      return notifStore.registerWebPush()
    }
    if (Notification.permission === 'granted') {
      return { ok: false, reason: 'unsupported' }
    }
    return { ok: false, reason: 'api' }
  }

  return { bootDriverOutboundNotifications, enableDriverPushFromButton }
}
