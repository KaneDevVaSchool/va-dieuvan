/**
 * Preview occurrence dates for recurring dispatch templates.
 * Mirrors app/Services/RecurringDispatch/DispatchRecurringMaintenanceService.php
 * (frequencyMatchesDay + effectiveRecurrenceEndDay), without filtering past times.
 */

const WEEKDAY_KEY_TO_ISO = { mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6, sun: 7 }

/** @param {string} ymd YYYY-MM-DD */
function parseLocalDay(ymd) {
  const [y, m, d] = String(ymd || '')
    .trim()
    .split('-')
    .map(Number)
  if (!y || !m || !d) return null
  const dt = new Date(y, m - 1, d)
  return Number.isNaN(dt.getTime()) ? null : dt
}

function formatYmd(dt) {
  const y = dt.getFullYear()
  const m = String(dt.getMonth() + 1).padStart(2, '0')
  const d = String(dt.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

/** ISO weekday 1=Mon … 7=Sun */
function isoWeekday(dt) {
  const js = dt.getDay()
  return js === 0 ? 7 : js
}

function addDays(dt, n) {
  const c = new Date(dt.getTime())
  c.setDate(c.getDate() + n)
  return c
}

/**
 * @param {{ startDate: string, recurrenceEndDate?: string|null, repeatCount?: number|null, endMode?: 'date'|'weeks' }} opts
 */
export function effectiveRecurrenceEndDayYmd(opts) {
  const start = parseLocalDay(opts.startDate)
  if (!start) return null

  const endMode = opts.endMode || 'date'
  if (endMode === 'date' && opts.recurrenceEndDate?.trim()) {
    return opts.recurrenceEndDate.trim().slice(0, 10)
  }

  if (endMode === 'weeks' && opts.repeatCount != null) {
    const weeks = Math.max(1, Math.round(Number(opts.repeatCount) || 0))
    const end = addDays(start, weeks * 7 - 1)
    return formatYmd(end)
  }

  if (opts.recurrenceEndDate?.trim()) {
    return opts.recurrenceEndDate.trim().slice(0, 10)
  }

  return null
}

/**
 * @param {string} frequency daily | weekly
 * @param {number} interval
 * @param {number[]|undefined} byweekday ISO 1-7
 * @param {Date} anchor start of day
 * @param {Date} day start of day
 */
function frequencyMatchesDay(frequency, interval, byweekday, anchor, day) {
  const freq = String(frequency || 'weekly').toLowerCase()

  if (freq === 'weekly') {
    let wanted = (byweekday || []).filter((d) => Number.isFinite(d)).map((d) => Number(d))
    wanted = [...new Set(wanted)].sort((a, b) => a - b)
    if (wanted.length === 0) {
      wanted = [isoWeekday(day)]
    }
    return wanted.includes(isoWeekday(day))
  }

  if (freq === 'daily') {
    const anchorMs = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate()).getTime()
    const dayMs = new Date(day.getFullYear(), day.getMonth(), day.getDate()).getTime()
    const delta = Math.round((dayMs - anchorMs) / (24 * 3600 * 1000))
    const iv = Math.max(1, interval || 1)
    return delta >= 0 && delta % iv === 0
  }

  return false
}

/**
 * @param {object} input
 * @param {'daily'|'weekly'} input.freq
 * @param {number} [input.interval]
 * @param {number[]} [input.byweekday] ISO weekdays when weekly
 * @param {string} input.startDate YYYY-MM-DD
 * @param {string} [input.recurrenceEndDate]
 * @param {number|string} [input.repeatCount]
 * @param {'date'|'weeks'} [input.endMode]
 * @param {boolean} [input.includePast] default true for preview
 * @returns {{ dates: string[], count: number }}
 */
export function computeRecurringOccurrenceDates(input) {
  const startDate = String(input.startDate || '').trim().slice(0, 10)
  const anchor = parseLocalDay(startDate)
  if (!anchor) {
    return { dates: [], count: 0 }
  }

  const endYmd = effectiveRecurrenceEndDayYmd({
    startDate,
    recurrenceEndDate: input.recurrenceEndDate,
    repeatCount: input.repeatCount,
    endMode: input.endMode || 'date',
  })
  const until = endYmd ? parseLocalDay(endYmd) : null
  if (!until || until < anchor) {
    return { dates: [], count: 0 }
  }

  const freq = input.freq === 'daily' ? 'daily' : 'weekly'
  const interval = Math.max(1, Number(input.interval) || 1)
  const byweekday = Array.isArray(input.byweekday) ? input.byweekday : undefined

  const dates = []
  for (let cursor = new Date(anchor.getTime()); cursor <= until; cursor = addDays(cursor, 1)) {
    if (!frequencyMatchesDay(freq, interval, byweekday, anchor, cursor)) {
      continue
    }
    dates.push(formatYmd(cursor))
  }

  return { dates, count: dates.length }
}

/** @param {Record<string, boolean>} e1Weekdays */
export function buildIsoWeekdaysFromE1(e1Weekdays) {
  const out = []
  if (!e1Weekdays || typeof e1Weekdays !== 'object') return out
  for (const [k, iso] of Object.entries(WEEKDAY_KEY_TO_ISO)) {
    if (e1Weekdays[k]) out.push(iso)
  }
  return out.sort((a, b) => a - b)
}

export { WEEKDAY_KEY_TO_ISO }
