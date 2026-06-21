/**
 * Nhãn thay cho dấu "—" trên màn chi tiết yêu cầu (staff).
 * @param {import('vue-i18n').ComposerTranslation} t
 * @param {string} [kind] — suffix của request_detail.empty_{kind}
 */
export function rdEmptyLabel(t, kind = 'default') {
  if (kind === 'default') return t('request_detail.ops_no_data')
  const key = `request_detail.empty_${kind}`
  const tr = t(key)
  return tr !== key ? tr : t('request_detail.ops_no_data')
}

/** @deprecated Chỉ dùng để nhận diện giá trị cũ còn "—" trong dữ liệu/cache. */
export const RD_LEGACY_DASH = '—'

export function isRdLegacyDash(value) {
  return value === RD_LEGACY_DASH
}

/**
 * @param {import('vue-i18n').ComposerTranslation} t
 * @param {unknown} raw
 * @param {(n: unknown) => number} parseMoneyVnd
 * @param {(n: number, suffix: string) => string} formatVndMoney
 * @param {string} suffix
 * @param {string} [emptyKind]
 */
export function formatVndOrRdEmpty(t, raw, parseMoneyVnd, formatVndMoney, suffix, emptyKind = 'money') {
  const amount = parseMoneyVnd(raw)
  if (!amount) return rdEmptyLabel(t, emptyKind)
  return formatVndMoney(amount, suffix)
}
