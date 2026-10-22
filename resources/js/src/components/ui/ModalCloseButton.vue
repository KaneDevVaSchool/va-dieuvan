<template>
  <button
    type="button"
    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 disabled:pointer-events-none disabled:opacity-50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
    :aria-label="t('app.close')"
    :title="t('app.close')"
  >
    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
  </button>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
