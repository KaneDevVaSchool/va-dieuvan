<template>
  <div class="grid grid-cols-1 gap-3 xs:grid-cols-2 lg:grid-cols-4 lg:gap-4">
    <template v-if="loading">
      <div v-for="i in 4" :key="i" class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="h-3.5 w-24 animate-pulse rounded bg-slate-200" />
        <div class="mt-3 h-9 w-16 animate-pulse rounded bg-slate-100" />
        <div class="mt-2 h-3.5 w-full animate-pulse rounded bg-slate-100" />
      </div>
    </template>
    <template v-else>
      <article
        v-for="card in cards"
        :key="card.key"
        class="rounded-2xl bg-gradient-to-br p-5 shadow-sm transition hover:shadow-md"
        :class="card.bgClass"
      >
        <div class="flex items-start justify-between gap-2">
          <p class="text-sm font-semibold text-slate-600">{{ card.title }}</p>
          <span class="flex rounded-xl p-2" :class="card.iconWrapClass">
            <component :is="card.icon" class="h-6 w-6 opacity-95" :class="card.iconClass" aria-hidden="true" />
          </span>
        </div>
        <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight sm:text-4xl" :class="card.valueClass">
          {{ card.value }}
        </p>
        <p v-if="card.sub" class="mt-1.5 text-sm leading-snug text-slate-500">{{ card.sub }}</p>
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
  /** @type {{ processing?: number, pending?: number, completed_this_month?: number, rejected?: number }} */
  summary: { type: Object, default: null },
})

const { t } = useI18n()

const cards = computed(() => {
  const s = props.summary || {}
  const n = (v) => (typeof v === 'number' && Number.isFinite(v) ? v : 0)
  return [
    {
      key: 'processing',
      title: t('portal.kpi_processing_title'),
      sub: t('portal.kpi_processing_sub'),
      value: n(s.processing),
      icon: TruckIcon,
      iconClass: 'text-sky-600',
      iconWrapClass: 'bg-sky-100 text-sky-600',
      bgClass: 'from-sky-50/90 to-white',
      valueClass: 'text-sky-700',
    },
    {
      key: 'pending',
      title: t('portal.kpi_pending_title'),
      sub: t('portal.kpi_pending_sub'),
      value: n(s.pending),
      icon: ClockIcon,
      iconClass: 'text-amber-600',
      iconWrapClass: 'bg-amber-100 text-amber-600',
      bgClass: 'from-amber-50/90 to-white',
      valueClass: 'text-amber-700',
    },
    {
      key: 'completed',
      title: t('portal.kpi_completed_title'),
      sub: t('portal.kpi_completed_sub'),
      value: n(s.completed_this_month),
      icon: CheckCircleIcon,
      iconClass: 'text-emerald-600',
      iconWrapClass: 'bg-emerald-100 text-emerald-600',
      bgClass: 'from-emerald-50/90 to-white',
      valueClass: 'text-emerald-700',
    },
    {
      key: 'rejected',
      title: t('portal.kpi_rejected_title'),
      sub: t('portal.kpi_rejected_sub'),
      value: n(s.rejected),
      icon: XCircleIcon,
      iconClass: 'text-rose-600',
      iconWrapClass: 'bg-rose-100 text-rose-600',
      bgClass: 'from-rose-50/90 to-white',
      valueClass: 'text-rose-700',
    },
  ]
})
</script>
