<template>
  <div
    v-if="visible"
    class="fixed bottom-0 inset-x-0 z-[100] pointer-events-none px-3 pt-2 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    role="region"
    :aria-label="t('pwa.install_title')"
  >
    <div
      class="pointer-events-auto mx-auto max-w-lg rounded-2xl border border-[#78001e]/18 bg-white px-4 py-4 shadow-lg shadow-slate-900/10 backdrop-blur-md dark:border-slate-600/40 dark:bg-slate-900/95 dark:shadow-black/40"
    >
      <p class="text-[15px] font-semibold leading-snug text-[#78001e] dark:text-white">
        {{ t('pwa.install_title') }}
      </p>
      <p class="mt-1.5 text-[13px] leading-relaxed text-slate-600 dark:text-slate-300">
        {{ t('pwa.install_hint') }}
      </p>
      <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-stretch">
        <button
          type="button"
          class="min-h-12 flex-1 touch-manipulation rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 active:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:active:bg-slate-700"
          @click="dismiss"
        >
          {{ t('pwa.install_later') }}
        </button>
        <button
          type="button"
          class="min-h-12 flex-1 touch-manipulation rounded-xl bg-[#78001e] px-4 text-sm font-bold text-white shadow-sm active:opacity-95 dark:bg-va-700"
          @click="install"
        >
          {{ t('pwa.install_cta') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const INSTALL_DELAY_DESKTOP_MS = 26_000
const INSTALL_DELAY_MOBILE_MS = 12_000
const DISMISS_DAYS = 7
const DISMISS_KEY = 'vas_install_dismissed'
const INSTALLED_KEY = 'vas_pwa_installed'

const deferredPrompt = ref(null)
const delayElapsed = ref(false)

const isStandalone = () =>
  typeof window !== 'undefined' &&
  (window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true)

function dismissedWithinCooldown() {
  try {
    const raw = localStorage.getItem(DISMISS_KEY)
    if (!raw) return false
    const ts = Number(raw)
    if (!Number.isFinite(ts)) return false
    return Date.now() - ts < DISMISS_DAYS * 24 * 60 * 60 * 1000
  } catch {
    return false
  }
}

const visible = computed(
  () =>
    delayElapsed.value &&
    !isStandalone() &&
    deferredPrompt.value != null &&
    !dismissedWithinCooldown(),
)

let delayTimer = null
let installListener = null

onMounted(() => {
  try {
    if (localStorage.getItem(INSTALLED_KEY) === '1') return
  } catch {
    /* ignore */
  }

  installListener = (e) => {
    if (dismissedWithinCooldown()) return
    e.preventDefault()
    deferredPrompt.value = e
    if (delayTimer != null) {
      window.clearTimeout(delayTimer)
      delayTimer = null
    }
    const delay =
      typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches
        ? INSTALL_DELAY_MOBILE_MS
        : INSTALL_DELAY_DESKTOP_MS
    delayTimer = window.setTimeout(() => {
      delayElapsed.value = true
    }, delay)
  }
  window.addEventListener('beforeinstallprompt', installListener)
})

onUnmounted(() => {
  if (installListener) {
    window.removeEventListener('beforeinstallprompt', installListener)
  }
  if (delayTimer != null) {
    window.clearTimeout(delayTimer)
  }
})

async function install() {
  const ev = deferredPrompt.value
  if (!ev) return
  ev.prompt()
  try {
    const { outcome } = await ev.userChoice
    if (outcome === 'accepted') {
      try {
        localStorage.setItem(INSTALLED_KEY, '1')
      } catch {
        /* ignore */
      }
    }
  } catch {
    /* ignore */
  }
  deferredPrompt.value = null
  delayElapsed.value = false
}

function dismiss() {
  try {
    localStorage.setItem(DISMISS_KEY, String(Date.now()))
  } catch {
    /* ignore */
  }
  deferredPrompt.value = null
  delayElapsed.value = false
}
</script>
