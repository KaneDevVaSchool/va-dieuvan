import { onMounted, onUnmounted } from 'vue'

/** Closes a <details> element when a click lands outside it. */
export function useDetailsAutoClose(detailsRef) {
  function handler(event) {
    const el = detailsRef.value
    if (el && el.open && !el.contains(event.target)) el.open = false
  }
  onMounted(() => document.addEventListener('click', handler, true))
  onUnmounted(() => document.removeEventListener('click', handler, true))
}

/** Closes every open <details> under containerRef when click is outside that details. */
export function useDetailsAutoCloseWithin(containerRef) {
  function handler(event) {
    const root = containerRef.value
    if (!root) return
    root.querySelectorAll('details[open]').forEach((d) => {
      if (!d.contains(event.target)) d.open = false
    })
  }
  onMounted(() => document.addEventListener('click', handler, true))
  onUnmounted(() => document.removeEventListener('click', handler, true))
}
