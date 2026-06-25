<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BoltIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  /** '' | 'overdue' | 'urgent' | 'today' */
  activeQueueFilter: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t, locale } = useI18n()

function displayCount(n) {
  if (props.loading) return '…'
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Intl.NumberFormat(loc).format(n ?? 0)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const activeCardKey = computed(() => {
  const f = props.activeQueueFilter
  if (f === 'overdue' || f === 'urgent' || f === 'today') return f
  return 'queue'
})

const cards = computed(() => {
  const s = props.stats
  const queue = s.pending_count ?? 0
  const overdue = s.overdue_count ?? 0

  return [
    {
      key: 'queue',
      label: t('dept.kpi_pending'),
      tone: 'brand',
      icon: ClipboardDocumentListIcon,
      display: displayCount(queue),
      sub: t('dept.inbox_kpi_queue_sub'),
      progress: queue > 0 ? sharePct(overdue, queue) : null,
      progressTotal: queue,
      filter: { kind: 'kpi', value: '' },
    },
    {
      key: 'overdue',
      label: t('dept.inbox_kpi_overdue'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: displayCount(overdue),
      sub: t('dept.inbox_kpi_overdue_sub'),
      progress: null,
      filter: { kind: 'kpi', value: 'overdue' },
    },
    {
      key: 'urgent',
      label: t('dept.inbox_kpi_urgent'),
      tone: 'amber',
      icon: BoltIcon,
      display: displayCount(s.urgent_pending_count),
      sub: t('dept.inbox_kpi_urgent_sub'),
      progress: null,
      filter: { kind: 'kpi', value: 'urgent' },
    },
    {
      key: 'today',
      label: t('dept.pending_kpi_today'),
      tone: 'sky',
      icon: ClockIcon,
      display: displayCount(s.pending_today),
      sub: t('dept.kpi_pending_sub'),
      progress: null,
      filter: { kind: 'kpi', value: 'today' },
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
    :aria-label="t('dept.pending_kpi_strip_aria')"
    :eyebrow="t('dept.approved_kpi_eyebrow')"
    :title="t('dept.inbox_kpi_title')"
    :hint="t('dept.approved_kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
