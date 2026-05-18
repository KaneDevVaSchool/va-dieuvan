<template>
  <button
    type="button"
    class="relative flex h-10 w-10 items-center justify-center rounded-full border transition active:scale-95"
    :class="toneClass"
    :title="t('notify.bell_open')"
    @click="onBellClick"
  >
    <BellIcon class="h-5 w-5" aria-hidden="true" />
    <template v-if="notifStore.lastUnread > 0">
      <!-- Ping ring: only pulses when panel is closed (draws attention without distracting while reading) -->
      <span
        v-if="!notifStore.panelOpen"
        class="absolute -right-0.5 -top-0.5 h-4 w-4 animate-ping rounded-full bg-rose-400 opacity-60"
        aria-hidden="true"
      />
      <span
        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
      >
        {{ notifStore.lastUnread > 99 ? '99+' : notifStore.lastUnread }}
      </span>
    </template>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { BellIcon } from '@heroicons/vue/24/outline'
import { useNotificationStore } from '../../store/notificationCenter'

const props = defineProps({
  /**
   * 'brand' — nền thương hiệu tối (sidebar dọc)
   * 'light' — nền trắng (header ngang)
   */
  tone: {
    type: String,
    default: 'brand',
    validator: (v) => ['brand', 'light'].includes(v),
  },
})

const { t } = useI18n()
const notifStore = useNotificationStore()

const toneClass = computed(() =>
  props.tone === 'light'
    ? 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-700'
    : 'border-white/20 bg-white/10 text-white hover:bg-white/20',
)

function onBellClick() {
  if (notifStore.panelOpen) {
    notifStore.closePanel()
  } else {
    notifStore.openPanel()
  }
  void notifStore.refreshBadges()
}
</script>
