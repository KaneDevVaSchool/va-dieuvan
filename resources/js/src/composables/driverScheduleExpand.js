/**
 * Mở rộng chuyến điều vận gán nhiều lịch (sáng/chiều) thành từng dòng trên lịch tài xế.
 * @param {object[]} trips
 * @returns {object[]}
 */
export function expandTripsForDriverCalendar(trips) {
  if (!Array.isArray(trips)) return []
  const out = []

  for (const trip of trips) {
    if (trip?._tp?.day_id) {
      continue
    }
    const legs = trip?.schedule_legs
    if (!Array.isArray(legs) || legs.length === 0) {
      const shift = trip._tp?.shift || inferShiftFromIso(trip.depart_at)
      out.push({
        ...trip,
        calendar_key: String(trip.id),
        calendar_kind: 'dispatch',
        ...(shift ? { calendar_shift: shift } : {}),
      })
      continue
    }

    const useLegs = legs.length > 1 ? legs : legs
    for (const leg of useLegs) {
      const departAt = resolveLegDepartIso(trip, leg)
      out.push({
        ...trip,
        calendar_key: `${trip.id}:${leg.key}`,
        calendar_kind: 'dispatch',
        schedule_leg_key: leg.key,
        status: leg.status ?? trip.status,
        depart_at: departAt,
        arrive_by: leg.arrive_by ?? trip.arrive_by,
        pickup_location: leg.pickup || trip.pickup_location,
        dropoff_location: leg.dropoff || trip.dropoff_location,
        calendar_shift: inferShiftFromIso(departAt),
      })
    }
  }

  return out
}

function resolveLegDepartIso(trip, leg) {
  const raw = leg?.depart_at
  if (raw) {
    const d = new Date(raw)
    if (!Number.isNaN(d.getTime())) return d.toISOString()
    if (typeof raw === 'string' && /^\d{4}-\d{2}-\d{2}/.test(raw)) {
      return raw
    }
  }
  return trip.depart_at || trip.dispatch_request?.depart_at || null
}

function inferShiftFromIso(iso) {
  if (!iso) return null
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return null
  return d.getHours() < 12 ? 'morning' : 'afternoon'
}

/**
 * Chuẩn hóa ca chương trình đưa đón thành object chuyến (D2D) cho dashboard / banner / carousel tài xế.
 * @param {object[]} tpItems từ GET /driver/tp-days
 * @returns {object[]}
 */
export function tpItemsToDriverTrips(tpItems) {
  if (!Array.isArray(tpItems)) return []

  return tpItems.map((row) => {
    const date = String(row.scheduled_date || '').slice(0, 10)
    const time = String(row.departure_time || '00:00').slice(0, 5)
    const departAt = date ? `${date}T${time}:00` : null
    let arriveBy = null
    if (row.arrival_time && date) {
      arriveBy = `${date}T${String(row.arrival_time).slice(0, 5)}:00`
    }

    const execStatus = row.execution_status
    const confirmed = !!row.confirmed_at
    let status = 'assigned'
    if (execStatus === 'in_progress') status = 'in_progress'
    else if (execStatus === 'completed') status = 'completed'
    else if (execStatus === 'cancelled') status = 'cancelled'
    else if (confirmed) status = 'driver_confirmed'

    const origin = (row.origin_name || row.program_name || '').toString().trim()
    const destination = (row.destination_name || '').toString().trim()
    const pax = Number(row.expected_count) || 0
    const shift = row.shift || 'morning'
    const listKey = row.list_key || `${row.day_id}-${shift}`
    const multiSlot = !!row.multi_slot

    return {
      id: `tp-${listKey}`,
      calendar_shift: shift,
      program_name: row.program_name,
      _tp: {
        day_id: row.day_id,
        shift,
        multi_slot: multiSlot,
        list_key: listKey,
        program_id: row.program_id,
      },
      trip_number: row.program_code ? String(row.program_code) : 'CPĐD',
      type: 'TP',
      status,
      depart_at: departAt,
      depart_date: date || null,
      pickup_time: time,
      pickup_location: origin,
      dropoff_location: destination,
      passenger_count: pax,
      arrive_by: arriveBy,
      dispatch_request: {
        trip_type: 'transport_program',
        origin,
        destination,
        depart_at: departAt,
        arrive_by: arriveBy,
        passenger_count: pax,
      },
      schedule_legs: [],
    }
  })
}

export function tpItemsToCalendarSlots(tpItems) {
  if (!Array.isArray(tpItems)) return []
  return tpItems.map((row) => {
    const date = String(row.scheduled_date || '').slice(0, 10)
    const time = String(row.departure_time || '00:00').slice(0, 5)
    const departAt = date ? `${date}T${time}:00` : null
    return {
      calendar_key: row.list_key || `tp-${row.day_id}-${row.shift || 'morning'}`,
      calendar_kind: 'tp',
      day_id: row.day_id,
      shift: row.shift || 'morning',
      multi_slot: !!row.multi_slot,
      depart_at: departAt,
      program_name: row.program_name,
      expected_count: row.expected_count,
      execution_status: row.execution_status,
      confirmed_at: row.confirmed_at,
      status: row.execution_status || (row.confirmed_at ? 'driver_confirmed' : 'assigned'),
    }
  })
}
