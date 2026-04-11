import { onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { NAV_SHORTCUT_PREFIX, NAV_SHORTCUT_ROUTES, SHORTCUT_WINDOW_MS } from '../config/navShortcuts'

function isTypingTarget(el) {
  if (!el || !(el instanceof HTMLElement)) return false
  const tag = el.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true
  if (el.isContentEditable) return true
  if (el.closest('[contenteditable="true"]')) return true
  return el.closest('[data-nav-shortcuts-ignore]') != null
}

/**
 * Phím tắt dạng `g` rồi chữ (ví dụ g+d → Dashboard). Không chạy khi focus ô nhập liệu.
 */
export function useNavShortcuts() {
  const router = useRouter()

  let expecting = false
  let timer = null

  function clearTimer() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  function onKeyDown(e) {
    if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.altKey) return
    const t = e.target
    if (isTypingTarget(t)) {
      expecting = false
      clearTimer()
      return
    }

    const key = e.key.length === 1 ? e.key.toLowerCase() : ''

    if (expecting) {
      clearTimer()
      expecting = false
      const path = NAV_SHORTCUT_ROUTES[key]
      if (path) {
        e.preventDefault()
        router.push(path)
      }
      return
    }

    if (key === NAV_SHORTCUT_PREFIX) {
      e.preventDefault()
      expecting = true
      clearTimer()
      timer = setTimeout(() => {
        expecting = false
        timer = null
      }, SHORTCUT_WINDOW_MS)
    }
  }

  window.addEventListener('keydown', onKeyDown)
  onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown)
    clearTimer()
  })
}
