<template>
  <AppShell v-if="!isLoginLayout">
    <RouterView />
  </AppShell>
  <RouterView v-else />
  <AppMessageModal />
  <ConfirmModal />
  <PwaInstallPrompt />
  <NotificationCenter />
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppShell from './components/layout/AppShell.vue'
import AppMessageModal from './components/ui/AppMessageModal.vue'
import ConfirmModal from './components/ui/ConfirmModal.vue'
import PwaInstallPrompt from './components/pwa/PwaInstallPrompt.vue'
import NotificationCenter from './components/notifications/NotificationCenter.vue'

const route = useRoute()
const isLoginLayout = computed(() => route.name === 'login')
</script>
