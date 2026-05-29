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
import { i18n } from '../i18n'
import { useAuthStore } from './index'

const POLL_INTERVAL_MS = 15000
const TOAST_MAX = 3

const SOUND_KEY = 'va_notify_sound'
const ASKED_PUSH_KEY = 'va_push_asked'

function readSoundPref() {
  try {
    return localStorage.getItem(SOUND_KEY) !== '0'
  } catch {
    return true
  }
}

function syncAppBadge(count) {
  if (typeof navigator === 'undefined') {
    return
  }
  if (!('setAppBadge' in navigator) || typeof navigator.setAppBadge !== 'function') {
    return
  }
  try {
    if (count > 0) {
      void navigator.setAppBadge(count > 99 ? 99 : count)
    } else if ('clearAppBadge' in navigator && typeof navigator.clearAppBadge === 'function') {
      void navigator.clearAppBadge()
    }
  } catch {
    /* ignore */
  }
}

function clearAppBadgeSafe() {
  if (typeof navigator === 'undefined') {
    return
  }
  if (!('clearAppBadge' in navigator) || typeof navigator.clearAppBadge !== 'function') {
    return
  }
  try {
    void navigator.clearAppBadge()
  } catch {
    /* ignore */
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

/** Tránh treo UI khi mạng / pushManager / SW không phản hồi (axios timeout + tầng race). */
const WEB_PUSH_REGISTER_TIMEOUT_MS = 25000

function createWebPushRegisterTimeoutPromise() {
  let timeoutId
  const p = new Promise((_, reject) => {
    timeoutId = setTimeout(() => {
      const err = new Error('Web push registration timed out')
      err.name = 'TimeoutError'
      reject(err)
    }, WEB_PUSH_REGISTER_TIMEOUT_MS)
  })
  return { promise: p, clear: () => clearTimeout(timeoutId) }
}

/**
 * Phân loại lỗi đăng ký push — tránh gán mọi AxiosError thành lỗi mạng.
 * @param {unknown} e
 * @returns {'denied' | 'network' | 'api'}
 */
function mapPushRegisterError(e) {
  const name = e && typeof e === 'object' && 'name' in e ? String(/** @type {{ name?: string }} */ (e).name) : ''
  if (name === 'NotAllowedError') {
    return 'denied'
  }
  if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
    return 'denied'
  }
  if (name === 'TimeoutError') {
    return 'network'
  }
  if (name === 'AbortError') {
    return 'network'
  }
  const code = e && typeof e === 'object' && 'code' in e ? String(/** @type {{ code?: string }} */ (e).code) : ''
  const response = e && typeof e === 'object' && 'response' in e ? /** @type {{ response?: unknown }} */ (e).response : undefined
  const isAxiosLike =
    response != null
    || name === 'AxiosError'
    || (e && typeof e === 'object' && /** @type {{ isAxiosError?: boolean }} */ (e).isAxiosError === true)
  if (isAxiosLike) {
    if (response != null) {
      return 'api'
    }
    if (
      code === 'ECONNABORTED'
      || code === 'ERR_NETWORK'
      || code === 'ETIMEDOUT'
      || code === 'ECONNRESET'
    ) {
      return 'network'
    }
    return 'api'
  }
  return 'api'
}

export const useNotificationStore = defineStore('notificationCenter', () => {
  const panelOpen = ref(false)
  const items = ref([])
  const loading = ref(false)
  const activeTab = ref('all')
  const lastUnread = ref(0)
  const navBadges = ref({})
  let badgePrimed = false
  const soundEnabled = ref(true)
  const pushState = ref('unknown')
  const pushRegisterLoading = ref(false)
  const pollHandle = ref(null)

  const toastQueue = ref([])
  /** IDs đã toast trong phiên — tránh hiện lại sau mỗi poll */
  let seenToastIds = new Set()
  /** Thời điểm bắt đầu poll — không toast notify cũ hơn */
  let sessionStartedAt = null
  /** Guard tránh gọi _fetchAndQueueToasts song song */
  let isFetchingToasts = false
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

  watch(lastUnread, (n) => {
    const num = Number(n)
    syncAppBadge(Number.isFinite(num) && num > 0 ? num : 0)
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

  async function loadInbox(tab = activeTab.value) {
    loading.value = true
    try {
      const audience = tab && tab !== 'all' ? tab : undefined
      const res = await fetchNotificationInbox({ per_page: 30, audience })
      items.value = res?.items ?? []
    } catch {
      items.value = []
    } finally {
      loading.value = false
    }
  }

  function setTab(tab, options = {}) {
    const reload = options.reload !== false
    const next = tab && typeof tab === 'string' ? tab : 'all'
    activeTab.value = next
    if (reload) {
      void loadInbox(next)
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
    if (!auth.isLoggedIn || !auth.user || auth.isPortalUser()) {
      return
    }
    try {
      const b = await fetchNavBadges()
      navBadges.value = b && typeof b === 'object' ? b : {}
      const n = Number(b?.notifications_unread ?? 0)
      if (Number.isFinite(n)) {
        if (badgePrimed && n > lastUnread.value) {
          if (soundEnabled.value) {
            playNotificationChime()
          }
          if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            try {
              new Notification(document.title, {
                body: i18n.global.t('notify.os_toast'),
                tag: 'va-bell',
                icon: '/images/logo/logo_pwa_v1.png',
              })
            } catch {
              /* ignore */
            }
          }
          void _fetchAndQueueToasts()
        }
        lastUnread.value = n
        badgePrimed = true
      }
    } catch {
      /* ignore */
    }
  }

  async function _fetchAndQueueToasts() {
    if (isFetchingToasts) return
    isFetchingToasts = true
    try {
      const res = await fetchNotificationInbox({ per_page: 5 })
      const inbox = res?.items ?? []
      const cutoff = sessionStartedAt instanceof Date ? sessionStartedAt : null
      for (const item of inbox) {
        if (toastQueue.value.length >= TOAST_MAX) break
        if (seenToastIds.has(item.id)) continue
        if (cutoff && item.created_at) {
          const t = new Date(item.created_at)
          if (t < cutoff) continue
        }
        if (item.read) continue
        _pushToast(item)
      }
    } catch {
      /* ignore */
    } finally {
      isFetchingToasts = false
    }
  }

  function _pushToast(item) {
    seenToastIds.add(item.id)
    toastQueue.value.push({
      id: item.id,
      title: item.data?.title ?? i18n.global.t('notify.title'),
      body: item.data?.body ?? '',
      url: item.data?.url ?? '',
      createdAt: item.created_at,
    })
  }

  function dismissToast(id) {
    toastQueue.value = toastQueue.value.filter((t) => t.id !== id)
  }

  function _onVisibilityChange() {
    if (document.visibilityState === 'visible') {
      void refreshBadges()
    }
  }

  function startPolling() {
    if (typeof window === 'undefined') {
      return
    }
    const auth = useAuthStore()
    if (!auth.isLoggedIn || !auth.user || auth.isPortalUser()) {
      stopPolling()
      return
    }
    stopPolling()
    sessionStartedAt = new Date()
    void refreshBadges()
    document.addEventListener('visibilitychange', _onVisibilityChange)
    pollHandle.value = window.setInterval(() => {
      if (document.visibilityState === 'visible') {
        void refreshBadges()
      }
    }, POLL_INTERVAL_MS)
  }

  function stopPolling() {
    if (pollHandle.value != null) {
      clearInterval(pollHandle.value)
      pollHandle.value = null
    }
    document.removeEventListener('visibilitychange', _onVisibilityChange)
    lastUnread.value = 0
    navBadges.value = {}
    badgePrimed = false
    panelOpen.value = false
    toastQueue.value = []
    seenToastIds = new Set()
    sessionStartedAt = null
    isFetchingToasts = false
    clearAppBadgeSafe()
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
   * Đăng ký Web Push khi trình duyệt hỗ trợ (chỉ môi trường production).
   * @returns {{ ok: boolean, reason?: 'denied' | 'network' | 'api' | 'unsupported' | 'no_vapid' }}
   */
  async function registerWebPush() {
    const auth = useAuthStore()
    if (!auth.isLoggedIn || !auth.user || auth.isPortalUser()) {
      return { ok: false, reason: 'api' }
    }
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
      pushState.value = 'unsupported'
      return { ok: false, reason: 'unsupported' }
    }
    if (!import.meta.env.PROD) {
      pushState.value = 'dev'
      return { ok: false, reason: 'unsupported' }
    }
    if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
      pushState.value = 'denied'
      return { ok: false, reason: 'denied' }
    }
    pushRegisterLoading.value = true
    const { promise: timeoutPromise, clear: clearRegisterTimeout } = createWebPushRegisterTimeoutPromise()
    try {
      const work = (async () => {
        try {
          try {
            const { publicKey: pub } = await getVapidPublicKey()
            if (!pub) {
              pushState.value = 'no_vapid'
              return { ok: false, reason: 'no_vapid' }
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
            return { ok: true }
          } catch (e) {
            pushState.value = 'error'
            if (import.meta.env.DEV) console.warn('[push]', e)
            return { ok: false, reason: mapPushRegisterError(e) }
          }
        } finally {
          clearRegisterTimeout()
        }
      })()

      return await Promise.race([work, timeoutPromise])
    } catch (e) {
      clearRegisterTimeout()
      pushState.value = 'error'
      if (import.meta.env.DEV) console.warn('[push]', e)
      if (e?.name === 'TimeoutError') {
        return { ok: false, reason: 'network' }
      }
      return { ok: false, reason: 'api' }
    } finally {
      pushRegisterLoading.value = false
    }
  }

  return {
    panelOpen,
    items,
    loading,
    activeTab,
    lastUnread,
    navBadges,
    soundEnabled,
    pushState,
    pushRegisterLoading,
    toastQueue,
    setPanel,
    openPanel,
    closePanel,
    loadInbox,
    setTab,
    onReadOne,
    onReadAll,
    refreshBadges,
    dismissToast,
    startPolling,
    stopPolling,
    requestBrowserNotificationPermission,
    registerWebPush,
  }
})
