import { formatDispatchRequestRefCode } from './portalRequestFormat'

/**
 * Mã phiếu REQ-YYYYMM-xxx — ưu tiên `dispatch_request_ref` từ API.
 * @param {{ dispatch_request_ref?: string|null, data?: object } | null | undefined} n
 */
export function refCodeFromPortalNotification(n) {
  if (!n) return ''
  const fromApi = n.dispatch_request_ref != null ? String(n.dispatch_request_ref).trim() : ''
  if (fromApi) return fromApi

  const d = n.data && typeof n.data === 'object' ? n.data : {}
  const embedded =
    d.dispatch_request_ref != null
      ? String(d.dispatch_request_ref).trim()
      : d.reference_code != null
        ? String(d.reference_code).trim()
        : ''
  if (embedded) return embedded

  const id = d.dispatch_request_id
  if (id == null || id === '') return ''

  return formatDispatchRequestRefCode({
    id,
    created_at: d.dispatch_request_created_at ?? null,
  })
}

/**
 * Resolve origin / destination from portal notification payload.
 * Many notifications only embed route in `body` as "A → B".
 */
export function routeFromPortalNotification(n) {
  const d = n?.data && typeof n.data === 'object' ? n.data : {}
  let origin = d.origin != null ? String(d.origin).trim() : ''
  let destination = d.destination != null ? String(d.destination).trim() : ''

  if (!origin && !destination && d.body != null) {
    const body = String(d.body).trim()
    const sep = body.includes('→') ? '→' : body.includes('->') ? '->' : null
    if (sep) {
      const parts = body.split(sep).map((s) => s.trim())
      origin = parts[0] ?? ''
      destination = parts.slice(1).join(sep).trim()
    }
  }

  return {
    origin,
    destination,
    hasRoute: origin !== '' || destination !== '',
  }
}
