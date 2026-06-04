import { http } from './http'

export async function listRoutes(params = {}) {
  const { data } = await http.get('/routes', { params })
  return data.data
}

export async function getRoute(id) {
  const { data } = await http.get(`/routes/${id}`)
  return data.data
}

/** POST /routes — tạo tuyến D2D (dùng cho P2P sau khi tạo cần thêm phiên bản/điểm dừng ở màn Tuyến D2D). */
export async function createRoute(payload) {
  const { data } = await http.post('/routes', payload)
  return data.data
}
