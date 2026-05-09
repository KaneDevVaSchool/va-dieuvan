/** Múi giờ thống nhất hiển thị lịch trình chuyến (khớp điều vận VN). */
export const TRIP_DISPLAY_TIMEZONE = 'Asia/Ho_Chi_Minh'

/**
 * @param {string | null | undefined} iso
 * @returns {Date | null}
 */
export function parseTripInstant(iso) {
  if (iso == null || String(iso).trim() === '') return null
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? null : d
}

/**
 * @param {Date} d
 * @param {string} [tz]
 */
function ymdKeyInTz(d, tz = TRIP_DISPLAY_TIMEZONE) {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: tz,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(d)
}

/**
 * Giờ: phút (24h) cố định theo múi giờ VN — tránh lỗi "CH"/AM/PM và lệch parse.
 * @param {string | null | undefined} iso
 * @param {{ locale?: string }} [opts]
 */
export function formatTripTimeHm24(iso, opts = {}) {
  const loc = opts.locale === 'en' ? 'en-GB' : 'vi-VN'
  const d = parseTripInstant(iso)
  if (!d) return '—'
  return new Intl.DateTimeFormat(loc, {
    timeZone: TRIP_DISPLAY_TIMEZONE,
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(d)
}

/**
 * Weekday + calendar date (dài) theo locale, cố định TRIP_DISPLAY_TIMEZONE.
 * @param {string | null | undefined} iso
 * @param {{ locale?: string }} [opts]
 */
export function formatTripWeekdayDateLong(iso, opts = {}) {
  const loc = opts.locale === 'en' ? 'en-GB' : 'vi-VN'
  const d = parseTripInstant(iso)
  if (!d) return '—'
  return new Intl.DateTimeFormat(loc, {
    timeZone: TRIP_DISPLAY_TIMEZONE,
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(d)
}

/**
 * Ngày ngắn + giờ (dùng dòng phụ chi phí / timeline ngắn).
 * @param {string | null | undefined} iso
 * @param {{ locale?: string }} [opts]
 */
export function formatTripDayMonthAndHm24(iso, opts = {}) {
  const loc = opts.locale === 'en' ? 'en-GB' : 'vi-VN'
  const d = parseTripInstant(iso)
  if (!d) return '—'
  const dayMonth = new Intl.DateTimeFormat(loc, {
    timeZone: TRIP_DISPLAY_TIMEZONE,
    day: 'numeric',
    month: 'short',
  }).format(d)
  const hm = formatTripTimeHm24(iso, opts)
  return `${dayMonth} · ${hm}`
}

/**
 * So sánh “hôm nay” theo calendar VN (không theo timezone máy chủ trình duyệt).
 * @param {string | null | undefined} iso
 */
export function isSameVnCalendarDayAsNow(iso) {
  const d = parseTripInstant(iso)
  if (!d) return false
  return ymdKeyInTz(d) === ymdKeyInTz(new Date())
}
