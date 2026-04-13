import { http } from './http'

export async function listVehicles(params = {}) {
  const { data } = await http.get('/vehicles', { params })
  return data.data
}

export async function listDrivers(params = {}) {
  const { data } = await http.get('/drivers', { params })
  return data.data
}

/** @param {number} driverId */
export async function getDriver(driverId) {
  const { data } = await http.get(`/drivers/${driverId}`)
  return data.data
}

/**
 * @param {number} driverId
 * @param {Record<string, unknown>} payload
 */
export async function updateDriver(driverId, payload) {
  const { data } = await http.patch(`/drivers/${driverId}`, payload)
  return data.data
}

/** @param {number} driverId */
export async function listDriverComplianceDocuments(driverId) {
  const { data } = await http.get(`/drivers/${driverId}/compliance-documents`)
  return data.data
}

/** @param {number} driverId */
export async function getDriverComplianceAudit(driverId) {
  const { data } = await http.get(`/drivers/${driverId}/compliance-audit`)
  return data.data
}

/**
 * @param {number} driverId
 * @param {FormData} formData
 */
export async function createDriverComplianceDocument(driverId, formData) {
  const { data } = await http.post(`/drivers/${driverId}/compliance-documents`, formData)
  return data.data
}

/**
 * @param {number} driverId
 * @param {number} docId
 * @param {FormData} formData
 */
export async function updateDriverComplianceDocument(driverId, docId, formData) {
  const { data } = await http.patch(`/drivers/${driverId}/compliance-documents/${docId}`, formData)
  return data.data
}

/**
 * @param {number} driverId
 * @param {number} docId
 */
export async function deleteDriverComplianceDocument(driverId, docId) {
  const { data } = await http.delete(`/drivers/${driverId}/compliance-documents/${docId}`)
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

/** @param {number} vehicleId */
export async function listVehicleComplianceDocuments(vehicleId) {
  const { data } = await http.get(`/vehicles/${vehicleId}/compliance-documents`)
  return data.data
}

/** @param {number} vehicleId */
export async function getVehicleComplianceAudit(vehicleId) {
  const { data } = await http.get(`/vehicles/${vehicleId}/compliance-audit`)
  return data.data
}

/**
 * @param {number} vehicleId
 * @param {FormData} formData
 */
export async function createVehicleComplianceDocument(vehicleId, formData) {
  const { data } = await http.post(`/vehicles/${vehicleId}/compliance-documents`, formData)
  return data.data
}

/**
 * @param {number} vehicleId
 * @param {number} docId
 * @param {FormData} formData
 */
export async function updateVehicleComplianceDocument(vehicleId, docId, formData) {
  const { data } = await http.patch(`/vehicles/${vehicleId}/compliance-documents/${docId}`, formData)
  return data.data
}

/**
 * @param {number} vehicleId
 * @param {number} docId
 */
export async function deleteVehicleComplianceDocument(vehicleId, docId) {
  const { data } = await http.delete(`/vehicles/${vehicleId}/compliance-documents/${docId}`)
  return data.data
}
