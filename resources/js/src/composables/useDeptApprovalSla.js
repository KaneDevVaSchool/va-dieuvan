/**
 * SLA phê duyệt trưởng đơn vị — hạn = price_filled_at + dept_approval_sla_hours.
 * @param {import('vue').Ref<number>|number} slaHoursRef
 */
import { computed, unref } from 'vue'

const DEFAULT_SLA_HOURS = 24

/**
 * @param {object|null|undefined} req
 * @param {number} [slaHours]
 * @returns {{ tone: 'green'|'orange'|'red'|'muted', labelKey: string, labelParams: Record<string, number|string>, overdue: boolean, dueAt: Date|null }}
 */
export function computeDeptApprovalSla(req, slaHours = DEFAULT_SLA_HOURS) {
  const hours = Math.max(1, Number(slaHours) || DEFAULT_SLA_HOURS)
  if (!req || req.status !== 'price_filled') {
    return { tone: 'muted', labelKey: 'dept.inbox_sla_waiting_dispatch', labelParams: {}, overdue: false, dueAt: null }
  }
  const filledAt = req.price_filled_at ? new Date(req.price_filled_at) : null
  if (!filledAt || Number.isNaN(filledAt.getTime())) {
    return { tone: 'orange', labelKey: 'dept.inbox_sla_unknown', labelParams: {}, overdue: false, dueAt: null }
  }
  const dueAt = new Date(filledAt.getTime() + hours * 60 * 60 * 1000)
  const now = Date.now()
  const diffMs = dueAt.getTime() - now
  if (diffMs <= 0) {
    const overdueHours = Math.max(1, Math.ceil(Math.abs(diffMs) / (60 * 60 * 1000)))
    const overdueMins = Math.ceil(Math.abs(diffMs) / (60 * 1000))
    if (overdueMins < 60) {
      return {
        tone: 'red',
        labelKey: 'dept.inbox_sla_overdue_minutes',
        labelParams: { minutes: overdueMins },
        overdue: true,
        dueAt,
      }
    }
    return {
      tone: 'red',
      labelKey: 'dept.inbox_sla_overdue_hours',
      labelParams: { hours: overdueHours },
      overdue: true,
      dueAt,
    }
  }
  const remainHours = diffMs / (60 * 60 * 1000)
  if (remainHours < 1) {
    const minutes = Math.max(1, Math.ceil(diffMs / (60 * 1000)))
    return {
      tone: 'orange',
      labelKey: 'dept.inbox_sla_remain_minutes',
      labelParams: { minutes },
      overdue: false,
      dueAt,
    }
  }
  const hoursLeft = Math.ceil(remainHours)
  return {
    tone: remainHours <= 3 ? 'orange' : 'green',
    labelKey: 'dept.inbox_sla_remain_hours',
    labelParams: { hours: hoursLeft },
    overdue: false,
    dueAt,
  }
}

export function useDeptApprovalSla(slaHoursRef) {
  return {
    slaHours: computed(() => Math.max(1, Number(unref(slaHoursRef)) || DEFAULT_SLA_HOURS)),
    computeFor: (req) => computeDeptApprovalSla(req, unref(slaHoursRef)),
  }
}

/** @param {object} req */
export function isDeptPriceFilledToday(req) {
  const at = req?.price_filled_at
  if (!at) return false
  const d = new Date(at)
  if (Number.isNaN(d.getTime())) return false
  return d.toDateString() === new Date().toDateString()
}

/**
 * @param {object} req
 * @param {string} kpiFilter '' | 'overdue' | 'urgent' | 'today'
 * @param {number} [slaHours]
 */
export function matchesDeptInboxKpiFilter(req, kpiFilter, slaHours = DEFAULT_SLA_HOURS) {
  if (!kpiFilter) return true
  if (kpiFilter === 'overdue') return computeDeptApprovalSla(req, slaHours).overdue
  if (kpiFilter === 'urgent') return Boolean(req?.is_urgent)
  if (kpiFilter === 'today') return isDeptPriceFilledToday(req)
  return true
}
