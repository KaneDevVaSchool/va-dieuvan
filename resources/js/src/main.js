import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import '../../css/app.css'
import { useAuthStore } from './store'
import { useUiStore } from './store/ui'
import { usePwaStore } from './store/pwa'
import { i18n } from './i18n'
import { bootstrapMonitoring } from './core/monitoring/bootstrapMonitoring'
import { attachOnlineFlush } from './core/offline/outbox'
import { initIntegrationsFromEnv } from './core/monitoring/logger'

bootstrapMonitoring()
initIntegrationsFromEnv()
attachOnlineFlush()

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(i18n)
app.use(router)
const ui = useUiStore()
ui.initFromStorage()
ui.initViewportListener()
const pwaStore = usePwaStore()
pwaStore.loadDismissed()

/** PWA: only defer the browser banner when we will show custom UI — never preventDefault while install was dismissed */
function onBeforeInstallPrompt(ev) {
  if (pwaStore.installDismissed) return
  ev.preventDefault()
  pwaStore.setDeferredInstallPrompt(ev)
}

if (typeof window !== 'undefined') {
  window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
}

useAuthStore().initFromStorage()
app.mount('#app')

const splash = document.getElementById('pwa-splash')
if (splash) {
  requestAnimationFrame(() => {
    splash.classList.add('pwa-splash--hide')
    const remove = () => splash.remove()
    splash.addEventListener('transitionend', remove, { once: true })
    window.setTimeout(remove, 500)
  })
}

if (import.meta.env.PROD && typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {})
}

