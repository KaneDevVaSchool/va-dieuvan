/**
 * Chặng lịch điều vận (sáng/chiều, nhiều dòng wizard) — dùng chung dashboard + chi tiết chuyến tài xế.
 */

const TERMINAL_LEG_STATUSES = new Set(['completed', 'cancelled', 'incident'])
const PENDING_LEG_STATUSES = new Set(['pending', 'assigned', 'incident'])

/**
 * @param {object | null | undefined} trip
 * @returns {boolean}
 */
export function tripHasMultipleScheduleLegs(trip) {
  const legs = trip?.schedule_legs
  if (Array.isArray(legs) && legs.length > 1) return true
  return countWizardScheduleLegDefinitions(trip) > 1
}

/**
 * Lọc các chặng tài xế đang thao tác (của riêng mình nếu khớp driver_id, ngược lại toàn bộ).
 * @param {object | null | undefined} trip
 * @param {number | null | undefined} myDriverId
 * @returns {Array<object>}
 */
export function driverScheduleLegPool(trip, myDriverId = null) {
  const all = trip?.schedule_legs
  if (!Array.isArray(all) || !all.length) return []
  const did =
    myDriverId != null && myDriverId !== '' && !Number.isNaN(Number(myDriverId))
      ? Number(myDriverId)
      : null
  if (did != null) {
    const mine = all.filter((l) => Number(l.assignment?.driver_id) === did)
    if (mine.length) return mine
  }
  return all
}

/**
 * Cờ thao tác cho một chặng dựa trên trạng thái vận hành của chặng đó.
 * @param {string | null | undefined} status
 * @returns {{ canConfirm: boolean, canStart: boolean, canEnd: boolean, isTerminal: boolean }}
 */
export function driverLegActionFlags(status) {
  const st = String(status ?? '').toLowerCase()
  return {
    canConfirm: PENDING_LEG_STATUSES.has(st),
    canStart: st === 'driver_confirmed' || st === 'approved',
    canEnd: st === 'in_progress',
    isTerminal: st === 'completed' || st === 'cancelled',
  }
}

/**
 * Danh sách chặng kèm trạng thái + cờ thao tác cho UI thẻ lịch trình tài xế.
 * @param {object | null | undefined} trip
 * @param {number | null | undefined} myDriverId
 * @returns {Array<{ key: string, status: string, canConfirm: boolean, canStart: boolean, canEnd: boolean, isTerminal: boolean }>}
 */
export function buildDriverOperationalLegs(trip, myDriverId = null) {
  const pool = driverScheduleLegPool(trip, myDriverId)
  return pool.map((leg) => {
    const status = String(leg?.status ?? trip?.status ?? '')
    return {
      key: leg?.key != null ? String(leg.key) : '',
      status,
      ...driverLegActionFlags(status),
    }
  })
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

function parseCompositeScheduleTail(raw) {
  if (raw == null || raw === '') return null
  const s = String(raw)
  if (!s.includes(':')) return null
  const tail = s.split(':').slice(1).join(':').trim()
  return tail || null
}

function wizardRowFilled(row, tripType) {
  if (!row || typeof row !== 'object') return false
  if (tripType === 'cargo') {
    return Boolean(
      String(row.pickup ?? row.origin ?? '').trim() ||
        String(row.dropoff ?? row.destination ?? '').trim(),
    )
  }
  return Boolean(
    String(row.depart_at ?? '').trim() ||
      String(row.pickup ?? '').trim() ||
      String(row.return_at ?? '').trim() ||
      String(row.dropoff ?? '').trim(),
  )
}

/**
 * Số chặng lịch theo wizard (khớp TripScheduleLegService::buildLegDefinitionsFromSnapshot).
 * @param {object | null | undefined} trip
 * @returns {number}
 */
export function countWizardScheduleLegDefinitions(trip) {
  const dr = trip?.dispatch_request ?? trip?.dispatchRequest ?? null
  if (!dr) return 0
  const snap = dr.wizard_snapshot
  if (!snap || typeof snap !== 'object') return 0
  const tripType = String(dr.trip_type ?? '')
  let count = 0
  if (tripType === 'cargo') {
    for (const row of snap.cargoRows ?? []) {
      if (wizardRowFilled(row, tripType)) count++
    }
    return count
  }
  if (tripType !== 'business') {
    for (const row of snap.passengerRows ?? []) {
      if (wizardRowFilled(row, tripType)) count++
    }
  }
  if (tripType !== 'point_to_point') {
    for (const row of snap.businessRows ?? []) {
      if (wizardRowFilled(row, tripType)) count++
    }
  }
  return count
}

export function driverTripRequiresScheduleKey(trip) {
  if (tripHasMultipleScheduleLegs(trip)) return true
  return countWizardScheduleLegDefinitions(trip) > 1
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
  const fromComposite =
    parseCompositeScheduleTail(trip?.id) ?? parseCompositeScheduleTail(trip?.calendar_key)
  if (fromComposite) return fromComposite

  if (!driverTripRequiresScheduleKey(trip)) return null

  const leg = resolveDriverOperationalLeg(trip, myDriverId)
  const legKey = leg?.key
  if (legKey != null && String(legKey).trim() !== '') {
    return String(legKey).trim()
  }

  const legs = trip?.schedule_legs
  if (Array.isArray(legs) && legs.length > 0) {
    const assigned = legs.find(
      (l) => String(l?.status ?? '').trim().toLowerCase() === 'assigned',
    )
    if (assigned?.key != null && String(assigned.key).trim() !== '') {
      return String(assigned.key).trim()
    }
    if (legs.length === 1 && legs[0]?.key != null && String(legs[0].key).trim() !== '') {
      return String(legs[0].key).trim()
    }
  }

  return null
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
