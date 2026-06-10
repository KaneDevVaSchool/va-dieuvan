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

/** @param {unknown} err */
export function isOptimisticLockConflict(err) {
  if (!isTripLockConflict(err)) return false
  const msg = String(
    /** @type {{ response?: { data?: { message?: unknown } } }} */ (err)?.response?.data
      ?.message ?? '',
  )
  return (
    /optimistic lock/i.test(msg) ||
    msg.includes('Dữ liệu đã thay đổi, vui lòng tải lại')
  )
}

/**
 * Gộp trip từ API vào state local (giữ quan hệ/field cũ nếu response thiếu).
 * @param {import('vue').Ref<Record<string, unknown> | null | undefined>} tripRef
 * @param {Record<string, unknown> | null | undefined} apiTrip
 * @param {import('vue').Ref<{ lock_version?: number } & Record<string, unknown>> | null} [assignRef]
 */
export function patchTripFromApi(tripRef, apiTrip, assignRef = null) {
  if (!apiTrip || !tripRef?.value) return
  tripRef.value = { ...tripRef.value, ...apiTrip }
  syncTripLockFromApi(tripRef, assignRef, apiTrip)
}

/**
 * Cập nhật lock_version ngay sau mutation (trước khi load() hoàn tất).
 * @param {import('vue').Ref<Record<string, unknown> | null | undefined>} tripRef
 * @param {import('vue').Ref<{ lock_version?: number } & Record<string, unknown>> | null} assignRef
 * @param {Record<string, unknown> | null | undefined} apiTrip
 */
export function syncTripLockFromApi(tripRef, assignRef, apiTrip) {
  if (!apiTrip) return
  const v = tripLockVersion(apiTrip)
  if (tripRef?.value) {
    tripRef.value = { ...tripRef.value, lock_version: v }
  }
  if (assignRef?.value) {
    assignRef.value = { ...assignRef.value, lock_version: v }
  }
}

/**
 * Mutation có lock_version: nếu optimistic lock fail thì refresh và thử lại một lần.
 * @template T
 * @param {{
 *   getTrip: () => Record<string, unknown> | null | undefined,
 *   refreshTrip: () => Promise<Record<string, unknown> | null | undefined>,
 *   execute: (trip: Record<string, unknown> | null | undefined) => Promise<T>,
 * }} ctx
 * @returns {Promise<T>}
 */
export async function runWithOptimisticLockRetry({ getTrip, refreshTrip, execute }) {
  try {
    return await execute(getTrip())
  } catch (e) {
    if (!isOptimisticLockConflict(e)) throw e
    const fresh = await refreshTrip()
    return await execute(fresh ?? getTrip())
  }
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
