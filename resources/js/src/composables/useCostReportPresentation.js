import { useI18n } from 'vue-i18n'
import { formatTripCode, labelTripType } from '../util/labels'

const PROVIDER_CODE_KEYS = {
  INTERNAL: 'cost_report.provider_internal',
}

/**
 * Hiển thị tên đối tác / đơn vị báo cáo (INTERNAL → Nội bộ, …).
 * @param {string|null|undefined} raw
 */
export function useCostReportPresentation() {
  const { t, te } = useI18n()

  function labelProviderDisplay(raw) {
    const v = String(raw ?? '').trim()
    if (!v || v === '—') return '—'
    const i18nKey = PROVIDER_CODE_KEYS[v] || PROVIDER_CODE_KEYS[v.toUpperCase()]
    if (i18nKey && te(i18nKey)) return t(i18nKey)
    return v
  }

  function labelUnitDisplay(raw) {
    const v = String(raw ?? '').trim()
    if (!v || v === '—') return t('cost_report.unit_unassigned')
    return v
  }

  function tripCodeForRow(row) {
    if (!row?.trip_id) return null
    return formatTripCode(row.trip_id)
  }

  function categoryLabel(category) {
    return labelTripType(category)
  }

  return {
    labelProviderDisplay,
    labelUnitDisplay,
    tripCodeForRow,
    categoryLabel,
  }
}

/** @param {string|null|undefined} provider */
export function labelReportProvider(provider, t, te) {
  const v = String(provider ?? '').trim()
  if (!v) return '—'
  if (v === 'INTERNAL') {
    const key = 'cost_report.provider_internal'
    return te(key) ? t(key) : 'Nội bộ'
  }
  return v
}
