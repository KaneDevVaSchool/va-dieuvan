import { onMounted, onUnmounted, ref } from 'vue'
import { applyServiceWorkerUpdate, takePendingPwaUpdate } from '../pwa/registerSW'

/**
 * Thông báo khi service worker mới đã sẵn sàng (prod / dev PWA).
 */
export function usePwaUpdate() {
  const updateAvailable = ref(takePendingPwaUpdate())

  function onUpdateAvailable() {
    updateAvailable.value = true
  }

  onMounted(() => {
    window.addEventListener('pwa:update-available', onUpdateAvailable)
  })

  onUnmounted(() => {
    window.removeEventListener('pwa:update-available', onUpdateAvailable)
  })

  async function applyUpdate() {
    updateAvailable.value = false
    await applyServiceWorkerUpdate()
  }

  function dismissUpdate() {
    updateAvailable.value = false
  }

  return { updateAvailable, applyUpdate, dismissUpdate }
}
