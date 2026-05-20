/** Giá trị lưu DB → nhãn hiển thị (i18n). */

export const P2P_POLICY_TYPE_VALUES = ['internal', 'default']

export const P2P_DIRECTION_VALUES = ['one_way', 'two_way']

export function policyTypeLabel(t, code) {
  if (!code) return '—'
  const key = `p2p_policy_page.policy_type_${code}`
  const translated = t(key)
  return translated === key ? String(code) : translated
}

export function directionLabel(t, code) {
  if (!code) return '—'
  const key = `p2p_policy_page.direction_${code}`
  const translated = t(key)
  return translated === key ? String(code) : translated
}

export function p2pTermStatusLabel(t, status) {
  if (!status) return '—'
  const key = `p2p_policy_page.status_${status}`
  const translated = t(key)
  return translated === key ? String(status) : translated
}

export function policyTypeBadgeClass(code) {
  const map = {
    internal: 'bg-teal-100 text-teal-900 ring-teal-600/20 dark:bg-teal-950/50 dark:text-teal-100',
    default: 'bg-slate-100 text-slate-800 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-200',
  }
  return map[code] ?? 'bg-violet-100 text-violet-900 dark:bg-violet-950/40 dark:text-violet-100'
}

export function directionBadgeClass(code) {
  const map = {
    one_way: 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
    two_way: 'bg-sky-100 text-sky-950 dark:bg-sky-950/40 dark:text-sky-100',
  }
  return map[code] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}
