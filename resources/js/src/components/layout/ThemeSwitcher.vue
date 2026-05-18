<template>
  <div>
    <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
      {{ t('app.theme') }}
    </p>
    <div class="flex rounded-md bg-slate-100/90 p-0.5 dark:bg-slate-800/90">
      <button
        v-for="opt in themeOptions"
        :key="opt.value"
        type="button"
        class="flex min-w-0 flex-1 items-center justify-center gap-1 rounded px-1 py-1 text-center text-[10px] font-semibold leading-none transition sm:py-1.5 sm:text-[11px]"
        :class="ui.theme === opt.value ? segmentActive : segmentIdle"
        :aria-pressed="ui.theme === opt.value"
        :title="t(opt.labelKey)"
        @click="ui.setTheme(opt.value)"
      >
        <component :is="opt.icon" class="h-3 w-3 shrink-0" aria-hidden="true" />
        <span>{{ t(opt.labelKey) }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { SunIcon, MoonIcon, ComputerDesktopIcon } from '@heroicons/vue/24/outline'
import { useUiStore } from '../../store/ui'

const { t } = useI18n()
const ui = useUiStore()

const themeOptions = [
  { value: 'auto', labelKey: 'app.theme_auto', icon: ComputerDesktopIcon },
  { value: 'light', labelKey: 'app.theme_light', icon: SunIcon },
  { value: 'dark', labelKey: 'app.theme_dark', icon: MoonIcon },
]

const segmentActive = 'bg-slate-800 text-white shadow-sm dark:bg-slate-600 dark:text-white'
const segmentIdle = 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'
</script>
