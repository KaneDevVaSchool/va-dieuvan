/**
 * Quy tắc hiển thị xác nhận chuyến trên app tài xế.
 * — Chương trình đưa đón (TP): chỉ ca sắp chạy trong cửa sổ trước giờ; tối đa một ca TP trên banner.
 * — Yêu cầu phát sinh (điều vận): giữ như cũ (hôm nay + ngày mai).
 */

/** Giờ trước giờ chạy để hiện ca đưa đón (khớp nhắc email / ca sáng). */
export const TP_CONFIRM_LEAD_HOURS = 18

/** Sau giờ chạy vẫn cho xác nhận nếu chưa bấm (phút). */
export const TP_CONFIRM_GRACE_AFTER_DEPART_MIN = 240

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function tripDepartMs(trip) {
  if (trip?.depart_at) {
    const t = new Date(trip.depart_at).getTime()
    if (Number.isFinite(t)) return t
  }
  if (trip?.depart_date) {
    const t = new Date(trip.depart_date).getTime()
    if (Number.isFinite(t)) return t
  }
  return NaN
}

export function tripDepartYmd(trip) {
  const ms = tripDepartMs(trip)
  if (!Number.isFinite(ms)) return null
  return ymd(new Date(ms))
}

export function isTpDriverTrip(trip) {
  return trip?._tp?.day_id != null
}

function adHocConfirmEndYmd(now = new Date()) {
  const d = new Date(now)
  d.setDate(d.getDate() + 1)
  return ymd(d)
}

/** Yêu cầu lẻ / chuyến điều vận: banner chờ xác nhận hôm nay và ngày mai. */
export function isAdHocDispatchConfirmRelevant(trip, now = new Date()) {
  if (isTpDriverTrip(trip)) return false
  const today = ymd(now)
  const end = adHocConfirmEndYmd(now)
  const day = tripDepartYmd(trip)
  if (day == null) return false
  if (day < today) return false
  if (day > end) return false
  return true
}

/** Ca chương trình đưa đón: chỉ trong cửa sổ trước giờ chạy (và grace ngắn sau giờ). */
export function isTpShiftConfirmVisible(trip, now = new Date()) {
  if (!isTpDriverTrip(trip)) return false
  const dep = tripDepartMs(trip)
  if (!Number.isFinite(dep)) return false
  const nowMs = now.getTime()
  const leadMs = TP_CONFIRM_LEAD_HOURS * 3600_000
  const graceMs = TP_CONFIRM_GRACE_AFTER_DEPART_MIN * 60_000
  const windowStart = dep - leadMs
  const windowEnd = dep + graceMs
  return nowMs >= windowStart && nowMs <= windowEnd
}

/**
 * Gộp danh sách chờ xác nhận cho banner: mọi chuyến lẻ đủ điều kiện + tối đa một ca TP sớm nhất.
 * @param {object[]} trips đã expand (schedule legs / TP)
 */
export function mergeDriverConfirmationBannerTrips(trips, now = new Date()) {
  if (!Array.isArray(trips) || trips.length === 0) return []

  const adHoc = []
  const tp = []

  for (const trip of trips) {
    if (isTpDriverTrip(trip)) {
      if (isTpShiftConfirmVisible(trip, now)) tp.push(trip)
    } else if (isAdHocDispatchConfirmRelevant(trip, now)) {
      adHoc.push(trip)
    }
  }

  tp.sort((a, b) => (tripDepartMs(a) || 0) - (tripDepartMs(b) || 0))
  const tpPick = tp.length > 0 ? [tp[0]] : []

  return [...adHoc, ...tpPick].sort((a, b) => (tripDepartMs(a) || 0) - (tripDepartMs(b) || 0))
}

/** Ẩn ca TP chưa xác nhận khỏi lịch hôm nay / carousel. */
export function isTpTripVisibleInTodaySchedule(trip) {
  if (!isTpDriverTrip(trip)) return true
  const s = String(trip?.status ?? '').trim().toLowerCase()
  return s !== 'assigned'
}
