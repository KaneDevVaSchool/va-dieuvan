export function newIdempotencyKey() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `idem-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`
}

/** Giữ cùng một key cho tới khi gọi reset() — tránh double-submit tạo hai idempotency key. */
export function createActionIdempotencyKey() {
  /** @type {string | null} */
  let key = null
  return {
    get() {
      if (!key) key = newIdempotencyKey()
      return key
    },
    reset() {
      key = null
    },
  }
}
