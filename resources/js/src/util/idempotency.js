export function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `idem-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`
}
