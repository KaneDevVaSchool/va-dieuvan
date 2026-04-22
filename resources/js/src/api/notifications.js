import { http } from './http'

export async function fetchNotificationInbox(params = {}) {
  const { data } = await http.get('/notifications/inbox', { params })
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
