<template>
  <div
    v-if="visible"
    class="fixed bottom-0 inset-x-0 z-[100] p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pointer-events-none"
    role="region"
    aria-label="Cài đặt ứng dụng"
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
import { computed } from 'vue'
import { usePwaStore } from '../../store/pwa'

const pwa = usePwaStore()

const isStandalone = () =>
  typeof window !== 'undefined' &&
  (window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true)

const visible = computed(
  () =>
    !isStandalone() &&
    !pwa.installDismissed &&
    pwa.deferredInstallPrompt != null,
)

async function install() {
  const ev = pwa.deferredInstallPrompt
  if (!ev) return
  ev.prompt()
  await ev.userChoice.catch(() => {})
  pwa.setDeferredInstallPrompt(null)
}

function dismiss() {
  pwa.dismissInstallPrompt()
}
</script>
