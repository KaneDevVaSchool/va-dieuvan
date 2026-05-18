<template>
  <!-- pt-* bù chiều cao PortalHeader (fixed); đồng bộ với safe-area + py-3.5 + hàng ~11 (logo) -->
  <div
    class="portal-app min-h-dvh bg-gradient-to-br from-slate-50 via-white to-indigo-50/25 pt-[calc(env(safe-area-inset-top,0px)+4.75rem)] text-slate-900 antialiased sm:pt-[calc(env(safe-area-inset-top,0px)+5rem)]"
  >
    <PortalHeader />
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import PortalHeader from '../../components/portal/PortalHeader.vue'
</script>
