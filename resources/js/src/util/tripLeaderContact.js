import { isAutoPassengerLabel } from './passengerDisplayName'

/**
 * Liên hệ trưởng đoàn / người liên hệ cho một chuyến (dùng chung cho banner xác
 * nhận và trang chi tiết tài xế).
 *
 * Thứ tự ưu tiên:
 *   1. `trip.contact` đã tính sẵn từ backend (payload danh sách tài xế).
 *   2. Chuyến Công tác: coordinator (trưởng đoàn BM.03) → person_in_charge hợp lệ.
 *   3. Fallback: người yêu cầu (requester).
 *
 * @typedef {{ name: string, phone: string|null, role: 'leader'|'coordinator'|'requester'|'contact' }} TripContact
 */

function normPhone(raw) {
  const digits = String(raw ?? '').replace(/\D+/g, '')
  return digits || null
}

function dispatchReqOf(trip) {
  return trip?.dispatch_request ?? trip?.dispatchRequest ?? null
}

/**
 * @param {Record<string, any> | null | undefined} trip
 * @returns {TripContact | null}
 */
export function resolveTripLeaderContact(trip) {
  if (!trip) return null

  // 1) Backend đã tính sẵn.
  const c = trip.contact
  if (c && String(c.name ?? '').trim()) {
    return {
      name: String(c.name).trim(),
      phone: normPhone(c.phone),
      role: c.role || 'contact',
    }
  }

  const dr = dispatchReqOf(trip)
  const snap = dr?.wizard_snapshot ?? null

  // 2) Chuyến Công tác: trưởng đoàn / điều phối.
  if (dr?.trip_type === 'business' && snap) {
    const form = snap.form ?? {}
    const coordName = String(form.coordinator_name ?? '').trim()
    if (coordName) {
      return { name: coordName, phone: normPhone(form.coordinator_phone), role: 'leader' }
    }
    const rows = [...(snap.businessRows ?? []), ...(snap.passengerRows ?? [])]
    for (const r of rows) {
      const name = String(r?.person_in_charge ?? '').trim()
      if (name && !isAutoPassengerLabel(name)) {
        return { name, phone: normPhone(r?.phone), role: 'leader' }
      }
    }
  }

  // 3) Fallback: người yêu cầu.
  const reqName = String(dr?.requester?.name ?? trip.requester_name ?? '').trim()
  if (reqName) {
    return { name: reqName, phone: normPhone(dr?.requester?.phone), role: 'requester' }
  }

  return null
}
