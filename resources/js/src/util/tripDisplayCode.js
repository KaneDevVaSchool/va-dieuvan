import { formatDispatchRequestRefCode } from './portalRequestFormat'

/**
 * Mã chuyến hiển thị cho người dùng (không dùng id DB / TRP-xxxx).
 * @param {{ request_code?: string, trip_number?: string, dispatch_request?: { id?: number, created_at?: string } } | null | undefined} trip
 */
export function resolveTripDisplayCode(trip) {
  if (!trip) return ''
  const direct = String(trip.request_code ?? trip.trip_number ?? '').trim()
  if (direct && !direct.startsWith('#')) return direct
  const dr = trip.dispatch_request
  if (dr) {
    const ref = formatDispatchRequestRefCode(dr)
    if (ref) return ref
  }
  return ''
}
