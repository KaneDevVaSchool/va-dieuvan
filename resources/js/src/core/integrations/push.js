/**
 * Điểm mở rộng Web Push / FCM — triển khai sau (đăng ký subscription, VAPID).
 * @returns {Promise<PushSubscription | null>}
 */
export async function registerWebPush() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return null
  }
  return null
}
