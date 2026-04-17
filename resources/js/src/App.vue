<template>
  <AppShell v-if="!isLoginLayout">
    <RouterView />
  </AppShell>
  <RouterView v-else />
  <AppMessageModal />
  <ConfirmModal />
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppShell from './components/layout/AppShell.vue'
import AppMessageModal from './components/ui/AppMessageModal.vue'
import ConfirmModal from './components/ui/ConfirmModal.vue'

const route = useRoute()
const isLoginLayout = computed(() => route.name === 'login')
</script>
