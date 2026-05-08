/**
 * Timing & debug chỉ trong dev — không gọi trong production build.
 */

const PREFIX = '[driver-dash]'

function enabled() {
  return Boolean(import.meta.env.DEV)
}

export function dashPerfMounted(label = 'dashboard') {
  if (!enabled()) return
  try {
    performance.mark(`${PREFIX}-mount:${label}`)
  } catch {
    /* ignore */
  }
  console.debug(PREFIX, 'mounted', label, { t: typeof performance !== 'undefined' ? performance.now() : 0 })
}

export function dashPerfHydrateDone(ms, hit) {
  if (!enabled()) return
  console.debug(PREFIX, 'hydrate', { ms, snapshot: hit ? 'hit' : 'miss' })
}

export function dashPerfApiStart(tags = {}) {
  if (!enabled()) return
  try {
    performance.mark(`${PREFIX}-api:start`)
  } catch {
    /* ignore */
  }
  console.debug(PREFIX, 'api_start', tags)
}

export function dashPerfApiEnd(tags = {}) {
  if (!enabled()) return
  let dur = null
  try {
    performance.mark(`${PREFIX}-api:end`)
    const m = performance.measure(
      `${PREFIX}-api-fetch`,
      `${PREFIX}-api:start`,
      `${PREFIX}-api:end`,
    )
    dur = m.duration
  } catch {
    /* ignore */
  }
  console.debug(PREFIX, 'api_end', { ms: dur, ...tags })
  dashPerfLogDriverResources()
}

export function dashPerfSkipStale() {
  if (!enabled()) return
  console.debug(PREFIX, 'silent_refresh_skipped_stale')
}

export function dashPerfSectionVisible(sectionId) {
  if (!enabled()) return
  console.debug(PREFIX, 'section_visible', sectionId)
}

export function dashPerfLogDriverResources() {
  if (!enabled() || typeof performance === 'undefined' || typeof performance.getEntriesByType !== 'function') {
    return
  }
  const entries = performance.getEntriesByType('resource').filter((e) => {
    try {
      return typeof e.name === 'string' && e.name.includes('/api/driver')
    } catch {
      return false
    }
  })
  const last = entries.slice(-6)
  if (!last.length) return
  console.debug(
    PREFIX,
    'resource_timing_recent',
    last.map((e) => ({
      name: e.name.split('?')[0],
      duration: Math.round(e.duration),
      transferSize: e.transferSize ?? null,
    })),
  )
}
