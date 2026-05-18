<template>
  <div class="portal-app min-h-dvh bg-slate-50 text-slate-900 antialiased">
    <PortalHeader />
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import PortalHeader from '../../components/portal/PortalHeader.vue'
</script>
