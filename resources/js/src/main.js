import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router, { CHUNK_RELOAD_SESSION_KEY } from './router'
import App from './App.vue'
import '../../css/app.css'
import { useAuthStore } from './store'
import { useUiStore } from './store/ui'
import { i18n } from './i18n'
import { bootstrapMonitoring } from './core/monitoring/bootstrapMonitoring'
import { attachOnlineFlush } from './core/offline/outbox'
import { initIntegrationsFromEnv } from './core/monitoring/logger'
import { setupServiceWorker } from './pwa/registerSW'

bootstrapMonitoring()
initIntegrationsFromEnv()
attachOnlineFlush()

setupServiceWorker()

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(i18n)
app.use(router)
const ui = useUiStore()
ui.initFromStorage()
ui.initViewportListener()

const auth = useAuthStore()
auth.initFromStorage()

;(async () => {
  await auth.restoreSession()
  app.mount('#app')
  sessionStorage.removeItem(CHUNK_RELOAD_SESSION_KEY)
})()
