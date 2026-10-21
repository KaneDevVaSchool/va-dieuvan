import { http } from './http'

export async function login(payload) {
  const { data } = await http.post('/login', payload)
  return data.data
}

/** Đổi mã một-lần (nhận qua URL sau OAuth) lấy token bearer. */
export async function exchangeOauthCode(code) {
  const { data } = await http.post('/auth/exchange', { code })
  return data.data
}

export async function logout() {
  const { data } = await http.post('/logout')
  return data.data
}

export async function patchProfile(payload) {
  const { data } = await http.patch('/user', payload)
  return data
}

/** Giá trị chọn trong modal giả lập = trải nghiệm Portal người yêu cầu (không vai trò). */
export const IMPERSONATE_PORTAL = '__portal__'

export async function startImpersonation(userId, role) {
  const payload = role === IMPERSONATE_PORTAL ? { portal: true } : { role }
  const { data } = await http.post(`/impersonate/${userId}`, payload)
  return data.data
}

export async function leaveImpersonation() {
  await http.post('/impersonate/leave')
}
