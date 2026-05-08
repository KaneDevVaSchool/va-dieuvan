import { onUnmounted, ref } from 'vue'

const MAIN_SCROLL_ID = 'app-main-scroll'

/**
 * Kéo xuống làm mới khi đang ở đầu vùng cuộn chính của driver shell.
 * @param {() => Promise<void>} onRefresh
 * @param {{ threshold?: number }} opts
 */
export function usePullToRefresh(onRefresh, opts = {}) {
  const threshold = opts.threshold ?? 72
  const pulling = ref(false)
  const pullDist = ref(0)
  const refreshing = ref(false)

  let startY = 0
  let tracking = false
  let scrollEl = null

  function getScrollEl() {
    if (typeof document === 'undefined') return null
    return document.getElementById(MAIN_SCROLL_ID)
  }

  function onTouchStart(e) {
    scrollEl = getScrollEl()
    if (!scrollEl || scrollEl.scrollTop > 2) return
    tracking = true
    startY = e.touches[0]?.clientY ?? 0
  }

  function onTouchMove(e) {
    if (!tracking || refreshing.value) return
    scrollEl = scrollEl || getScrollEl()
    if (!scrollEl || scrollEl.scrollTop > 2) {
      pulling.value = false
      pullDist.value = 0
      return
    }
    const y = e.touches[0]?.clientY ?? 0
    const dy = y - startY
    if (dy > 8) {
      pulling.value = true
      pullDist.value = Math.min(threshold * 1.35, dy * 0.55)
      if (dy > threshold * 1.2) e.preventDefault()
    }
  }

  async function onTouchEnd() {
    if (!tracking) return
    tracking = false
    const should = pulling.value && pullDist.value >= threshold * 0.72
    pulling.value = false
    pullDist.value = 0
    if (!should || refreshing.value) return
    refreshing.value = true
    try {
      await onRefresh()
    } finally {
      refreshing.value = false
    }
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('touchstart', onTouchStart, { passive: true })
    window.addEventListener('touchmove', onTouchMove, { passive: false })
    window.addEventListener('touchend', onTouchEnd, { passive: true })
  }

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('touchstart', onTouchStart)
      window.removeEventListener('touchmove', onTouchMove)
      window.removeEventListener('touchend', onTouchEnd)
    }
  })

  return { pulling, pullDist, refreshing, threshold }
}
