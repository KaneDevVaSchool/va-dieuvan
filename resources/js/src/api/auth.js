import { http } from './http'

export async function login(payload) {
  const { data } = await http.post('/login', payload)
  return data.data
}

export async function logout() {
  const { data } = await http.post('/logout')
  return data.data
}
