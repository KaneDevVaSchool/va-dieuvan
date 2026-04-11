import { http } from './http'

export async function listAuditLogs(params = {}) {
  const { data } = await http.get('/audit-logs', { params })
  return data.data
}

