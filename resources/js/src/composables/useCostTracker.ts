import { computed, type Ref } from 'vue'

export const COST_TRACKER_TYPES = [
  { value: 'fuel', labelKey: 'trip_detail.costs.type_fuel', color: 'amber' as const },
  { value: 'toll', labelKey: 'trip_detail.costs.type_toll', color: 'blue' as const },
  { value: 'parking', labelKey: 'trip_detail.costs.type_parking', color: 'purple' as const },
  /** labor + other aggregate into gray chip in breakdown */
  { value: 'other', labelKey: 'trip_detail.costs.type_other', color: 'gray' as const },
] as const

export type CostRow = {
  id?: number
  type?: string | null
  amount?: number | string | null
  currency?: string | null
  status?: string | null
  description?: string | null
  receipt_url?: string | null
}

function normalizeCostType(t: string | null | undefined): string {
  return String(t ?? '')
    .trim()
    .toLowerCase()
}

/** Map labor → other for chip + breakdown bars */
export function mapCostTypeForUi(type: string | null | undefined): string {
  const x = normalizeCostType(type)
  if (x === 'labor') return 'other'
  return x
}

export function useCostTypeBreakdown(costs: Ref<CostRow[] | null | undefined>) {
  return computed(() => {
    const list = costs.value ?? []
    const totals: Record<string, number> = { fuel: 0, toll: 0, parking: 0, other: 0 }
    for (const c of list) {
      const raw = Number(c.amount)
      if (!Number.isFinite(raw) || raw <= 0) continue
      const key = mapCostTypeForUi(c.type)
      if (key === 'fuel') totals.fuel += raw
      else if (key === 'toll') totals.toll += raw
      else if (key === 'parking') totals.parking += raw
      else totals.other += raw
    }
    const grand = totals.fuel + totals.toll + totals.parking + totals.other
    return { totals, grand }
  })
}
