import {
  isPassengerRowFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
} from '../composables/dispatchWizardConstants'

function rowGuestsValue(row) {
  const g = parseInt(String(row?.guests ?? '1'), 10)
  return Number.isFinite(g) && g >= 1 ? g : 1
}

/**
 * Tổng khách theo loại chuyến — cùng quy tắc lọc dòng như `useDispatchScheduleCards`.
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
  if (tt !== 'business') {
    for (const r of snapshot.passengerRows ?? []) {
      if (isPassengerRowFilled(r)) sum += rowGuestsValue(r)
    }
  }
  if (tt !== 'point_to_point') {
    for (const r of snapshot.businessRows ?? []) {
      if (isBusinessRowFilled(r)) sum += rowGuestsValue(r)
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
