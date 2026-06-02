import { formatIsoDate } from './datetime'

/** @param {string | import('vue').Ref<string>} localeRef */
export function p2pPolicyDateLocale(localeRef) {
  const v = typeof localeRef === 'string' ? localeRef : localeRef?.value
  return v === 'en' ? 'en' : 'vi'
}

/** @param {string | null | undefined} iso @param {'vi' | 'en'} [locale] */
export function formatP2pOperatingDate(iso, locale = 'vi') {
  if (iso == null || iso === '') return ''
  const formatted = formatIsoDate(iso, locale)
  return formatted === '—' ? '' : formatted
}

/** @param {'vi' | 'en'} locale */
export function formatP2pDateRange(from, to, locale = 'vi') {
  const f = formatP2pOperatingDate(from, locale)
  const t = formatP2pOperatingDate(to, locale)
  if (!f || !t) return ''
  return locale === 'en' ? `${f} – ${t}` : `${f} đến ${t}`
}

/** @param {Record<string, unknown> | null | undefined} term @param {'vi' | 'en'} locale */
export function formatP2pTermLabel(term, locale = 'vi') {
  if (!term) return '—'
  const custom = String(term.name ?? '').trim()
  const range = formatP2pDateRange(term.operating_from, term.operating_to, locale)
  const withRange = (head) => (range ? `${head} (${range})` : head)

  if (custom) return withRange(custom)

  const year = term.academic_term?.academic_year ?? ''
  if (year) return withRange(year)

  return withRange(`#${term.id}`)
}
