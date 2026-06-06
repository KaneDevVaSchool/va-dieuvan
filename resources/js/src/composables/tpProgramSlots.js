/**
 * Ca chạy (sáng / chiều) của chương trình đưa đón — khớp TpProgramScheduleSlots (PHP).
 * @param {object|null} program
 * @returns {Array<{ shift: 'morning'|'afternoon', departure: string|null, arrival: string|null, label: string, title: string }>}
 */
export function slotsForProgram(program) {
  if (!program) return []

  const settings = program.settings || {}
  const morning = settings.morning || {}
  const afternoon = settings.afternoon || {}
  const out = []

  const morningEnabled = morning.enabled != null ? !!morning.enabled : !!program.departure_time
  const afternoonEnabled = afternoon.enabled != null ? !!afternoon.enabled : !!program.return_time

  if (morningEnabled) {
    out.push({
      shift: 'morning',
      departure: (morning.departure || program.departure_time || '').toString().slice(0, 5) || null,
      arrival: (morning.arrival || '').toString().slice(0, 5) || null,
      label: 'Chuyến sáng',
      title: 'Chuyến sáng — Đưa đến trường',
    })
  }
  if (afternoonEnabled) {
    out.push({
      shift: 'afternoon',
      departure: (afternoon.departure || program.return_time || '').toString().slice(0, 5) || null,
      arrival: (afternoon.arrival || '').toString().slice(0, 5) || null,
      label: 'Chuyến chiều',
      title: 'Chuyến chiều — Đón về nhà',
    })
  }

  return out
}

/**
 * Mở rộng mỗi ngày vận hành thành từng chuyến (ca) độc lập.
 * @param {object} program
 * @param {object[]} days
 */
export function expandOperatingDaysToTrips(program, days) {
  const slots = slotsForProgram(program)
  const useSlots = slots.length ? slots : [{ shift: 'morning', departure: null, arrival: null, label: 'Chuyến', title: 'Chuyến' }]

  const out = []
  for (const day of days) {
    if (day.day_type !== 'operating') continue
    const multi = useSlots.length > 1
    for (const slot of useSlots) {
      out.push({
        ...day,
        trip_key: `${day.id}-${slot.shift}`,
        shift: slot.shift,
        multi_slot: multi,
        slot_departure: slot.departure,
        slot_arrival: slot.arrival,
        slot_label: slot.label,
        slot_title: slot.title,
      })
    }
  }

  return out.sort((a, b) => {
    const d = a.scheduled_date.localeCompare(b.scheduled_date)
    if (d !== 0) return d
    const order = { morning: 0, afternoon: 1 }
    return (order[a.shift] ?? 0) - (order[b.shift] ?? 0)
  })
}
