import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../store'
import { dispatchRequestDisplayPassengerCount } from '../util/dispatchRequestPassengers'
import { formatVndCurrency, parseMoneyVnd, VND_CURRENCY_SUFFIX } from '../util/money'
import { rdEmptyLabel, formatVndOrRdEmpty, isRdLegacyDash } from '../util/requestDetailEmpty'

/**
 * Copy trạng thái + mini-stepper cho tab Phê duyệt (staff).
 * @param {import('vue').Ref|import('vue').ComputedRef} reqRef
 * @param {import('vue').Ref|import('vue').ComputedRef} ctxRef
 */
export function useStaffRequestApprovalWorkspace(reqRef, ctxRef) {
  const { t } = useI18n()
  const auth = useAuthStore()

  const handlerName = computed(() => {
    const u = auth.user
    const name = (u?.name || u?.full_name || '').trim()
    return name || t('request_detail.approval_ws_handler_fallback')
  })

  const statusTitle = computed(() => {
    const c = unref(ctxRef)
    const r = unref(reqRef)
    if (!r) return ''
    if (c?.showFillPriceSection) return t('request_detail.fill_price_title')
    if (c?.showD2dDecisionSection) return t('request_detail.approval_ws_status_d2d_pending')
    if (r.status === 'price_filled') return t('request_detail.approval_ws_status_dept_pending')
    if (r.status === 'approved') return t('request_detail.approval_ws_status_done')
    if (r.status === 'rejected') return t('request_detail.approval_ws_status_rejected')
    if (r.status === 'pending') return t('request_detail.approval_ws_status_pending')
    return t('request_detail.approval_ws_status_watching')
  })

  const nextActorLine = computed(() => {
    const c = unref(ctxRef)
    const r = unref(reqRef)
    if (c?.showFillPriceSection && c?.fillPriceSummary?.deptHeadDisplayLine) {
      const line = String(c.fillPriceSummary.deptHeadDisplayLine || '').trim()
      if (line && !isRdLegacyDash(line)) return line
    }
    const head = r?.assigned_dept_head
    if (head?.name) return String(head.name).trim()
    if (c?.showD2dDecisionSection) return t('request_detail.approval_ws_next_dispatch')
    return t('request_detail.approval_ws_next_none')
  })

  const miniSteps = computed(() => {
    const r = unref(reqRef)
    const c = unref(ctxRef)
    if (!r) return []

    const d2d = r.trip_type === 'door_to_door'
    const defs = d2d
      ? [
          { key: 'created', labelKey: 'request_detail.approval_ws_step_created' },
          { key: 'received', labelKey: 'request_detail.approval_ws_step_received' },
          { key: 'approve', labelKey: 'request_detail.approval_ws_step_d2d_approve' },
          { key: 'dispatch', labelKey: 'request_detail.approval_ws_step_dispatch' },
          { key: 'done', labelKey: 'request_detail.approval_ws_step_done' },
        ]
      : [
          { key: 'created', labelKey: 'request_detail.approval_ws_step_created' },
          { key: 'received', labelKey: 'request_detail.approval_ws_step_received' },
          { key: 'revenue', labelKey: 'request_detail.approval_ws_step_revenue' },
          { key: 'dept', labelKey: 'request_detail.approval_ws_step_dept' },
          { key: 'done', labelKey: 'request_detail.approval_ws_step_done' },
        ]

    let activeIndex = 1
    const st = r.status
    const tripSt = r.trip?.status

    if (d2d) {
      if (st === 'draft') activeIndex = 0
      else if (st === 'pending') activeIndex = 2
      else if (st === 'approved') {
        if (!r.trip || tripSt === 'approved') activeIndex = 3
        else if (tripSt === 'completed') activeIndex = 4
        else activeIndex = 3
      } else if (st === 'rejected') activeIndex = 2
    } else {
      if (st === 'draft') activeIndex = 0
      else if (st === 'pending') activeIndex = c?.showFillPriceSection ? 2 : 1
      else if (st === 'price_filled') activeIndex = 3
      else if (st === 'approved') {
        if (!r.trip || tripSt === 'approved') activeIndex = 4
        else if (tripSt === 'completed') activeIndex = 4
        else activeIndex = 3
      } else if (st === 'rejected') activeIndex = 2
    }

    return defs.map((d, idx) => {
      let state = 'upcoming'
      if (st === 'rejected' && idx === activeIndex) state = 'rejected'
      else if (idx < activeIndex) state = 'done'
      else if (idx === activeIndex) state = 'current'
      return { ...d, label: t(d.labelKey), state }
    })
  })

  const stepFraction = computed(() => {
    const steps = miniSteps.value
    if (!steps.length) return { current: 0, total: 0 }
    const total = steps.length
    const current = Math.max(1, steps.findIndex((s) => s.state === 'current') + 1 || steps.filter((s) => s.state === 'done').length)
    return { current, total }
  })

  function formatAmount(n) {
    return formatVndOrRdEmpty(t, n, parseMoneyVnd, formatVndCurrency, VND_CURRENCY_SUFFIX, 'money')
  }

  const readOnlyMetrics = computed(() => {
    const r = unref(reqRef)
    const c = unref(ctxRef)
    const empty = rdEmptyLabel(t, 'default')
    if (!r) {
      return {
        unitPrice: rdEmptyLabel(t, 'unit_price'),
        extraFee: rdEmptyLabel(t, 'extra_fee'),
        passengers: rdEmptyLabel(t, 'passengers'),
        total: rdEmptyLabel(t, 'money_total'),
      }
    }

    const pax = dispatchRequestDisplayPassengerCount(r)
    const passengers =
      pax != null && pax !== ''
        ? t('request_detail.approval_ws_passengers_n', { n: pax })
        : rdEmptyLabel(t, 'passengers')

    const fromSummary = c?.fillPriceSummary
    if (fromSummary?.totalFmt && c?.showFillPriceSection) {
      const unitFmt = c?.fillPriceWorkspace?.unitSumFmt
      const extraFmt = c?.fillPriceWorkspace?.extraSumFmt
      return {
        unitPrice: unitFmt && !isRdLegacyDash(unitFmt) ? unitFmt : rdEmptyLabel(t, 'unit_price'),
        extraFee: extraFmt && !isRdLegacyDash(extraFmt) ? extraFmt : rdEmptyLabel(t, 'extra_fee'),
        passengers,
        total: fromSummary.totalFmt,
      }
    }

    const service = r.service_price != null ? formatAmount(r.service_price) : rdEmptyLabel(t, 'unit_price')
    const estTotal = c?.declaredTotalDisplay ?? empty
    const totalEmpty = !estTotal || isRdLegacyDash(estTotal) || estTotal === empty

    return {
      unitPrice: service,
      extraFee: rdEmptyLabel(t, 'extra_fee'),
      passengers,
      total: totalEmpty ? service : estTotal,
    }
  })

  return {
    handlerName,
    statusTitle,
    nextActorLine,
    miniSteps,
    stepFraction,
    readOnlyMetrics,
  }
}
