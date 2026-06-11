import { onBeforeUnmount, onMounted } from 'vue'

/** Khóa cuộn document — chỉ vùng nội dung app (main) được scroll. */
export const APP_VIEWPORT_LOCK_CLASS = 'app-viewport-lock'

export function useAppViewportLock() {
  onMounted(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.classList.add(APP_VIEWPORT_LOCK_CLASS)
    }
  })

  onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.classList.remove(APP_VIEWPORT_LOCK_CLASS)
    }
  })
}
