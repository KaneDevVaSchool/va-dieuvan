<template>
  <header
    class="w-full bg-[#070f0d] pt-[env(safe-area-inset-top)] pb-5 pl-[max(0.75rem,env(safe-area-inset-left))] pr-[max(0.75rem,env(safe-area-inset-right))] sm:pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1rem,env(safe-area-inset-right))]"
  >
    <div class="flex items-start justify-between gap-3">
      <p class="text-base text-slate-400">
        {{ t('driver_home.hello') }}
      </p>

      <div class="flex shrink-0 items-center gap-2">
        <NotificationBell />
        <img
          v-if="avatarUrl"
          :src="avatarUrl"
          alt=""
          class="h-16 min-h-[48px] w-16 min-w-[48px] rounded-full border-2 border-[#7fdcc8]/45 object-cover"
        />
        <div
          v-else
          class="flex h-16 min-h-[48px] w-16 min-w-[48px] items-center justify-center rounded-full border-2 border-[#7fdcc8]/45 bg-[#0f1816] text-xl font-bold text-[#7fdcc8]"
        >
          {{ initials }}
        </div>
      </div>
    </div>

    <h1 class="mt-3 w-full break-words text-4xl font-bold leading-snug tracking-tight text-white text-balance">
      {{ user?.name || '—' }}
    </h1>
    <p class="mt-2 text-base font-semibold text-[#7fdcc8]">
      {{ todayLabel }}
    </p>
  </header>
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
