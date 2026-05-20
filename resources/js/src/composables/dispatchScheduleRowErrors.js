function guestsNum(row) {
  const g = Number(String(row.guests ?? '').trim().replace(/\s/g, ''))
  return Number.isFinite(g) ? g : NaN
}

/**
 * @param {Record<string, unknown>} row
 * @param {'passenger' | 'business' | 'cargo'} variant
 * @param {{ timesFromRecurringTemplate?: boolean }} [options]
 */
export function dispatchScheduleRowErrors(row, variant, options = {}) {
  const timesFromRecurringTemplate = !!options.timesFromRecurringTemplate
  const e = {}
  let departKey = 'depart_at'
  let retKey = 'return_at'
  if (variant === 'cargo') {
    departKey = 'pickup_at'
    retKey = 'delivery_at'
  }

  const hasDepart = !!(row[departKey] && String(row[departKey]).trim())
  const hasReturn = !!(row[retKey] && String(row[retKey]).trim())
  const hasReturnPlace =
    variant === 'cargo'
      ? !!(row.delivery_place && String(row.delivery_place).trim())
      : !!(row.dropoff && String(row.dropoff).trim())

  if (!timesFromRecurringTemplate && !hasDepart && !hasReturn) {
    e.time_required = true
  }

  if (variant === 'cargo') {
    if (hasDepart && !(row.pickup_place && String(row.pickup_place).trim())) {
      e.pickup_place = true
    }
  } else if (hasDepart && !(row.pickup && String(row.pickup).trim())) {
    const routeStarted =
      variant !== 'cargo' &&
      timesFromRecurringTemplate &&
      !!(row.dropoff && String(row.dropoff).trim())
    if (!timesFromRecurringTemplate || routeStarted) {
      e.outbound_place = true
    }
  }

  if (!timesFromRecurringTemplate && hasDepart && !hasReturn) {
    e.return_time_required = true
  }

  if (hasReturn && !hasReturnPlace) {
    const routeStarted =
      variant !== 'cargo' &&
      timesFromRecurringTemplate &&
      !!(row.pickup && String(row.pickup).trim())
    if (!timesFromRecurringTemplate || routeStarted) {
      e.return_place = true
    }
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

export function rowsHaveNoInlineErrors(rows, variant, options) {
  if (!rows.length) return true
  return rows.every((r) => Object.keys(dispatchScheduleRowErrors(r, variant, options)).length === 0)
}
