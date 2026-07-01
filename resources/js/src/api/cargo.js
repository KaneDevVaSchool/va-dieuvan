import { http } from './http'

export async function listCargoShipments(params = {}) {
  const { data } = await http.get('/cargo-shipments', { params })
  return data.data
}

export async function getCargoShipment(id) {
  const { data } = await http.get(`/cargo-shipments/${id}`)
  return data.data
}

export async function getCargoShipmentTimeline(id) {
  const { data } = await http.get(`/cargo-shipments/${id}/timeline`)
  return data.data
}

export async function createCargoShipment(payload) {
  const { data } = await http.post('/cargo-shipments', payload)
  return data.data
}

export async function updateCargoStatus(id, payload) {
  const { data } = await http.post(`/cargo-shipments/${id}/status`, payload)
  return data.data
}

/** Tài xế cập nhật trạng thái nhận/giao hàng cho chuyến mình được phân. */
export async function driverUpdateCargoStatus(id, payload) {
  const { data } = await http.post(`/driver/cargo-shipments/${id}/status`, payload)
  return data.data
}

/**
 * Xóa hàng loạt đơn hàng theo bộ lọc danh sách hiện tại.
 * @param {Record<string, unknown> & { permanent: boolean, confirm_phrase: string, expected_count: number }} payload
 */
export async function purgeAllCargoShipments(payload) {
  const { data } = await http.post('/cargo-shipments/purge-all', payload)
  return data.data
}
