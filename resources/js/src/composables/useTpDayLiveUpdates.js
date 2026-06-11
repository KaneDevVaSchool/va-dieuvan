import { onBeforeUnmount, ref, watch } from 'vue'
import { APP_CONFIG } from '../core/config/appConfig'
import { TOKEN_KEY } from '../core/config/authKeys'

/**
 * SSE điểm danh vận hành (Phase 8) — fetch + Bearer vì EventSource không gửi Authorization.
 *
 * @param {import('vue').Ref<number|string|null>} dayIdRef
 * @param {import('vue').Ref<string|null|undefined>} shiftRef morning | afternoon | null
 * @param {{ onChange?: () => void | Promise<void> }} handlers
 */
export function useTpDayLiveUpdates(dayIdRef, shiftRef, handlers = {}) {
  const connected = ref(false)
  let abortCtrl = null
  let reconnectTimer = null
  let stopped = false

  function clearReconnect() {
    if (reconnectTimer != null && typeof window !== 'undefined') {
      window.clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
  }

  function scheduleReconnect(delayMs = 800) {
    if (stopped || typeof window === 'undefined') return
    clearReconnect()
    reconnectTimer = window.setTimeout(() => {
      reconnectTimer = null
      void connect()
    }, delayMs)
  }

  function parseSseChunk(buffer) {
    const events = []
    const parts = buffer.split('\n\n')
    const rest = parts.pop() ?? ''
    for (const block of parts) {
      if (!block.trim() || block.trim().startsWith(':')) continue
      let eventName = 'message'
      const dataLines = []
      for (const line of block.split('\n')) {
        if (line.startsWith('event:')) eventName = line.slice(6).trim()
        else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim())
      }
      if (!dataLines.length) continue
      try {
        events.push({ event: eventName, data: JSON.parse(dataLines.join('\n')) })
      } catch {
        /* ignore malformed */
      }
    }
    return { events, rest }
  }

  async function connect() {
    if (stopped || typeof document === 'undefined') return
    if (document.visibilityState !== 'visible') return
    const dayId = dayIdRef.value
    if (!dayId) return

    abortCtrl?.abort()
    abortCtrl = new AbortController()

    const params = new URLSearchParams()
    const shift = shiftRef.value
    if (shift === 'morning' || shift === 'afternoon') params.set('shift', shift)
    const qs = params.toString()
    const url = `${APP_CONFIG.apiBase}/tp-program-days/${dayId}/live${qs ? `?${qs}` : ''}`

    let token = ''
    try {
      token = localStorage.getItem(TOKEN_KEY) || ''
    } catch {
      /* ignore */
    }

    try {
      const res = await fetch(url, {
        method: 'GET',
        headers: {
          Accept: 'text/event-stream',
          'X-Requested-With': 'XMLHttpRequest',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        signal: abortCtrl.signal,
        credentials: 'same-origin',
      })
      if (!res.ok || !res.body) {
        scheduleReconnect(4000)
        return
      }

      connected.value = true
      const reader = res.body.getReader()
      const decoder = new TextDecoder()
      let buffer = ''

      while (!stopped) {
        const { done, value } = await reader.read()
        if (done) break
        buffer += decoder.decode(value, { stream: true })
        const parsed = parseSseChunk(buffer)
        buffer = parsed.rest
        for (const ev of parsed.events) {
          if (ev.event === 'ready') continue
          if (ev.event === 'attendance_changed' || ev.event === 'execution_changed') {
            try {
              await handlers.onChange?.(ev)
            } catch {
              /* swallow */
            }
          }
        }
      }
    } catch (err) {
      if (err?.name === 'AbortError') return
    } finally {
      connected.value = false
    }

    if (!stopped) scheduleReconnect(600)
  }

  function disconnect() {
    abortCtrl?.abort()
    abortCtrl = null
    connected.value = false
  }

  function start() {
    stopped = false
    void connect()
  }

  function stop() {
    stopped = true
    clearReconnect()
    disconnect()
  }

  function onVisibilityChange() {
    if (typeof document === 'undefined') return
    if (document.visibilityState === 'visible') {
      void connect()
    } else {
      disconnect()
    }
  }

  watch(
    () => [dayIdRef.value, shiftRef.value],
    () => {
      if (stopped) return
      disconnect()
      scheduleReconnect(200)
    },
  )

  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', onVisibilityChange)
  }

  onBeforeUnmount(() => {
    stop()
    if (typeof document !== 'undefined') {
      document.removeEventListener('visibilitychange', onVisibilityChange)
    }
  })

  return { connected, start, stop }
}
