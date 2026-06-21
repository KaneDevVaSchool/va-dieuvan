<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  BuildingStorefrontIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  metrics: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const intFmt = new Intl.NumberFormat('vi-VN')

function formatInt(n) {
  return intFmt.format(Number(n ?? 0))
}

/** Tiền tệ rút gọn cho thẻ KPI hẹp: 1.250.000 → «1,3 tr», 2.400.000.000 → «2,4 tỷ». */
function formatMoneyCompact(v) {
  const n = Number(v ?? 0)
  if (!Number.isFinite(n) || n === 0) return '0 ₫'
  const abs = Math.abs(n)
  const short = (x, unit) =>
    `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 1 }).format(x)} ${unit}`
  if (abs >= 1e9) return short(n / 1e9, 'tỷ')
  if (abs >= 1e6) return short(n / 1e6, 'tr')
  return `${intFmt.format(Math.round(n))} ₫`
}

function formatDistanceKm(v) {
  const n = Number(v ?? 0)
  if (!Number.isFinite(n) || n <= 0) return t('trips_page.empty_distance')
  return `${intFmt.format(Math.round(n))} km`
}

function translateFleetModeSlug(raw) {
  const k = String(raw ?? '').trim()
  const map = {
    internal: t('dashboard_analytics.fleet_internal'),
    vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
    taxi: t('dashboard_analytics.fleet_taxi'),
    unspecified: t('dashboard_analytics.fleet_unspecified'),
  }
  return map[k] ?? raw
}

const cards = computed(() => {
  const m = props.metrics ?? {}
  const completionRate = m.completionRate
  const sla = Number(m.slaBreaches ?? 0)
  const rawProvider = m.topProviderName && String(m.topProviderName).trim() !== '' ? m.topProviderName : ''
  const providerName = rawProvider ? translateFleetModeSlug(rawProvider) : ''
  const distanceKm = Number(m.distanceKm ?? 0)
  const hasDistance = Number.isFinite(distanceKm) && distanceKm > 0
  return [
    {
      key: 'trips',
      label: t('dashboard_analytics.kpi_trips_title'),
      tone: 'brand',
      icon: TruckIcon,
      display: formatInt(m.totalTrips),
      sub: t('dashboard_analytics.kpi_trips_sub'),
    },
    {
      key: 'completion',
      label: t('dashboard_analytics.kpi_completion_title'),
      tone: 'emerald',
      icon: CheckBadgeIcon,
      display: completionRate != null ? `${completionRate}%` : t('dashboard_analytics.kpi_empty_completion'),
      sub: t('dashboard_analytics.kpi_completion_ratio', {
        done: formatInt(m.completedTrips),
        total: formatInt(m.totalTripsInRange),
      }),
    },
    {
      key: 'revenue',
      label: t('dashboard_analytics.kpi_cost_title'),
      tone: 'amber',
      icon: BanknotesIcon,
      display: formatMoneyCompact(m.confirmedCost),
      sub: t('dashboard_analytics.kpi_cost_sub'),
    },
    {
      key: 'distance',
      label: t('dashboard_analytics.kpi_distance_title'),
      tone: 'sky',
      icon: MapPinIcon,
      display: formatDistanceKm(m.distanceKm),
      displayClass: hasDistance ? '' : 'text-xs font-medium leading-snug',
      sub: t('dashboard_analytics.kpi_distance_sub'),
    },
    {
      key: 'provider',
      label: t('dashboard_analytics.kpi_providers_title'),
      tone: 'violet',
      icon: BuildingStorefrontIcon,
      display: m.topProviderAmount ? formatMoneyCompact(m.topProviderAmount) : t('dashboard_analytics.kpi_empty_provider'),
      sub: providerName || t('dashboard_analytics.kpi_providers_sub'),
    },
    {
      key: 'sla',
      label: t('dashboard_analytics.kpi_sla_title'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: formatInt(sla),
      sub: sla > 0 ? t('dashboard_analytics.kpi_sla_action') : t('dashboard_analytics.kpi_sla_ok'),
    },
  ]
})
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6"
    :aria-label="t('dashboard_analytics.kpi_strip_aria')"
    :eyebrow="t('dashboard_analytics.kpi_strip_eyebrow')"
    :title="t('dashboard_analytics.kpi_strip_title')"
    :hint="t('dashboard_analytics.kpi_strip_hint')"
  />
</template>
