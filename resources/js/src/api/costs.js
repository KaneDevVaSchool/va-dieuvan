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
