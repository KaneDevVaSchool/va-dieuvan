<template>
  <div
    v-if="visible"
    class="rounded-2xl border border-sky-500/35 bg-[#0a1a18]/95 px-4 py-3 shadow-[0_8px_30px_-12px_rgba(56,189,248,0.25)] ring-1 ring-sky-500/20"
  >
    <p class="text-sm font-bold leading-snug text-[#eaf8f5]">
      {{ t('driver_home.ios_push_title') }}
    </p>
    <p class="mt-1.5 text-xs leading-relaxed text-[#94a3b8]">
      {{ t('driver_home.ios_push_hint') }}
    </p>
    <p
      v-if="showAddToHomeHint"
      class="mt-2 rounded-xl bg-amber-950/50 px-3 py-2 text-xs leading-relaxed text-amber-100/95 ring-1 ring-amber-600/25"
    >
      {{ t('driver_home.ios_push_add_home') }}
    </p>

    <p
      v-if="permission === 'denied'"
      class="mt-3 text-xs leading-relaxed text-amber-200"
    >
      {{ t('driver_home.ios_push_denied') }}
    </p>

    <button
      v-else
      type="button"
      :disabled="busy"
      class="mt-3 flex min-h-[48px] w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 text-base font-bold text-white shadow-md transition active:bg-sky-700 disabled:opacity-60"
      @click="onEnable"
    >
      <span
        v-if="busy"
        class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
        aria-hidden="true"
      />
      <span>{{ t('driver_home.ios_push_btn') }}</span>
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '../../store/notificationCenter'
import { useDriverWebPushBoot } from '../../composables/useDriverWebPushBoot'
import { isLikelyIos, isStandaloneDisplayMode } from '../../util/platform'
import { showAppError, showAppSuccess } from '../../composables/appMessage'

const { t } = useI18n()
const notifStore = useNotificationStore()
const { enableDriverPushFromButton } = useDriverWebPushBoot()

const busy = ref(false)
const permission = ref(
  typeof Notification !== 'undefined' ? Notification.permission : 'denied',
)

function syncPermission() {
  permission.value = typeof Notification !== 'undefined' ? Notification.permission : 'denied'
}

const showAddToHomeHint = computed(() => isLikelyIos() && !isStandaloneDisplayMode())

const visible = computed(() => {
  if (!isLikelyIos() || typeof Notification === 'undefined') {
    return false
  }
  if (permission.value === 'denied') {
    return true
  }
  if (
    import.meta.env.PROD
    && permission.value === 'granted'
    && notifStore.pushState === 'subscribed'
  ) {
    return false
  }
  if (!import.meta.env.PROD && permission.value === 'granted') {
    return false
  }
  return true
})

function showPushResult(result) {
  if (result?.ok) {
    showAppSuccess(t('notify.push_ok'))
    return
  }
  if (result?.reason === 'denied') {
    showAppError(t('notify.push_err_denied'))
    return
  }
  if (result?.reason === 'network') {
    showAppError(t('notify.push_err_network'))
    return
  }
  if (result?.reason === 'unsupported') {
    showAppSuccess(t('driver_home.ios_push_dev_granted'))
    return
  }
  if (result && !result.ok && result.reason !== 'no_vapid') {
    showAppError(t('notify.push_err_api'))
  }
}

async function onEnable() {
  busy.value = true
  try {
    const result = await enableDriverPushFromButton()
    syncPermission()
    showPushResult(result)
  } finally {
    busy.value = false
    syncPermission()
  }
}

onMounted(() => {
  syncPermission()
})

watch(
  () => notifStore.pushState,
  () => {
    syncPermission()
  },
)
</script>
