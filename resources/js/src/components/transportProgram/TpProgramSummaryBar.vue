<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BoltIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  RectangleStackIcon,
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
  if (!props.activeStatus) return 'total'
  const map = {
    active: 'active',
    completed: 'completed',
  }
  return map[props.activeStatus] ?? ''
})

const cards = computed(() => {
  const total = props.stats.total ?? 0
  const by = props.stats.by_status ?? {}
  const active = by.active ?? 0
  const completed = by.completed ?? 0
  const operatingDays = props.stats.operating_days ?? 0

  return [
    {
      key: 'total',
      label: t('tp_programs_page.kpi_total'),
      tone: 'brand',
      icon: RectangleStackIcon,
      display: displayCount(total),
      sub: t('tp_programs_page.kpi_total_sub'),
      progress: null,
      filter: { kind: 'reset' },
    },
    {
      key: 'active',
      label: t('tp_programs_page.kpi_active'),
      tone: 'emerald',
      icon: BoltIcon,
      display: displayCount(active),
      sub: total ? t('tp_programs_page.kpi_share_of_total', { pct: sharePct(active, total) }) : '',
      progress: sharePct(active, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'active' },
    },
    {
      key: 'completed',
      label: t('tp_programs_page.kpi_completed'),
      tone: 'sky',
      icon: CheckBadgeIcon,
      display: displayCount(completed),
      sub: total ? t('tp_programs_page.kpi_share_of_total', { pct: sharePct(completed, total) }) : '',
      progress: sharePct(completed, total),
      progressTotal: total,
      filter: { kind: 'status', value: 'completed' },
    },
    {
      key: 'days',
      label: t('tp_programs_page.kpi_operating_days'),
      tone: 'violet',
      icon: CalendarDaysIcon,
      display: displayCount(operatingDays),
      sub: t('tp_programs_page.kpi_operating_days_sub'),
      progress: null,
      filter: null,
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
    :aria-label="t('tp_programs_page.kpi_strip_aria')"
    :eyebrow="t('tp_programs_page.kpi_stats_eyebrow')"
    :title="t('tp_programs_page.kpi_strip_overview')"
    :hint="t('tp_programs_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
