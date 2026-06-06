import { onMounted, onUnmounted, ref, computed } from 'vue'
import { isLikelyIos, isStandaloneDisplayMode } from '../util/platform'

const DISMISS_KEY = 'va-pwa-install-dismissed'
const DISMISS_DAYS = 14

/** @type {BeforeInstallPromptEvent | null} */
let deferredInstallPrompt = null

/**
 * @returns {boolean}
 */
function isInstallDismissedRecently() {
  try {
    const raw = localStorage.getItem(DISMISS_KEY)
    if (!raw) return false
    const ts = Number.parseInt(raw, 10)
    if (!Number.isFinite(ts)) return false
    return Date.now() - ts < DISMISS_DAYS * 24 * 60 * 60 * 1000
  } catch {
    return false
  }
}

function rememberInstallDismissed() {
  try {
    localStorage.setItem(DISMISS_KEY, String(Date.now()))
  } catch {
    /* ignore */
  }
}

/**
 * Banner “Thêm vào màn hình chính” — Android (beforeinstallprompt) & hướng dẫn Safari.
 */
export function usePwaInstall() {
  const canNativePrompt = ref(false)
  const showIosGuide = ref(false)

  const shouldOfferInstall = computed(() => {
    if (typeof window === 'undefined') return false
    if (isStandaloneDisplayMode()) return false
    if (isInstallDismissedRecently()) return false
    return canNativePrompt.value || showIosGuide.value
  })

  function syncIosGuide() {
    showIosGuide.value = isLikelyIos() && !deferredInstallPrompt
  }

  function onBeforeInstallPrompt(event) {
    event.preventDefault()
    deferredInstallPrompt = event
    canNativePrompt.value = true
    showIosGuide.value = false
  }

  function onAppInstalled() {
    deferredInstallPrompt = null
    canNativePrompt.value = false
    showIosGuide.value = false
  }

  onMounted(() => {
    if (isStandaloneDisplayMode()) return
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled', onAppInstalled)
    syncIosGuide()
  })

  onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.removeEventListener('appinstalled', onAppInstalled)
  })

  async function promptInstall() {
    const prompt = deferredInstallPrompt
    if (!prompt) return false
    try {
      await prompt.prompt()
      const { outcome } = await prompt.userChoice
      deferredInstallPrompt = null
      canNativePrompt.value = false
      if (outcome === 'accepted') {
        showIosGuide.value = false
      }
      return outcome === 'accepted'
    } catch {
      return false
    }
  }

  function dismissInstallOffer() {
    rememberInstallDismissed()
    canNativePrompt.value = false
    showIosGuide.value = false
  }

  return {
    shouldOfferInstall,
    canNativePrompt,
    showIosGuide,
    promptInstall,
    dismissInstallOffer,
  }
}
