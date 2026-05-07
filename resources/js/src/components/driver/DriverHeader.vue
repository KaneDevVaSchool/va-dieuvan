<template>
  <div class="pb-4 pt-1">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex-1">
        <p class="text-sm text-slate-400">
          {{ t('driver_home.hello') }}
        </p>
        <h1 class="mt-0.5 truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">
          {{ user?.name || '—' }}
        </h1>
        <p class="mt-1 text-sm font-semibold text-[#7fdcc8]">
          {{ todayLabel }}
        </p>
      </div>

      <div class="mt-1 flex shrink-0 items-center gap-2">
        <NotificationBell />
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt=""
          class="h-11 min-h-[44px] w-11 min-w-[44px] rounded-full border-2 border-[#7fdcc8]/45 object-cover"
        />
        <div
          v-else
          class="flex h-11 min-h-[44px] w-11 min-w-[44px] items-center justify-center rounded-full border-2 border-[#7fdcc8]/45 bg-[#0f1816] text-base font-bold text-[#7fdcc8]"
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

defineProps({
  user: { type: Object, default: null },
  avatarUrl: { type: String, default: null },
  initials: { type: String, default: '?' },
})

const { locale, t } = useI18n()

const todayLabel = computed(() => {
  const d = new Date()
  return d.toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  })
})
</script>
