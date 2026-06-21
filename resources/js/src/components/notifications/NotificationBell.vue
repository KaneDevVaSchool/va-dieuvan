<template>
  <button
    type="button"
    class="relative transition active:scale-95"
    :class="buttonClass"
    :aria-label="showLabel ? undefined : t('notify.bell_open')"
    data-testid="notification-bell"
    @click="onBellClick"
  >
    <template v-if="showLabel">
      <BellIcon class="h-5 w-5 shrink-0 text-current opacity-90" aria-hidden="true" />
      <span class="min-w-0 flex-1 truncate text-left text-sm font-medium">
        {{ t('notify.title') }}
      </span>
      <span
        v-if="notifStore.lastUnread > 0"
        :class="labeledBadgeClass"
      >
        {{ unreadLabel }}
      </span>
    </template>
    <template v-else>
      <BellIcon class="h-5 w-5" aria-hidden="true" />
      <template v-if="notifStore.lastUnread > 0">
        <!-- Ping ring: only pulses when panel is closed (draws attention without distracting while reading) -->
        <span
          v-if="!notifStore.panelOpen"
          class="absolute -right-0.5 -top-0.5 h-4 w-4 animate-ping rounded-full bg-rose-400 opacity-60"
          aria-hidden="true"
        />
        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white">
          {{ unreadLabel }}
        </span>
      </template>
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
  /** Hiển thị nhãn «Thông báo» cạnh chuông (sidebar mở rộng) */
  showLabel: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const notifStore = useNotificationStore()

const unreadLabel = computed(() =>
  notifStore.lastUnread > 99 ? '99+' : String(notifStore.lastUnread),
)

const buttonClass = computed(() => {
  if (props.showLabel) {
    if (props.tone === 'light') {
      return 'flex w-full min-w-0 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-3 text-sm text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:bg-slate-700'
    }
    return 'flex w-full min-w-0 items-center gap-3 rounded-lg py-2.5 pl-3 pr-3 text-sm text-white/90 hover:bg-white/10'
  }
  return props.tone === 'light'
    ? 'flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-700'
    : 'flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20'
})

const labeledBadgeClass = computed(() => {
  const base =
    'inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none'
  if (props.tone === 'brand') {
    return `${base} bg-amber-100 text-amber-900`
  }
  return `${base} bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-100`
})

function onBellClick() {
  if (notifStore.panelOpen) {
    notifStore.closePanel()
  } else {
    notifStore.openPanel()
  }
  void notifStore.refreshBadges()
}
</script>
