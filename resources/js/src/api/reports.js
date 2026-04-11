import { http } from './http'

export async function getSummary(params = {}) {
  const { data } = await http.get('/reports/summary', { params })
  return data.data
}

