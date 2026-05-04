import { ref, onMounted, onUnmounted } from 'vue'
import { applyServiceWorkerUpdate } from '../pwa/registerSW'

export function usePwaUpdate() {
  const updateAvailable = ref(false)

  function onUpdateAvailable() {
    updateAvailable.value = true
  }

  function applyUpdate() {
    applyServiceWorkerUpdate()
  }

  function dismissUpdate() {
    updateAvailable.value = false
  }

  onMounted(() => {
    window.addEventListener('pwa:update-available', onUpdateAvailable)
  })

  onUnmounted(() => {
    window.removeEventListener('pwa:update-available', onUpdateAvailable)
  })

  return { updateAvailable, applyUpdate, dismissUpdate }
}
