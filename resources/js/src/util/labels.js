/** @param {string|undefined|null} v */
export function labelTripType(v) {
  const m = {
    door_to_door: 'Đưa đón (D2D)',
    point_to_point: 'Điểm — điểm',
    business: 'Công tác',
    cargo: 'Hàng hóa',
  }
  return m[v] ?? v ?? '—'
}

/** @param {string|undefined|null} v */
export function labelRequestStatus(v) {
  const m = {
    draft: 'Nháp',
    pending: 'Chờ duyệt',
    approved: 'Đã duyệt',
    rejected: 'Từ chối',
    cancelled: 'Đã huỷ',
  }
  return m[v] ?? v ?? '—'
}

/** @param {string|undefined|null} v */
export function labelSourceChannel(v) {
  const m = {
    portal: 'Portal',
    zalo: 'Zalo',
    paper: 'Phiếu giấy',
  }
  return m[v] ?? v ?? '—'
}

/** @param {string|undefined|null} v */
export function labelPaperStatus(v) {
  const m = {
    pending: 'Chưa nhận',
    received: 'Đã nhận',
    digitally_signed: 'Đã ký số',
  }
  return m[v] ?? v ?? '—'
}

/** @param {string|undefined|null} v */
export function labelTripStatus(v) {
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
  return m[v] ?? v ?? '—'
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
