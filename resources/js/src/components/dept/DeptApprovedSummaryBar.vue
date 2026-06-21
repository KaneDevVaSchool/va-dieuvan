<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  CubeIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  activeTripType: { type: String, default: '' },
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
  if (props.activeTripType === 'cargo') return 'cargo'
  if (!props.activeTripType) return 'total'
  return ''
})

const cards = computed(() => {
  const s = props.stats
  const total = s.total ?? 0
  const cargo = s.cargo ?? 0
  const month = s.approved_this_month ?? 0
  const pending = s.pending_count ?? 0
  const rate = s.approval_rate_30d

  return [
    {
      key: 'total',
      label: t('dept.approved_kpi_total'),
      tone: 'brand',
      icon: ClipboardDocumentCheckIcon,
      display: displayCount(total),
      sub: t('dept.approved_kpi_total_sub'),
      progress: null,
      filter: { kind: 'trip_type', value: '' },
    },
    {
      key: 'month',
      label: t('dept.kpi_approved_month'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(month),
      sub: t('dept.kpi_approved_sub'),
      progress: null,
      filter: { kind: 'month' },
    },
    {
      key: 'pending',
      label: t('dept.kpi_pending'),
      tone: 'sky',
      icon: ClockIcon,
      display: displayCount(pending),
      sub: t('dept.approved_kpi_pending_sub'),
      progress: null,
      filter: { kind: 'navigate', to: 'pending' },
    },
    {
      key: 'rate',
      label: t('dept.kpi_rate'),
      tone: 'amber',
      icon: ChartBarIcon,
      display: rate != null ? `${rate}%` : '—',
      sub: t('dept.kpi_rate_sub'),
      progress: null,
      filter: null,
    },
    {
      key: 'cargo',
      label: t('dept.approved_kpi_cargo'),
      tone: 'violet',
      icon: CubeIcon,
      display: displayCount(cargo),
      sub: total ? t('dept.approved_kpi_share', { pct: sharePct(cargo, total) }) : '',
      progress: sharePct(cargo, total),
      progressTotal: total,
      filter: { kind: 'trip_type', value: 'cargo' },
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
    :aria-label="t('dept.approved_kpi_strip_aria')"
    :eyebrow="t('dept.approved_kpi_eyebrow')"
    :title="t('dept.approved_kpi_strip_title')"
    :hint="t('dept.approved_kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
