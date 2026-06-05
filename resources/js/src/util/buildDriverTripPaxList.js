import { dispatchRequestEffectivePassengerCount } from './dispatchRequestPassengers'
import { isAutoPassengerLabel, passengerDisplayName } from './passengerDisplayName'

export function normalizeTripPassengers(trip) {
  const raw = trip?.trip_passengers ?? trip?.tripPassengers
  return Array.isArray(raw) ? raw : []
}

/**
 * Danh sách hành khách hiển thị trên màn tài xế (khớp logic slot TripDetailView cho P2P/D2D).
 */
export function buildDriverTripPaxList(options) {
  const {
    dr,
    trip,
    snap,
    t,
    locale,
    formatTripTimeHm24,
    isPassengerRowFilled,
    isBusinessRowFilled,
    isCargoRowFilled,
    classFromNotes,
  } = options

  const ttr = dr
  if (!ttr) return []

  const s = snap
  const tt = ttr.trip_type
  const tplist = normalizeTripPassengers(trip)
  const wizardPassengerRows = s?.passengerRows ?? []

  function paxFromSlot(i, tp, wr) {
    let name = String(tp?.name ?? '').trim()
    let phoneRaw = tp?.phone ?? wr?.phone ?? ''
    let notes = String(tp?.note ?? tp?.notes ?? wr?.notes ?? '').trim()

    if (!name && wr && isPassengerRowFilled(wr)) {
      name = (wr.person_in_charge || '').trim()
      phoneRaw = phoneRaw || wr.phone || ''
      if (!notes) notes = (wr.notes || '').trim()
    }

    if (!name || isAutoPassengerLabel(name)) name = passengerDisplayName(name, i, t)

    const classGuess = classFromNotes(notes)
    const rawTime = wr?.depart_at || ttr.depart_at
    const subtitle = classGuess || notes || '—'
    const phone = String(phoneRaw || '').replace(/\D/g, '') || null
    const address = (wr?.pickup || '').trim() || null
    const time = rawTime ? formatTripTimeHm24(rawTime, { locale }) : null

    return { name, subtitle, phone, address, time }
  }

  function buildPassengerSlots(targetN) {
    const out = []
    for (let i = 0; i < targetN; i += 1) {
      out.push(paxFromSlot(i, tplist[i], wizardPassengerRows[i]))
    }
    return out
  }

  if (tt === 'door_to_door' || tt === 'point_to_point') {
    const targetN = dispatchRequestEffectivePassengerCount(ttr)
    if (targetN > 0) return buildPassengerSlots(targetN)
    return []
  }

  const out = []

  if (tt === 'cargo' && s?.cargoRows?.length) {
    let i = 0
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      out.push({
        name: r.name?.trim() || t('driver_trip_detail.cargo_item', { n: ++i }),
        subtitle: [r.pickup_at, r.pickup_place].filter(Boolean).join(' · ') || '—',
        phone: (r.pickup_contact || r.delivery_contact || '').replace(/\D/g, '') || null,
        address: null,
        time: null,
      })
    }
    return out
  }

  if (tt === 'business' && s?.businessRows?.length) {
    let i = 0
    for (const r of s.businessRows) {
      if (!isBusinessRowFilled(r)) continue
      out.push({
        name: passengerDisplayName('', i, t),
        subtitle: r.notes?.trim() || r.pickup || '—',
        phone: null,
        address: null,
        time: null,
      })
      i += 1
    }
    const targetBiz = dispatchRequestEffectivePassengerCount(ttr)
    if (targetBiz > out.length) {
      for (let j = out.length; j < targetBiz; j += 1) {
        out.push(paxFromSlot(j, tplist[j], wizardPassengerRows[j]))
      }
    }
    if (out.length) return out
  }

  for (let pi = 0; pi < wizardPassengerRows.length; pi += 1) {
    const r = wizardPassengerRows[pi]
    if (!isPassengerRowFilled(r)) continue
    out.push(paxFromSlot(pi, tplist[pi], r))
  }

  let bizIdx = out.length
  for (const r of s?.businessRows ?? []) {
    if (tt === 'business') break
    if (!isBusinessRowFilled(r)) continue
    bizIdx += 1
    out.push({
      name: passengerDisplayName('', bizIdx - 1, t),
      subtitle: r.notes?.trim() || '—',
      phone: null,
      address: null,
      time: null,
    })
  }

  const targetN = dispatchRequestEffectivePassengerCount(ttr)
  if (targetN > out.length) {
    const start = out.length
    for (let i = start; i < targetN; i += 1) {
      out.push(paxFromSlot(i, tplist[i], wizardPassengerRows[i]))
    }
  } else if (!out.length && targetN > 0) {
    return buildPassengerSlots(targetN)
  }

  return out
}

