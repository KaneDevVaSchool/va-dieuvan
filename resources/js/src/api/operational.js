import { http } from './http'

export async function listVehicles(params = {}) {
  const { data } = await http.get('/vehicles', { params })
  return data.data
}

export async function getVehicleScheduleConflicts(vehicleId, params = {}) {
  const { data } = await http.get(`/vehicles/${vehicleId}/conflicts`, { params })
  return data.data
}

/** @param {number} vehicleId */
export async function restoreVehicle(vehicleId) {
  const { data } = await http.post(`/vehicles/${vehicleId}/restore`)
  return data.data
}

/** @param {number} vehicleId */
export async function forceDeleteVehicle(vehicleId) {
  const { data } = await http.delete(`/vehicles/${vehicleId}/force`)
  return data.data
}

/** @param {number[]} ids */
export async function bulkDeleteVehicles(ids) {
  const { data } = await http.post('/vehicles/bulk-delete', { ids })
  return data.data
}

/** @param {number[]} ids — chỉ bản ghi đang ở thùng rác */
export async function bulkForceDeleteVehicles(ids) {
  const { data } = await http.post('/vehicles/bulk-force-delete', { ids })
  return data.data
}

export async function listDrivers(params = {}) {
  const { data } = await http.get('/drivers', { params })
  return data.data
}

/**
 * Tài xế không gắn user (ngoài hệ thống). Chỉ cần full_name; các trường khác tùy chọn.
 * @param {Record<string, unknown>} payload
 */
export async function createDriver(payload) {
  const { data } = await http.post('/drivers', payload)
  return data.data
}

/** @param {number} driverId */
export async function deleteDriver(driverId) {
  const { data } = await http.delete(`/drivers/${driverId}`)
  return data.data
}

/** @param {number} driverId */
export async function restoreDriver(driverId) {
  const { data } = await http.post(`/drivers/${driverId}/restore`)
  return data.data
}

/** @param {number} driverId */
export async function forceDeleteDriver(driverId) {
  const { data } = await http.delete(`/drivers/${driverId}/force`)
  return data.data
}

/** @param {number[]} ids */
export async function bulkDeleteDrivers(ids) {
  const { data } = await http.post('/drivers/bulk-delete', { ids })
  return data.data
}

/** @param {number[]} ids — chỉ bản ghi đang ở thùng rác */
export async function bulkForceDeleteDrivers(ids) {
  const { data } = await http.post('/drivers/bulk-force-delete', { ids })
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

/** @param {number} id */
export async function deleteTransportProvider(id) {
  const { data } = await http.delete(`/transport-providers/${id}`)
  return data.data
}

/** @param {number} id */
export async function restoreTransportProvider(id) {
  const { data } = await http.post(`/transport-providers/${id}/restore`)
  return data.data
}

/** @param {number} id */
export async function forceDeleteTransportProvider(id) {
  const { data } = await http.delete(`/transport-providers/${id}/force`)
  return data.data
}

/** @param {number[]} ids */
export async function bulkDeleteTransportProviders(ids) {
  const { data } = await http.post('/transport-providers/bulk-delete', { ids })
  return data.data
}

/** @param {number[]} ids — chỉ bản ghi đang ở thùng rác */
export async function bulkForceDeleteTransportProviders(ids) {
  const { data } = await http.post('/transport-providers/bulk-force-delete', { ids })
  return data.data
}

/** @param {string} q */
export async function searchUsersForDriverAssignment(q) {
  const { data } = await http.get('/users/for-driver-assignment', { params: { q } })
  return data.data
}

/** Tìm nhân sự theo tên/email (form tạo yêu cầu điều vận — mọi user đã đăng nhập). @param {string} q */
export async function searchUsersForDispatchForm(q) {
  const { data } = await http.get('/users/for-dispatch-form', { params: { q } })
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
export async function deleteVehicle(vehicleId) {
  const { data } = await http.delete(`/vehicles/${vehicleId}`)
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
