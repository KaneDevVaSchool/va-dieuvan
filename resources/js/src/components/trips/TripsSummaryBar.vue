<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BoltIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
  QueueListIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  tripType: { type: String, default: '' },
  runBucket: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function displayCount(n) {
  return props.loading ? '…' : formatInt(n)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const activeCardKey = computed(() => {
  if (props.runBucket === 'awaiting_dispatch') return 'awaiting'
  if (props.runBucket === 'in_progress') return 'running'
  if (props.runBucket === 'completed') return 'done'
  if (props.runBucket === 'incident') return 'issue'
  if (props.tripType === 'door_to_door') return 'd2d'
  if (props.tripType === 'point_to_point') return 'p2p'
  if (props.tripType === 'business') return 'biz'
  if (!props.tripType && !props.runBucket) return 'total'
  return ''
})

const cards = computed(() => {
  const total = props.stats.total ?? 0
  const bt = props.stats.by_trip_type ?? {}
  const br = props.stats.by_run ?? {}
  const d2d = bt.door_to_door ?? 0
  const p2p = bt.point_to_point ?? 0
  const biz = bt.business ?? 0
  const awaiting = br.awaiting_dispatch ?? 0
  const running = br.in_progress ?? 0
  const done = br.completed ?? 0
  const issue = br.incident ?? props.stats.incident ?? 0

  return [
    {
      key: 'total',
      label: t('trips_page.kpi_total'),
      tone: 'brand',
      icon: QueueListIcon,
      display: displayCount(total),
      sub: t('trips_page.kpi_total_sub'),
      progress: null,
      filter: { kind: 'reset' },
    },
    {
      key: 'd2d',
      label: t('trips_page.tab_d2d'),
      tone: 'sky',
      icon: TruckIcon,
      display: displayCount(d2d),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(d2d, total) }) : '',
      progress: sharePct(d2d, total),
      progressTotal: total,
      filter: { kind: 'trip_type', value: 'door_to_door' },
    },
    {
      key: 'p2p',
      label: t('trips_page.tab_p2p'),
      tone: 'emerald',
      icon: MapPinIcon,
      display: displayCount(p2p),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(p2p, total) }) : '',
      progress: sharePct(p2p, total),
      progressTotal: total,
      filter: { kind: 'trip_type', value: 'point_to_point' },
    },
    {
      key: 'biz',
      label: t('trips_page.tab_business'),
      tone: 'amber',
      icon: BriefcaseIcon,
      display: displayCount(biz),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(biz, total) }) : '',
      progress: sharePct(biz, total),
      progressTotal: total,
      filter: { kind: 'trip_type', value: 'business' },
    },
    {
      key: 'awaiting',
      label: t('trips_page.kpi_awaiting_dispatch'),
      tone: 'violet',
      icon: ClockIcon,
      display: displayCount(awaiting),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(awaiting, total) }) : '',
      progress: sharePct(awaiting, total),
      progressTotal: total,
      filter: { kind: 'run', value: 'awaiting_dispatch' },
    },
    {
      key: 'running',
      label: t('trips_page.kpi_running'),
      tone: 'brand',
      icon: BoltIcon,
      display: displayCount(running),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(running, total) }) : '',
      progress: sharePct(running, total),
      progressTotal: total,
      filter: { kind: 'run', value: 'in_progress' },
    },
    {
      key: 'done',
      label: t('trips_page.kpi_completed'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(done),
      sub: total ? t('trips_page.kpi_share_of_total', { pct: sharePct(done, total) }) : '',
      progress: sharePct(done, total),
      progressTotal: total,
      filter: { kind: 'run', value: 'completed' },
    },
    {
      key: 'issue',
      label: t('trips_page.kpi_issues'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: displayCount(issue),
      sub:
        issue > 0 ? t('trips_page.kpi_action_required') : t('trips_page.kpi_ops_ok'),
      progress: sharePct(issue, total),
      progressTotal: total,
      filter: { kind: 'run', value: 'incident' },
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
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4"
    :aria-label="t('trips_page.kpi_strip_aria')"
    :eyebrow="t('trips_page.kpi_stats_eyebrow')"
    :title="t('trips_page.kpi_strip_overview')"
    :hint="t('trips_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
