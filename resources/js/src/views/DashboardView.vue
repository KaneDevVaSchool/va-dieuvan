<template>
  <div class="space-y-4 md:space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
      {{ loadError }}
    </div>

    <div class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
            {{ t('dashboard_analytics.title') }}
          </h1>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            {{ t('dashboard_analytics.subtitle') }}
          </p>
        </div>
        <RouterLink
          class="shrink-0 text-sm font-medium text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300"
          to="/reports"
        >
          {{ t('dashboard_analytics.reports_link') }} →
        </RouterLink>
      </div>

      <!-- Truy cập nhanh: một hàng, cuộn ngang -->
      <div>
        <h2 class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('dashboard_analytics.quick_title') }}
        </h2>
        <div class="relative -mx-1 mt-2">
          <div
            class="flex snap-x snap-mandatory gap-2 overflow-x-auto overscroll-x-contain px-1 pb-1 pt-0.5 [-webkit-overflow-scrolling:touch] scroll-smooth sm:gap-3"
          >
            <RouterLink
              v-for="item in quickLinks"
              :key="item.to"
              :to="item.to"
              class="snap-start flex w-[4.75rem] shrink-0 flex-col items-center gap-1.5 rounded-2xl border border-slate-200/90 bg-white/90 px-2 py-3 text-center shadow-sm transition hover:border-teal-200 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-teal-800 sm:w-[5.25rem]"
            >
              <component :is="item.icon" class="h-6 w-6 text-teal-600 dark:text-teal-400" aria-hidden="true" />
              <span class="line-clamp-2 text-[11px] font-medium leading-tight text-slate-800 dark:text-slate-100">
                {{ item.title }}
              </span>
            </RouterLink>
          </div>
        </div>
      </div>

      <AppFilterBar>
        <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <details class="group relative">
            <summary
              class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            >
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
            >
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('dashboard_analytics.filter_applied_title') }}
              </p>
              <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li class="font-medium text-slate-900 dark:text-slate-100">{{ currentPresetLabel }}</li>
                <li class="tabular-nums text-slate-600 dark:text-slate-400">
                  {{ t('dashboard_analytics.filter_applied_range', { from: rangeFrom, to: rangeTo }) }}
                </li>
              </ul>
              <button
                type="button"
                class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="resetFilters"
              >
                {{ t('dashboard_analytics.filter_clear_all') }}
              </button>
            </div>
          </details>
          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <details class="group relative min-w-0">
              <summary
                class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                  {{ t('dashboard_analytics.filter_period_label') }}
                </span>
                <span class="max-w-[10rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ currentPresetLabel }}
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
              >
                <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                  <li v-for="p in presetDefs" :key="p.id">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        preset === p.id
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                      ]"
                      @click="applyPreset(p.id)"
                    >
                      {{ p.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>
            <details class="group relative min-w-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                  {{ t('dashboard_analytics.filter_dates_label') }}
                </span>
                <span class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ rangeFrom }} — {{ rangeTo }}
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 w-[min(100vw-1.5rem,320px)] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950 sm:w-max"
              >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <input
                    v-model="rangeFrom"
                    type="date"
                    class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                    @change="onManualDateChange"
                  />
                  <span class="hidden text-slate-300 dark:text-slate-600 sm:inline">—</span>
                  <input
                    v-model="rangeTo"
                    type="date"
                    class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                    @change="onManualDateChange"
                  />
                </div>
                <button
                  v-if="preset === 'custom'"
                  type="button"
                  class="mt-3 w-full rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-teal-700 disabled:opacity-50 dark:bg-teal-500 dark:hover:bg-teal-600"
                  :disabled="loading || !rangeValid"
                  @click="reloadSummary"
                >
                  {{ t('dashboard_analytics.apply_range') }}
                </button>
                <p v-if="!rangeValid" class="mt-2 text-xs text-rose-600 dark:text-rose-400">
                  {{ t('dashboard_analytics.range_invalid') }}
                </p>
              </div>
            </details>
          </div>
          <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
              :title="t('dashboard_analytics.filter_clear_all')"
              @click="resetFilters"
            >
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                <XMarkIcon
                  class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                  aria-hidden="true"
                />
              </span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <!-- KPI hàng 1 -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card :title="t('dashboard_analytics.kpi_trips_title')">
        <div class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white md:text-3xl">{{ totalTrips }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_trips_sub') }}</p>
        <div class="mt-2 max-h-24 space-y-1 overflow-y-auto border-t border-slate-100 pt-2 text-[11px] text-slate-600 dark:border-slate-700 dark:text-slate-300 md:max-h-28">
          <div v-for="row in tripStatusRows" :key="row.key" class="flex justify-between gap-2 tabular-nums">
            <span class="truncate text-slate-500 dark:text-slate-400">{{ row.label }}</span>
            <span class="shrink-0 font-medium text-slate-900 dark:text-slate-100">{{ row.n }}</span>
          </div>
          <div v-if="!tripStatusRows.length" class="text-slate-400 dark:text-slate-500">—</div>
        </div>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_cost_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">{{ formatMoney(totalConfirmedCost) }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_cost_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_sla_title')">
        <div class="text-xl font-bold tabular-nums text-rose-600 dark:text-rose-400 md:text-2xl">{{ summary?.cargo_sla_breaches ?? 0 }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_sla_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_providers_title')">
        <div class="truncate text-base font-semibold text-slate-900 dark:text-white md:text-lg">{{ topProviderName }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_providers_sub') }}</p>
        <p v-if="topProviderAmount" class="mt-1.5 text-sm font-medium tabular-nums text-slate-700 dark:text-slate-300">
          {{ formatMoney(topProviderAmount) }}
        </p>
      </Card>
    </div>

    <!-- KPI hàng 2 -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <Card :title="t('dashboard_analytics.kpi_completion_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">
          <template v-if="completionRate != null">{{ completionRate }}%</template>
          <template v-else>—</template>
        </div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
          {{ completedTrips }} / {{ totalTripsInRange }} · {{ t('dashboard_analytics.kpi_completion_sub') }}
        </p>
      </Card>
      <Card :title="t('dashboard_analytics.kpi_distance_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">
          {{ formatDistanceKm(summary?.trip_records_distance_km) }}
        </div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_distance_sub') }}</p>
      </Card>
    </div>

    <!-- Tuân thủ xe -->
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-4">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
        {{ t('dashboard_analytics.section_compliance') }}
      </h2>
      <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="box in complianceBoxes"
          :key="box.key"
          class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950/50"
        >
          <div class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ box.title }}</div>
          <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] tabular-nums">
            <span class="text-rose-600 dark:text-rose-400">{{ t('dashboard_analytics.compliance_overdue') }}: {{ box.overdue }}</span>
            <span v-if="box.soon != null" class="text-amber-700 dark:text-amber-400">{{ t('dashboard_analytics.compliance_due_30d') }}: {{ box.soon }}</span>
            <span v-if="box.stale != null" class="text-slate-600 dark:text-slate-400">{{ box.staleLabel }}: {{ box.stale }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_trips_donut')">
        <DashboardEChart
          :height="chartHeight"
          :option="optDonut"
          :aria-label="t('dashboard_analytics.chart_trips_donut')"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_fleet_mode')">
        <DashboardEChart
          :height="chartHeight"
          :option="optFleet"
          :aria-label="t('dashboard_analytics.chart_fleet_mode')"
        />
      </Card>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_trip_type')">
        <DashboardEChart
          :height="chartHeight"
          :option="optTripType"
          :aria-label="t('dashboard_analytics.chart_trip_type')"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_dispatch_status')">
        <DashboardEChart
          :height="chartHeight"
          :option="optDispatchDonut"
          :aria-label="t('dashboard_analytics.chart_dispatch_status')"
        />
      </Card>
    </div>

    <div
      class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      :title="t('dashboard_analytics.chart_hour_hint')"
    >
      <div class="mb-2 text-sm font-semibold text-slate-900 dark:text-white">
        {{ t('dashboard_analytics.chart_hour_line') }}
      </div>
      <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-0.5 dark:border-slate-700 dark:bg-slate-950/40">
        <DashboardEChart
          :height="chartHeightWide"
          :option="optHourLine"
          :aria-label="t('dashboard_analytics.chart_hour_line')"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_costs_bar')">
        <DashboardEChart
          :height="chartHeight"
          :option="optCostBar"
          :aria-label="t('dashboard_analytics.chart_costs_bar')"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_costs_pipeline')">
        <DashboardEChart
          :height="chartHeight"
          :option="optCostPipeline"
          :aria-label="t('dashboard_analytics.chart_costs_pipeline')"
        />
      </Card>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_providers')">
        <DashboardEChart
          :height="chartHeight"
          :option="optProviders"
          :aria-label="t('dashboard_analytics.chart_providers')"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_top_requesters')">
        <DashboardEChart
          :height="chartHeightTall"
          :option="optRequesters"
          :aria-label="t('dashboard_analytics.chart_top_requesters')"
        />
      </Card>
    </div>

    <Card :title="t('dashboard_analytics.chart_trips_by_plate')">
      <DashboardEChart
        :height="chartHeightTall"
        :option="optPlates"
        :aria-label="t('dashboard_analytics.chart_trips_by_plate')"
      />
    </Card>
  </div>
</template>

<script setup>
import { computed, markRaw, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  ChevronDownIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  DocumentMagnifyingGlassIcon,
  FunnelIcon,
  PlusCircleIcon,
  Square2StackIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import Card from '../components/ui/Card.vue'
import AppFilterBar from '../components/filters/AppFilterBar.vue'
import DashboardEChart from '../components/dashboard/DashboardEChart.vue'
import { getSummary } from '../api/reports'
import { labelTripStatus, labelTripType, labelRequestStatus } from '../util/labels'
import {
  sumTrips,
  normalizeTripsByHour,
  tripsStatusDonutOption,
  fleetModeDonutOption,
  statusDonutOption,
  costsByTypeBarOption,
  costsPipelineBarOption,
  providersHorizontalBarOption,
  topRequestersBarOption,
  licensePlateTripsBarOption,
  tripsByHourLineOption,
  tripsByTripTypeBarOption,
} from '../util/transportDashboardCharts'

const { t } = useI18n()

function ymd(d) {
  const x = new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function subDays(base, n) {
  const x = new Date(base)
  x.setDate(x.getDate() - n)
  return x
}

function startOfQuarter(d) {
  const m = d.getMonth()
  const q0 = Math.floor(m / 3) * 3
  return new Date(d.getFullYear(), q0, 1)
}

const today = new Date()
const rangeFrom = ref(ymd(new Date(today.getFullYear(), today.getMonth(), 1)))
const rangeTo = ref(ymd(today))
const preset = ref('month')

const loading = ref(false)
const loadError = ref('')
const summary = ref(null)

const chartHeight = ref('260px')
const chartHeightWide = ref('280px')
const chartHeightTall = ref('300px')

function updateChartHeights() {
  const narrow = typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches
  chartHeight.value = narrow ? '220px' : '260px'
  chartHeightWide.value = narrow ? '240px' : '300px'
  chartHeightTall.value = narrow ? '260px' : '320px'
}

const presetDefs = computed(() => [
  { id: 'month', label: t('dashboard_analytics.preset_month') },
  { id: 'last30', label: t('dashboard_analytics.preset_last30') },
  { id: 'last7', label: t('dashboard_analytics.preset_last7') },
  { id: 'quarter', label: t('dashboard_analytics.preset_quarter') },
  { id: 'custom', label: t('dashboard_analytics.preset_custom') },
])

const currentPresetLabel = computed(() => presetDefs.value.find((p) => p.id === preset.value)?.label ?? '')

const quickLinks = computed(() => [
  { to: '/dispatcher', title: t('dashboard_analytics.quick_dispatcher'), icon: markRaw(Square2StackIcon) },
  { to: '/trips', title: t('dashboard_analytics.quick_trips'), icon: markRaw(TruckIcon) },
  { to: '/dispatch-requests/new', title: t('dashboard_analytics.quick_new_request'), icon: markRaw(PlusCircleIcon) },
  { to: '/requests', title: t('dashboard_analytics.quick_requests'), icon: markRaw(ClipboardDocumentListIcon) },
  { to: '/resources/list', title: t('dashboard_analytics.quick_resources'), icon: markRaw(UserGroupIcon) },
  { to: '/cargo', title: t('dashboard_analytics.quick_cargo'), icon: markRaw(CubeIcon) },
  { to: '/costs', title: t('dashboard_analytics.quick_costs'), icon: markRaw(BanknotesIcon) },
  { to: '/pricing', title: t('dashboard_analytics.quick_pricing'), icon: markRaw(TableCellsIcon) },
  { to: '/audit-logs', title: t('dashboard_analytics.quick_audit'), icon: markRaw(DocumentMagnifyingGlassIcon) },
])

const emptyChartLabel = computed(() => t('dashboard_analytics.chart_empty'))

const rangeValid = computed(() => {
  if (!rangeFrom.value || !rangeTo.value) return false
  return rangeFrom.value <= rangeTo.value
})

function syncRangeForPreset(id) {
  const now = new Date()
  const end = ymd(now)
  if (id === 'month') {
    rangeFrom.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1))
    rangeTo.value = end
  } else if (id === 'last30') {
    rangeFrom.value = ymd(subDays(now, 29))
    rangeTo.value = end
  } else if (id === 'last7') {
    rangeFrom.value = ymd(subDays(now, 6))
    rangeTo.value = end
  } else if (id === 'quarter') {
    rangeFrom.value = ymd(startOfQuarter(now))
    rangeTo.value = end
  }
}

function applyPreset(id) {
  preset.value = id
  if (id !== 'custom') {
    syncRangeForPreset(id)
    reloadSummary()
  }
}

function resetFilters() {
  applyPreset('month')
}

function onManualDateChange() {
  preset.value = 'custom'
}

function formatMoney(v) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat('vi-VN').format(n) + ' VND'
}

