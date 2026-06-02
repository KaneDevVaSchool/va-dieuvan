/** Nhãn mặc định cũ (wizard / trip_passengers) — hiển thị lại thống nhất. */
const AUTO_PASSENGER_LABEL =
  /^(Khách|Hành khách|Guest|Passengers?|Đoàn công tác|Đoàn|Group)\s*[#№]?\s*\d+$/iu

/**
 * @param {string | null | undefined} name
 */
export function isAutoPassengerLabel(name) {
  const s = String(name ?? '').trim()
  if (!s) return true
  return AUTO_PASSENGER_LABEL.test(s)
}

/**
 * @param {string | null | undefined} rawName
 * @param {number} slotIndex 0-based
 * @param {(key: string, params?: Record<string, unknown>) => string} t
 */
export function passengerDisplayName(rawName, slotIndex, t) {
  const n = Math.max(1, Math.round(Number(slotIndex) || 0) + 1)
  const trimmed = String(rawName ?? '').trim()
  if (!trimmed || isAutoPassengerLabel(trimmed)) {
    return t('trip_detail.passengers.passenger', { n })
  }
  return trimmed
}
