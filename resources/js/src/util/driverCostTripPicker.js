import {
  formatDepartForTrip,
  tripDestination,
  tripOrigin,
  tripOutboundInboundTimeRange,
} from '../composables/useDriverTripDisplay'
import { formatTripCode } from './labels'
import { toLocalDateKey } from './dates'

/** @param {object} tr */
export function driverCostTripSearchHaystack(tr, localeTag = 'vi') {
  const code = formatTripCode(tr?.id)
  const { dateLine, time } = formatDepartForTrip(tr, localeTag)
  const range = tripOutboundInboundTimeRange(tr, localeTag, () => '–')
  const parts = [
    code,
    tr?.trip_number,
    tr?.request_code,
    String(tr?.id ?? ''),
    tripOrigin(tr),
    tripDestination(tr),
    dateLine,
    time,
    range,
    tr?.pickup_location,
    tr?.dropoff_location,
    tr?.origin,
    tr?.destination,
  ]
  return parts
    .filter((x) => x != null && String(x).trim() !== '' && String(x) !== '—')
    .join(' ')
    .toLowerCase()
}

/** @param {object} tr */
export function tripMatchesDriverCostSearch(tr, query, localeTag = 'vi') {
  const q = String(query ?? '').trim().toLowerCase()
  if (!q) return true
  return driverCostTripSearchHaystack(tr, localeTag).includes(q)
}

/** @param {object[]} trips @param {string} query @param {'vi'|'en'} localeTag @param {(key: string) => string} t */
export function buildDriverCostTripGroups(trips, query, localeTag, t) {
  const filtered = (trips ?? []).filter((tr) => tripMatchesDriverCostSearch(tr, query, localeTag))

  /** @type {Map<string, { dateKey: string, dateLabel: string, trips: object[] }>} */
  const map = new Map()
  for (const tr of filtered) {
    const iso = tr?.depart_at || tr?.dispatch_request?.depart_at
    const dateKey = toLocalDateKey(iso) || 'unknown'
    const { dateLine } = formatDepartForTrip(tr, localeTag)
    const dateLabel = dateLine || dateKey
    if (!map.has(dateKey)) {
      map.set(dateKey, { dateKey, dateLabel, trips: [] })
    }
    map.get(dateKey).trips.push(tr)
  }

  for (const group of map.values()) {
    group.trips.sort((a, b) => {
      const ta = new Date(a?.depart_at || a?.dispatch_request?.depart_at || 0).getTime()
      const tb = new Date(b?.depart_at || b?.dispatch_request?.depart_at || 0).getTime()
      return tb - ta
    })
  }

  return [...map.values()].sort((a, b) => b.dateKey.localeCompare(a.dateKey))
}

/** @param {object[]} trips @param {string|number} id */
export function findDriverCostTrip(trips, id) {
  const raw = String(id ?? '').trim()
  if (!raw) return null
  return (trips ?? []).find((tr) => String(tr?.id) === raw) ?? null
}

/** @param {object} tr @param {(key: string) => string} t */
export function driverCostTripStatusLabel(tr, t) {
  const st = String(tr?.status ?? '').toLowerCase()
  const map = {
    completed: 'driver_costs.trip_st_completed',
    in_progress: 'driver_costs.trip_st_in_progress',
    assigned: 'driver_costs.trip_st_assigned',
    driver_confirmed: 'driver_costs.trip_st_driver_confirmed',
    approved: 'driver_costs.trip_st_approved',
    pending: 'driver_costs.trip_st_pending',
  }
  const key = map[st]
  return key ? t(key) : ''
}
