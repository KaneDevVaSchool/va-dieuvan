import { http } from './http'

export async function getVapidPublicKey() {
  const { data } = await http.get('/push/vapid-public-key')
  return data.data
}

/**
 * @param {PushSubscription} subscription — kết quả từ pushManager.subscribe
 */
export async function storePushSubscription(subscription) {
  const j = subscription.toJSON()
  const { data } = await http.post('/push/subscriptions', {
    endpoint: j.endpoint,
    keys: j.keys,
    contentEncoding: 'aesgcm',
  })
  return data.data
}

export async function removePushSubscription(endpoint) {
  const { data } = await http.delete('/push/subscriptions', {
    data: endpoint ? { endpoint } : {},
  })
  return data.data
}
