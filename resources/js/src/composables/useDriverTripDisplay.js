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

/**
 * Một dòng tóm tắt điểm đón → điểm đến cho thanh lịch (truncate gọn).
 * @param {object} trip
 * @param {(key: string) => string} t
 */
export function tripTimelineRouteLine(trip, t) {
  const o = (trip?.dispatch_request?.origin || '').trim()
  const d = (trip?.dispatch_request?.destination || '').trim()
  if (!o && !d) return t('driver_home.line_route')
  if (!d || d === o) {
    const line = o || d
    return line.length > 34 ? `${line.slice(0, 32)}…` : line
  }
  const arrow = ' → '
  let s = `${o}${arrow}${d}`
  if (s.length <= 40) return s
  const ho = o.length > 18 ? `${o.slice(0, 16)}…` : o
  const hd = d.length > 18 ? `${d.slice(0, 16)}…` : d
  return `${ho}${arrow}${hd}`
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
 * Nhãn loại dịch vụ đầy đủ cho lịch / timeline (D2D, P2P, Hàng hóa, Công tác…).
 * @param {object} trip
 * @param {(key: string) => string} t vue-i18n `t`
 */
export function tripServiceTypeCalendarLabel(trip, t) {
  const tt = trip?.dispatch_request?.trip_type
  if (tt === 'door_to_door') return t('driver_home.calendar_svc_d2d')
  if (tt === 'point_to_point') return t('driver_home.calendar_svc_p2p')
  if (tt === 'cargo') return t('driver_home.calendar_svc_cargo')
  if (tt === 'business') return t('driver_home.calendar_svc_business')
  return t('driver_home.calendar_svc_other')
}

/**
 * Khung giờ đi → về (khởi hành – dự kiến kết thúc / đến).
 * @param {object} trip
 * @param {'vi'|'en'} localeTag
 * @param {(key: string) => string} t
 */
export function tripOutboundInboundTimeRange(trip, localeTag, t) {
  const depIso = trip?.depart_at || trip?.dispatch_request?.depart_at
  const arrIso = trip?.arrive_by || trip?.dispatch_request?.arrive_by
  const loc = localeTag === 'vi' ? 'vi-VN' : 'en-US'

  function hm(iso) {
    if (!iso) return ''
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit', hour12: false })
  }

  const start = hm(depIso)
  if (!start) return ''
  const end = hm(arrIso)
  if (!end || end === start) return start
  return `${start}\u00a0${t('driver_home.calendar_time_sep')}\u00a0${end}`
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
