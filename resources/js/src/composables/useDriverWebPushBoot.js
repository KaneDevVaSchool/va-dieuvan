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

  return { bootDriverOutboundNotifications }
}
