<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ClockIcon,
  ExclamationTriangleIcon,
  RectangleStackIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  activeTab: { type: String, default: 'all' },
  slaRiskOnly: { type: Boolean, default: false },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function trendLabel(pct) {
  if (pct > 0) return t('requests_page.trend_up', { pct })
  if (pct < 0) return t('requests_page.trend_down', { pct: Math.abs(pct) })
  return t('requests_page.trend_flat')
}

const approvalPendingCount = computed(() => Number(props.stats.by_status?.pending ?? 0))
const approvalApprovedCount = computed(() => Number(props.stats.by_status?.approved ?? 0))

const pendingShareOfTotalPct = computed(() => {
  const total = props.stats.total || 0
  const p = approvalPendingCount.value
  if (total <= 0) return 0
  return Math.min(100, Math.round((p / total) * 100))
})

const approvedShareOfTotalPct = computed(() => {
  const total = props.stats.total || 0
  const a = approvalApprovedCount.value
  if (total <= 0) return 0
  return Math.min(100, Math.round((a / total) * 100))
})

const activeCardKey = computed(() => {
  if (props.slaRiskOnly) return 'sla_risk'
  if (props.activeTab === 'pending') return 'pending'
  if (props.activeTab === 'approved') return 'approved'
  if (props.activeTab === 'all') return 'total'
  return ''
})

const cards = computed(() => {
  const total = props.stats.total ?? 0
  const trend = props.stats.month_trend_pct
  return [
    {
      key: 'total',
      label: t('requests_page.kpi_total'),
      tone: 'brand',
      icon: RectangleStackIcon,
      display: formatInt(total),
      sub:
        trend != null ? trendLabel(trend) : t('requests_page.kpi_no_trend'),
      progress: null,
      filter: { tab: 'all', sla: false },
    },
    {
      key: 'pending',
      label: t('requests_page.tab_pending'),
      tone: 'sky',
      icon: ClockIcon,
      display: formatInt(approvalPendingCount.value),
      sub: total ? `${pendingShareOfTotalPct.value}% tổng phiếu` : '',
      progress: pendingShareOfTotalPct.value,
      progressTotal: total,
      filter: { tab: 'pending', sla: false },
    },
    {
      key: 'approved',
      label: t('requests_page.tab_approved'),
      tone: 'emerald',
      icon: ClipboardDocumentCheckIcon,
      display: formatInt(approvalApprovedCount.value),
      sub: total ? `${approvedShareOfTotalPct.value}% tổng phiếu` : '',
      progress: approvedShareOfTotalPct.value,
      progressTotal: total,
      filter: { tab: 'approved', sla: false },
    },
    {
      key: 'sla_risk',
      label: t('requests_page.kpi_sla_risk'),
      tone: 'amber',
      icon: ExclamationTriangleIcon,
      display: formatInt(props.stats.sla_risk),
      sub:
        (props.stats.sla_risk ?? 0) > 0
          ? t('requests_page.kpi_action_required')
          : t('requests_page.kpi_sla_ok'),
      progress: null,
      filter: { tab: 'all', sla: true },
    },
  ]
})

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('requests_page.kpi_strip_aria')"
    :eyebrow="t('requests_page.kpi_stats_eyebrow')"
    :title="t('requests_page.kpi_strip_overview')"
    :hint="t('requests_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
