import { http } from './http'

export async function fetchNavBadges() {
  const { data } = await http.get('/nav/badges')
  return data.data
}
