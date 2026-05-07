<template>
  <section class="transition-opacity duration-300 ease-out">
    <div
      v-if="loading"
      class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5"
    >
      <div
        v-for="n in 5"
        :key="n"
        class="h-[92px] animate-pulse rounded-2xl bg-slate-200/90 dark:bg-slate-800/90"
      />
    </div>
    <div
      v-else
      class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5"
    >
      <article
        v-for="card in cards"
        :key="card.key"
        class="group relative overflow-hidden rounded-2xl border px-3.5 py-3 shadow-sm transition duration-200 ease-out hover:scale-[1.02] hover:shadow-md active:scale-[0.99] sm:px-4 sm:py-3.5"
        :class="card.surface"
      >
        <div class="flex items-start justify-between gap-2">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border transition-colors duration-200"
            :class="card.iconWrap"
            aria-hidden="true"
            v-html="card.iconSvg"
          />
        </div>
        <p class="mt-2.5 text-2xl font-bold tabular-nums tracking-tight text-slate-900 dark:text-white">
          {{ card.value }}
        </p>
        <p class="mt-0.5 text-[11px] font-medium leading-tight text-slate-600 dark:text-slate-400 sm:text-xs">
          {{ card.label }}
        </p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      completed: 0,
      inProgress: 0,
      pending: 0,
      cancelled: 0,
    }),
  },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const ICONS = {
  total: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>`,
  completed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>`,
  inProgress: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.75a.75.75 0 0 0-1.5 0v4.59l-1.95 2.1a.75.75 0 1 0 1.1 1.02l2.25-2.43a.75.75 0 0 0 .1-.38v-5Z" clip-rule="evenodd" /></svg>`,
  pending: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm1-6a1 1 0 1 0-2 0v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2h-1V8Z" clip-rule="evenodd" /></svg>`,
  cancelled: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>`,
}

const cards = computed(() => [
  {
    key: 'total',
    value: props.stats?.total ?? 0,
    label: t('driver_home.stats_total'),
    iconSvg: ICONS.total,
    surface:
      'border-slate-200/80 bg-slate-50/90 dark:border-slate-700/80 dark:bg-slate-900/40',
    iconWrap:
      'border-slate-200/80 bg-white text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300',
  },
  {
    key: 'completed',
    value: props.stats?.completed ?? 0,
    label: t('driver_home.stats_completed'),
    iconSvg: ICONS.completed,
    surface:
      'border-emerald-200/70 bg-emerald-50/80 dark:border-emerald-900/50 dark:bg-emerald-950/25',
    iconWrap:
      'border-emerald-200/80 bg-white text-emerald-600 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
  },
  {
    key: 'inProgress',
    value: props.stats?.inProgress ?? 0,
    label: t('driver_home.stats_in_progress'),
    iconSvg: ICONS.inProgress,
    surface:
      'border-amber-200/70 bg-amber-50/80 dark:border-amber-900/50 dark:bg-amber-950/25',
    iconWrap:
      'border-amber-200/80 bg-white text-amber-600 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  },
  {
    key: 'pending',
    value: props.stats?.pending ?? 0,
    label: t('driver_home.stats_pending_confirm'),
    iconSvg: ICONS.pending,
    surface:
      'border-sky-200/70 bg-sky-50/80 dark:border-sky-900/50 dark:bg-sky-950/25',
    iconWrap:
      'border-sky-200/80 bg-white text-sky-600 dark:border-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
  },
  {
    key: 'cancelled',
    value: props.stats?.cancelled ?? 0,
    label: t('driver_home.stats_cancelled'),
    iconSvg: ICONS.cancelled,
    surface:
      'border-rose-200/70 bg-rose-50/80 dark:border-rose-900/50 dark:bg-rose-950/25',
    iconWrap:
      'border-rose-200/80 bg-white text-rose-600 dark:border-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
  },
])
</script>
