import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import '../../css/app.css'
import { useAuthStore } from './store'
import { useUiStore } from './store/ui'
import { i18n } from './i18n'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(i18n)
app.use(router)
const ui = useUiStore()
ui.initFromStorage()
ui.initViewportListener()
useAuthStore().initFromStorage()
app.mount('#app')

if (import.meta.env.PROD && typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(() => {})
}

