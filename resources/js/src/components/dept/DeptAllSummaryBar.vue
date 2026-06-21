<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  RectangleStackIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  activeStatus: { type: String, default: '' },
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
  if (props.activeStatus === 'price_filled') return 'price_filled'
  if (props.activeStatus === 'approved') return 'approved'
  if (props.activeStatus === 'rejected') return 'rejected'
  if (!props.activeStatus && !props.activeTripType) return 'total'
  return ''
})

const cards = computed(() => {
  const s = props.stats
  const total = s.total ?? 0

  return [
    {
      key: 'total',
      label: t('dept.all_kpi_total'),
      tone: 'brand',
      icon: RectangleStackIcon,
      display: displayCount(total),
      sub: t('dept.all_kpi_total_sub'),
      progress: null,
      filter: { kind: 'reset' },
    },
    {
      key: 'price_filled',
      label: t('dept.all_kpi_price_filled'),
      tone: 'sky',
      icon: ClipboardDocumentListIcon,
      display: displayCount(s.price_filled ?? 0),
      sub: total ? t('dept.kpi_share_of_total', { pct: sharePct(s.price_filled ?? 0, total) }) : '',
      progress: sharePct(s.price_filled ?? 0, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'price_filled' },
    },
    {
      key: 'approved',
      label: t('dept.nav_approved'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(s.approved ?? 0),
      sub: total ? t('dept.kpi_share_of_total', { pct: sharePct(s.approved ?? 0, total) }) : '',
      progress: sharePct(s.approved ?? 0, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'approved' },
    },
    {
      key: 'rejected',
      label: t('dept.nav_rejected'),
      tone: 'rose',
      icon: XCircleIcon,
      display: displayCount(s.rejected ?? 0),
      sub: total ? t('dept.kpi_share_of_total', { pct: sharePct(s.rejected ?? 0, total) }) : '',
      progress: sharePct(s.rejected ?? 0, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'rejected' },
    },
    {
      key: 'cargo',
      label: t('dept.approved_kpi_cargo'),
      tone: 'violet',
      icon: CubeIcon,
      display: displayCount(s.cargo ?? 0),
      sub: total ? t('dept.kpi_share_of_total', { pct: sharePct(s.cargo ?? 0, total) }) : '',
      progress: sharePct(s.cargo ?? 0, total),
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
    :aria-label="t('dept.all_kpi_strip_aria')"
    :eyebrow="t('dept.approved_kpi_eyebrow')"
    :title="t('dept.all_kpi_strip_title')"
    :hint="t('dept.approved_kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
