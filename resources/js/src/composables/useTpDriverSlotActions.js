/** Trạng thái ca TP từ API list/detail — dùng disable Xác nhận / Bắt đầu trước khi gọi API. */

const STARTED_EXECUTION_STATUSES = new Set(['in_progress', 'completed', 'cancelled'])

/**
 * @param {string|null|undefined} status execution_status từ GET /driver/tp-days
 */
export function tpExecutionStatusBlocksConfirmStart(status) {
  if (!status) return false
  return STARTED_EXECUTION_STATUSES.has(String(status))
}

/**
 * Hàng list TP hoặc _tp trên trip dashboard.
 * @param {{ execution_status?: string|null, confirmed_at?: string|null, slot_confirmed_at?: string|null }} row
 */
export function tpRowCanConfirm(row) {
  if (!row) return false
  if (tpExecutionStatusBlocksConfirmStart(row.execution_status)) return false
  const confirmed = row.slot_confirmed_at ?? row.confirmed_at
  if (confirmed) return false
  return true
}

export function tpRowCanStart(row) {
  if (!row) return false
  if (tpExecutionStatusBlocksConfirmStart(row.execution_status)) return false
  const confirmed = row.slot_confirmed_at ?? row.confirmed_at
  return Boolean(confirmed)
}

/**
 * @param {object|null|undefined} trip trip từ dashboard (tpItemsToDriverTrips)
 */
export function tpDriverTripCanConfirm(trip) {
  const tp = trip?._tp
  if (!tp?.day_id) return true
  return tpRowCanConfirm({
    execution_status: tp.execution_status,
    slot_confirmed_at: tp.slot_confirmed_at,
    confirmed_at: tp.slot_confirmed_at,
  })
}

export function tpDriverTripCanStart(trip) {
  const tp = trip?._tp
  if (!tp?.day_id) {
    const st = String(trip?.status ?? '').trim().toLowerCase()
    return ['driver_confirmed', 'assigned'].includes(st)
  }
  return tpRowCanStart({
    execution_status: tp.execution_status,
    slot_confirmed_at: tp.slot_confirmed_at,
    confirmed_at: tp.slot_confirmed_at,
  })
}
