import { http } from './http'

const PUSH_HTTP_TIMEOUT_MS = 20000

/** Tránh gọi lặp /push/vapid-public-key (nhiều màn driver + thử lại đăng ký push). */
let vapidInflight = null
/** @type {boolean} */
let vapidResolved = false
/** @type {unknown} */
let vapidPayload = null

export async function getVapidPublicKey() {
  if (vapidResolved) {
    return vapidPayload
  }
  if (vapidInflight) {
    return vapidInflight
  }
  vapidInflight = http
    .get('/push/vapid-public-key', { timeout: PUSH_HTTP_TIMEOUT_MS })
    .then(({ data }) => {
      vapidPayload = data.data
      vapidResolved = true
      vapidInflight = null
      return vapidPayload
    })
    .catch((e) => {
      vapidInflight = null
      throw e
    })
  return vapidInflight
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
