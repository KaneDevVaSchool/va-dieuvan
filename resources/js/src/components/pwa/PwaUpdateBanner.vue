<template>
  <Transition name="pwa-update-banner">
    <div
      v-if="visible"
      class="pwa-update-banner"
      role="region"
      :aria-label="t('pwa.update_title')"
    >
      <div class="pwa-update-banner__inner">
        <div class="pwa-update-banner__text">
          <p class="pwa-update-banner__title">{{ t('pwa.update_title') }}</p>
          <p class="pwa-update-banner__hint">{{ t('pwa.update_hint') }}</p>
        </div>
        <div class="pwa-update-banner__actions">
          <button
            type="button"
            class="pwa-update-banner__btn pwa-update-banner__btn--ghost"
            :disabled="applying"
            @click="dismiss"
          >
            {{ t('pwa.update_later') }}
          </button>
          <button
            type="button"
            class="pwa-update-banner__btn pwa-update-banner__btn--primary"
            :disabled="applying"
            :aria-busy="applying ? 'true' : undefined"
            @click="onReload"
          >
            {{ applying ? t('common.processing') : t('pwa.update_cta') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { applyServiceWorkerUpdate, takePendingPwaUpdate } from '../../pwa/registerSW'

const { t } = useI18n()

const visible = ref(false)
const applying = ref(false)

function onUpdateAvailable() {
  visible.value = true
}

function dismiss() {
  visible.value = false
}

async function onReload() {
  if (applying.value) return
  applying.value = true
  try {
    await applyServiceWorkerUpdate()
  } catch {
    applying.value = false
    window.location.reload()
  }
}

onMounted(() => {
  window.addEventListener('pwa:update-available', onUpdateAvailable)
  if (takePendingPwaUpdate()) {
    visible.value = true
  }
})

onUnmounted(() => {
  window.removeEventListener('pwa:update-available', onUpdateAvailable)
})
</script>

<style scoped>
.pwa-update-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  padding: max(10px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) 10px
    max(12px, env(safe-area-inset-left));
  pointer-events: none;
}

.pwa-update-banner__inner {
  pointer-events: auto;
  margin: 0 auto;
  max-width: 42rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px 16px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #0f172a;
  color: #f8fafc;
  box-shadow:
    0 10px 40px rgba(0, 0, 0, 0.25),
    0 0 0 1px rgba(255, 255, 255, 0.08);
}

.pwa-update-banner__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.3;
}

.pwa-update-banner__hint {
  margin: 4px 0 0;
  font-size: 0.8125rem;
  line-height: 1.4;
  color: rgba(248, 250, 252, 0.82);
}

.pwa-update-banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-left: auto;
}

.pwa-update-banner__btn {
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s ease;
}

.pwa-update-banner__btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.pwa-update-banner__btn--ghost {
  background: transparent;
  color: rgba(248, 250, 252, 0.9);
}

.pwa-update-banner__btn--ghost:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
}

.pwa-update-banner__btn--primary {
  background: #78001e;
  color: #fff;
}

.pwa-update-banner__btn--primary:hover:not(:disabled) {
  background: #5c0017;
}

.pwa-update-banner-enter-active,
.pwa-update-banner-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

.pwa-update-banner-enter-from,
.pwa-update-banner-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
