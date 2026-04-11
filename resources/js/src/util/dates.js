/** @param {Date | string | number} d */
export function toLocalDateKey(d) {
  const x = new Date(d)
  if (Number.isNaN(x.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${x.getFullYear()}-${pad(x.getMonth() + 1)}-${pad(x.getDate())}`
}

/** @param {Date} start @param {number} days */
export function eachLocalDayKey(start, days) {
  const out = []
  const cur = new Date(start.getFullYear(), start.getMonth(), start.getDate())
  for (let i = 0; i < days; i++) {
    out.push(toLocalDateKey(cur))
    cur.setDate(cur.getDate() + 1)
  }
  return out
}

/** DD/MM label */
export function shortViDayLabel(dateKey) {
  const [y, m, d] = dateKey.split('-').map(Number)
  if (!y || !m || !d) return dateKey
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d)}/${pad(m)}`
}

/** Thứ trong tuần: 0 = Thứ Hai … 6 = Chủ nhật */
export function weekdayMon0(d) {
  const w = d.getDay()
  return w === 0 ? 6 : w - 1
}

export function daysInMonth(year, monthIndex) {
  return new Date(year, monthIndex + 1, 0).getDate()
}

export function monthRangeKeys(year, monthIndex) {
  const n = daysInMonth(year, monthIndex)
  const keys = []
  for (let day = 1; day <= n; day++) {
    const dt = new Date(year, monthIndex, day)
    keys.push(toLocalDateKey(dt))
  }
  return keys
}
