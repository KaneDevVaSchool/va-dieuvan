import { defineStore } from 'pinia'
import { ref } from 'vue'

const DISMISS_KEY = 'va_pwa_install_dismissed'

export const usePwaStore = defineStore('pwa', () => {
  /** @type {import('vue').Ref<BeforeInstallPromptEvent | null>} */
  const deferredInstallPrompt = ref(null)
  const installDismissed = ref(false)

  function loadDismissed() {
    try {
      installDismissed.value = localStorage.getItem(DISMISS_KEY) === '1'
    } catch {
      installDismissed.value = false
    }
  }

  /** @param {BeforeInstallPromptEvent | null} e */
  function setDeferredInstallPrompt(e) {
    deferredInstallPrompt.value = e
  }

  function dismissInstallPrompt() {
    installDismissed.value = true
    try {
      localStorage.setItem(DISMISS_KEY, '1')
    } catch {
      /* ignore */
    }
    deferredInstallPrompt.value = null
  }

  return {
    deferredInstallPrompt,
    installDismissed,
    loadDismissed,
    setDeferredInstallPrompt,
    dismissInstallPrompt,
  }
})
