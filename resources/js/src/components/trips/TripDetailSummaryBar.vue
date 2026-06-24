<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CalendarDaysIcon,
  RectangleStackIcon,
  TruckIcon,
  UserGroupIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  scheduleLegCount: { type: Number, default: 1 },
  /** Chuyến hàng hoá — ẩn thẻ hành khách và số ghế. */
  hidePassengerMetrics: { type: Boolean, default: false },
})

const { t } = useI18n()

const DASH = '—'

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

const cards = computed(() => {
  const s = props.summary ?? {}
  const passengers = Number(s.passengers) || 0
  const seats = Number(s.seats) || 0
  const vehicles = Number(s.vehicles) || 0
  const drivers = Number(s.drivers) || 0
  const schedule =
    typeof s.schedule === 'string' && s.schedule.trim() ? s.schedule.trim() : ''
  const revenue =
    typeof s.revenue === 'string' && s.revenue.trim() ? s.revenue.trim() : ''

  const all = [
    {
      key: 'passengers',
      label: t('trip_detail.summary.passengers'),
      tone: 'brand',
      icon: UserGroupIcon,
      display: formatInt(passengers),
      sub: passengers
        ? t('trip_detail.overview.pax_count', { n: passengers })
        : t('trip_detail.empty.not_available'),
      filter: null,
    },
    {
      key: 'seats',
      label: t('trip_detail.summary.seats'),
      tone: 'sky',
      icon: RectangleStackIcon,
      display: seats > 0 ? formatInt(seats) : DASH,
      sub:
        seats > 0
          ? t('trip_detail.kpi_seats_sub_assigned')
          : t('trip_detail.kpi_seats_sub_empty'),
      filter: null,
    },
    {
      key: 'vehicles',
      label: t('trip_detail.summary.vehicles'),
      tone: 'emerald',
      icon: TruckIcon,
      display: formatInt(vehicles),
      sub: vehicles
        ? t('trip_detail.kpi_vehicles_sub', { n: vehicles })
        : t('trip_detail.kpi_dispatch_unassigned'),
      filter: null,
    },
    {
      key: 'drivers',
      label: t('trip_detail.summary.drivers'),
      tone: 'violet',
      icon: UserIcon,
      display: formatInt(drivers),
      sub: drivers
        ? t('trip_detail.kpi_drivers_sub', { n: drivers })
        : t('trip_detail.kpi_dispatch_unassigned'),
      filter: null,
    },
    {
      key: 'schedule',
      label: t('trip_detail.summary.schedule'),
      tone: 'amber',
      icon: CalendarDaysIcon,
      display: schedule || DASH,
      displayClass: 'text-base font-semibold leading-snug',
      sub:
        props.scheduleLegCount > 1
          ? t('trip_detail.overview.multi_schedule_hint', {
              n: props.scheduleLegCount,
            })
          : schedule
            ? t('trip_detail.kpi_schedule_sub')
            : t('trip_detail.empty.datetime'),
      filter: null,
    },
    {
      key: 'revenue',
      label: t('trip_detail.summary.revenue'),
      tone: 'brand',
      icon: BanknotesIcon,
      display: revenue || DASH,
      displayClass: 'text-base font-semibold leading-snug',
      sub: revenue
        ? t('trip_detail.overview.cost_from_wizard')
        : t('trip_detail.overview.cost_no_estimate'),
      filter: null,
    },
  ]

  if (props.hidePassengerMetrics) {
    return all.filter((c) => c.key !== 'passengers' && c.key !== 'seats')
  }
  return all
})

const gridClass = computed(() =>
  props.hidePassengerMetrics
    ? 'grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4'
    : 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6',
)
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    :grid-class="gridClass"
    :aria-label="t('trip_detail.kpi_strip_aria')"
    :eyebrow="t('trip_detail.kpi_stats_eyebrow')"
    :title="t('trip_detail.kpi_strip_overview')"
    :hint="t('trip_detail.kpi_strip_hint')"
  />
</template>
