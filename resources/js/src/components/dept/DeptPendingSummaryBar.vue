<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  CubeIcon,
  ClipboardDocumentListIcon,
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
  if (!props.activeTripType) return 'queue'
  return ''
})

const cards = computed(() => {
  const s = props.stats
  const queue = s.pending_count ?? 0
  const today = s.pending_today ?? 0
  const cargo = s.cargo ?? 0
  const rate = s.approval_rate_30d

  return [
    {
      key: 'queue',
      label: t('dept.kpi_pending'),
      tone: 'brand',
      icon: ClipboardDocumentListIcon,
      display: displayCount(queue),
      sub: t('dept.pending_kpi_queue_sub'),
      progress: null,
      filter: { kind: 'trip_type', value: '' },
    },
    {
      key: 'today',
      label: t('dept.pending_kpi_today'),
      tone: 'sky',
      icon: ClockIcon,
      display: displayCount(today),
      sub: t('dept.kpi_pending_sub'),
      progress: null,
      filter: null,
    },
    {
      key: 'month',
      label: t('dept.kpi_approved_month'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(s.approved_this_month ?? 0),
      sub: t('dept.pending_kpi_goto_approved'),
      progress: null,
      filter: { kind: 'navigate', to: 'approved' },
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
      sub: queue ? t('dept.kpi_share_of_total', { pct: sharePct(cargo, queue) }) : '',
      progress: sharePct(cargo, queue),
      progressTotal: queue,
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
    :aria-label="t('dept.pending_kpi_strip_aria')"
    :eyebrow="t('dept.approved_kpi_eyebrow')"
    :title="t('dept.pending_kpi_strip_title')"
    :hint="t('dept.approved_kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
