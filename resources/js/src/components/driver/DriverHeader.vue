<template>
  <!-- Nền gradient + glow do khối hero cha; chỉ padding safe-area cho nội dung -->
  <div
    class="w-full pl-[max(0.75rem,env(safe-area-inset-left))] pr-[max(0.75rem,env(safe-area-inset-right))] pt-[env(safe-area-inset-top)] pb-3 sm:pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1rem,env(safe-area-inset-right))]"
  >
    <div class="flex items-start justify-between gap-3">
      <p class="pt-0.5 text-sm font-medium leading-tight text-slate-400">
        {{ t('driver_home.hello') }}
      </p>

      <div class="flex shrink-0 items-center gap-2.5">
        <NotificationBell />
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt=""
          class="h-14 min-h-[48px] w-14 min-w-[48px] rounded-full border-2 border-[#7fdcc8]/40 object-cover shadow-[0_0_20px_-4px_rgba(127,220,200,0.45)] ring-1 ring-[#7fdcc8]/25 sm:h-16 sm:w-16"
        />
        <div
          v-else
          class="flex h-14 min-h-[48px] w-14 min-w-[48px] items-center justify-center rounded-full border-2 border-[#7fdcc8]/40 bg-[#0a1a18]/90 text-lg font-bold text-[#7fdcc8] shadow-[0_0_18px_-4px_rgba(127,220,200,0.4)] ring-1 ring-[#7fdcc8]/20 sm:h-16 sm:w-16 sm:text-xl"
        >
          {{ initials }}
        </div>
      </div>
    </div>

    <h1
      class="mt-5 w-full break-words text-4xl font-bold leading-[1.15] tracking-tight text-white text-balance sm:text-[2.5rem]"
    >
      {{ user?.name || '—' }}
    </h1>
    <p class="mt-2.5 text-base font-semibold leading-snug text-[#7fdcc8]">
      {{ todayLabel }}
    </p>
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
