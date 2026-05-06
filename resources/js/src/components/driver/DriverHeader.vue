<template>
  <div
    class="-mx-3 rounded-b-2xl bg-gradient-to-br from-[#78001e] via-[#78001e] to-[#5a0016] px-4 pb-5 text-white shadow-lg sm:-mx-4 sm:px-5"
    :style="{ paddingTop: 'max(1rem, env(safe-area-inset-top))' }"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex-1">
        <p class="text-sm text-white/70">{{ t('driver_home.hello') }}</p>
        <h1 class="mt-0.5 truncate text-2xl font-bold tracking-tight sm:text-3xl">
          {{ user?.name || '—' }}
        </h1>
        <div class="mt-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold"
            :class="statusChipClass"
          >
            <span class="h-2 w-2 rounded-full" :class="statusDotClass" />
            {{ statusLabel }}
          </span>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <NotificationBell />
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt=""
          class="h-12 w-12 rounded-full border-2 border-white/20 object-cover"
        />
        <div
          v-else
          class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/20 bg-white/10 text-lg font-bold"
        >
          {{ initials }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import NotificationBell from '../notifications/NotificationBell.vue'

const props = defineProps({
  user: { type: Object, default: null },
  avatarUrl: { type: String, default: null },
  initials: { type: String, default: '?' },
  driverStatus: { type: String, default: 'idle' }, // 'idle' | 'running'
})

const { t } = useI18n()

const statusLabel = computed(() =>
  props.driverStatus === 'running'
    ? t('driver_home.status_running')
    : t('driver_home.status_idle'),
)

const statusChipClass = computed(() =>
  props.driverStatus === 'running'
    ? 'bg-amber-400/20 text-amber-300'
    : 'bg-emerald-400/20 text-emerald-300',
)

const statusDotClass = computed(() =>
  props.driverStatus === 'running'
    ? 'bg-amber-400 animate-pulse'
    : 'bg-emerald-400',
)
</script>
