<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CalendarDaysIcon,
  ChartBarIcon,
  TruckIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  program: { type: Object, required: true },
  operatingDayCount: { type: Number, default: 0 },
  daysLoading: { type: Boolean, default: false },
})

const { t } = useI18n()

const capacity = computed(() => Number(props.program?.settings?.vehicle?.max_capacity || 0))

const hasProgramDates = computed(() => !!(props.program?.start_date && props.program?.end_date))

const fillPct = computed(() => {
  const cap = capacity.value
  if (!cap) return 0
  return Math.min(100, Math.round(((props.program.enrolled_count ?? 0) / cap) * 100))
})

const progressPct = computed(() => {
  const p = props.program
  if (!hasProgramDates.value) return null
  if (p.status === 'completed') return 100
  if (p.status === 'draft' || p.status === 'cancelled') return 0
  const start = new Date(p.start_date).getTime()
  const end = new Date(p.end_date).getTime()
  const now = Date.now()
  if (now <= start) return 0
  if (now >= end) return 100
  return Math.min(100, Math.round(((now - start) / (end - start)) * 100))
})

const totalDays = computed(() => props.program?.day_count ?? 0)

const compactValueClass = 'text-base font-semibold leading-snug'

const cards = computed(() => {
  const cap = capacity.value
  const enrolled = props.program.enrolled_count ?? 0

  const enrolledSub = cap
    ? t('tp_program_detail.kpi_enrolled_sub', { cap })
    : t('tp_program_detail.kpi_enrolled_sub_no_cap')

  const operatingDisplay = props.daysLoading ? '…' : String(props.operatingDayCount)
  const operatingSub = totalDays.value
    ? t('tp_program_detail.kpi_operating_days_sub', { total: totalDays.value })
    : t('tp_program_detail.kpi_operating_days_sub_none')

  const fillCard = cap
    ? {
        key: 'fill',
        label: t('tp_program_detail.kpi_fill'),
        tone: 'sky',
        icon: TruckIcon,
        display: `${fillPct.value}%`,
        sub: t('tp_program_detail.kpi_fill_sub', { enrolled, cap }),
        progress: fillPct.value,
        progressTotal: cap,
      }
    : {
        key: 'fill',
        label: t('tp_program_detail.kpi_fill'),
        tone: 'sky',
        icon: TruckIcon,
        display: t('tp_program_detail.kpi_fill_no_data'),
        displayClass: compactValueClass,
        sub: t('tp_program_detail.kpi_fill_no_data_sub'),
        progress: null,
        progressTotal: 0,
      }

  const progressCard =
    progressPct.value != null
      ? {
          key: 'progress',
          label: t('tp_program_detail.kpi_progress'),
          tone: 'emerald',
          icon: ChartBarIcon,
          display: `${progressPct.value}%`,
          sub: t('tp_program_detail.kpi_progress_sub'),
          progress: progressPct.value,
          progressTotal: 100,
        }
      : {
          key: 'progress',
          label: t('tp_program_detail.kpi_progress'),
          tone: 'emerald',
          icon: ChartBarIcon,
          display: t('tp_program_detail.kpi_progress_undetermined'),
          displayClass: compactValueClass,
          sub: t('tp_program_detail.kpi_progress_no_dates_sub'),
          progress: null,
          progressTotal: 0,
        }

  return [
    {
      key: 'enrolled',
      label: t('tp_program_detail.kpi_enrolled'),
      tone: 'brand',
      icon: UsersIcon,
      display: String(enrolled),
      sub: enrolledSub,
    },
    fillCard,
    {
      key: 'operating_days',
      label: t('tp_program_detail.kpi_operating_days'),
      tone: 'violet',
      icon: CalendarDaysIcon,
      display: operatingDisplay,
      sub: operatingSub,
    },
    progressCard,
  ]
})
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('tp_program_detail.kpi_strip_aria')"
    :eyebrow="t('tp_program_detail.kpi_stats_eyebrow')"
    :title="t('tp_program_detail.kpi_strip_overview')"
    :hint="t('tp_program_detail.kpi_strip_hint')"
  />
</template>