function formatDistanceKm(v) {
  const n = Number(v ?? 0)
  if (!Number.isFinite(n) || n <= 0) return '—'
  return `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Math.round(n))} km`
}

const tripLabelMap = computed(() => {
  const o = summary.value?.trips_by_status ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = labelTripStatus(k)
  }
  return map
})

const tripTypeLabelMap = computed(() => {
  const o = summary.value?.trips_by_trip_type ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = k === 'unspecified' ? t('dashboard_analytics.trip_type_unspecified') : labelTripType(k)
  }
  return map
})

const fleetLabelMap = computed(() => ({
  internal: t('dashboard_analytics.fleet_internal'),
  vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
  taxi: t('dashboard_analytics.fleet_taxi'),
  unspecified: t('dashboard_analytics.fleet_unspecified'),
}))

const dispatchStatusLabelMap = computed(() => {
  const o = summary.value?.dispatch_requests_by_status ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = labelRequestStatus(k)
  }
  return map
})

const costPipelineLabelMap = computed(() => ({
  draft: t('dashboard_analytics.cost_status_draft'),
  submitted: t('dashboard_analytics.cost_status_submitted'),
  confirmed: t('dashboard_analytics.cost_status_confirmed'),
  rejected: t('dashboard_analytics.cost_status_rejected'),
}))

