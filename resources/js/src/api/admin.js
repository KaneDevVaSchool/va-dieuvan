import { http } from './http'

export async function listRoles() {
  const { data } = await http.get('/admin/roles')
  return data.data
}

export async function getRole(id) {
  const { data } = await http.get(`/admin/roles/${id}`)
  return data.data
}

export async function createRole(payload) {
  const { data } = await http.post('/admin/roles', payload)
  return data.data
}

export async function updateRole(id, payload) {
  const { data } = await http.put(`/admin/roles/${id}`, payload)
  return data.data
}

export async function deleteRole(id) {
  const { data } = await http.delete(`/admin/roles/${id}`)
  return data.data
}

export async function listPermissions() {
  const { data } = await http.get('/admin/permissions')
  return data.data
}

export async function createPermission(payload) {
  const { data } = await http.post('/admin/permissions', payload)
  return data.data
}

export async function updatePermission(id, payload) {
  const { data } = await http.put(`/admin/permissions/${id}`, payload)
  return data.data
}

export async function deletePermission(id) {
  const { data } = await http.delete(`/admin/permissions/${id}`)
  return data.data
}

export async function searchUsers(q) {
  const { data } = await http.get('/admin/users/search', { params: { q } })
  return data.data
}

export async function getUserRoles(userId) {
  const { data } = await http.get(`/admin/users/${userId}/roles`)
  return data.data
}

export async function syncUserRoles(userId, roleIds) {
  const { data } = await http.put(`/admin/users/${userId}/roles`, { role_ids: roleIds })
  return data.data
}

export async function listFeatureToggles() {
  const { data } = await http.get('/admin/feature-toggles')
  return data.data
}

export async function createFeatureToggle(payload) {
  const { data } = await http.post('/admin/feature-toggles', payload)
  return data.data
}

export async function updateFeatureToggle(id, payload) {
  const { data } = await http.put(`/admin/feature-toggles/${id}`, payload)
  return data.data
}

export async function deleteFeatureToggle(id) {
  const { data } = await http.delete(`/admin/feature-toggles/${id}`)
  return data.data
}
