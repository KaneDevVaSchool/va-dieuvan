<template>
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
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
        class="rounded-2xl border bg-white p-4 shadow-sm ring-1 ring-inset ring-black/[0.03]"
        :class="card.borderClass"
      >
        <div class="flex items-start justify-between gap-2">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ card.title }}</p>
          <component :is="card.icon" class="h-6 w-6 shrink-0 opacity-90" :class="card.iconClass" aria-hidden="true" />
        </div>
        <p class="mt-2 font-mono text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        <p class="mt-1 text-xs leading-snug text-slate-500">{{ card.subtitle }}</p>
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
      value: n(s.processing),
      subtitle: t('portal.kpi_processing_sub'),
      icon: TruckIcon,
      iconClass: 'text-sky-600',
      borderClass: 'border-sky-100',
    },
    {
      key: 'pending',
      title: t('portal.kpi_pending_title'),
      value: n(s.pending),
      subtitle: t('portal.kpi_pending_sub'),
      icon: ClockIcon,
      iconClass: 'text-amber-600',
      borderClass: 'border-amber-100',
    },
    {
      key: 'completed',
      title: t('portal.kpi_completed_title'),
      value: n(s.completed_this_month),
      subtitle: t('portal.kpi_completed_sub'),
      icon: CheckCircleIcon,
      iconClass: 'text-emerald-600',
      borderClass: 'border-emerald-100',
    },
    {
      key: 'rejected',
      title: t('portal.kpi_rejected_title'),
      value: n(s.rejected),
      subtitle: t('portal.kpi_rejected_sub'),
      icon: XCircleIcon,
      iconClass: 'text-rose-600',
      borderClass: 'border-rose-100',
    },
  ]
})
</script>
