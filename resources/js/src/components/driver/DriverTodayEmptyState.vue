<template>
  <div
    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[#7fdcc8]/20 bg-[#0a1512]/80 px-6 py-12 text-center"
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
      class="h-9 w-9 text-[#64748b]"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h4.875c.621 0 1.125.504 1.125 1.125v0c0 .621.504 1.125 1.125 1.125H15a3 3 0 0 1 3 3v2.25M3 3l18 18"
      />
    </svg>
    <p class="mt-4 text-lg font-bold text-[#94a3b8]">
      {{ t('driver_home.empty_today_title') }}
    </p>
    <p class="mt-2 max-w-xs text-sm leading-relaxed text-[#64748b]">
      {{ t('driver_home.empty_today_hint') }}
    </p>
    <RouterLink
      to="/driver/trips"
      class="mt-5 text-base font-semibold text-[#7fdcc8] underline decoration-[#7fdcc8]/40 underline-offset-4 transition hover:text-[#a0ebe0]"
    >
      {{ t('driver_home.empty_week_schedule_link') }}
    </RouterLink>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
