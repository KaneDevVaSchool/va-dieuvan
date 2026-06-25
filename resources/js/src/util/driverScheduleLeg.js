/**
 * Chặng lịch điều vận (sáng/chiều, nhiều dòng wizard) — dùng chung dashboard + chi tiết chuyến tài xế.
 */

const TERMINAL_LEG_STATUSES = new Set(['completed', 'cancelled', 'incident'])

/**
 * @param {object | null | undefined} trip
 * @returns {boolean}
 */
export function tripHasMultipleScheduleLegs(trip) {
  const legs = trip?.schedule_legs
  return Array.isArray(legs) && legs.length > 1
}

/**
 * Chặng tài xế đang thao tác (khớp logic chi tiết chuyến).
 * @param {object | null | undefined} trip
 * @param {number | null | undefined} myDriverId
 * @returns {object | null}
 */
export function resolveDriverOperationalLeg(trip, myDriverId = null) {
  const all = trip?.schedule_legs
  if (!Array.isArray(all) || !all.length) return null

  const did =
    myDriverId != null && myDriverId !== '' && !Number.isNaN(Number(myDriverId))
      ? Number(myDriverId)
      : null

  let pool = all
  if (did != null) {
    const mine = all.filter((l) => Number(l.assignment?.driver_id) === did)
    if (mine.length) pool = mine
  } else if (all.length === 1) {
    pool = all
  } else {
    return null
  }

  const inProgress = pool.find((l) => l.status === 'in_progress')
  if (inProgress) return inProgress

  const waiting = pool.find((l) => !TERMINAL_LEG_STATUSES.has(String(l.status ?? '').toLowerCase()))
  return waiting ?? pool[0] ?? null
}

/**
 * `schedule_key` gửi API đổi trạng thái — ưu tiên `schedule_leg_key` trên row đã tách chặng.
 * @param {object | null | undefined} trip
 * @param {number | null | undefined} myDriverId
 * @returns {string | null}
 */
export function resolveDriverScheduleKey(trip, myDriverId = null) {
  const explicit = trip?.schedule_leg_key
  if (explicit != null && String(explicit).trim() !== '') {
    return String(explicit).trim()
  }
  if (!tripHasMultipleScheduleLegs(trip)) return null
  const leg = resolveDriverOperationalLeg(trip, myDriverId)
  const key = leg?.key
  return key != null && String(key).trim() !== '' ? String(key).trim() : null
}

/**
 * @param {object | null | undefined} trip
 * @returns {number | null}
 */
export function resolveDispatchTripApiId(trip) {
  if (trip == null) return null
  if (trip.trip_id != null && trip.trip_id !== '') {
    const n = Number(trip.trip_id)
    if (!Number.isNaN(n)) return n
  }
  const raw = trip.id
  if (raw == null || raw === '') return null
  if (typeof raw === 'string' && raw.includes(':')) {
    const head = Number(raw.split(':')[0])
    return Number.isNaN(head) ? null : head
  }
  if (typeof raw === 'string' && raw.startsWith('tp-')) return null
  const n = Number(raw)
  return Number.isNaN(n) ? null : n
}

/**
 * @param {string} status
 * @param {object} trip
 * @param {number | null | undefined} myDriverId
 * @returns {{ status: string, schedule_key?: string }}
 */
export function buildDriverTripStatusFields(status, trip, myDriverId = null) {
  const fields = { status }
  const scheduleKey = resolveDriverScheduleKey(trip, myDriverId)
  if (scheduleKey) fields.schedule_key = scheduleKey
  return fields
}

/** Khóa UI (busy, v-for) — ưu tiên `calendar_key` sau khi tách chặng. */
export function driverTripListRowKey(trip) {
  const ck = trip?.calendar_key
  if (ck != null && String(ck).trim() !== '') return String(ck)
  if (trip?.id != null && trip.id !== '') return String(trip.id)
  return ''
}

/**
 * Gộp row từ list API với row đã tách chặng / thẻ dashboard.
 * @param {object} tripHint
 * @param {(id: number) => object | null} tripSnapshot
 */
export function mergeTripRowForStatusAction(tripHint, tripSnapshot) {
  if (tripHint == null || typeof tripHint !== 'object') return tripHint
  const apiId = resolveDispatchTripApiId(tripHint)
  if (apiId == null) return tripHint
  const fromList = typeof tripSnapshot === 'function' ? tripSnapshot(apiId) : null
  if (!fromList) return tripHint
  return {
    ...fromList,
    ...tripHint,
    trip_id: tripHint.trip_id ?? apiId,
    schedule_legs: tripHint.schedule_legs ?? fromList.schedule_legs,
    schedule_leg_key: tripHint.schedule_leg_key ?? fromList.schedule_leg_key,
  }
}
