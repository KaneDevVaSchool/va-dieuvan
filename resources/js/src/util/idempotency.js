export function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `idem-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`
}

/** Stable key per term — replays activation via Idempotency-Key after success (≥8 chars). */
export function p2pTermActivateIdempotencyKey(termId) {
  return `p2p-activate-term-${Number(termId)}`
}
