<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ClockIcon,
  TruckIcon,
  UserGroupIcon,
  UserMinusIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  transportStatus: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function formatInt(n) {
  if (props.loading) return '…'
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const activeCardKey = computed(() => {
  if (!props.transportStatus) return 'total'
  return props.transportStatus
})

const cards = computed(() => {
  const total = props.stats.total ?? 0
  const transporting = props.stats.transporting ?? 0
  const pending = props.stats.pending ?? 0
  const unregistered = props.stats.unregistered ?? 0

  return [
    {
      key: 'total',
      label: t('tp_student_page.kpi_total'),
      tone: 'brand',
      icon: UserGroupIcon,
      display: formatInt(total),
      sub: t('tp_student_page.kpi_total_sub'),
      filter: { transport_status: '' },
    },
    {
      key: 'transporting',
      label: t('tp_student_page.kpi_transporting'),
      tone: 'emerald',
      icon: TruckIcon,
      display: formatInt(transporting),
      sub: total ? t('tp_student_page.kpi_share', { pct: sharePct(transporting, total) }) : '',
      progress: sharePct(transporting, total),
      progressTotal: total || 1,
      filter: { transport_status: 'transporting' },
    },
    {
      key: 'pending',
      label: t('tp_student_page.kpi_pending'),
      tone: 'amber',
      icon: ClockIcon,
      display: formatInt(pending),
      sub: total ? t('tp_student_page.kpi_share', { pct: sharePct(pending, total) }) : '',
      progress: sharePct(pending, total),
      progressTotal: total || 1,
      filter: { transport_status: 'pending' },
    },
    {
      key: 'unregistered',
      label: t('tp_student_page.kpi_unregistered'),
      tone: 'slate',
      icon: UserMinusIcon,
      display: formatInt(unregistered),
      sub: total ? t('tp_student_page.kpi_share', { pct: sharePct(unregistered, total) }) : '',
      progress: sharePct(unregistered, total),
      progressTotal: total || 1,
      filter: { transport_status: 'unregistered' },
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
    :aria-label="t('tp_student_page.kpi_strip_aria')"
    :eyebrow="t('tp_student_page.kpi_stats_eyebrow')"
    :title="t('tp_student_page.kpi_strip_overview')"
    :hint="t('tp_student_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
