<template>
  <div
    v-if="visible"
    class="fixed bottom-0 inset-x-0 z-[100] p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pointer-events-none"
    role="region"
    :aria-label="$t('pwa.install_title')"
  >
    <div
      class="pointer-events-auto mx-auto max-w-lg rounded-xl border border-[#78001e]/20 bg-white/95 shadow-lg backdrop-blur-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
    >
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-[#78001e]">{{ $t('pwa.install_title') }}</p>
        <p class="text-xs text-gray-600 mt-0.5">{{ $t('pwa.install_hint') }}</p>
      </div>
      <div class="flex gap-2 shrink-0">
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
          @click="dismiss"
        >
          {{ $t('pwa.install_later') }}
        </button>
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#78001e] text-white hover:opacity-95"
          @click="install"
        >
          {{ $t('pwa.install_cta') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const INSTALL_DELAY_MS = 30_000
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
    delayTimer = window.setTimeout(() => {
      delayElapsed.value = true
    }, INSTALL_DELAY_MS)
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
