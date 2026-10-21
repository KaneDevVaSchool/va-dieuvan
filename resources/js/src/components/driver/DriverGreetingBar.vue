<template>
  <div
    class="border-b border-[#7fdcc8]/20 bg-[#020B0B] pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-1.5 pt-[max(0.375rem,env(safe-area-inset-top))] sm:px-5 sm:pb-2"
    data-testid="driver-home-greeting-bar"
  >
    <div class="flex items-center justify-between gap-3">
      <div
        class="min-w-0 flex-1"
      >
        <p
          class="flex items-center gap-1.5 text-xs font-semibold leading-snug text-[#7fdcc8] sm:text-sm"
        >
          <component :is="greetingIcon" class="h-4 w-4 shrink-0" :class="greetingPeriod === 'evening' ? 'text-indigo-200' : 'text-amber-300'" aria-hidden="true" />
          <span class="truncate" :title="greetingByTime">{{ greetingByTime }}</span>
        </p>
      </div>

      <div class="flex shrink-0 items-center sm:gap-1">
        <NotificationBell compact />
        <RouterLink
          to="/driver/account"
          class="flex h-9 w-9 items-center justify-center rounded-full p-0.5 ring-2 ring-transparent transition hover:ring-[#7fdcc8]/35 active:scale-[0.97]"
          :title="t('nav.bottom_driver_account')"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt=""
            class="h-7.5 w-7.5 rounded-full border border-[#7fdcc8]/40 object-cover"
          />
          <div
            v-else
            class="flex h-7.5 w-7.5 items-center justify-center rounded-full border border-[#7fdcc8]/40 bg-[#0a1a18]/90 text-xs font-bold text-[#7fdcc8]"
          >
            {{ initials }}
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { SunIcon, CloudIcon, MoonIcon } from '@heroicons/vue/24/outline'
import NotificationBell from '../notifications/NotificationBell.vue'

defineProps({
  avatarUrl: { type: String, default: null },
  initials: { type: String, default: '?' },
})

const { t } = useI18n()

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

</script>
