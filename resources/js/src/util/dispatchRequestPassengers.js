import {
  isPassengerRowCounted,
  isBusinessRowCounted,
  isCargoRowFilled,
} from '../composables/dispatchWizardConstants'
import { isAutoPassengerLabel } from './passengerDisplayName'

function rowGuestsValue(row) {
  const raw = String(row?.guests ?? '').trim().replace(/\s/g, '')
  if (raw === '') return 0
  const g = Number(raw)
  return Number.isFinite(g) && g >= 1 ? g : 0
}

/**
 * Tổng khách theo loại chuyến — khớp `DispatchWizardPassengerCount::sumFromSnapshot` (PHP).
 * @param {object | null | undefined} snapshot wizard_snapshot
 * @param {string} [tripType] door_to_door | point_to_point | business | cargo
 */
export function wizardSnapshotGuestTotal(snapshot, tripType = '') {
  if (!snapshot || typeof snapshot !== 'object') return 0
  const tt = String(tripType || '').trim()

  if (tt === 'cargo') {
    let sum = 0
    for (const r of snapshot.cargoRows ?? []) {
      if (!isCargoRowFilled(r)) continue
      const q = parseInt(String(r?.qty ?? '1'), 10)
      sum += Number.isFinite(q) && q >= 1 ? q : 1
    }
    return sum
  }

  let sum = 0
  if (tt === 'business') {
    for (const r of snapshot.businessRows ?? []) {
      if (isBusinessRowCounted(r)) sum += rowGuestsValue(r)
    }
  } else {
    for (const r of snapshot.passengerRows ?? []) {
      if (isPassengerRowCounted(r)) sum += rowGuestsValue(r)
    }
  }
  return sum
}

/**
 * Số khách / HS hiển thị trên chuyến & phiếu (CLB định kỳ lưu student_count_actual).
 * @param {{ student_count_actual?: number | string | null, passenger_count?: number | string | null } | null | undefined} dr
 */
export function dispatchRequestEffectivePassengerCount(dr) {
  if (!dr) return 0
  const actual = dr.student_count_actual
  if (actual != null && actual !== '') {
    const n = Number(actual)
    if (Number.isFinite(n) && n > 0) return Math.round(n)
  }
  const pc = Number(dr.passenger_count)
  return Number.isFinite(pc) && pc > 0 ? Math.round(pc) : 0
}

/**
 * Số khách hiển thị — ưu tiên tổng wizard_snapshot (tránh lệch passenger_count DB).
 * @param {{ wizard_snapshot?: object, trip_type?: string, student_count_actual?: number|string|null, passenger_count?: number|string|null } | null | undefined} dr
 */
export function dispatchRequestDisplayPassengerCount(dr) {
  if (!dr) return 0
  const actual = dr.student_count_actual
  if (actual != null && actual !== '') {
    const n = Number(actual)
    if (Number.isFinite(n) && n > 0) return Math.round(n)
  }
  const fromSnapshot = wizardSnapshotGuestTotal(dr?.wizard_snapshot, dr?.trip_type ?? '')
  if (fromSnapshot > 0) return fromSnapshot
  return dispatchRequestEffectivePassengerCount(dr)
}

/**
 * Số slot trip_passengers có dữ liệu thực (không chỉ nhãn "Khách N" placeholder).
 * @param {Array<{ name?: string, phone?: string, note?: string }> | null | undefined} tplist
 */
export function meaningfulNamedTripPassengerCount(tplist) {
  if (!Array.isArray(tplist) || tplist.length === 0) return 0
  let last = 0
  for (let i = 0; i < tplist.length; i++) {
    const p = tplist[i]
    const name = String(p?.name ?? '').trim()
    const phone = String(p?.phone ?? '').trim()
    const note = String(p?.note ?? '').trim()
    if ((name && !isAutoPassengerLabel(name)) || phone || note) {
      last = i + 1
    }
  }
  return last
}

/**
 * Số khách hiển thị trên màn chi tiết chuyến — không phóng theo length trip_passengers.
 * (Slot "Hành khách N" vẫn tính vào số khai báo; chỉ dùng passenger_count / snapshot làm nguồn.)
 * @param {{ wizard_snapshot?: object, trip_type?: string, student_count_actual?: number|string|null, passenger_count?: number|string|null } | null | undefined} dr
 * @param {Array<{ name?: string, phone?: string, note?: string }> | null | undefined} [_tplist]
 */
export function tripNamedPassengerDisplayCount(dr, _tplist) {
  const display = dispatchRequestDisplayPassengerCount(dr)
  if (display > 0) return display
  return meaningfulNamedTripPassengerCount(_tplist)
}
