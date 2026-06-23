const VI_WEEKDAY = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']

/**
 * @param {string | null | undefined} iso
 * @param {'vi' | 'en'} [locale]
 * @returns {string}
 */
export function formatPortalDepartLine(iso, locale = 'vi') {
  if (iso == null || iso === '') return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = d.getFullYear()
  const hhmm = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
  if (locale === 'en') {
    const day = d.toLocaleDateString('en-US', { weekday: 'short' })
    return `${day}, ${dd}/${mm}/${yyyy} · ${hhmm}`
  }
  const day = VI_WEEKDAY[d.getDay()]
  return `${day}, ${dd}/${mm}/${yyyy} · ${hhmm}`
}

/**
 * @param {string | null | undefined} iso
 * @returns {string} HH:mm
 */
export function formatPortalTimeHm(iso) {
  if (iso == null || iso === '') return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

/**
 * Ngày hôm nay cho header portal (không giờ).
 * @param {'vi' | 'en'} [locale]
 */
export function formatPortalTodayDate(locale = 'vi') {
  const d = new Date()
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yr = d.getFullYear()
  if (locale === 'en') {
    const day = d.toLocaleDateString('en-US', { weekday: 'long' })
    return `${day}, ${dd}/${mm}/${yr}`
  }
  const day = VI_WEEKDAY[d.getDay()]
  return `${day}, ${dd}/${mm}/${yr}`
}
