/**
 * @param {{ license_plate?: string|null, type?: string|null, seat_count?: number|null }} vehicle
 * @param {(key: string, params?: Record<string, unknown>) => string} t
 */
export function formatDriverVehicleSelectLabel(vehicle, t) {
  const plate = String(vehicle?.license_plate ?? '').trim() || '—'
  const type = String(vehicle?.type ?? '').trim()
  const seats = Number(vehicle?.seat_count ?? 0)
  const typePart = type || t('driver_costs.vehicle_type_unknown')
  if (Number.isFinite(seats) && seats > 0) {
    return t('driver_costs.vehicle_option_with_seats', {
      plate,
      type: typePart,
      seats,
    })
  }
  return t('driver_costs.vehicle_option', { plate, type: typePart })
}

/**
 * @param {object | null | undefined} trip
 * @returns {number | null}
 */
export function vehicleIdFromDriverTrip(trip) {
  if (!trip) return null
  const raw = trip.vehicle_id ?? trip.vehicle?.id ?? null
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : null
}
