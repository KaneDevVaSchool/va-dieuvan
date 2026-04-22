import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { playNotificationChime } from '../util/notificationChime'
import { fetchNavBadges } from '../api/navBadges'
import {
  fetchNotificationInbox,
  markAllNotificationsRead,
  markNotificationRead,
} from '../api/notifications'
import { getVapidPublicKey, storePushSubscription } from '../api/push'
import { useAuthStore } from './index'

const SOUND_KEY = 'va_notify_sound'
const ASKED_PUSH_KEY = 'va_push_asked'

function readSoundPref() {
  try {
    return localStorage.getItem(SOUND_KEY) !== '0'
  } catch {
    return true
  }
}

/**
 * @param {string} b64
 * @returns {Uint8Array}
 */
function urlBase64ToUint8Array(b64) {
  const padding = '='.repeat((4 - (b64.length % 4)) % 4)
  const base64 = (b64 + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const out = new Uint8Array(raw.length)
  for (let i = 0; i < raw.length; i += 1) out[i] = raw.charCodeAt(i)
  return out
}

export const useNotificationStore = defineStore('notificationCenter', () => {
  const panelOpen = ref(false)
  const items = ref([])
  const loading = ref(false)
  const lastUnread = ref(0)
  let badgePrimed = false
  const soundEnabled = ref(true)
  const pushState = ref('unknown')
  const pollHandle = ref(null)
  if (typeof window !== 'undefined') {
    soundEnabled.value = readSoundPref()
  }

  watch(soundEnabled, (v) => {
    try {
      localStorage.setItem(SOUND_KEY, v ? '1' : '0')
    } catch {
      /* ignore */
    }
  })

  function setPanel(open) {
    panelOpen.value = open
  }

  function openPanel() {
    panelOpen.value = true
    loadInbox()
  }

  function closePanel() {
    panelOpen.value = false
  }

  async function loadInbox() {
    loading.value = true
    try {
      const res = await fetchNotificationInbox({ per_page: 30 })
      items.value = res?.items ?? []
    } catch {
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function onReadOne(id) {
    try {
      await markNotificationRead(id)
    } catch {
      /* ignore */
    }
    const row = items.value.find((x) => x.id === id)
    if (row) row.read = true
    await refreshBadges()
  }

  async function onReadAll() {
    try {
      await markAllNotificationsRead()
    } catch {
      /* ignore */
    }
    for (const x of items.value) {
      if (x) x.read = true
    }
    await refreshBadges()
  }

  async function refreshBadges() {
    const auth = useAuthStore()
    if (!auth.isLoggedIn) {
      return
    }
    try {
      const b = await fetchNavBadges()
      const n = Number(b?.notifications_unread ?? 0)
      if (Number.isFinite(n)) {
        if (badgePrimed && n > lastUnread.value && soundEnabled.value) {
          playNotificationChime()
          if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            try {
              new Notification(document.title, {
                body: 'Bạn có thông báo mới',
                tag: 'va-bell',
                icon: '/images/logo/logo_pwa_v1.png',
              })
            } catch {
              /* ignore */
            }
          }
        }
        lastUnread.value = n
        badgePrimed = true
      }
    } catch {
      /* ignore */
    }
  }

  function startPolling() {
    if (typeof window === 'undefined') {
      return
    }
    stopPolling()
    void refreshBadges()
    pollHandle.value = window.setInterval(() => {
      if (document.visibilityState === 'visible') {
        void refreshBadges()
      }
    }, 45000)
  }

  function stopPolling() {
    if (pollHandle.value != null) {
      clearInterval(pollHandle.value)
      pollHandle.value = null
    }
    lastUnread.value = 0
    badgePrimed = false
    panelOpen.value = false
  }

  async function requestBrowserNotificationPermission() {
    if (typeof Notification === 'undefined') {
      return
    }
    if (Notification.permission === 'granted' || Notification.permission === 'denied') {
      return
    }
    try {
      await Notification.requestPermission()
    } catch {
      /* ignore */
    }
  }

  /**
   * Đăng ký Web Push (PWA) khi trình duyệt hỗ trợ.
   */
  async function registerWebPush() {
    const auth = useAuthStore()
    if (!auth.isLoggedIn) {
      return
    }
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
      pushState.value = 'unsupported'
      return
    }
    if (!import.meta.env.PROD) {
      pushState.value = 'dev'
      return
    }
    try {
      const { publicKey: pub } = await getVapidPublicKey()
      if (!pub) {
        pushState.value = 'no_vapid'
        return
      }
      const reg = await navigator.serviceWorker.ready
      let sub = await reg.pushManager.getSubscription()
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(pub),
        })
      }
      await storePushSubscription(sub)
      pushState.value = 'subscribed'
      try {
        localStorage.setItem(ASKED_PUSH_KEY, '1')
      } catch {
        /* ignore */
      }
    } catch (e) {
      pushState.value = 'error'
      console.warn('[push]', e)
    }
  }

  return {
    panelOpen,
    items,
    loading,
    lastUnread,
    soundEnabled,
    pushState,
    setPanel,
    openPanel,
    closePanel,
    loadInbox,
    onReadOne,
    onReadAll,
    refreshBadges,
    startPolling,
    stopPolling,
    requestBrowserNotificationPermission,
    registerWebPush,
  }
})
