import { dispatchRequestDisplayPassengerCount } from './dispatchRequestPassengers'

/**
 * Mã tham chiếu phiếu — khớp DispatchRequestMailPresenter::referenceCode (PHP).
 * @param {{ id?: number|string, created_at?: string|null }} req
 */
export function formatDispatchRequestRefCode(req) {
  const id = req?.id
  if (id == null || id === '') return ''
  const d = req.created_at ? new Date(req.created_at) : new Date()
  if (Number.isNaN(d.getTime())) return `REQ-${String(id).padStart(3, '0')}`
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `REQ-${y}${m}-${String(id).padStart(3, '0')}`
}

/** @param {string|null|undefined} value */
export function formatPortalPlace(value) {
  const s = String(value ?? '').trim()
  return s || '—'
}

/**
 * Số khách hiển thị trên cổng — ưu tiên tổng từ wizard_snapshot (tránh lệch passenger_count).
 * @param {{ wizard_snapshot?: object, trip_type?: string, student_count_actual?: number|string|null, passenger_count?: number|string|null } | null | undefined} req
 */
export function portalRequestPassengerCount(req) {
  return dispatchRequestDisplayPassengerCount(req)
}
