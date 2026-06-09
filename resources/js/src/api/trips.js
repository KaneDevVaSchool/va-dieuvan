import { http } from './http'

/** Khớp max trong `ListTripsRequest` (Laravel) — vượt sẽ 422. */
export const TRIPS_LIST_MAX_PER_PAGE = 100

export async function listTrips(params = {}) {
  const { data } = await http.get('/trips', { params })
  return data.data
}

/**
 * Lấy toàn bộ mục theo filter đã cho (paginate phía server cho đến hết).
 * @param {Record<string, unknown>} params from/to/status/… (per_page clamp ≤ 100)
 */
export async function listTripsAll(params = {}) {
  const { page: _drop, per_page: perPageRequested, ...rest } = params
  const perPage = Math.min(
    TRIPS_LIST_MAX_PER_PAGE,
    Math.max(1, Number(perPageRequested) || TRIPS_LIST_MAX_PER_PAGE),
  )
  let page = 1
  const allItems = []
  let lastMeta = null
  const maxPages = 50

  while (page <= maxPages) {
    const res = await listTrips({ ...rest, per_page: perPage, page })
    const batch = res?.items ?? []
    lastMeta = res?.meta ?? lastMeta
    allItems.push(...batch)
    const lastPage = Number(res?.meta?.last_page ?? 1)
    if (page >= lastPage || batch.length === 0) break
    page += 1
  }

  return { items: allItems, meta: lastMeta }
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

export async function updateTripPassengerList(tripId, payload) {
  const { data } = await http.patch(`/trips/${tripId}/passenger-list`, payload)
  return data.data
}

export async function upsertTripRecord(tripId, payload) {
  const { data } = await http.put(`/trips/${tripId}/record`, payload)
  return data.data
}

export async function duplicateTrip(tripId) {
  const { data } = await http.post(`/trips/${tripId}/duplicate`)
  return data.data
}

export async function passengerCheckIn(tripId, passengerKey, payload) {
  const { data } = await http.patch(`/trips/${tripId}/passengers/${encodeURIComponent(passengerKey)}/checkin`, payload)
  return data.data
}

export async function passengerUncheckIn(tripId, passengerKey, { lock_version } = {}) {
  const { data } = await http.delete(`/trips/${tripId}/passengers/${encodeURIComponent(passengerKey)}/checkin`, {
    params: lock_version != null ? { lock_version } : undefined,
  })
  return data.data
}
