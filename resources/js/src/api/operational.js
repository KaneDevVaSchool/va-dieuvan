import { http } from './http'

export async function listVehicles(params = {}) {
  const { data } = await http.get('/vehicles', { params })
  return data.data
}

export async function listDrivers(params = {}) {
  const { data } = await http.get('/drivers', { params })
  return data.data
}

export async function listTransportProviders(params = {}) {
  const { data } = await http.get('/transport-providers', { params })
  return data.data
}

/** @param {Record<string, unknown>} payload */
export async function createTransportProvider(payload) {
  const { data } = await http.post('/transport-providers', payload)
  return data.data
}

/**
 * @param {number} id
 * @param {Record<string, unknown>} payload
 */
export async function updateTransportProvider(id, payload) {
  const { data } = await http.patch(`/transport-providers/${id}`, payload)
  return data.data
}

/** @param {string} q */
export async function searchUsersForDriverAssignment(q) {
  const { data } = await http.get('/users/for-driver-assignment', { params: { q } })
  return data.data
}

/** @param {number} userId */
export async function createDriverFromUser(userId) {
  const { data } = await http.post('/drivers/from-user', { user_id: userId })
  return data.data
}

/**
 * @param {Record<string, unknown>} payload
 */
export async function createVehicle(payload) {
  const { data } = await http.post('/vehicles', payload)
  return data.data
}

/**
 * @param {number} vehicleId
 * @param {Record<string, unknown>} payload
 */
export async function updateVehicle(vehicleId, payload) {
  const { data } = await http.patch(`/vehicles/${vehicleId}`, payload)
  return data.data
}
