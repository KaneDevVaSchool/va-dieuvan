import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripType } from '../util/labels'
import { formatVndCurrency, parseMoneyVnd } from '../util/money'

/**
 * Dự toán từ wizard_snapshot (+ service_price điều vận).
 * @param {import('vue').Ref|import('vue').ComputedRef} reqRef
 */
export function useRequestCostEstimate(reqRef) {
  const { t } = useI18n()

  const costEstimate = computed(() => {
    const r = unref(reqRef)
    const snap = r?.wizard_snapshot
    if (!snap?.form) return null

    const f = snap.form
    const money = parseMoneyVnd
    const breakdown = []

    let extras = 0
    if (f.need_porters) {
      const a = money(f.porter_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'porter', label: t('request_detail.cost_line_porter'), amount: a })
    }
    if (f.interprovincial) {
      const a = money(f.interprovincial_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'toll', label: t('request_detail.lbl_toll_estimate'), amount: a })
    }
    if (f.e1_use_3plus_days) {
      const a = money(f.e1_extra_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'e1', label: t('request_detail.cost_line_e1'), amount: a })
    }
    if (f.e2_door_pickup) {
      const a = money(f.e2_door_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'e2d', label: t('request_detail.cost_line_e2_door'), amount: a })
    }
    if (f.e2_driver_self) {
      const a = money(f.e2_driver_self_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'e2s', label: t('request_detail.cost_line_e2_self'), amount: a })
    }
    if (f.e2_after_21h) {
      const a = money(f.e2_after_21h_cost)
      extras += a
      if (a > 0) breakdown.push({ key: 'e2n', label: t('request_detail.cost_line_e2_late'), amount: a })
    }

    const rowTotal = (row) => money(row.unit_price) + money(row.extra_fee)
    const totalPass = (snap.passengerRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
    const totalBus = (snap.businessRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
    const cargoCosts = (snap.cargoRows ?? []).reduce((s, row) => s + money(row.cost), 0)

    if (totalPass > 0) breakdown.push({ key: 'pass', label: t('request_detail.cost_line_passenger_rows'), amount: totalPass })
    if (totalBus > 0) breakdown.push({ key: 'bus', label: t('request_detail.cost_line_business_rows'), amount: totalBus })
    if (cargoCosts > 0) breakdown.push({ key: 'cargo', label: t('request_detail.cost_line_cargo'), amount: cargoCosts })

    const total = extras + totalPass + totalBus + cargoCosts

    const passU = snap.passengerRows?.[0]?.unit_price
    const busU = snap.businessRows?.[0]?.unit_price
    const cargoU = snap.cargoRows?.[0]?.cost
    const evc = f.estimated_vehicle_cost
    const formRef = f.reference_unit_price
    const refRaw = passU || busU || cargoU || evc || formRef
    const refUnitLabel =
      refRaw != null && String(refRaw).trim() !== '' ? formatVndCurrency(money(refRaw)) : null

    const toll = f.interprovincial ? money(f.interprovincial_cost) : 0
    const tollLabel = f.interprovincial
      ? toll > 0
        ? formatVndCurrency(toll)
        : t('request_detail.toll_declared_zero')
      : null

    const distRaw = f.estimated_distance_km ?? snap.estimated_distance_km
    let distanceLabel = null
    if (distRaw != null && String(distRaw).trim() !== '') {
      const n = Number(String(distRaw).replace(',', '.'))
      if (Number.isFinite(n) && n > 0) {
        distanceLabel = `${n.toLocaleString('vi-VN')} km`
      }
    }

    let vehicleHint = f.pricing_vehicle_hint ? String(f.pricing_vehicle_hint) : null
    const wRaw = (snap.cargoRows ?? []).map((c) => c.weight).find((x) => String(x ?? '').trim())
    if (!vehicleHint) {
      if (r.trip_type === 'cargo' && wRaw) {
        const n = parseFloat(String(wRaw).replace(',', '.'))
        if (Number.isFinite(n)) vehicleHint = t('request_detail.vehicle_load_estimate_tons', { n })
        else vehicleHint = String(wRaw)
      } else if (r.trip_type) {
        vehicleHint = labelTripType(r.trip_type)
        if (r.passenger_count != null && r.passenger_count > 0) {
          vehicleHint += ` · ${t('request_detail.passengers_count_line', { n: r.passenger_count })}`
        }
      }
    }

    const hasDeclaredAmount =
      total > 0 || !!refUnitLabel || (f.interprovincial && toll > 0) || extras > 0 || cargoCosts > 0

    const dispatcherPriceLabel =
      r.service_price != null && Number(r.service_price) >= 0
        ? formatVndCurrency(Number(r.service_price))
        : null

    const declaredMismatch =
      dispatcherPriceLabel != null &&
      hasDeclaredAmount &&
      Math.abs(Number(r.service_price) - total) > 1

    return {
      distanceLabel,
      vehicleHint,
      refUnitLabel,
      tollLabel,
      total,
      breakdown,
      hasDeclaredAmount,
      declaredTotalLabel: hasDeclaredAmount ? formatVndCurrency(total) : null,
      dispatcherPriceLabel,
      declaredMismatch,
      priceFilledByUser: r.price_filled_by_user ?? null,
      priceFilledAt: r.price_filled_at ?? null,
    }
  })

  return { costEstimate }
}
