import { onBeforeUnmount } from 'vue'

/**
 * Gần realtime bằng poll khi tab đang hiển thị (PWA driver).
 * Phase 2: có thể bổ sung Laravel Echo/Pusher và gọi chung handler refetch để khỏi chờ chu kỳ.
 */

/** Chu kỳ refetch danh sách khi tab đang xem — tránh chờ realtime. */
export const DRIVER_VISIBLE_POLL_MS_DEFAULT = 55_000

const FOCUS_THROTTLE_MS = 35_000
const VISIBILITY_THROTTLE_MS = 12_000

/**
 * Poll nhẹ `refetch()` khi document đang hiển thị; pause khi tab ẩn.
 *
 * @param {() => void | Promise<void>} refetch
 * @param {{ intervalMs?: number }} [opts]
 */
export function useDriverVisiblePoll(refetch, opts = {}) {
  const intervalMs = opts.intervalMs ?? DRIVER_VISIBLE_POLL_MS_DEFAULT
  let intervalId = null
  let lastBurst = 0

  async function burstIfReady(minGapMs) {
    const now = Date.now()
    if (now - lastBurst < minGapMs) return
    lastBurst = now
    try {
      await refetch()
    } catch {
      /* swallow — caller không throw */
    }
  }

  function onVisibilityChange() {
    if (typeof document !== 'undefined' && document.visibilityState === 'visible') {
      void burstIfReady(VISIBILITY_THROTTLE_MS)
    }
  }

  function onPageshow(ev) {
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return
    if (eventOrPersistent(ev)) {
      void burstIfReady(VISIBILITY_THROTTLE_MS)
    }
  }

  function eventOrPersistent(ev) {
    try {
      if (ev?.persisted === true) return true
    } catch {
      /* ignore */
    }
    return true
  }

  function onWindowFocus() {
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return
    void burstIfReady(FOCUS_THROTTLE_MS)
  }

  function tickInterval() {
    if (typeof document === 'undefined' || document.visibilityState !== 'visible') return
    void Promise.resolve(refetch()).catch(() => {})
  }

  function start() {
    stop()
    if (typeof window === 'undefined') return

    intervalId = window.setInterval(tickInterval, intervalMs)

    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('pageshow', onPageshow)
    window.addEventListener('focus', onWindowFocus)
  }

  function stop() {
    if (intervalId != null && typeof window !== 'undefined') {
      window.clearInterval(intervalId)
      intervalId = null
    }
    if (typeof document === 'undefined' || typeof window === 'undefined') return
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('pageshow', onPageshow)
    window.removeEventListener('focus', onWindowFocus)
  }

  onBeforeUnmount(() => stop())

  return { start, stop }
}
