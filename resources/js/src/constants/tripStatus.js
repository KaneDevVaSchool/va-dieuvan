/** Trạng thái chuyến — khớp backend `UpdateTripStatusRequest` + lifecycle chuyến. */
export const TRIP_STATUS = Object.freeze({
  PENDING: 'pending',
  APPROVED: 'approved',
  ASSIGNED: 'assigned',
  DRIVER_CONFIRMED: 'driver_confirmed',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  INCIDENT: 'incident',
})

/** Chuyến vẫn có thể thao tác phía tài xế (chi phí / đón khách trong luồng hiện tại). */
export const DRIVER_TRIP_ACTIVE_FOR_COSTS = Object.freeze([
  TRIP_STATUS.IN_PROGRESS,
  TRIP_STATUS.ASSIGNED,
  TRIP_STATUS.DRIVER_CONFIRMED,
  TRIP_STATUS.PENDING,
  TRIP_STATUS.APPROVED,
])

export function isTripCostEditableStatus(status) {
  const s = String(status ?? '').trim().toLowerCase()
  return DRIVER_TRIP_ACTIVE_FOR_COSTS.includes(s)
}

/** Chọn chuyến khi ghi chi phí phát sinh (gồm chuyến đã hoàn thành; loại hủy). */
export function isTripEligibleForDriverLinkedCost(status) {
  const s = String(status ?? '').trim().toLowerCase()
  return s !== 'cancelled' && s !== ''
}

/** Chuyến đã xong — không thêm chi phí trong màn chi tiết chuyến; dùng màn “Thêm chi phí phát sinh”. */
export function isTripCompletedForDriver(status) {
  return String(status ?? '').trim().toLowerCase() === TRIP_STATUS.COMPLETED
}

export function isTripBlockingDriverCostMutations(status) {
  return String(status ?? '').trim().toLowerCase() === TRIP_STATUS.CANCELLED
}

/** Lớp Tailwind pill trạng thái chuyến (theme admin list / light). */
export function tripStatusAdminPillClass(status) {
  const s = String(status ?? '').trim().toLowerCase()
  const map = {
    pending: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
    approved: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
    assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
    driver_confirmed: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    in_progress: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    completed: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    incident: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}
