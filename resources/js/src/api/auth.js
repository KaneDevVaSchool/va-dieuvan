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
