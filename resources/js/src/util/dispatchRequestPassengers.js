import {
  isPassengerRowFilled,
  isBusinessRowFilled,
} from '../composables/dispatchWizardConstants'

function rowGuestsValue(row) {
  const g = parseInt(String(row?.guests ?? '1'), 10)
  return Number.isFinite(g) && g >= 1 ? g : 1
}

/**
 * Tổng `guests` trên các dòng lịch trình đã điền (passengerRows + businessRows).
 * @param {object | null | undefined} snapshot wizard_snapshot
 */
export function wizardSnapshotGuestTotal(snapshot) {
  if (!snapshot || typeof snapshot !== 'object') return 0
  let sum = 0
  for (const r of snapshot.passengerRows ?? []) {
    if (isPassengerRowFilled(r)) sum += rowGuestsValue(r)
  }
  for (const r of snapshot.businessRows ?? []) {
    if (isBusinessRowFilled(r)) sum += rowGuestsValue(r)
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
