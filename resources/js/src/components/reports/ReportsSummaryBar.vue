<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  completionRate: { type: [Number, String], default: null },
  slaBreaches: { type: Number, default: 0 },
  avgCostDisplay: { type: String, default: '—' },
  totalTrips: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

function displayRate() {
  if (props.loading) return '…'
  if (props.completionRate != null && props.completionRate !== '') return `${props.completionRate}%`
  return '—'
}

function displayInt(n) {
  return props.loading ? '…' : new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

const cards = computed(() => [
  {
    key: 'completion',
    label: t('reports_page.kpi_on_time'),
    tone: 'emerald',
    icon: CheckCircleIcon,
    display: displayRate(),
    sub: t('reports_page.kpi_on_time_hint'),
  },
  {
    key: 'sla',
    label: t('reports_page.kpi_sla_breaches'),
    tone: 'amber',
    icon: ExclamationTriangleIcon,
    display: displayInt(props.slaBreaches),
    sub: t('reports_page.kpi_sla_breaches_hint'),
  },
  {
    key: 'avg_cost',
    label: t('reports_page.kpi_avg_cost'),
    tone: 'brand',
    icon: BanknotesIcon,
    display: props.loading ? '…' : props.avgCostDisplay,
    sub: t('reports_page.kpi_avg_cost_hint'),
  },
  {
    key: 'trips',
    label: t('reports_page.kpi_trips'),
    tone: 'sky',
    icon: QueueListIcon,
    display: displayInt(props.totalTrips),
    sub: t('reports_page.kpi_trips_hint'),
  },
])
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('reports_page.kpi_strip_aria')"
    :eyebrow="t('reports_page.kpi_stats_eyebrow')"
    :title="t('reports_page.kpi_strip_overview')"
    :hint="t('reports_page.kpi_strip_hint')"
  />
</template>
