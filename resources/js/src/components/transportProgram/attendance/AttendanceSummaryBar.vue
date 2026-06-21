<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClockIcon,
  UserGroupIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  summary: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  activeStatus: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function displayCount(n) {
  if (props.loading) return '…'
  return String(n ?? 0)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const activeCardKey = computed(() => {
  if (!props.activeStatus) return 'total'
  return props.activeStatus
})

const cards = computed(() => {
  const s = props.summary
  const total = s.total ?? 0
  const present = s.present ?? 0
  const excused = s.excused ?? 0
  const unexcused = s.unexcused ?? 0
  const absentTotal = s.absent_total ?? excused + unexcused
  const rate = s.attendance_rate ?? 0

  return [
    {
      key: 'total',
      label: t('tp_attendance_page.kpi_total'),
      tone: 'brand',
      icon: UserGroupIcon,
      display: displayCount(total),
      sub: t('tp_attendance_page.kpi_total_sub', { rate }),
      progress: rate,
      progressTotal: 100,
      filter: { display_status: '' },
    },
    {
      key: 'present',
      label: t('tp_attendance_page.kpi_present'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(present),
      sub: total ? t('tp_attendance_page.kpi_share', { pct: sharePct(present, total) }) : '',
      progress: sharePct(present, total),
      progressTotal: total || 1,
      filter: { display_status: 'present' },
    },
    {
      key: 'excused',
      label: t('tp_attendance_page.kpi_excused'),
      tone: 'amber',
      icon: ClockIcon,
      display: displayCount(excused),
      sub: total ? t('tp_attendance_page.kpi_share', { pct: sharePct(excused, total) }) : '',
      progress: sharePct(excused, total),
      progressTotal: total || 1,
      filter: { display_status: 'excused' },
    },
    {
      key: 'unexcused',
      label: t('tp_attendance_page.kpi_unexcused'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: displayCount(unexcused),
      sub: total ? t('tp_attendance_page.kpi_share', { pct: sharePct(unexcused, total) }) : '',
      progress: sharePct(unexcused, total),
      progressTotal: total || 1,
      filter: { display_status: 'unexcused' },
    },
    {
      key: 'absent',
      label: t('tp_attendance_page.kpi_absent'),
      tone: 'sky',
      icon: XCircleIcon,
      display: displayCount(absentTotal),
      sub: t('tp_attendance_page.kpi_absent_sub'),
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
    data-testid="attendance-summary"
    :cards="cards"
    :active-card-key="activeCardKey"
    :aria-label="t('tp_attendance_page.kpi_strip_aria')"
    :eyebrow="t('tp_attendance_page.kpi_stats_eyebrow')"
    :title="t('tp_attendance_page.kpi_strip_overview')"
    :hint="t('tp_attendance_page.kpi_strip_hint')"
    @card-action="onCardAction"
  />
</template>