const totalTrips = computed(() => sumTrips(summary.value?.trips_by_status))

const totalTripsInRange = computed(() => Number(summary.value?.trip_completion?.total ?? totalTrips.value))

const completedTrips = computed(() => Number(summary.value?.trip_completion?.completed ?? 0))

const completionRate = computed(() => {
  const r = summary.value?.trip_completion?.rate_pct
  return r != null ? Number(r) : null
})

const tripStatusRows = computed(() => {
  const raw = summary.value?.trips_by_status ?? {}
  return Object.entries(raw)
    .map(([key, v]) => ({
      key,
      label: labelTripStatus(key),
      n: Number(v ?? 0),
    }))
    .filter((r) => r.n > 0)
    .sort((a, b) => b.n - a.n)
})

const totalConfirmedCost = computed(() => {
  const o = summary.value?.confirmed_costs_by_type ?? {}
  return Object.values(o).reduce((a, b) => a + Number(b ?? 0), 0)
})

const topProvider = computed(() => {
  const list = summary.value?.confirmed_costs_by_provider
  if (!Array.isArray(list) || !list.length) return null
  return list[0]
})

const topProviderName = computed(() => topProvider.value?.provider ?? '—')
const topProviderAmount = computed(() => topProvider.value?.total_amount ?? 0)

