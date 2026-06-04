<template>
  <Teleport to="body">
    <Transition name="va-pwa-update">
      <div
        v-if="visible"
        class="pointer-events-none fixed inset-x-0 bottom-0 z-[320] flex justify-center p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] print:hidden"
        role="region"
        :aria-label="t('pwa.update_title')"
      >
        <div
          class="pointer-events-auto flex w-full max-w-lg flex-col gap-3 rounded-2xl border border-teal-200/80 bg-white px-4 py-3 shadow-lg ring-1 ring-slate-900/5 dark:border-teal-900/50 dark:bg-slate-900 dark:ring-white/10 sm:flex-row sm:items-center"
        >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
              {{ t('pwa.update_title') }}
            </p>
            <p class="mt-0.5 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
              {{ t('pwa.update_hint') }}
            </p>
          </div>
          <div class="flex shrink-0 gap-2 sm:flex-col sm:gap-1.5 md:flex-row">
            <button
              type="button"
              class="rounded-xl px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
              @click="dismiss"
            >
              {{ t('pwa.update_later') }}
            </button>
            <button
              type="button"
              class="rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-teal-800 disabled:opacity-60 dark:bg-teal-600 dark:hover:bg-teal-500"
              :disabled="applying"
              @click="applyUpdate"
            >
              {{ applying ? t('common.processing') : t('pwa.update_cta') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { applyServiceWorkerUpdate, takePendingPwaUpdate } from '../../pwa/registerSW'

const { t } = useI18n()
const visible = ref(false)
const applying = ref(false)

function show() {
  visible.value = true
}

function dismiss() {
  visible.value = false
}

async function applyUpdate() {
  applying.value = true
  try {
    await applyServiceWorkerUpdate()
  } catch {
    window.location.reload()
  }
}

function onUpdateAvailable() {
  show()
}

onMounted(() => {
  if (takePendingPwaUpdate()) show()
  window.addEventListener('pwa:update-available', onUpdateAvailable)
})

onUnmounted(() => {
  window.removeEventListener('pwa:update-available', onUpdateAvailable)
})
</script>

<style scoped>
.va-pwa-update-enter-active,
.va-pwa-update-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.va-pwa-update-enter-from,
.va-pwa-update-leave-to {
  opacity: 0;
  transform: translateY(0.75rem);
}
</style>
