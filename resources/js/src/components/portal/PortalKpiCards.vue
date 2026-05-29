<template>
  <div class="grid grid-cols-1 gap-3 xs:grid-cols-2 lg:grid-cols-4 lg:gap-4">
    <template v-if="loading">
      <div v-for="i in 4" :key="i" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="h-3 w-24 animate-pulse rounded bg-slate-200" />
        <div class="mt-3 h-8 w-16 animate-pulse rounded bg-slate-100" />
        <div class="mt-2 h-3 w-full animate-pulse rounded bg-slate-100" />
      </div>
    </template>
    <template v-else>
      <article
        v-for="card in cards"
        :key="card.key"
        class="rounded-2xl border bg-gradient-to-br p-4 shadow-sm ring-1 ring-inset ring-black/[0.03] transition hover:shadow-md"
        :class="[card.borderClass, card.bgClass]"
      >
        <div class="flex items-start justify-between gap-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ card.title }}</p>
          <div class="flex shrink-0 items-center gap-1.5">
            <span
              v-if="card.trend != null && card.trend !== 0"
              class="rounded-md px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
              :class="card.trend > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
            >
              {{ card.trend > 0 ? '+' : '' }}{{ card.trend }}
            </span>
            <span class="flex rounded-xl p-2" :class="card.iconWrapClass">
              <component :is="card.icon" class="h-6 w-6 opacity-95" :class="card.iconClass" aria-hidden="true" />
            </span>
          </div>
        </div>
        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight" :class="card.valueClass">{{ card.value }}</p>
        <p v-if="card.sub" class="mt-1 text-[11px] leading-snug text-slate-500">{{ card.sub }}</p>
      </article>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClockIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  loading: { type: Boolean, default: false },
  /** @type {{ processing?: number, pending?: number, completed_this_month?: number, rejected?: number, trends?: { completed_this_month?: number } }} */
  summary: { type: Object, default: null },
})

const { t } = useI18n()

const cards = computed(() => {
  const s = props.summary || {}
  const trends = s.trends || {}
  const n = (v) => (typeof v === 'number' && Number.isFinite(v) ? v : 0)
  return [
    {
      key: 'processing',
      title: t('portal.kpi_processing_title'),
      sub: t('portal.kpi_processing_sub'),
      value: n(s.processing),
      trend: null,
      icon: TruckIcon,
      iconClass: 'text-sky-600',
      iconWrapClass: 'bg-sky-100 text-sky-600',
      bgClass: 'from-sky-50/90 to-white',
      borderClass: 'border-sky-100',
      valueClass: 'text-sky-700',
    },
    {
      key: 'pending',
      title: t('portal.kpi_pending_title'),
      sub: t('portal.kpi_pending_sub'),
      value: n(s.pending),
      trend: null,
      icon: ClockIcon,
      iconClass: 'text-amber-600',
      iconWrapClass: 'bg-amber-100 text-amber-600',
      bgClass: 'from-amber-50/90 to-white',
      borderClass: 'border-amber-100',
      valueClass: 'text-amber-700',
    },
    {
      key: 'completed',
      title: t('portal.kpi_completed_title'),
      sub: t('portal.kpi_completed_sub'),
      value: n(s.completed_this_month),
      trend: typeof trends.completed_this_month === 'number' ? trends.completed_this_month : null,
      icon: CheckCircleIcon,
      iconClass: 'text-emerald-600',
      iconWrapClass: 'bg-emerald-100 text-emerald-600',
      bgClass: 'from-emerald-50/90 to-white',
      borderClass: 'border-emerald-100',
      valueClass: 'text-emerald-700',
    },
    {
      key: 'rejected',
      title: t('portal.kpi_rejected_title'),
      sub: t('portal.kpi_rejected_sub'),
      value: n(s.rejected),
      trend: null,
      icon: XCircleIcon,
      iconClass: 'text-rose-600',
      iconWrapClass: 'bg-rose-100 text-rose-600',
      bgClass: 'from-rose-50/90 to-white',
      borderClass: 'border-rose-100',
      valueClass: 'text-rose-700',
    },
  ]
})
</script>