const complianceBoxes = computed(() => {
  const vc = summary.value?.vehicle_compliance ?? {}
  const ins = vc.inspection ?? {}
  const insu = vc.insurance ?? {}
  const road = vc.road_fee ?? {}
  const maint = vc.maintenance ?? {}
  return [
    {
      key: 'insp',
      title: t('dashboard_analytics.compliance_inspection'),
      overdue: ins.overdue ?? 0,
      soon: ins.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'insu',
      title: t('dashboard_analytics.compliance_insurance'),
      overdue: insu.overdue ?? 0,
      soon: insu.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'road',
      title: t('dashboard_analytics.compliance_road_fee'),
      overdue: road.overdue ?? 0,
      soon: road.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'maint',
      title: t('dashboard_analytics.compliance_maintenance'),
      overdue: 0,
      soon: null,
      stale: maint.no_recent_service_180d ?? 0,
      staleLabel: t('dashboard_analytics.compliance_maint_stale'),
    },
  ]
})

const chartT = (key) => t(`dashboard_analytics.${key}`)

const optDonut = computed(() =>
  tripsStatusDonutOption({
    tripsByStatus: summary.value?.trips_by_status,
    labelMap: tripLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optFleet = computed(() =>
  fleetModeDonutOption({
    tripsByFleetMode: summary.value?.trips_by_fleet_mode,
    labelMap: fleetLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optTripType = computed(() =>
  tripsByTripTypeBarOption({
    tripsByTripType: summary.value?.trips_by_trip_type,
    labelMap: tripTypeLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optDispatchDonut = computed(() =>
  statusDonutOption({
    countsByStatus: summary.value?.dispatch_requests_by_status,
    labelMap: dispatchStatusLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optHourLine = computed(() => {
  const counts = normalizeTripsByHour(summary.value?.trips_by_hour)
  const base = tripsByHourLineOption({
    counts24: counts,
    t: chartT,
    emptyText: emptyChartLabel.value,
  })
  if (base.graphic) return base
  return {
    ...base,
    backgroundColor: 'transparent',
    xAxis: {
      ...base.xAxis,
      axisLabel: { ...base.xAxis.axisLabel, color: '#94a3b8' },
      axisLine: { lineStyle: { color: 'rgba(148,163,184,0.35)' } },
    },
    yAxis: {
      ...base.yAxis,
      nameTextStyle: { color: '#94a3b8', fontSize: 10 },
      axisLabel: { color: '#94a3b8', fontSize: 10 },
      splitLine: { lineStyle: { color: 'rgba(148,163,184,0.2)' } },
    },
    series: base.series.map((s) => ({
      ...s,
      lineStyle: { ...s.lineStyle, color: '#60a5fa' },
      itemStyle: { color: '#60a5fa' },
      label: s.label ? { ...s.label, color: '#94a3b8' } : s.label,
    })),
  }
})

const optCostBar = computed(() =>
  costsByTypeBarOption({
    costsByType: summary.value?.confirmed_costs_by_type,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optCostPipeline = computed(() =>
  costsPipelineBarOption({
    costsByPipelineStatus: summary.value?.costs_by_pipeline_status,
    labelMap: costPipelineLabelMap.value,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optProviders = computed(() =>
  providersHorizontalBarOption({
    rows: summary.value?.confirmed_costs_by_provider,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optRequesters = computed(() =>
  topRequestersBarOption({
    rows: summary.value?.top_requesters,
    tripsSuffix: t('dashboard_analytics.tooltip_trips_unit'),
    emptyText: emptyChartLabel.value,
  }),
)

const optPlates = computed(() =>
  licensePlateTripsBarOption({
    tripsByPlate: summary.value?.trips_by_license_plate,
    plateSuffix: t('dashboard_analytics.tooltip_trips_unit'),
    emptyText: emptyChartLabel.value,
  }),
)

async function reloadSummary() {
  if (!rangeValid.value) return
  loading.value = true
  loadError.value = ''
  try {
    summary.value = await getSummary({ from: rangeFrom.value, to: rangeTo.value })
  } catch {
    loadError.value = t('dashboard_analytics.load_error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  updateChartHeights()
  window.addEventListener('resize', updateChartHeights)
  reloadSummary()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateChartHeights)
})
</script>
