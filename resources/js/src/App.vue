<template>
  <AppShell v-if="!isLoginLayout">
    <RouterView />
  </AppShell>
  <RouterView v-else />
  <AppMessageModal />
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppShell from './components/layout/AppShell.vue'
import AppMessageModal from './components/ui/AppMessageModal.vue'

const route = useRoute()
const isLoginLayout = computed(() => route.name === 'login')
</script>
