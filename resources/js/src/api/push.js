import { http } from './http'

const PUSH_HTTP_TIMEOUT_MS = 20000

export async function getVapidPublicKey() {
  const { data } = await http.get('/push/vapid-public-key', { timeout: PUSH_HTTP_TIMEOUT_MS })
  return data.data
}

/**
 * @param {PushSubscription} subscription — kết quả từ pushManager.subscribe
 */
export async function storePushSubscription(subscription) {
  const j = subscription.toJSON()
  const { data } = await http.post(
    '/push/subscriptions',
    {
      endpoint: j.endpoint,
      keys: j.keys,
      contentEncoding: 'aesgcm',
    },
    { timeout: PUSH_HTTP_TIMEOUT_MS },
  )
  return data.data
}

export async function removePushSubscription(endpoint) {
  const { data } = await http.delete('/push/subscriptions', {
    data: endpoint ? { endpoint } : {},
  })
  return data.data
}
