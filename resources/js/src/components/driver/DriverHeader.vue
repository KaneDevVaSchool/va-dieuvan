<template>
  <header
    class="grid w-full gap-1.5 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-3 pt-2.5 sm:px-5 sm:pb-4 sm:pt-3"
    data-testid="driver-home-header"
  >
    <h1
      class="flex w-full min-w-0 items-baseline gap-1.5 whitespace-nowrap text-lg font-bold leading-snug tracking-tight text-white sm:text-xl"
    >
      <span class="shrink-0">{{ t('driver_home.hello') }}</span>
      <span class="min-w-0 truncate" :title="user?.name">{{ user?.name || '—' }}</span>
    </h1>
    <p class="flex items-center gap-1.5 whitespace-nowrap text-xs font-medium leading-snug text-[#7fdcc8] sm:text-[13px]">
      <CalendarDaysIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
      <span>{{ todayLabel }}</span>
    </p>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'

defineProps({
  user: { type: Object, default: null },
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
