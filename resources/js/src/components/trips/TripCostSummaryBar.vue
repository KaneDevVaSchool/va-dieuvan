<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CalculatorIcon,
  ChartBarIcon,
  ClockIcon,
  ScaleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps<{
  revenue: number
  budget: number
  actualTotal: number
  variance: number
  variancePct: number | null
  pendingCount: number
  pendingTotal: number
  budgetUsedPct: number | null
  currency: string
  activeStatusFilter: string
}>()

const emit = defineEmits<{ 'quick-filter': [payload: { status: string }] }>()

const { t, locale } = useI18n()

function fmtMoney(v: number) {
  if (!Number.isFinite(v)) return '—'
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(v)} ${props.currency}`
}

function fmtMoneySigned(v: number) {
  const sign = v > 0 ? '+' : v < 0 ? '−' : ''
  return `${sign}${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(Math.abs(v))} ${props.currency}`
}

const activeCardKey = computed(() => {
  if (props.activeStatusFilter === '__pending__') return 'pending'
  return ''
})

const cards = computed(() => {
  const variance = props.variance
  const vPct = props.variancePct
  const list = [
    {
      key: 'revenue',
      label: t('cost_center.kpi_revenue'),
      tone: 'brand' as const,
      icon: BanknotesIcon,
      display: fmtMoney(props.revenue),
    },
    {
      key: 'budget',
      label: t('cost_center.kpi_budget'),
      tone: 'slate' as const,
      icon: CalculatorIcon,
      display: fmtMoney(props.budget),
      sub:
        props.budgetUsedPct != null && props.budget > 0
          ? t('cost_center.budget_used') + `: ${Math.round(props.budgetUsedPct)}%`
          : undefined,
      progress: props.budget > 0 && props.budgetUsedPct != null ? Math.min(100, Math.round(props.budgetUsedPct)) : undefined,
      progressTotal: props.budget > 0 ? 100 : 0,
    },
    {
      key: 'actual',
      label: t('cost_center.kpi_actual'),
      tone: 'sky' as const,
      icon: ChartBarIcon,
      display: fmtMoney(props.actualTotal),
    },
    {
      key: 'variance',
      label: t('cost_center.kpi_variance'),
      tone: variance > 0 ? ('rose' as const) : variance < 0 ? ('emerald' as const) : ('slate' as const),
      icon: ScaleIcon,
      display: props.budget > 0 ? fmtMoneySigned(variance) : '—',
      sub:
        vPct == null
          ? undefined
          : variance > 0
            ? t('cost_center.over_pct', { pct: Math.abs(Math.round(vPct)) })
            : t('cost_center.save_pct', { pct: Math.abs(Math.round(vPct)) }),
    },
    {
      key: 'pending',
      label: t('cost_center.kpi_pending'),
      tone: 'amber' as const,
      icon: ClockIcon,
      display: t('cost_center.pending_count', { n: props.pendingCount }),
      sub: props.pendingCount ? fmtMoney(props.pendingTotal) : undefined,
      filter: { status: '__pending__' },
    },
  ]
  return list
})

function onCardAction(card: { filter?: { status: string } }) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    class="!mb-4"
    compact
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
    :aria-label="t('cost_center.kpi_strip_aria')"
    :eyebrow="t('cost_center.kpi_stats_eyebrow')"
    :title="t('cost_center.kpi_strip_title')"
    :hint="t('cost_center.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
