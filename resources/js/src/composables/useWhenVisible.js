import { onBeforeUnmount, ref, watch } from 'vue'
import { dashPerfSectionVisible } from '../util/devDriverDashboardPerf'

/**
 * Theo dõi visibility của phần tử qua IntersectionObserver.
 * @param {import('vue').Ref<HTMLElement | null | undefined>} elRef template ref của phần tử gốc
 * @param {{ rootMargin?: string, once?: boolean, sectionId?: string }} [opts]
 * @returns {{ isVisible: import('vue').Ref<boolean> }}
 */
export function useWhenVisible(elRef, opts = {}) {
  const { rootMargin = '120px', once = true, sectionId = 'section' } = opts
  const isVisible = ref(false)
  /** @type {IntersectionObserver | null} */
  let observer = null

  function disconnect() {
    if (observer) {
      try {
        observer.disconnect()
      } catch {
        /* ignore */
      }
      observer = null
    }
  }

  watch(
    () => elRef.value,
    (el) => {
      disconnect()
      if (!(el instanceof Element) || typeof IntersectionObserver === 'undefined') return
      observer = new IntersectionObserver(
        (entries) => {
          const hit = entries.some((e) => e.isIntersecting)
          if (!hit) return
          dashPerfSectionVisible(sectionId)
          isVisible.value = true
          if (once) disconnect()
        },
        { root: null, rootMargin, threshold: 0 },
      )
      observer.observe(el)
    },
    { flush: 'post' },
  )

  onBeforeUnmount(() => disconnect())

  return { isVisible }
}
