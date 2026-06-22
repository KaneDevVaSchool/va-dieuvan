<template>
  <div
    class="portal-app flex h-full min-h-0 flex-col overflow-hidden bg-slate-50 text-slate-900 antialiased"
    style="--portal-header: 3rem; --portal-bottom-nav: 4.75rem"
  >
    <PortalHeader />
    <main
      class="portal-app-main min-h-0 flex-1 overflow-y-auto overscroll-y-contain
             pt-[calc(env(safe-area-inset-top,0px)+var(--portal-header))]
             pb-[calc(env(safe-area-inset-bottom,0px)+var(--portal-bottom-nav))]
             md:pb-[calc(env(safe-area-inset-bottom,0px)+1rem)]"
      :class="hideBottomNav ? '!pb-[calc(env(safe-area-inset-bottom,0px)+1rem)]' : ''"
    >
      <RouterView />
    </main>
    <PortalBottomNav v-if="!hideBottomNav" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import PortalHeader from '../../components/portal/PortalHeader.vue'
import PortalBottomNav from '../../components/portal/shell/PortalBottomNav.vue'
import { useAuthStore } from '../../store'

const auth = useAuthStore()
const route = useRoute()

const hideBottomNav = computed(() => {
  const names = [
    'portalCreate',
    'portalExtracurricularCreate',
    'portalRequestDetail',
    'portalExtracurricularDetail',
    'portalNotifications',
  ]
  return names.includes(route.name)
})

onMounted(() => {
  if (auth.isLoggedIn && !auth.user?.avatar_url) {
    auth.fetchMe().catch(() => {})
  }
})
</script>

<style>
.portal-app {
  -webkit-tap-highlight-color: transparent;
}

.portal-app :is(button, a, [role='option'], [role='menuitem']):focus:not(:focus-visible) {
  outline: none;
}
</style>
