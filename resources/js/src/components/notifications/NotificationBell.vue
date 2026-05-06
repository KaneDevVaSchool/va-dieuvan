<template>
  <button
    type="button"
    class="relative flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20 active:scale-95"
    :title="t('notify.bell_open')"
    @click="onBellClick"
  >
    <BellIcon class="h-5 w-5" aria-hidden="true" />
    <span
      v-if="notifStore.lastUnread > 0"
      class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
    >
      {{ notifStore.lastUnread > 99 ? '99+' : notifStore.lastUnread }}
    </span>
  </button>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { BellIcon } from '@heroicons/vue/24/outline'
import { useNotificationStore } from '../../store/notificationCenter'

const { t } = useI18n()
const notifStore = useNotificationStore()

function onBellClick() {
  if (notifStore.panelOpen) {
    notifStore.closePanel()
  } else {
    notifStore.openPanel()
  }
  void notifStore.refreshBadges()
}

onMounted(() => {
  void notifStore.refreshBadges()
})
</script>
