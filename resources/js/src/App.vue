<template>
  <SplashScreen
    v-if="showSplash"
    :app-ready="splashAppReady"
    @done="showSplash = false"
  />
  <Onboarding
    v-if="onboardingViewportOk && !hasOnboarded && isAuthenticated && !isPortalShell && !isDeptShell"
    @done="completeOnboarding"
  />
  <template v-else>
    <LayoutDriver v-if="isDriverApp">
      <RouterView v-slot="{ Component, route: rv }">
        <Transition :name="driverTransition" mode="out-in">
          <component :is="Component" :key="rv.path" />
        </Transition>
      </RouterView>
    </LayoutDriver>
    <AppShell v-else-if="!isLoginLayout && !isDeptShell">
      <RouterView />
    </AppShell>
    <RouterView v-else-if="isDeptShell" />
    <RouterView v-else />
  </template>

  <AppMessageModal />
  <ConfirmModal />

  <NotificationCenter />
  <NotificationToast />

</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from './store'
import { useOnboarding } from './composables/useOnboarding'
import AppShell from './components/layout/AppShell.vue'
import LayoutDriver from './components/layout/LayoutDriver.vue'
import AppMessageModal from './components/ui/AppMessageModal.vue'
import ConfirmModal from './components/ui/ConfirmModal.vue'
import SplashScreen from './components/SplashScreen.vue'
import Onboarding from './components/Onboarding.vue'
import NotificationCenter from './components/notifications/NotificationCenter.vue'
import NotificationToast from './components/notifications/NotificationToast.vue'
const route = useRoute()
const auth = useAuthStore()
const { hasOnboarded, completeOnboarding } = useOnboarding()

const isPortalShell = computed(() => route.matched.some((record) => record.meta.portal))
const isDeptShell = computed(
  () =>
    route.path === '/dept' ||
    route.path.startsWith('/dept/') ||
    route.matched.some((record) => record.meta.deptHead === true),
)

const isLoginLayout = computed(
  () => route.name === 'login' || route.name === 'home' || isPortalShell.value,
)
const isDriverApp = computed(() => !!route.meta?.driverApp)
const isAuthenticated = computed(() => auth.isAuthenticated)

/**
 * Hướng chuyển trang trong driver shell:
 *  - đi sâu hơn (vd. lịch → chi tiết chuyến) → trượt tới (fwd)
 *  - quay lại (ít cấp hơn) → trượt lui (back)
 *  - cùng cấp (đổi tab bottom nav) → fade nhẹ
 */
const driverTransition = ref('driver-fade')
function segDepth(p) {
  return String(p || '')
    .split('/')
    .filter(Boolean).length
}
watch(
  () => route.path,
  (to, from) => {
    const dTo = segDepth(to)
    const dFrom = segDepth(from)
    if (dTo > dFrom) driverTransition.value = 'driver-fwd'
    else if (dTo < dFrom) driverTransition.value = 'driver-back'
    else driverTransition.value = 'driver-fade'
  },
)

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
