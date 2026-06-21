export const DEPT_TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

export const DEPT_DEFAULT_PER_PAGE = 20

export const DEPT_PER_PAGE_OPTIONS = [10, 20, 25, 50, 100]

export const DEPT_FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

export const DEPT_FILTER_LABEL_KEYS = {
  trip_type: 'dept.filter_label_trip_type',
  date_range: 'dept.filter_label_date_range',
  status: 'dept.filter_label_status',
  per_page: 'filter_bar.per_page',
}

export function deptMonthRangeIso() {
  const now = new Date()
  const y = now.getFullYear()
  const m = now.getMonth()
  const pad = (n) => String(n).padStart(2, '0')
  const from = `${y}-${pad(m + 1)}-01`
  const lastDay = new Date(y, m + 1, 0).getDate()
  const to = `${y}-${pad(m + 1)}-${pad(lastDay)}`
  return { from, to }
}
