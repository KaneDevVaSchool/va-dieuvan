<template>
  <div
    v-if="visible"
    class="rounded-2xl border border-[#7fdcc8]/25 bg-[#0a1a18]/90 px-4 py-3.5 ring-1 ring-[#7fdcc8]/10"
    role="region"
    :aria-label="t('driver_home.ios_push_title')"
  >
    <p class="text-sm font-bold text-[#7fdcc8]">
      {{ t('driver_home.ios_push_title') }}
    </p>
    <p class="mt-1 text-xs leading-relaxed text-[#eaf8f5]/70">
      {{ t('driver_home.ios_push_hint') }}
    </p>
    <p class="mt-1.5 text-[11px] leading-snug text-[#eaf8f5]/50">
      {{ t('driver_home.ios_push_add_home') }}
    </p>
    <button
      type="button"
      class="mt-3 flex min-h-[48px] w-full items-center justify-center rounded-xl bg-[#7fdcc8] text-sm font-bold text-[#020B0B] transition active:scale-[0.99] disabled:opacity-50"
      :disabled="busy"
      @click="onEnable"
    >
      {{ busy ? '…' : t('driver_home.ios_push_btn') }}
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '../../store/notificationCenter'
import { useDriverWebPushBoot } from '../../composables/useDriverWebPushBoot'

const { t } = useI18n()
const notifStore = useNotificationStore()
const { enableDriverPushFromButton } = useDriverWebPushBoot()

const busy = ref(false)
const perm = ref('default')

function syncPerm() {
  perm.value = typeof Notification !== 'undefined' ? Notification.permission : 'denied'
}

const isProd = import.meta.env.PROD

const visible = computed(() => {
  if (!isProd) return false
  if (perm.value === 'denied') return false
  if (perm.value === 'granted' && notifStore.pushState === 'subscribed') return false
  return true
})

async function onEnable() {
  busy.value = true
  try {
    await enableDriverPushFromButton()
    syncPerm()
  } finally {
    busy.value = false
  }
}

onMounted(() => {
  syncPerm()
})
</script>
