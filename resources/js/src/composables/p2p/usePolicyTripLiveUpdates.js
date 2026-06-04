import { onBeforeUnmount, ref } from 'vue'
import { TOKEN_KEY } from '../../core/config/authKeys'

/**
 * Client realtime cho dashboard chuyến policy (§8.1, U5).
 *
 * Dùng `fetch` + ReadableStream để stream SSE — không dùng `EventSource` vì nó
 * không gửi được header Authorization (app xác thực bằng Bearer token). Khi
 * stream lỗi/không hỗ trợ → fallback polling theo `p2p.sse.poll_fallback_seconds`.
 *
 * @param {(change: { type: 'patch'|'reload', row?: object }) => void} onChange
 */
export function usePolicyTripLiveUpdates(onChange) {
  /** @type {import('vue').Ref<'off'|'sse'|'polling'>} */
  const connection = ref('off')

  const POLL_MS = 30000
  let abortCtrl = null
  let pollTimer = null
  let stopped = true
  let current = null

  function buildUrl({ date, timeSlot }) {
    const qs = new URLSearchParams({ date })
    if (timeSlot) qs.set('time_slot', timeSlot)
    return `/api/policy-trips/live-updates?${qs.toString()}`
  }

  function parseFrame(frame) {
    let event = 'message'
    const dataLines = []
    for (const line of frame.split('\n')) {
      if (line.startsWith(':')) continue // heartbeat comment
      if (line.startsWith('event:')) event = line.slice(6).trim()
      else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim())
    }
    if (event !== 'trip' || dataLines.length === 0) return
    try {
      onChange({ type: 'patch', row: JSON.parse(dataLines.join('\n')) })
    } catch {
      /* ignore malformed frame */
    }
  }

  async function runSse(params) {
    if (typeof fetch !== 'function' || typeof ReadableStream === 'undefined') {
      return startPolling()
    }

    abortCtrl = new AbortController()
    let connected = false
    try {
      const token = typeof localStorage !== 'undefined' ? localStorage.getItem(TOKEN_KEY) : ''
      const res = await fetch(buildUrl(params), {
        headers: { Accept: 'text/event-stream', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        signal: abortCtrl.signal,
      })
      if (!res.ok || !res.body) throw new Error('sse-unavailable')

      connected = true
      connection.value = 'sse'
      const reader = res.body.getReader()
      const decoder = new TextDecoder()
      let buffer = ''

      while (!stopped) {
        const { value, done } = await reader.read()
        if (done) break
        buffer += decoder.decode(value, { stream: true })
        let idx
        while ((idx = buffer.indexOf('\n\n')) !== -1) {
          parseFrame(buffer.slice(0, idx))
          buffer = buffer.slice(idx + 2)
        }
      }
    } catch {
      if (stopped) return
      // Kết nối ban đầu thất bại → polling đáng tin cậy hơn.
      if (!connected) return startPolling()
    }

    // Stream kết thúc bình thường (server đóng sau max_seconds) → kết nối lại.
    if (!stopped) {
      setTimeout(() => {
        if (!stopped) runSse(current)
      }, 1500)
    }
  }

  function startPolling() {
    connection.value = 'polling'
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = setInterval(() => {
      if (!stopped) onChange({ type: 'reload' })
    }, POLL_MS)
  }

  /** @param {{ date: string, timeSlot?: string|null }} params */
  function start(params) {
    stop()
    if (!params?.date) return
    stopped = false
    current = params
    runSse(params)
  }

  function stop() {
    stopped = true
    connection.value = 'off'
    if (abortCtrl) {
      try {
        abortCtrl.abort()
      } catch {
        /* ignore */
      }
      abortCtrl = null
    }
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  onBeforeUnmount(stop)

  return { connection, start, stop }
}
