import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { parseMoneyVnd } from '../util/money'
import {
  itineraryRowHeading,
  itineraryRowRouteLabel,
  resolveItineraryTripType,
} from '../util/requestItineraryRowDisplay'

/**
 * @param {Record<string, unknown>|null|undefined} req
 * @returns {Array<Record<string, unknown>>}
 */
export function wizardItineraryRows(req) {
  if (!req) return []
  const snap = req.wizard_snapshot ?? {}
  if (req.trip_type === 'cargo') {
    return Array.isArray(snap.cargoRows) ? snap.cargoRows : []
  }
  if (req.trip_type === 'business') {
    return Array.isArray(snap.businessRows) ? snap.businessRows : []
  }
  return Array.isArray(snap.passengerRows) ? snap.passengerRows : []
}

/**
 * Dòng chi phí theo chặng cho tab Phê duyệt (staff).
 * @param {import('vue').Ref|import('vue').ComputedRef} reqRef
 */
export function useRequestApprovalLegLines(reqRef) {
  const { t } = useI18n()

  const itineraryRowCount = computed(() => wizardItineraryRows(unref(reqRef)).length)

  const approvalLegLines = computed(() => {
    const r = unref(reqRef)
    const rows = wizardItineraryRows(r)
    if (rows.length <= 1) return []

    const isCargo = r?.trip_type === 'cargo'
    const tripType = resolveItineraryTripType(isCargo, r?.trip_type === 'business')

    return rows.map((row, index) => {
      const unit = isCargo ? parseMoneyVnd(row?.cost) : parseMoneyVnd(row?.unit_price)
      const extra = isCargo ? 0 : parseMoneyVnd(row?.extra_fee)
      const heading = itineraryRowHeading(row, index, { tripType, t })
      const route = itineraryRowRouteLabel(row)

      return {
        line_key: `approval-leg-${index}`,
        leg_seq: index + 1,
        unit_price: unit,
        extra_fee: extra,
        amount: unit + extra,
        description: heading,
        leg_route: route && route !== heading ? route : route,
      }
    })
  })

  return { approvalLegLines, itineraryRowCount }
}
