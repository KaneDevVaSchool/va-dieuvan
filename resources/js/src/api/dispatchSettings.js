import { http } from './http'

/** Ngưỡng gấp (giờ) cho form BM.03 — trùng khớp đọc của backend cho auto Gấp. */
export async function getDispatchFormSettings() {
  const { data } = await http.get('/dispatch-form-settings')
  return data.data
}

/** Trang quản trị ngưỡng — cần `dispatch.settings.manage`. */
export async function getAdminDispatchSettings() {
  const { data } = await http.get('/admin/dispatch-settings')
  return data.data
}

/** @param {{ passenger_urgent_threshold_hours: number, cargo_urgent_threshold_hours: number }} payload */
export async function updateDispatchSettings(payload) {
  const { data } = await http.put('/admin/dispatch-settings', payload)
  return data.data
}
