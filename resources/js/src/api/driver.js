import { http } from './http'

/** @returns {Promise<object>} Tổng hợp bối cảnh tài xế (xe, số liệu, bảo trì) */
export async function getDriverSummary() {
  const { data } = await http.get('/driver/summary')
  return data.data
}
