import {
  emptyPassengerRow,
  emptyBusinessRow,
  emptyCargoRow,
} from './dispatchWizardConstants'

/** Đảm bảo mỗi dòng có id ổn định cho :key và nhân bản. */
export function ensureScheduleRowIds(rows) {
  if (!Array.isArray(rows)) return
  for (const row of rows) {
    if (row && row.id == null) {
      row.id = crypto.randomUUID()
    }
  }
}

function prevPickupLike(row, variant) {
  if (variant === 'cargo') return row.pickup_place ?? ''
  return row.pickup ?? ''
}

function prevPic(row, variant) {
  if (variant !== 'passenger') return ''
  return row.person_in_charge ?? ''
}

export function appendScheduleRow(rows, variant) {
  const prev = rows.length ? rows[rows.length - 1] : null
  let row
  if (variant === 'cargo') {
    row = emptyCargoRow()
  } else if (variant === 'business') {
    row = emptyBusinessRow()
  } else {
    row = emptyPassengerRow()
  }
  ensureScheduleRowIds([row])
  if (prev) {
    if (variant === 'cargo') {
      row.pickup_place = prevPickupLike(prev, variant)
    } else {
      row.pickup = prevPickupLike(prev, variant)
      row.person_in_charge = prevPic(prev, variant)
    }
  }
  rows.push(row)
}

export function duplicateScheduleRow(rows, index) {
  const raw = rows[index]
  if (!raw) return
  const clone = { ...raw }
  clone.id = crypto.randomUUID()
  rows.splice(index + 1, 0, clone)
}

export function removeScheduleRowAt(rows, index) {
  rows.splice(index, 1)
}

export function autoFillScheduleRowFromPrevious(rows, index, variant) {
  if (index <= 0) return
  const prev = rows[index - 1]
  const row = rows[index]
  if (!prev || !row) return
  if (variant === 'cargo') {
    row.pickup_place = prev.pickup_place ?? ''
  } else {
    row.pickup = prev.pickup ?? ''
    if (variant === 'passenger') row.person_in_charge = prev.person_in_charge ?? ''
  }
}
