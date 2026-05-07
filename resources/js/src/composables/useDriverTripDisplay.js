/**
 * Định dạng / đọc trường hiển thị chuyến cho giao diện tài xế (dashboard).
 * @param {string} localeTag vi hoặc en
 */
export function tripDepartIso(trip) {
  return trip?.depart_at || trip?.dispatch_request?.depart_at || null
}

export function isTripUrgent(trip) {
  return !!trip?.dispatch_request?.is_urgent
}

export function tripOrigin(trip) {
  const o = (trip?.dispatch_request?.origin || '').trim()
  return o || '—'
}

export function tripDestination(trip) {
  const o = (trip?.dispatch_request?.destination || '').trim()
  return o || '—'
}

export function tripRequesterLine(trip) {
  return trip?.dispatch_request?.requester?.name?.trim() || ''
}

/** @param {(key: string, vars?: object) => string} t */
export function tripPassengerLine(trip, t) {
  const dr = trip?.dispatch_request
  const n = dr?.passenger_count
  if (n != null && n !== '') return t('driver_home.pending_passengers', { n })
  return ''
}

export function tripTypeBadgeText(trip) {
  const tt = trip?.dispatch_request?.trip_type
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return 'CT'
  if (tt === 'cargo') return 'CG'
  return 'TR'
}

/**
 * @param {object} trip
 * @param {string} localeTag
 */
export function formatDepartForTrip(trip, localeTag) {
  const iso = tripDepartIso(trip)
  if (!iso) return { time: '—', dateLine: '' }
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return { time: '—', dateLine: '' }
  const loc = localeTag === 'vi' ? 'vi-VN' : 'en-US'
  const time = d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit', hour12: false })
  const dateLine = d.toLocaleDateString(loc, {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  })
  return { time, dateLine }
}
