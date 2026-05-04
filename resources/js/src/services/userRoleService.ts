import { http } from '../api/http'

export interface UserListMeta {
  total: number
  current_page: number
  last_page: number
  per_page: number
  per_page_mode: string
  truncated: boolean
  cap: number | null
}

export interface RoleDto {
  id: number
  name: string
  display_name?: string | null
  guard_name?: string
  permissions_count?: number
  category?: string
}

export interface UserDto {
  id: number
  name: string
  email: string
  employee_code?: string | null
  roles?: RoleDto[]
}

export async function fetchUsersForAssignment(params: Record<string, unknown>): Promise<{
  items: UserDto[]
  meta: UserListMeta
}> {
  const { data } = await http.get<{ data: { items: UserDto[]; meta: UserListMeta } }>('/admin/users', {
    params,
  })
  return data.data
}

export async function fetchRolesWithCategories(): Promise<RoleDto[]> {
  const { data } = await http.get<{ data: RoleDto[] }>('/admin/roles')
  return data.data
}

export async function syncUserRoleIds(userId: number, roleIds: number[]): Promise<unknown> {
  const { data } = await http.put(`/admin/users/${userId}/roles`, { role_ids: roleIds })
  return data.data
}

export async function bulkUpdateUserRoles(payload: {
  user_ids: number[]
  roles: string[]
  action: 'assign' | 'remove'
}): Promise<{ data: null; message: string; errors: null }> {
  const { data } = await http.post<{ data: null; message: string; errors: null }>(
    '/v1/users/roles/bulk-update',
    payload,
  )
  return data
}
