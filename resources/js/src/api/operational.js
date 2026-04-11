import { http } from './http'

export async function listVehicles(params = {}) {
  const { data } = await http.get('/vehicles', { params })
  return data.data
}

export async function listDrivers(params = {}) {
  const { data } = await http.get('/drivers', { params })
  return data.data
}
