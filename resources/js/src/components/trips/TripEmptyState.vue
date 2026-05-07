<template>
  <div
    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[rgba(255,255,255,0.1)] bg-[#0f2318]/50 px-6 py-12 text-center"
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="1.25"
      stroke="currentColor"
      class="mx-auto h-14 w-14 text-[#7fdcc8]/50"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.25 2.25 0 0 0-1.227-1.318l-4.003-2.02a1.125 1.125 0 0 0-.97 0l-4.003 2.02a2.246 2.246 0 0 0-.502.618A18.008 18.008 0 0 0 2.25 12.75V18c0 .897.42 1.648 1.08 2.25m12-4.5v-3.75a2.25 2.25 0 0 0-2.25-2.25H8.25A2.25 2.25 0 0 0 6 9.75v3.75"
      />
    </svg>
    <p class="mt-4 text-sm font-medium text-white">
      {{ t('trip_history_page.empty_title') }}
    </p>
    <p class="mt-2 max-w-xs text-xs text-[#94a3b8]">
      {{ t('trip_history_page.empty_subtitle') }}
    </p>
    <button
      type="button"
      class="mt-6 rounded-full bg-[#7fdcc8]/15 px-5 py-2.5 text-sm font-semibold text-[#7fdcc8] ring-1 ring-[#7fdcc8]/30 transition hover:bg-[#7fdcc8]/25"
      @click="$emit('reset')"
    >
      {{ t('trip_history_page.empty_btn_all') }}
    </button>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineEmits(['reset'])

const { t } = useI18n()
</script>
