<template>
  <!-- Top inset do hero dashboard bọc (viewport có notch); chỉ spacing nội bộ -->
  <header
    class="grid w-full gap-2 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-4 pt-2 sm:px-5 sm:pb-5 sm:pt-3"
    data-testid="driver-home-header"
  >
    <div class="flex items-center justify-between gap-3">
      <div
        class="min-w-0 flex-1"
      >
        <p
          class="flex items-center gap-2 text-sm font-semibold leading-snug text-[#7fdcc8] sm:text-base"
        >
          <component :is="greetingIcon" class="h-5 w-5 shrink-0" :class="greetingPeriod === 'evening' ? 'text-indigo-200' : 'text-amber-300'" aria-hidden="true" />
          <span class="truncate" :title="greetingByTime">{{ greetingByTime }}</span>
        </p>
      </div>

      <div class="flex shrink-0 items-center sm:gap-1">
        <NotificationBell compact />
        <RouterLink
          to="/driver/account"
          class="flex h-11 w-11 items-center justify-center rounded-full p-1 ring-2 ring-transparent transition hover:ring-[#7fdcc8]/35 active:scale-[0.97]"
          :title="t('nav.bottom_driver_account')"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt=""
            class="h-9 w-9 rounded-full border border-[#7fdcc8]/40 object-cover"
          />
          <div
            v-else
            class="flex h-9 w-9 items-center justify-center rounded-full border border-[#7fdcc8]/40 bg-[#0a1a18]/90 text-sm font-bold text-[#7fdcc8]"
          >
            {{ initials }}
          </div>
        </RouterLink>
      </div>
    </div>

    <h1
      class="flex w-full min-w-0 items-baseline gap-1.5 whitespace-nowrap text-xl font-bold leading-snug tracking-tight text-white sm:text-2xl"
    >
      <span class="shrink-0">{{ t('driver_home.hello') }}</span>
      <span class="min-w-0 truncate" :title="user?.name">{{ user?.name || '—' }}</span>
    </h1>
    <p class="flex items-center gap-2 whitespace-nowrap text-[13px] font-medium leading-snug text-[#7fdcc8] sm:text-sm">
      <CalendarDaysIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
      <span>{{ todayLabel }}</span>
    </p>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { CalendarDaysIcon, SunIcon, CloudIcon, MoonIcon } from '@heroicons/vue/24/outline'
import NotificationBell from '../notifications/NotificationBell.vue'

defineProps({
  user: { type: Object, default: null },
  avatarUrl: { type: String, default: null },
  initials: { type: String, default: '?' },
})

const { locale, t } = useI18n()

const greetingPeriod = computed(() => {
  const h = new Date().getHours()
  if (h >= 5 && h < 12) return 'morning'
  if (h >= 12 && h < 18) return 'afternoon'
  return 'evening'
})

const greetingIcon = computed(() => ({ morning: SunIcon, afternoon: CloudIcon, evening: MoonIcon })[greetingPeriod.value])

const greetingByTime = computed(() => {
  const p = greetingPeriod.value
  if (p === 'morning') return t('driver_home.good_morning')
  if (p === 'afternoon') return t('driver_home.good_afternoon')
  return t('driver_home.good_evening')
})

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
