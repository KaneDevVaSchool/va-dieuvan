import { computed, unref, type Ref, type ComputedRef } from 'vue'

/**
 * Financial Control Center — tính toán toàn bộ chỉ số tài chính của một chuyến
 * từ danh sách chi phí (trip.costs), doanh thu dự kiến và dự toán chi phí.
 *
 * Mục tiêu: người dùng trả lời trong < 5s — đã chi bao nhiêu, còn bao nhiêu ngân
 * sách, lãi/lỗ, khoản chờ duyệt, khoản thiếu biên lai, có vượt ngân sách không.
 */

export type CostPerson = {
  id?: number
  name?: string | null
  email?: string | null
  avatar_url?: string | null
}

export type TripCostRow = {
  id?: number
  type?: string | null
  amount?: number | string | null
  currency?: string | null
  status?: string | null
  description?: string | null
  receipt_url?: string | null
  rejection_reason?: string | null
  created_at?: string | null
  confirmed_at?: string | null
  created_by?: number | null
  confirmed_by?: number | null
  creator?: CostPerson | null
  confirmer?: CostPerson | null
  attachments_count?: number | null
  vehicle_id?: number | null
}

/** Nhóm chi phí chuẩn hoá (khớp brief: xăng, lương TX, cầu đường, bãi, ăn, KS, khác). */
export type CostGroupKey =
  | 'fuel'
  | 'driver_salary'
  | 'toll'
  | 'parking'
  | 'food'
  | 'hotel'
  | 'other'

export const COST_GROUPS: Record<
  CostGroupKey,
  { labelKey: string; color: string; barClass: string; dotClass: string }
> = {
  fuel: {
    labelKey: 'trip_detail.costs.type_fuel',
    color: 'amber',
    barClass: 'bg-amber-500',
    dotClass: 'bg-amber-500',
  },
  driver_salary: {
    labelKey: 'cost_center.group_driver_salary',
    color: 'violet',
    barClass: 'bg-violet-500',
    dotClass: 'bg-violet-500',
  },
  toll: {
    labelKey: 'trip_detail.costs.type_toll',
    color: 'blue',
    barClass: 'bg-blue-500',
    dotClass: 'bg-blue-500',
  },
  parking: {
    labelKey: 'trip_detail.costs.type_parking',
    color: 'cyan',
    barClass: 'bg-cyan-500',
    dotClass: 'bg-cyan-500',
  },
  food: {
    labelKey: 'cost_center.group_food',
    color: 'rose',
    barClass: 'bg-rose-500',
    dotClass: 'bg-rose-500',
  },
  hotel: {
    labelKey: 'cost_center.group_hotel',
    color: 'fuchsia',
    barClass: 'bg-fuchsia-500',
    dotClass: 'bg-fuchsia-500',
  },
  other: {
    labelKey: 'trip_detail.costs.type_other',
    color: 'slate',
    barClass: 'bg-slate-400',
    dotClass: 'bg-slate-400',
  },
}

const GROUP_ORDER: CostGroupKey[] = [
  'fuel',
  'driver_salary',
  'toll',
  'parking',
  'food',
  'hotel',
  'other',
]

/** Map type tự do (slug VN/EN) về nhóm chuẩn. */
export function costGroupOf(type: string | null | undefined): CostGroupKey {
  const x = String(type ?? '')
    .trim()
    .toLowerCase()
  if (!x) return 'other'
  if (/(fuel|xang|xăng|dau|dầu|gas|petrol|diesel)/.test(x)) return 'fuel'
  if (/(salary|luong|lương|labor|labour|nhan_cong|nhân|driver_pay|wage|allowance|phu_cap|phụ)/.test(x))
    return 'driver_salary'
  if (/(toll|cau_duong|cầu|duong|đường|bot|phi_duong)/.test(x)) return 'toll'
  if (/(parking|bai|bãi|park|giu_xe|gửi)/.test(x)) return 'parking'
  if (/(food|meal|an_uong|ăn|uong|uống|com|cơm)/.test(x)) return 'food'
  if (/(hotel|lodg|khach_san|khách|san|sạn|nghi|nghỉ|room)/.test(x)) return 'hotel'
  return 'other'
}

