import { http } from './http'

export async function listTripCosts(params = {}) {
  const { data } = await http.get('/trip-costs', { params })
  return data.data
}

export async function listCostsForTrip(tripId, params = {}) {
  const { data } = await http.get(`/trips/${tripId}/costs`, { params })
  return data.data
}

export async function submitTripCost(tripId, payload, { idempotencyKey } = {}) {
  const headers = {}
  if (idempotencyKey) headers['Idempotency-Key'] = idempotencyKey
  const { data } = await http.post(`/trips/${tripId}/costs`, payload, { headers })
  return data.data
}

export async function decideTripCost(tripCostId, payload) {
  const { data } = await http.post(`/trip-costs/${tripCostId}/decision`, payload)
  return data.data
}

export async function getTripCost(id) {
  const { data } = await http.get(`/trip-costs/${id}`)
  return data.data
}

export async function updateTripCost(id, payload) {
  const { data } = await http.patch(`/trip-costs/${id}`, payload)
  return data.data
}

export async function deleteTripCost(id) {
  const { data } = await http.delete(`/trip-costs/${id}`)
  return data.data
}
