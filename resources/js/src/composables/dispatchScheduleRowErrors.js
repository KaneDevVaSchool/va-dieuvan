function guestsNum(row) {
  const g = Number(String(row.guests ?? '').trim().replace(/\s/g, ''))
  return Number.isFinite(g) ? g : NaN
}

/**
 * @param {Record<string, unknown>} row
 * @param {'passenger' | 'business' | 'cargo'} variant
 */
export function dispatchScheduleRowErrors(row, variant) {
  const e = {}
  let departKey = 'depart_at'
  let retKey = 'return_at'
  if (variant === 'cargo') {
    departKey = 'pickup_at'
    retKey = 'delivery_at'
  }

  const hasDepart = !!(row[departKey] && String(row[departKey]).trim())
  const hasReturn = !!(row[retKey] && String(row[retKey]).trim())

  if (!hasDepart && !hasReturn) {
    e.time_required = true
  }

  if (hasDepart && hasReturn && String(row[retKey]) <= String(row[departKey])) {
    e.return_time = true
  }

  if (variant !== 'cargo') {
    const g = guestsNum(row)
    if (String(row.guests ?? '').trim() !== '' && (!Number.isFinite(g) || g < 1)) {
      e.passengers = true
    }
  }

  return e
}

export function rowsHaveNoInlineErrors(rows, variant) {
  if (!rows.length) return false
  return rows.every((r) => Object.keys(dispatchScheduleRowErrors(r, variant)).length === 0)
}
