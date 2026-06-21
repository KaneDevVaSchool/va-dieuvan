<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClockIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  activeStatus: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function displayCount(n) {
  if (props.loading) return '…'
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const activeCardKey = computed(() => {
  const map = {
    pending: 'pending',
    picked_up: 'in_transit',
    in_transit: 'in_transit',
    delivered: 'delivered',
    failed: 'issues',
    cancelled: 'issues',
  }
  return map[props.activeStatus] || (!props.activeStatus ? 'total' : '')
})

const cards = computed(() => {
  const s = props.stats
  const total = s.total ?? 0
  const inTransit = (s.in_transit ?? 0) + (s.picked_up ?? 0)
  const delivered = s.delivered ?? 0
  const pending = s.pending ?? 0
  const issues = (s.failed ?? 0) + (s.cancelled ?? 0)

  return [
    {
      key: 'total',
      label: t('cargo_page.kpi_total'),
      tone: 'brand',
      icon: CubeIcon,
      display: displayCount(total),
      sub: t('cargo_page.kpi_total_sub'),
      progress: null,
      filter: { kind: 'reset' },
    },
    {
      key: 'pending',
      label: t('cargo_page.kpi_pending'),
      tone: 'amber',
      icon: ClockIcon,
      display: displayCount(pending),
      sub: total ? t('cargo_page.kpi_share_of_total', { pct: sharePct(pending, total) }) : '',
      progress: sharePct(pending, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'pending' },
    },
    {
      key: 'in_transit',
      label: t('cargo_page.kpi_in_transit'),
      tone: 'sky',
      icon: TruckIcon,
      display: displayCount(inTransit),
      sub: total ? t('cargo_page.kpi_share_of_total', { pct: sharePct(inTransit, total) }) : '',
      progress: sharePct(inTransit, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'in_transit' },
    },
    {
      key: 'delivered',
      label: t('cargo_page.kpi_completed'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(delivered),
      sub: total ? t('cargo_page.kpi_share_of_total', { pct: sharePct(delivered, total) }) : '',
      progress: sharePct(delivered, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'delivered' },
    },
    {
      key: 'issues',
      label: t('cargo_page.kpi_issues'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: displayCount(issues),
      sub: issues > 0 ? t('cargo_page.kpi_action_required') : t('cargo_page.kpi_ops_ok'),
      progress: sharePct(issues, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'failed' },
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
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
    :aria-label="t('cargo_page.kpi_strip_aria')"
    :eyebrow="t('cargo_page.kpi_stats_eyebrow')"
    :title="t('cargo_page.kpi_strip_overview')"
    :hint="t('cargo_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
