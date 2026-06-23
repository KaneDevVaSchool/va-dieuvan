import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatVnd } from '../util/labels'

/**
 * @param {import('vue').Ref|import('vue').ComputedRef|Array} linesRef
 */
export function useCostEstimateLineRows(linesRef) {
  const { t } = useI18n()

  const rows = computed(() => {
    const lines = unref(linesRef)
    if (!Array.isArray(lines) || !lines.length) return []
    return lines.map((line, index) => {
      const legSeq = line?.leg_seq != null && Number(line.leg_seq) > 0 ? Number(line.leg_seq) : null
      const unit = Number(line?.unit_price ?? 0)
      const extra = Number(line?.extra_fee ?? 0)
      const amount = Number(line?.amount ?? unit + extra)
      const description = String(line?.description ?? '').trim() || t('costs_page.empty_not_available')
      const legRoute = String(line?.leg_route ?? '').trim()
      return {
        key: String(line?.line_key ?? `line-${index}`),
        leg_seq: legSeq,
        leg_label: legSeq
          ? t('trip_detail.passengers.leg_seq', { n: legSeq })
          : t('costs_page.detail_leg_trip_wide'),
        description,
        leg_route: legRoute,
        amount,
        unit_price_display: unit > 0 ? formatVnd(unit) : t('costs_page.empty_not_available'),
        extra_fee_display: extra > 0 ? formatVnd(extra) : t('costs_page.empty_not_available'),
      }
    })
  })

  const total = computed(() => rows.value.reduce((sum, row) => sum + (Number(row.amount) || 0), 0))

  return { rows, total, formatVnd }
}
