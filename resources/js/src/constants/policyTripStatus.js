/** Trạng thái policy_trips — khớp ENUM backend. */
export const POLICY_TRIP_STATUS = Object.freeze({
  SCHEDULED: 'scheduled',
  ASSIGNED: 'assigned',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
})

/** Lớp Tailwind pill cho trạng thái policy trip. */
export function policyTripStatusPillClass(status) {
  const s = String(status ?? '').trim().toLowerCase()
  const map = {
    scheduled: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
    assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
    in_progress: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    completed: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

/** Label tiếng Việt cho trạng thái policy trip. */
export function labelPolicyTripStatus(status) {
  const map = {
    scheduled: 'Chờ gán tài xế',
    assigned: 'Đã phân công',
    in_progress: 'Đang chạy',
    completed: 'Hoàn thành',
    cancelled: 'Đã huỷ',
  }
  return map[String(status ?? '').trim().toLowerCase()] ?? status ?? '—'
}

/** Trạng thái student_policies */
export const STUDENT_POLICY_STATUS = Object.freeze({
  ACTIVE: 'active',
  INACTIVE: 'inactive',
  SUSPENDED: 'suspended',
})

export function studentPolicyStatusPillClass(status) {
  const s = String(status ?? '').trim().toLowerCase()
  const map = {
    active: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    inactive: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    suspended: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
}

export function labelStudentPolicyStatus(status) {
  const map = { active: 'Đang hoạt động', inactive: 'Ngưng', suspended: 'Tạm ngưng' }
  return map[String(status ?? '').toLowerCase()] ?? status ?? '—'
}

/** day_type trong school_calendars */
export function dayTypePillClass(dayType) {
  const map = {
    school_day: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    makeup_day: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    holiday: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    weekend: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
  }
  return map[String(dayType ?? '').toLowerCase()] ?? 'bg-slate-100 text-slate-600'
}

export function labelDayType(dayType) {
  const map = {
    school_day: 'Ngày học',
    makeup_day: 'Học bù',
    holiday: 'Nghỉ lễ',
    weekend: 'Cuối tuần',
  }
  return map[String(dayType ?? '').toLowerCase()] ?? dayType ?? '—'
}

/** Ca học */
export function labelTimeSlot(slot) {
  const map = { morning: 'Sáng', afternoon: 'Chiều' }
  return map[String(slot ?? '').toLowerCase()] ?? slot ?? '—'
}

/** Lý do vắng */
export function absenceReasonPillClass(reason) {
  const map = {
    absent_reported: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
    absent_no_notice: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    late_cancellation: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
  }
  return map[String(reason ?? '').toLowerCase()] ?? 'bg-slate-100 text-slate-600'
}

export function labelAbsenceReason(reason) {
  const map = {
    absent_reported: 'Vắng có phép',
    absent_no_notice: 'Vắng không phép',
    late_cancellation: 'Hủy muộn',
  }
  return map[String(reason ?? '').toLowerCase()] ?? reason ?? '—'
}
