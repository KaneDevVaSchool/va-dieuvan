import { http } from './http'

export async function getAuditLogDetail(id) {
  const { data } = await http.get(`/audit-logs/${id}/detail`)
  return data.data
}
