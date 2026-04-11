import { http } from './http'

export async function listRoutes(params = {}) {
  const { data } = await http.get('/routes', { params })
  return data.data
}

export async function getRoute(id) {
  const { data } = await http.get(`/routes/${id}`)
  return data.data
}

export async function listStudents(params = {}) {
  const { data } = await http.get('/students', { params })
  return data.data
}
