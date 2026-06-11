import { i18n } from '../i18n'

function tOrFallback(key, fallback) {
  try {
    const te = i18n?.global?.te
    const t = i18n?.global?.t
    if (typeof te === 'function' && te(key)) return t(key)
  } catch {
    // ignore
  }
  return fallback
}

/** @param {number|string|null|undefined} id */
export function formatTripCode(id) {
  if (id == null || id === '') return 'TRP-—'
  return `TRP-${String(id).padStart(4, '0')}`
}

/** @param {number|string|null|undefined} id */
export function formatCostNoteCode(id) {
  if (id == null || id === '') return 'GCN-—'
  return `GCN-${String(id).padStart(4, '0')}`
}

/** @param {string|undefined|null} v */
export function labelTripType(v) {
  const key = v ? `labels.trip_type.${v}` : ''
  const m = {
    door_to_door: 'Đưa đón (D2D)',
    point_to_point: 'Điểm — điểm',
    business: 'Công tác',
    cargo: 'Hàng hóa',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelRequestStatus(v) {
  const key = v ? `labels.request_status.${v}` : ''
  const m = {
    draft: 'Nháp',
    pending: 'Chờ duyệt',
    price_filled: 'Chờ Trưởng đơn vị duyệt',
    approved: 'Đã duyệt',
    rejected: 'Từ chối',
    cancelled: 'Đã huỷ',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelSourceChannel(v) {
  const key = v ? `labels.source_channel.${v}` : ''
  const m = {
    portal: 'Portal',
    zalo: 'Zalo',
    paper: 'Phiếu giấy',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelPaperStatus(v) {
  const key = v ? `labels.paper_status.${v}` : ''
  const m = {
    pending: 'Chưa nhận',
    received: 'Đã nhận',
    digitally_signed: 'Đã ký số',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelCargoStatus(v) {
  const key = v ? `labels.cargo_status.${v}` : ''
  const m = {
    pending: 'Chờ xử lý',
    picked_up: 'Đã lấy hàng',
    in_transit: 'Đang vận chuyển',
    delivered: 'Đã giao',
    failed: 'Thất bại',
    cancelled: 'Đã huỷ',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelTripStatus(v) {
  const key = v ? `labels.trip_status.${v}` : ''
  const m = {
    pending: 'Chờ xử lý',
    approved: 'Đã duyệt',
    assigned: 'Đã phân công',
    driver_confirmed: 'TX xác nhận',
    in_progress: 'Đang chạy',
    completed: 'Hoàn thành',
    cancelled: 'Đã huỷ',
    incident: 'Sự cố',
  }
  const fb = m[v] ?? v ?? '—'
  return key ? tOrFallback(key, fb) : fb
}

/** @param {string|undefined|null} v */
export function labelPricingNoteCategory(v) {
  const m = {
    passenger_general: 'Xe khách — ghi chú chung',
    passenger_driver: 'Xe khách — tài xế',
    passenger_cancel: 'Xe khách — huỷ xe',
    cargo_general: 'Hàng hóa — ghi chú',
  }
  return m[v] ?? v ?? '—'
}

export function formatVnd(n) {
  const x = Number(n ?? 0)
  return new Intl.NumberFormat('vi-VN').format(x) + ' đ'
}

/** Chỉ phần số, dấu phân cách kiểu vi-VN (vd. 1000000 → 1.000.000) — dùng cho ô nhập tiền. */
export function formatVndDigitsInput(digits) {
  const d = String(digits ?? '').replace(/\D/g, '')
  if (!d) return ''
  const n = Number(d)
  if (!Number.isFinite(n) || n < 0) return ''
  return new Intl.NumberFormat('vi-VN').format(n)
}
