import { onUnmounted } from 'vue'

/**
 * Gentle polling for portal request detail — stops after rejection or trip completed.
 * @param {(opts?: { silent?: boolean }) => Promise<void>} loadFn
 * @param {import('vue').Ref<Record<string, unknown>|null>} reqRef
 * @param {number} [intervalMs]
 */
export function usePortalDetailPoll(loadFn, reqRef, intervalMs = 30000) {
  function shouldPoll() {
    const r = reqRef.value
    if (!r) return false
    if (r.status === 'rejected') return false
    if (r.trip?.status === 'completed') return false
    return true
  }

  const timer = setInterval(() => {
    if (!shouldPoll()) return
    loadFn({ silent: true })
  }, intervalMs)

  onUnmounted(() => clearInterval(timer))
}
