/** Giá trị placeholder cũ trong dữ liệu / wizard — không hiển thị cho người dùng. */
export const LEGACY_EMPTY_MARK = '—'

/** @param {unknown} value */
export function isEmptyDisplay(value) {
  if (value == null) return true
  const s = String(value).trim()
  return s === '' || s === LEGACY_EMPTY_MARK || s === '-'
}

/** @param {unknown} value */
export function displayTextOrNull(value) {
  if (isEmptyDisplay(value)) return null
  return String(value).trim()
}

/**
 * Ghép giờ/ngày ngắn và địa điểm (bỏ phần thiếu).
 * @param {string} timePart
 * @param {string} placePart
 */
export function joinScheduleParts(timePart, placePart) {
  const parts = []
  const t = displayTextOrNull(timePart)
  const p = displayTextOrNull(placePart)
  if (t) parts.push(t)
  if (p) parts.push(p)
  return parts.join(' · ')
}
