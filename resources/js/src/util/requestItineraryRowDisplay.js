export function nz(v) {
  return v == null ? '' : String(v).trim()
}

export function itineraryRowEndpoints(row) {
  const from = nz(row.pickup_place) || nz(row.pickup)
  const to = nz(row.dropoff_place) || nz(row.delivery_place) || nz(row.dropoff)
  return { from, to }
}

export function itineraryRowRouteLabel(row) {
  const { from, to } = itineraryRowEndpoints(row)
  if (!from && !to) return ''
  return `${from || '—'} → ${to || '—'}`
}

export function formatItineraryRowDt(v) {
  if (!v) return ''
  const d = new Date(String(v).trim())
  if (Number.isNaN(d.getTime())) return nz(v)
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

/**
 * @param {'cargo'|'business'|'passenger'} tripType
 */
export function itineraryRowHeading(row, index, { tripType, t }) {
  const n = index + 1
  const route = itineraryRowRouteLabel(row)

  if (tripType === 'cargo') {
    if (nz(row.name)) return nz(row.name)
    if (route) return route
    return t('request_detail.ops_row_heading_cargo', { n })
  }

  if (tripType === 'business') {
    if (nz(row.description)) return nz(row.description)
    if (route) return route
    if (nz(row.waypoint)) return nz(row.waypoint)
    return t('request_detail.ops_row_heading_business', { n })
  }

  if (nz(row.description)) return nz(row.description)
  if (nz(row.name)) return nz(row.name)
  if (route) return route
  const departLabel = formatItineraryRowDt(row.depart_at)
  if (departLabel) return t('request_detail.ops_row_heading_depart', { at: departLabel })
  const guests = nz(row.guests)
  if (guests) return t('request_detail.ops_row_heading_guests', { n: guests })
  return t('request_detail.ops_row_heading_passenger', { n })
}

/**
 * @param {'cargo'|'business'|'passenger'} tripType
 */
export function itineraryRowSummaryLines(row, { tripType, t, index = 0 }) {
  const lines = []
  const route = itineraryRowRouteLabel(row)
  const heading = itineraryRowHeading(row, index, { tripType, t })
  const { from, to } = itineraryRowEndpoints(row)

  if (tripType === 'cargo') {
    if (route && heading !== route) lines.push(route)
    const pickupAt = formatItineraryRowDt(row.pickup_at)
    const deliveryAt = formatItineraryRowDt(row.delivery_at)
    if (pickupAt || from) {
      lines.push(t('request_detail.ops_row_line_pickup', { time: pickupAt || '—', place: from || '—' }))
    }
    if (deliveryAt || to) {
      lines.push(t('request_detail.ops_row_line_delivery', { time: deliveryAt || '—', place: to || '—' }))
    }
    const qty = nz(row.qty)
    const weight = nz(row.weight)
    if (qty || weight) {
      lines.push(
        t('request_detail.ops_row_line_cargo_load', {
          qty: qty || '—',
          weight: weight || '—',
        }),
      )
    }
    return lines
  }

  if (route && heading !== route) lines.push(route)

  const outTime = formatItineraryRowDt(row.depart_at)
  const backTime = formatItineraryRowDt(row.return_at)
  if (outTime || from) {
    lines.push(t('request_detail.ops_row_line_out', { time: outTime || '—', place: from || '—' }))
  }
  if (backTime || to) {
    lines.push(t('request_detail.ops_row_line_back', { time: backTime || '—', place: to || '—' }))
  }
  if (tripType === 'business' && nz(row.waypoint)) {
    lines.push(t('request_detail.ops_row_line_waypoint', { place: nz(row.waypoint) }))
  }
  const guests = nz(row.guests)
  if (guests) lines.push(t('request_detail.ops_row_line_guests', { n: guests }))
  const pic = nz(row.person_in_charge)
  if (pic && tripType === 'passenger') {
    lines.push(t('request_detail.ops_row_line_pic', { name: pic }))
  }

  return lines
}

export function resolveItineraryTripType(isCargo, isBusiness) {
  if (isCargo) return 'cargo'
  if (isBusiness) return 'business'
  return 'passenger'
}
