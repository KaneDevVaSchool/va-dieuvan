import { http } from './http'

/** Portal-only inbox (auth:sanctum, không cần dispatch.web). */
export async function fetchPortalNotifications(params = {}) {
  const { data } = await http.get('/portal/notifications', { params })
  return data.data
}

export async function markPortalNotificationRead(id) {
  const { data } = await http.post(`/portal/notifications/${id}/read`)
  return data.data
}

export async function markAllPortalNotificationsRead() {
  const { data } = await http.post('/portal/notifications/read-all')
  return data.data
}

export async function fetchNotificationInbox(params = {}) {
  const { per_page = 20, audience, ...rest } = params
  const query = { per_page, ...rest }
  if (audience) {
    query.audience = audience
  }
  const { data } = await http.get('/notifications/inbox', { params: query })
  return data.data
}

export async function markNotificationRead(id) {
  const { data } = await http.post(`/notifications/${id}/read`)
  return data.data
}

export async function markAllNotificationsRead() {
  const { data } = await http.post('/notifications/read-all')
  return data.data
}
