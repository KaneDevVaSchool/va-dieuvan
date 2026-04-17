import { http } from './http'

export async function listTrips(params = {}) {
  const { data } = await http.get('/trips', { params })
  return data.data
}

export async function getTripStats(params = {}) {
  const { data } = await http.get('/trips/stats', { params })
  return data.data
}

export async function getTrip(id) {
  const { data } = await http.get(`/trips/${id}`)
  return data.data
}

export async function assignTrip(tripId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/trips/${tripId}/assign`, payload, { headers })
  return data.data
}

export async function rescheduleTrip(tripId, payload) {
  const { data } = await http.post(`/trips/${tripId}/reschedule`, payload)
  return data.data
}

export async function updateTripStatus(tripId, payload) {
  const { data } = await http.post(`/trips/${tripId}/status`, payload)
  return data.data
}

export async function addTripEvent(tripId, payload) {
  const { data } = await http.post(`/trips/${tripId}/events`, payload)
  return data.data
}
