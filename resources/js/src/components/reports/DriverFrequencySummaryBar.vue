<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ClockIcon,
  TruckIcon,
  UserGroupIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  kpi: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  year: { type: Number, required: true },
  yearDeltaLabel: { type: String, default: '' },
})

const { t } = useI18n()

const cards = computed(() => {
  const k = props.kpi
  const loading = props.loading

  const totalSub = props.yearDeltaLabel
    ? `${t('driver_freq.kpi_total_trips_sub', { year: props.year })} · ${props.yearDeltaLabel}`
    : t('driver_freq.kpi_total_trips_sub', { year: props.year })

  return [
    {
      key: 'total_trips',
      label: t('driver_freq.kpi_total_trips'),
      tone: 'brand',
      icon: QueueListIcon,
      display: loading ? '…' : k.totalTrips.toLocaleString('vi-VN'),
      sub: totalSub,
    },
    {
      key: 'active_drivers',
      label: t('driver_freq.kpi_active_drivers'),
      tone: 'sky',
      icon: UserGroupIcon,
      display: loading ? '…' : String(k.activeDrivers),
      sub: `${t('driver_freq.kpi_active_drivers_avg', { avg: k.avgTripsPerDriver })} · ${t('driver_freq.kpi_active_drivers_above', { n: k.driversAboveThreshold })}`,
    },
    {
      key: 'active_vehicles',
      label: t('driver_freq.kpi_active_vehicles'),
      tone: 'amber',
      icon: TruckIcon,
      display: loading ? '…' : String(k.activeVehicles),
      sub: `${t('driver_freq.kpi_active_vehicles_avg', { avg: k.avgTripsPerVehicle })} · ${t('driver_freq.kpi_active_vehicles_below', { n: k.vehiclesBelowThreshold })}`,
    },
    {
      key: 'total_hours',
      label: t('driver_freq.kpi_total_hours'),
      tone: 'emerald',
      icon: ClockIcon,
      display: loading ? '…' : `${k.totalHours.toLocaleString('vi-VN')}h`,
      sub: `${t('driver_freq.kpi_total_hours_avg', { avg: k.avgHoursPerDriver })} · ${t('driver_freq.kpi_total_hours_ontime', { pct: k.overallOnTime })}`,
    },
  ]
})
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('driver_freq.kpi_strip_aria')"
    :eyebrow="t('driver_freq.kpi_stats_eyebrow')"
    :title="t('driver_freq.kpi_strip_overview')"
    :hint="t('driver_freq.kpi_strip_hint')"
  />
</template>
