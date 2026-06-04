import { http } from './http'

export async function getSystemDashboard() {
  const { data } = await http.get('/admin/system/dashboard')
  return data.data
}

export async function getPermissionMatrix() {
  const { data } = await http.get('/admin/permissions/matrix')
  return data.data
}

export async function syncPermissionMatrix(changes) {
  const { data } = await http.post('/admin/permissions/sync', { changes })
  return data.data
}

export async function listAssignmentUsers(params = {}) {
  const { data } = await http.get('/admin/assignments/users', { params })
  return data.data
}

export async function assignUserRoles(payload) {
  const { data } = await http.post('/admin/assignments', payload)
  return data.data
}

export async function getMenuMe() {
  const { data } = await http.get('/menu/me')
  return data.data
}

export async function listMenuItems() {
  const { data } = await http.get('/admin/menu')
  return data.data
}

export async function createMenuItem(payload) {
  const { data } = await http.post('/admin/menu', payload)
  return data.data
}

export async function updateMenuItem(id, payload) {
  const { data } = await http.patch(`/admin/menu/${id}`, payload)
  return data.data
}

export async function deleteMenuItem(id) {
  const { data } = await http.delete(`/admin/menu/${id}`)
  return data.data
}

export async function reorderMenuItems(items) {
  const { data } = await http.patch('/admin/menu/reorder', { items })
  return data.data
}

export async function getAuditLogDetail(id) {
  const { data } = await http.get(`/audit-logs/${id}/detail`)
  return data.data
}
