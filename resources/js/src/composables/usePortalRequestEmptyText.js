import { useI18n } from 'vue-i18n'

/** @param {unknown} value */
export function portalFieldHasValue(value) {
  const s = String(value ?? '').trim()
  return s.length > 0 && s !== '—'
}

/**
 * Nhãn thay thế khi thiếu dữ liệu trên /portal/requests và chi tiết.
 */
export function usePortalRequestEmptyText() {
  const { t } = useI18n()

  /** @param {unknown} value @param {string} emptyKey i18n key */
  function display(value, emptyKey) {
    return portalFieldHasValue(value) ? String(value).trim() : t(emptyKey)
  }

  return {
    portalFieldHasValue,
    origin: (value) => display(value, 'portal.list_empty.origin'),
    destination: (value) => display(value, 'portal.list_empty.destination'),
    departAt: (value) => display(value, 'portal.list_empty.depart_at'),
    createdAt: (value) => display(value, 'portal.list_empty.created_at'),
    tripType: (value) => display(value, 'portal.list_empty.trip_type'),
    dispatcher: (value) => display(value, 'portal.list_empty.dispatcher'),
    routeJourneyOrigin: (value) =>
      portalFieldHasValue(value) ? String(value).trim() : t('portal.list_empty.origin'),
    routeJourneyDestination: (value) =>
      portalFieldHasValue(value) ? String(value).trim() : t('portal.list_empty.destination'),
  }
}
