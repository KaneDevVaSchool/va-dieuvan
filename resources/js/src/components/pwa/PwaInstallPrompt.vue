<template>
  <Transition name="pwa-sheet">
    <div
      v-if="visible"
      class="pwa-install-sheet"
      role="dialog"
      aria-labelledby="pwa-install-title"
    >
      <p id="pwa-install-title" class="text-sm font-semibold text-white">
        {{ t('pwa.install_title') }}
      </p>
      <p class="mt-1 text-xs leading-relaxed text-white/80">
        {{ showIosGuide ? t('pwa.install_ios_hint') : t('pwa.install_hint') }}
      </p>
      <div class="mt-3 flex gap-2">
        <button
          v-if="canNativePrompt"
          type="button"
          class="flex-1 rounded-xl bg-white py-2.5 text-sm font-semibold text-[#8B1A1A]"
          @click="onInstall"
        >
          {{ t('pwa.install_cta') }}
        </button>
        <button
          type="button"
          class="rounded-xl border border-white/35 px-4 py-2.5 text-sm font-medium text-white/95"
          :class="{ 'flex-1': !canNativePrompt }"
          @click="dismiss"
        >
          {{ t('pwa.install_later') }}
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePwaInstall } from '../../composables/usePwaInstall'

const props = defineProps({
  /** Ẩn khi splash / onboarding che toàn màn hình. */
  allowShow: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
const {
  shouldOfferInstall,
  canNativePrompt,
  showIosGuide,
  promptInstall,
  dismissInstallOffer,
} = usePwaInstall()

const visible = computed(() => props.allowShow && shouldOfferInstall.value)

async function onInstall() {
  await promptInstall()
}

function dismiss() {
  dismissInstallOffer()
}
</script>

<style scoped>
.pwa-install-sheet {
  position: fixed;
  left: max(12px, env(safe-area-inset-left));
  right: max(12px, env(safe-area-inset-right));
  bottom: max(12px, env(safe-area-inset-bottom));
  z-index: 9990;
  border-radius: 1rem;
  border: 1px solid rgb(255 255 255 / 0.12);
  background: linear-gradient(145deg, #8b1a1a 0%, #5c1212 100%);
  padding: 1rem 1rem 1.1rem;
  box-shadow: 0 12px 40px rgb(0 0 0 / 0.35);
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
