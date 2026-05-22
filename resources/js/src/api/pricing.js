import { http } from './http'

export async function getReferencePricing() {
  const { data } = await http.get('/reference-pricing')
  return data.data
}

/** @param {{ trip_type: string, origin?: string, destination?: string, passenger_count?: number }} params */
export async function getPricingSuggestions(params) {
  const { data } = await http.get('/reference-pricing/suggest', { params })
  return data.data
}

/**
 * @param {'passenger_fare_rate'|'cargo_fare_rate'|'pricing_note'} type
 * @param {number} id
 */
export async function getReferencePricingRevisions(type, id) {
  const { data } = await http.get('/reference-pricing/revisions', { params: { type, id } })
  return data.data.items
}

export async function updatePassengerFare(id, payload) {
  const { data } = await http.patch(`/reference-pricing/passenger-fares/${id}`, payload)
  return data.data.passenger_fare
}

export async function updateCargoFare(id, payload) {
  const { data } = await http.patch(`/reference-pricing/cargo-fares/${id}`, payload)
  return data.data.cargo_fare
}

export async function updatePricingNote(id, payload) {
  const { data } = await http.patch(`/reference-pricing/notes/${id}`, payload)
  return data.data.note
}
