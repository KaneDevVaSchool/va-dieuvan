<template>
  <SplashScreen
    v-if="showSplash"
    :app-ready="splashAppReady"
    @done="showSplash = false"
  />
  <template v-else>
    <Onboarding
      v-if="onboardingViewportOk && !hasOnboarded && isAuthenticated"
      @done="completeOnboarding"
    />
    <template v-else>
      <LayoutDriver v-if="isDriverApp">
        <RouterView />
      </LayoutDriver>
      <AppShell v-else-if="!isLoginLayout">
        <RouterView />
      </AppShell>
      <RouterView v-else />
    </template>
  </template>

  <AppMessageModal />
  <ConfirmModal />
  <PwaInstallBanner />

  <Transition name="slide-up">
    <div
      v-if="updateAvailable"
      class="fixed bottom-0 inset-x-0 z-[101] pointer-events-none px-3 pt-2 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div
        class="pointer-events-auto mx-auto max-w-lg rounded-2xl border border-white/15 bg-[#78001e] px-4 py-4 text-white shadow-lg shadow-slate-900/20"
      >
        <p class="text-[15px] font-semibold leading-snug">
          {{ t('pwa.update_title') }}
        </p>
        <p class="mt-1 text-[13px] leading-relaxed text-white/85">
          {{ t('pwa.update_hint') }}
        </p>
        <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-stretch">
          <button
            type="button"
            class="min-h-12 flex-1 touch-manipulation rounded-xl border border-white/25 bg-white/10 px-4 text-sm font-semibold text-white active:bg-white/20"
            @click="dismissUpdate"
          >
            {{ t('pwa.update_later') }}
          </button>
          <button
            type="button"
            class="min-h-12 flex-1 touch-manipulation rounded-xl bg-white px-4 text-sm font-bold text-[#78001e] shadow-sm active:opacity-95"
            @click="applyUpdate"
          >
            {{ t('pwa.update_cta') }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <NotificationCenter />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from './store'
import { useOnboarding } from './composables/useOnboarding'
import { usePwaUpdate } from './composables/usePwaUpdate'
import AppShell from './components/layout/AppShell.vue'
import LayoutDriver from './components/layout/LayoutDriver.vue'
import AppMessageModal from './components/ui/AppMessageModal.vue'
import ConfirmModal from './components/ui/ConfirmModal.vue'
import SplashScreen from './components/SplashScreen.vue'
import Onboarding from './components/Onboarding.vue'
import PwaInstallBanner from './components/PwaInstallBanner.vue'
import NotificationCenter from './components/notifications/NotificationCenter.vue'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const { hasOnboarded, completeOnboarding } = useOnboarding()
const { updateAvailable, applyUpdate, dismissUpdate } = usePwaUpdate()

const isLoginLayout = computed(() => route.name === 'login')
const isDriverApp = computed(() => !!route.meta?.driverApp)
const isAuthenticated = computed(() => auth.isAuthenticated)

const showSplash = ref(true)
const splashAppReady = ref(false)

/** Onboarding chỉ trên màn hẹp (mobile / tablet); desktop ≥1024px bỏ qua. */
const ONBOARDING_MAX_MQ = '(max-width: 1023px)'
const onboardingViewportOk = ref(
  typeof window !== 'undefined' && window.matchMedia(ONBOARDING_MAX_MQ).matches,
)

let onboardingMq = null
function onboardingMqSync() {
  if (onboardingMq) onboardingViewportOk.value = onboardingMq.matches
}

onMounted(() => {
  splashAppReady.value = true
  onboardingMq = window.matchMedia(ONBOARDING_MAX_MQ)
  onboardingMqSync()
  onboardingMq.addEventListener('change', onboardingMqSync)
})

onUnmounted(() => {
  if (onboardingMq) {
    onboardingMq.removeEventListener('change', onboardingMqSync)
  }
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition:
    transform 0.28s ease,
    opacity 0.28s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(100%);
}
</style>
