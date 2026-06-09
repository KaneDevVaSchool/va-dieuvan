/** @param {Record<string, unknown> | null | undefined} trip */
export function tripLockVersion(trip) {
  const n = Number(trip?.lock_version ?? 0)
  return Number.isFinite(n) && n >= 0 ? n : 0
}

/** @param {Record<string, unknown>} payload @param {Record<string, unknown> | null | undefined} trip */
export function withTripLockVersion(payload, trip) {
  return { ...payload, lock_version: tripLockVersion(trip) }
}

/** @param {unknown} err */
export function isTripLockConflict(err) {
  return /** @type {{ response?: { status?: number } }} */ (err)?.response?.status === 409
}

/**
 * Gộp trip trả về API vào bản ghi danh sách (giữ lock_version đồng bộ).
 * @param {Record<string, unknown> | null | undefined} row
 * @param {Record<string, unknown> | null | undefined} apiTrip
 * @param {Record<string, unknown>} [extra]
 */
export function mergeTripRowFromApi(row, apiTrip, extra = {}) {
  if (!row || !apiTrip) return { ...row, ...extra }
  return {
    ...row,
    ...extra,
    status: apiTrip.status ?? extra.status ?? row.status,
    lock_version: apiTrip.lock_version ?? row.lock_version,
  }
}
