<template>
  <section class="rounded-3xl border border-[#86c2b5]/20 bg-[#142421] p-4 shadow-lg shadow-black/15 ring-1 ring-[#86c2b5]/10">
    <div v-if="loading" class="grid grid-cols-4 gap-3">
      <div v-for="n in 4" :key="n" class="flex flex-col items-center gap-2">
        <div class="h-14 w-14 animate-pulse rounded-full bg-[#86c2b5]/10" />
        <div class="h-4 w-8 animate-pulse rounded bg-[#86c2b5]/15" />
        <div class="h-3 w-10 animate-pulse rounded bg-[#86c2b5]/10" />
      </div>
    </div>

    <div v-else class="grid grid-cols-4 gap-2">
      <button
        v-for="card in cards"
        :key="card.key"
        type="button"
        class="flex min-h-[44px] flex-col items-center gap-1.5 rounded-xl py-1 transition-transform duration-150 active:scale-95"
      >
        <div
          class="flex h-14 w-14 min-h-[44px] min-w-[44px] items-center justify-center rounded-full"
          :class="card.circleCls"
          aria-hidden="true"
          v-html="card.iconSvg"
        />
        <span class="text-xl font-bold tabular-nums leading-none text-white">
          {{ card.value }}
        </span>
        <span class="px-0.5 text-center text-[10px] font-medium leading-tight text-slate-400">
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
  completed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>`,
  inProgress: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.75a.75.75 0 0 0-1.5 0v4.59l-1.95 2.1a.75.75 0 1 0 1.1 1.02l2.25-2.43a.75.75 0 0 0 .1-.38v-5Z" clip-rule="evenodd" /></svg>`,
  pending: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm1-6a1 1 0 1 0-2 0v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2h-1V8Z" clip-rule="evenodd" /></svg>`,
  cancelled: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>`,
}

const cards = computed(() => [
  {
    key: 'completed',
    value: props.stats?.completed ?? 0,
    label: t('driver_home.stats_completed'),
    iconSvg: ICONS.completed,
    circleCls: 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/35',
  },
  {
    key: 'inProgress',
    value: props.stats?.inProgress ?? 0,
    label: t('driver_home.stats_in_progress'),
    iconSvg: ICONS.inProgress,
    circleCls: 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/30',
  },
  {
    key: 'pending',
    value: props.stats?.pending ?? 0,
    label: t('driver_home.stats_pending_confirm'),
    iconSvg: ICONS.pending,
    circleCls: 'bg-[#86c2b5]/15 text-[#86c2b5] ring-1 ring-[#86c2b5]/35',
  },
  {
    key: 'cancelled',
    value: props.stats?.cancelled ?? 0,
    label: t('driver_home.stats_cancelled'),
    iconSvg: ICONS.cancelled,
    circleCls: 'bg-slate-700 text-slate-300 ring-1 ring-slate-600/80',
  },
])
</script>
