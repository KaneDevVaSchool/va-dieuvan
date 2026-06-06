<template>
  <Transition name="pwa-sheet">
    <div
      v-if="updateAvailable"
      class="pwa-update-sheet"
      role="dialog"
      aria-labelledby="pwa-update-title"
    >
      <p id="pwa-update-title" class="text-sm font-semibold text-[#020B0B]">
        {{ t('pwa.update_title') }}
      </p>
      <p class="mt-1 text-xs leading-relaxed text-[#020B0B]/75">
        {{ t('pwa.update_hint') }}
      </p>
      <div class="mt-3 flex gap-2">
        <button
          type="button"
          class="flex-1 rounded-xl bg-[#020B0B] py-2.5 text-sm font-semibold text-[#7fdcc8]"
          @click="applyUpdate"
        >
          {{ t('pwa.update_cta') }}
        </button>
        <button
          type="button"
          class="rounded-xl border border-[#020B0B]/20 px-4 py-2.5 text-sm font-medium text-[#020B0B]"
          @click="dismissUpdate"
        >
          {{ t('pwa.update_later') }}
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { usePwaUpdate } from '../../composables/usePwaUpdate'

const { t } = useI18n()
const { updateAvailable, applyUpdate, dismissUpdate } = usePwaUpdate()
</script>

<style scoped>
.pwa-update-sheet {
  position: fixed;
  left: max(12px, env(safe-area-inset-left));
  right: max(12px, env(safe-area-inset-right));
  bottom: max(12px, env(safe-area-inset-bottom));
  z-index: 9989;
  border-radius: 1rem;
  border: 1px solid rgb(127 220 200 / 0.45);
  background: #7fdcc8;
  padding: 1rem 1rem 1.1rem;
  box-shadow: 0 12px 40px rgb(0 0 0 / 0.25);
}

.pwa-sheet-enter-active,
.pwa-sheet-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}
.pwa-sheet-enter-from,
.pwa-sheet-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
