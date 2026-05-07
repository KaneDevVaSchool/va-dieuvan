<template>
  <section class="rounded-3xl bg-[#0f1816] px-3 py-4 shadow-xl shadow-black/25 sm:px-4">
    <div v-if="loading" class="grid grid-cols-4 gap-2">
      <div v-for="n in 4" :key="n" class="flex min-w-0 flex-col items-center gap-2">
        <div class="w-16 max-w-full animate-pulse rounded-full bg-[#7fdcc8]/10 aspect-square" />
        <div class="h-4 w-8 animate-pulse rounded bg-[#7fdcc8]/15" />
        <div class="h-3 w-10 animate-pulse rounded bg-[#7fdcc8]/10" />
      </div>
    </div>

    <div v-else class="grid grid-cols-4 gap-1 sm:gap-2">
      <button
        v-for="card in cards"
        :key="card.key"
        type="button"
        class="flex min-h-[48px] min-w-0 w-full flex-col items-center gap-1.5 rounded-xl py-2 transition-transform duration-150 active:scale-95"
      >
        <div
          class="flex w-16 max-w-full shrink-0 items-center justify-center rounded-full aspect-square [&_svg]:h-8 [&_svg]:w-8"
          :class="card.circleCls"
          aria-hidden="true"
          v-html="card.iconSvg"
        />
        <span class="text-[clamp(1.25rem,5.5vw,1.75rem)] font-bold tabular-nums leading-none text-white">
          {{ card.value }}
        </span>
        <span class="w-full px-0.5 text-center text-[13px] font-medium leading-tight text-slate-400">
          {{ card.label }}
        </span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ completed: 0, inProgress: 0, pending: 0, cancelled: 0 }),
  },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const ICONS = {
  completed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>`,
  inProgress: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.75a.75.75 0 0 0-1.5 0v4.59l-1.95 2.1a.75.75 0 1 0 1.1 1.02l2.25-2.43a.75.75 0 0 0 .1-.38v-5Z" clip-rule="evenodd" /></svg>`,
  pending: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm1-6a1 1 0 1 0-2 0v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2h-1V8Z" clip-rule="evenodd" /></svg>`,
  cancelled: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>`,
}

const cards = computed(() => [
  {
    key: 'completed',
    value: props.stats?.completed ?? 0,
    label: t('driver_home.stats_completed'),
    iconSvg: ICONS.completed,
    circleCls: 'bg-emerald-500/15 text-emerald-300',
  },
  {
    key: 'inProgress',
    value: props.stats?.inProgress ?? 0,
    label: t('driver_home.stats_in_progress'),
    iconSvg: ICONS.inProgress,
    circleCls: 'bg-amber-500/15 text-amber-300',
  },
  {
    key: 'pending',
    value: props.stats?.pending ?? 0,
    label: t('driver_home.stats_pending_confirm'),
    iconSvg: ICONS.pending,
    circleCls: 'bg-[#7fdcc8]/15 text-[#7fdcc8]',
  },
  {
    key: 'cancelled',
    value: props.stats?.cancelled ?? 0,
    label: t('driver_home.stats_cancelled'),
    iconSvg: ICONS.cancelled,
    circleCls: 'bg-slate-700 text-slate-300',
  },
])
</script>
