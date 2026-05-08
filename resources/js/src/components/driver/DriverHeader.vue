<template>
  <!-- Top inset do hero dashboard bọc (viewport có notch); chỉ spacing nội bộ -->
  <header
    class="w-full pl-[max(0.75rem,env(safe-area-inset-left))] pr-[max(0.75rem,env(safe-area-inset-right))] pb-3 pt-1.5 sm:pt-2 sm:pl-[max(1rem,env(safe-area-inset-left))] sm:pr-[max(1rem,env(safe-area-inset-right))]"
  >
    <div class="flex items-start justify-between gap-3">
      <div
        class="min-w-0 flex-1 space-y-1.5 px-3 py-2 sm:px-4 sm:py-2.5 sm:my-0"
      >
        <p
          class="flex items-center gap-2 text-base font-semibold leading-snug text-[#7fdcc8]"
        >
          <!-- morning -->
          <svg
            v-if="greetingPeriod === 'morning'"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-5 w-5 shrink-0 text-[#fbbf24]/90"
            aria-hidden="true"
          >
            <path d="M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-2.25a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.59 1.591a.75.75 0 0 0 1.06 1.06l1.591-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z" />
          </svg>
          <!-- afternoon -->
          <svg
            v-else-if="greetingPeriod === 'afternoon'"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-5 w-5 shrink-0 text-sky-300/90"
            aria-hidden="true"
          >
            <!-- Cùng glyph mặt trời, màu chiều (cloud-sun theo plan = sun + sky) -->
            <path d="M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-2.25a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.59 1.591a.75.75 0 0 0 1.06 1.06l1.591-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z" />
          </svg>
          <!-- evening -->
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="h-5 w-5 shrink-0 text-indigo-200/85"
            aria-hidden="true"
          >
            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 0 1 .162.819A8.25 8.25 0 0 0 9.75 21.75 8.25 8.25 0 0 0 19.872 8.826a.75.75 0 0 1 .818-.162A9.75 9.75 0 0 1 9.528 1.718Z" clip-rule="evenodd" />
            <path d="M12.832 3.445a.75.75 0 0 1 .818-.162 9.75 9.75 0 1 1-10.37 9.967.75.75 0 0 1 .54-.86A8.25 8.25 0 0 0 12.832 3.445Z" />
          </svg>
          <span class="break-words">{{ greetingByTime }}</span>
        </p>
        <p
          class="text-2xl font-bold leading-tight tracking-tight text-white sm:text-[1.75rem]"
        >
          {{ t('driver_home.hello') }}
        </p>
      </div>

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

const greetingPeriod = computed(() => {
  const h = new Date().getHours()
  if (h >= 5 && h < 12) return 'morning'
  if (h >= 12 && h < 18) return 'afternoon'
  return 'evening'
})

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