export function toAmount(v: unknown): number {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

const PENDING_STATUSES = new Set(['submitted', 'draft', 'pending'])
const APPROVED_STATUSES = new Set(['confirmed', 'approved'])

export function isPendingCost(c: TripCostRow): boolean {
  return PENDING_STATUSES.has(String(c.status ?? '').toLowerCase())
}
export function isApprovedCost(c: TripCostRow): boolean {
  return APPROVED_STATUSES.has(String(c.status ?? '').toLowerCase())
}
export function isRejectedCost(c: TripCostRow): boolean {
  return String(c.status ?? '').toLowerCase() === 'rejected'
}
export function isPaidCost(c: TripCostRow): boolean {
  return String(c.status ?? '').toLowerCase() === 'paid'
}

const DAY_MS = 86_400_000

export type TripFinancialsOptions = {
  /** Số ngày coi là "chờ duyệt quá lâu" cho insight. */
  staleApprovalDays?: number
  /** Ngưỡng % nhóm chi phí được coi là "cao bất thường" cho insight. */
  dominantShareThreshold?: number
}

export function useTripFinancials(
  costsRef: Ref<TripCostRow[] | null | undefined> | ComputedRef<TripCostRow[] | null | undefined>,
  revenueRef: Ref<number> | ComputedRef<number>,
  budgetRef: Ref<number> | ComputedRef<number>,
  options: TripFinancialsOptions = {},
) {
  const staleDays = options.staleApprovalDays ?? 7
  const dominantShare = options.dominantShareThreshold ?? 0.45

  const costs = computed<TripCostRow[]>(() => unref(costsRef) ?? [])
  const revenue = computed(() => toAmount(unref(revenueRef)))
  const budget = computed(() => toAmount(unref(budgetRef)))

  const approvedCosts = computed(() => costs.value.filter(isApprovedCost))
  const pendingCosts = computed(() => costs.value.filter(isPendingCost))
  const rejectedCosts = computed(() => costs.value.filter(isRejectedCost))
  const paidCosts = computed(() => costs.value.filter(isPaidCost))

  const sum = (list: TripCostRow[]) => list.reduce((s, c) => s + toAmount(c.amount), 0)

  /** Chi phí thực tế = đã duyệt + đã thanh toán (đã chốt sổ). */
  const actualTotal = computed(() => sum(approvedCosts.value) + sum(paidCosts.value))
  const pendingTotal = computed(() => sum(pendingCosts.value))
  const pendingCount = computed(() => pendingCosts.value.length)

  /** Chênh lệch so với dự toán: âm = tiết kiệm, dương = vượt. */
  const variance = computed(() => actualTotal.value - budget.value)
  const variancePct = computed(() =>
    budget.value > 0 ? (variance.value / budget.value) * 100 : null,
  )
  const isOverBudget = computed(() => budget.value > 0 && actualTotal.value > budget.value)
  const isSaving = computed(() => budget.value > 0 && actualTotal.value < budget.value)

  /** Lợi nhuận dự kiến = doanh thu − chi phí thực tế. */
  const profit = computed(() => revenue.value - actualTotal.value)
  const profitMargin = computed(() =>
    revenue.value > 0 ? (profit.value / revenue.value) * 100 : null,
  )

  /** % ngân sách đã dùng (gồm cả chờ duyệt cho cảnh báo sớm). */
  const budgetUsedPct = computed(() =>
    budget.value > 0 ? (actualTotal.value / budget.value) * 100 : null,
  )
  const projectedUsedPct = computed(() =>
    budget.value > 0 ? ((actualTotal.value + pendingTotal.value) / budget.value) * 100 : null,
  )

  /** 🟢 / 🟡 / 🔴 — trạng thái sức khoẻ tài chính. */
  const health = computed<{
    level: 'good' | 'warning' | 'danger' | 'unknown'
    usedPct: number | null
  }>(() => {
    const used = budgetUsedPct.value
    const projected = projectedUsedPct.value
    if (used == null) return { level: 'unknown', usedPct: null }
    if (used > 100) return { level: 'danger', usedPct: used }
    if (used >= 90 || (projected != null && projected > 100))
      return { level: 'warning', usedPct: used }
    return { level: 'good', usedPct: used }
  })

  /** Phân bổ theo nhóm (chỉ tính khoản đã chốt — actual). */
  const breakdown = computed(() => {
    const source = [...approvedCosts.value, ...paidCosts.value]
    const totals = new Map<CostGroupKey, number>()
    for (const c of source) {
      const amt = toAmount(c.amount)
      if (amt <= 0) continue
      const g = costGroupOf(c.type)
      totals.set(g, (totals.get(g) ?? 0) + amt)
    }
    const grand = [...totals.values()].reduce((s, v) => s + v, 0)
    const rows = GROUP_ORDER.filter((k) => (totals.get(k) ?? 0) > 0).map((key) => {
      const amount = totals.get(key) ?? 0
      return {
        key,
        labelKey: COST_GROUPS[key].labelKey,
        barClass: COST_GROUPS[key].barClass,
        dotClass: COST_GROUPS[key].dotClass,
        amount,
        pct: grand > 0 ? (amount / grand) * 100 : 0,
      }
    })
    rows.sort((a, b) => b.amount - a.amount)
    return { rows, grand }
  })

  /** Dòng thời gian — mới nhất trước. */
  const timeline = computed(() =>
    [...costs.value]
      .filter((c) => c.created_at)
      .sort((a, b) => new Date(b.created_at!).getTime() - new Date(a.created_at!).getTime()),
  )

  const missingReceiptCount = computed(
    () =>
      costs.value.filter(
        (c) =>
          !isRejectedCost(c) &&
          !c.receipt_url &&
          !(Number(c.attachments_count) > 0),
      ).length,
  )

  const staleApprovalCount = computed(() => {
    const now = Date.now()
    return pendingCosts.value.filter((c) => {
      if (!c.created_at) return false
      const t = new Date(c.created_at).getTime()
      return Number.isFinite(t) && now - t > staleDays * DAY_MS
    }).length
  })

  /** Cảnh báo thông minh — sắp xếp theo mức độ nghiêm trọng. */
  const insights = computed(() => {
    const list: {
      key: string
      tone: 'danger' | 'warning' | 'info'
      i18nKey: string
      params?: Record<string, unknown>
    }[] = []

    if (isOverBudget.value && variancePct.value != null) {
      list.push({
        key: 'over_budget',
        tone: 'danger',
        i18nKey: 'cost_center.insight_over_budget',
        params: { pct: Math.abs(Math.round(variancePct.value)) },
      })
    }

    if (staleApprovalCount.value > 0) {
      list.push({
        key: 'stale_approval',
        tone: 'warning',
        i18nKey: 'cost_center.insight_stale_approval',
        params: { n: staleApprovalCount.value, days: staleDays },
      })
    }

    if (missingReceiptCount.value > 0) {
      list.push({
        key: 'missing_receipt',
        tone: 'warning',
        i18nKey: 'cost_center.insight_missing_receipt',
        params: { n: missingReceiptCount.value },
      })
    }

    const top = breakdown.value.rows[0]
    if (top && breakdown.value.grand > 0 && top.pct >= dominantShare * 100) {
      list.push({
        key: `dominant_${top.key}`,
        tone: 'info',
        i18nKey: 'cost_center.insight_dominant_group',
        params: { groupKey: top.labelKey, pct: Math.round(top.pct) },
      })
    }

    if (pendingCount.value > 0) {
      list.push({
        key: 'pending_review',
        tone: 'info',
        i18nKey: 'cost_center.insight_pending_review',
        params: { n: pendingCount.value },
      })
    }

    return list
  })

  return {
    costs,
    revenue,
    budget,
    approvedCosts,
    pendingCosts,
    rejectedCosts,
    paidCosts,
    actualTotal,
    pendingTotal,
    pendingCount,
    variance,
    variancePct,
    isOverBudget,
    isSaving,
    profit,
    profitMargin,
    budgetUsedPct,
    projectedUsedPct,
    health,
    breakdown,
    timeline,
    missingReceiptCount,
    staleApprovalCount,
    insights,
  }
}
