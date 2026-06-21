<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BuildingOffice2Icon,
  CheckCircleIcon,
  TruckIcon,
  UserGroupIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  vehicles: { type: Array, default: () => [] },
  drivers: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

function displayCount(n) {
  if (props.loading) return '…'
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function sharePct(part, total) {
  if (!total || total <= 0) return 0
  return Math.min(100, Math.round((part / total) * 100))
}

const cards = computed(() => {
  const activeVehicles = props.vehicles.filter((v) => !v.deleted_at)
  const activeDrivers = props.drivers.filter((d) => !d.deleted_at)
  const activeSuppliers = props.suppliers.filter((s) => !s.deleted_at)

  const totalVehicles = activeVehicles.length
  const readyVehicles = activeVehicles.filter((v) => v.status === 'ready' || v.status === 'active').length
  const maintVehicles = activeVehicles.filter((v) => v.status === 'maintenance' || v.status === 'broken').length
  const inUseVehicles = activeVehicles.filter((v) => v.status === 'in_use').length

  const totalDrivers = activeDrivers.length
  const availDrivers = activeDrivers.filter((d) => d.availability_status === 'available').length
  const busyDrivers = activeDrivers.filter((d) => d.availability_status === 'busy').length

  const totalSuppliers = activeSuppliers.length

  return [
    {
      key: 'vehicles',
      label: t('resources.tab_vehicles'),
      tone: 'brand',
      icon: TruckIcon,
      display: displayCount(totalVehicles),
      sub: inUseVehicles > 0 ? t('resources.kpi_in_use', { n: inUseVehicles }) : t('resources.kpi_vehicles_sub'),
      progress: null,
    },
    {
      key: 'vehicles_ready',
      label: t('resources.kpi_ready'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: displayCount(readyVehicles),
      sub: totalVehicles ? t('resources.kpi_share_of_total', { pct: sharePct(readyVehicles, totalVehicles) }) : '',
      progress: sharePct(readyVehicles, totalVehicles),
      progressTotal: totalVehicles || 1,
    },
    {
      key: 'vehicles_maint',
      label: t('resources.kpi_maintenance'),
      tone: 'amber',
      icon: WrenchScrewdriverIcon,
      display: displayCount(maintVehicles),
      sub: maintVehicles > 0 ? t('resources.kpi_action_required') : t('resources.kpi_fleet_ok'),
      progress: null,
    },
    {
      key: 'drivers',
      label: t('resources.tab_drivers'),
      tone: 'violet',
      icon: UserGroupIcon,
      display: displayCount(totalDrivers),
      sub: availDrivers > 0 ? t('resources.kpi_available', { n: availDrivers }) : busyDrivers > 0 ? t('resources.kpi_busy', { n: busyDrivers }) : '',
      progress: null,
    },
    {
      key: 'suppliers',
      label: t('resources.tab_suppliers'),
      tone: 'sky',
      icon: BuildingOffice2Icon,
      display: displayCount(totalSuppliers),
      sub: t('resources.kpi_suppliers_sub'),
      progress: null,
    },
  ]
})
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
    :aria-label="t('resources.kpi_strip_aria')"
    :eyebrow="t('resources.kpi_stats_eyebrow')"
    :title="t('resources.kpi_strip_overview')"
    :hint="t('resources.kpi_strip_hint')"
    active-card-key=""
  />
</template>
