<template>
  <!-- pt-* bù chiều cao PortalHeader (fixed); đồng bộ với safe-area + py-3.5 + hàng ~11 (logo) -->
  <div
    class="portal-app min-h-dvh bg-white pt-[calc(env(safe-area-inset-top,0px)+4.75rem)] text-slate-900 antialiased sm:pt-[calc(env(safe-area-inset-top,0px)+5rem)]"
  >
    <PortalHeader />
    <RouterView />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import PortalHeader from '../../components/portal/PortalHeader.vue'
import { useAuthStore } from '../../store'

const auth = useAuthStore()

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

/* Giữ outline cho bàn phím; bỏ viền focus khi click chuột / chạm trên portal */
.portal-app :is(button, a, [role='option'], [role='menuitem']):focus:not(:focus-visible) {
  outline: none;
}
</style>
