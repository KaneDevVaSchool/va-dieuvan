<template>
  <div class="freq-dash space-y-5 pb-14">

    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
          {{ t('driver_freq.hero_title') }}
        </h1>
        <p class="mt-0.5 max-w-xl text-sm text-slate-500 dark:text-slate-400">
          {{ t('driver_freq.hero_sub') }}
        </p>
      </div>
      <div v-if="canExport" class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          :disabled="!!exporting || loading"
          class="freq-btn freq-btn--xlsx"
          @click="doExportXlsx"
        >
          <span v-if="exporting === 'xlsx'" class="freq-spinner freq-spinner--emerald" />
          <TableCellsIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('driver_freq.btn_export_xlsx') }}
        </button>
        <button
          type="button"
          :disabled="!!exporting || loading"
          class="freq-btn freq-btn--pdf"
          @click="doExportPdf"
        >
          <span v-if="exporting === 'pdf'" class="freq-spinner freq-spinner--rose" />
          <DocumentTextIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('driver_freq.btn_export_pdf') }}
        </button>
      </div>
    </header>

    <AppFilterBar>
      <div ref="freqFilterBarRef" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('driver_freq.filter_menu_title') }}
          </p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li class="flex justify-between gap-2">
              <span class="text-slate-500">{{ t('driver_freq.filter_year') }}</span>
              <span class="font-medium tabular-nums">{{ filters.year }}</span>
            </li>
            <li v-if="filters.quarter" class="flex justify-between gap-2">
              <span class="text-slate-500">{{ t('driver_freq.filter_quarter') }}</span>
              <span class="font-medium">{{ filters.quarter.toUpperCase() }}</span>
            </li>
            <li v-if="filters.driverId" class="flex justify-between gap-2">
              <span class="text-slate-500">{{ t('driver_freq.filter_driver') }}</span>
              <span class="max-w-[10rem] truncate font-medium">{{ driverFilterSummary }}</span>
            </li>
            <li v-if="filters.vehiclePlate" class="flex justify-between gap-2">
              <span class="text-slate-500">{{ t('driver_freq.filter_vehicle') }}</span>
              <span class="font-medium">{{ filters.vehiclePlate }}</span>
            </li>
            <li v-if="filters.tripType" class="flex justify-between gap-2">
              <span class="text-slate-500">{{ t('driver_freq.filter_trip_type') }}</span>
              <span class="font-medium">{{ tripTypeFilterSummary }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400">{{ t('filter_bar.empty') }}</li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
              {{ t('trips_page.filter_show_controls_title') }}
            </p>
            <p class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
              {{ t('trips_page.filter_show_controls_hint') }}
            </p>
            <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
              <li v-for="opt in filterBarVisibilityOptions" :key="'df-vis-' + opt.id" class="flex items-start gap-2">
                <input
                  :id="'driver-freq-filter-vis-' + opt.id"
                  v-model="filterBarVisible[opt.id]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
                />
                <label :for="'driver-freq-filter-vis-' + opt.id" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">
                  {{ t(opt.labelKey) }}
                </label>
              </li>
            </ul>
          </div>
          <button
            type="button"
            class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="resetFilters(); closeFilterMenu()"
          >
            {{ t('driver_freq.clear_filters') }}
          </button>
        </AppFilterFunnelMenu>
        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />
        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
          :title="t('filter_bar.clear_icon')"
          :aria-label="t('filter_bar.clear_icon')"
          @click="resetFilters"
        >
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" />
            <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40" />
          </span>
        </button>
      </div>
      <div
        v-if="hasVisibleBarFilters"
        class="mt-2 flex min-w-0 flex-wrap items-center gap-2 border-t border-violet-100/80 pt-2 dark:border-violet-900/30"
      >
        <select
          v-if="filterBarVisible.year"
          v-model.number="filters.year"
          class="freq-select"
          :aria-label="t('driver_freq.filter_year')"
          @change="onFilterChange"
        >
          <option v-for="y in YEAR_OPTIONS" :key="y" :value="y">{{ y }}</option>
        </select>
        <select
          v-if="filterBarVisible.quarter"
          v-model="filters.quarter"
          class="freq-select"
          :aria-label="t('driver_freq.filter_quarter')"
          @change="onFilterChange"
        >
          <option value="">{{ t('driver_freq.filter_quarter') }}</option>
          <option value="q1">{{ t('driver_freq.filter_quarter_q1') }}</option>
          <option value="q2">{{ t('driver_freq.filter_quarter_q2') }}</option>
          <option value="q3">{{ t('driver_freq.filter_quarter_q3') }}</option>
          <option value="q4">{{ t('driver_freq.filter_quarter_q4') }}</option>
        </select>
        <select
          v-if="filterBarVisible.driverId"
          v-model="filters.driverId"
          class="freq-select freq-select--wide"
          :aria-label="t('driver_freq.filter_driver')"
          @change="onFilterChange"
        >
          <option value="">{{ t('driver_freq.filter_driver') }}</option>
          <option v-for="d in filterOptions.drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <select
          v-if="filterBarVisible.vehiclePlate"
          v-model="filters.vehiclePlate"
          class="freq-select"
          :aria-label="t('driver_freq.filter_vehicle')"
          @change="onFilterChange"
        >
          <option value="">{{ t('driver_freq.filter_vehicle') }}</option>
          <option v-for="v in filterOptions.vehicles" :key="v.id" :value="v.plate">{{ v.plate }}</option>
        </select>
        <select
          v-if="filterBarVisible.tripType"
          v-model="filters.tripType"
          class="freq-select freq-select--wide"
          :aria-label="t('driver_freq.filter_trip_type')"
          @change="onFilterChange"
        >
          <option value="">{{ t('driver_freq.filter_trip_type') }}</option>
          <option v-for="tt in TRIP_TYPES" :key="tt.value" :value="tt.value">{{ tt.label }}</option>
        </select>
      </div>
    </AppFilterBar>

    <div
      v-if="loadError"
      role="alert"
      class="freq-alert"
    >
      {{ t('driver_freq.load_error') }}
    </div>

    <!-- ============================================================
         KPI CARDS
         ============================================================ -->
    <p v-if="loading" class="freq-loading">{{ t('driver_freq.loading') }}</p>
    <div v-else class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <article class="freq-kpi freq-kpi--va">
        <p class="freq-kpi__label">{{ t('driver_freq.kpi_total_trips') }}</p>
        <p class="freq-kpi__value">{{ kpi.totalTrips.toLocaleString('vi-VN') }}</p>
        <p class="freq-kpi__hint">{{ t('driver_freq.kpi_total_trips_sub', { year: filters.year }) }}</p>
        <span v-if="yearDeltaLabel" class="freq-kpi__badge" :class="yearDeltaClass">{{ yearDeltaLabel }}</span>
      </article>
      <article class="freq-kpi freq-kpi--sky">
        <p class="freq-kpi__label">{{ t('driver_freq.kpi_active_drivers') }}</p>
        <p class="freq-kpi__value freq-kpi__value--sky">{{ kpi.activeDrivers }}</p>
        <p class="freq-kpi__hint">{{ t('driver_freq.kpi_active_drivers_avg', { avg: kpi.avgTripsPerDriver }) }}</p>
        <span class="freq-kpi__foot freq-kpi__foot--sky">{{ t('driver_freq.kpi_active_drivers_above', { n: kpi.driversAboveThreshold }) }}</span>
      </article>
      <article class="freq-kpi freq-kpi--amber">
        <p class="freq-kpi__label">{{ t('driver_freq.kpi_active_vehicles') }}</p>
        <p class="freq-kpi__value freq-kpi__value--amber">{{ kpi.activeVehicles }}</p>
        <p class="freq-kpi__hint">{{ t('driver_freq.kpi_active_vehicles_avg', { avg: kpi.avgTripsPerVehicle }) }}</p>
        <span class="freq-kpi__foot freq-kpi__foot--amber">{{ t('driver_freq.kpi_active_vehicles_below', { n: kpi.vehiclesBelowThreshold }) }}</span>
      </article>
      <article class="freq-kpi freq-kpi--emerald">
        <p class="freq-kpi__label">{{ t('driver_freq.kpi_total_hours') }}</p>
        <p class="freq-kpi__value">{{ kpi.totalHours.toLocaleString('vi-VN') }}<span class="text-lg font-semibold">h</span></p>
        <p class="freq-kpi__hint">{{ t('driver_freq.kpi_total_hours_avg', { avg: kpi.avgHoursPerDriver }) }}</p>
        <span class="freq-kpi__foot freq-kpi__foot--emerald">{{ t('driver_freq.kpi_total_hours_ontime', { pct: kpi.overallOnTime }) }}</span>
      </article>
    </div>

    <!-- ============================================================
         ROW 1: Two bar charts
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="freq-chart-card">
        <p class="freq-chart-title">{{ t('driver_freq.chart_driver_trips') }}</p>
        <DashboardEChart
          :option="chartDriverTrips"
          height="260px"
          :aria-label="t('driver_freq.chart_driver_trips')"
          @chart-click="onDriverChartClick"
        />
      </div>
      <div class="freq-chart-card">
        <p class="freq-chart-title">{{ t('driver_freq.chart_vehicle_freq') }}</p>
        <DashboardEChart
          :option="chartVehicleFreq"
          height="260px"
          :aria-label="t('driver_freq.chart_vehicle_freq')"
        />
      </div>
    </div>

    <!-- ============================================================
         ROW 2: Ranking table (3/5) + Right column: Bonus + Quarterly (2/5)
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">

      <!-- Ranking table -->
      <div class="freq-panel lg:col-span-3">
        <div class="freq-panel__head">
          <h2 class="freq-panel__title">{{ t('driver_freq.section_ranking') }}</h2>
        </div>
        <div class="overflow-x-auto overscroll-x-contain">
          <table class="freq-sheet w-full min-w-[640px]">
            <thead>
              <tr>
                <th class="freq-th freq-th--center w-10">{{ t('driver_freq.col_rank') }}</th>
                <th class="freq-th min-w-[10rem]">{{ t('driver_freq.col_driver') }}</th>
                <th class="freq-th min-w-[7rem]">{{ t('driver_freq.col_trips') }}</th>
                <th class="freq-th">{{ t('driver_freq.col_hours') }}</th>
                <th class="freq-th">{{ t('driver_freq.col_ontime') }}</th>
                <th class="freq-th min-w-[8rem]">{{ t('driver_freq.col_trip_types') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && filteredDrivers.length === 0">
                <td colspan="6" class="freq-empty">{{ t('driver_freq.empty_ranking') }}</td>
              </tr>
              <tr
                v-for="(driver, idx) in filteredDrivers"
                :key="driver.id"
                class="freq-row"
                :class="{
                  'freq-row--alt': idx % 2 === 1,
                  'freq-row--selected': selectedDriverCode === driver.code,
                }"
                @click="toggleSelectedDriver(driver.code)"
              >
                <td class="freq-td freq-td--center">
                  <span class="freq-rank" :class="rankBadgeClass(idx + 1)">{{ idx + 1 }}</span>
                </td>
                <td class="freq-td">
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ driver.name }}</p>
                  <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ driver.employeeCode || driver.code }}</p>
                </td>
                <td class="freq-td">
                  <div class="flex flex-col gap-1.5">
                    <span class="font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ driver.trips }}</span>
                    <div class="freq-bar-track">
                      <div
                        class="freq-bar-fill"
                        :style="{ width: `${(driver.trips / (filteredDrivers[0]?.trips || 1)) * 100}%` }"
                      />
                    </div>
                  </div>
                </td>
                <td class="freq-td tabular-nums">{{ driver.hours }}h</td>
                <td class="freq-td">
                  <span class="font-semibold" :class="onTimeClass(driver.onTime)">{{ driver.onTime }}%</span>
                </td>
                <td class="freq-td">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="(count, ti) in tripTypeCounts(driver)"
                      :key="ti"
                      class="freq-type-pill"
                      :title="TYPE_EXPORT_SHORT[ti]"
                    >{{ TYPE_EXPORT_SHORT[ti] }} {{ count }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="freq-footnote">{{ t('driver_freq.kpi_formula') }}</p>
      </div>

      <div class="flex flex-col gap-4 lg:col-span-2">
        <div class="freq-chart-card">
          <p class="freq-chart-title">{{ t('driver_freq.chart_quarterly') }}</p>
          <DashboardEChart
            :option="chartQuarterlyTrend"
            height="175px"
            :aria-label="t('driver_freq.chart_quarterly')"
          />
        </div>

      </div>
    </div>

    <!-- ============================================================
         ROW 3: Trip types stacked + Monthly activity
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="freq-chart-card">
        <p class="freq-chart-title">{{ t('driver_freq.chart_driver_types') }}</p>
        <DashboardEChart
          :option="chartDriverTypes"
          height="280px"
          :aria-label="t('driver_freq.chart_driver_types')"
        />
      </div>
      <div class="freq-chart-card">
        <p class="freq-chart-title">{{ t('driver_freq.chart_monthly') }}</p>
        <DashboardEChart
          :option="chartMonthlyActivity"
          height="280px"
          :aria-label="t('driver_freq.chart_monthly')"
        />
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { DocumentTextIcon, FunnelIcon, TableCellsIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import {
  downloadDriverFrequencyPdf,
  downloadDriverFrequencyXlsx,
  getDriverFrequencyReport,
} from '../../api/reports'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()

const canExport = computed(() => auth.hasPermission('report.export'))

const EMPTY_KPI = {
  totalTrips: 0,
  activeDrivers: 0,
  avgTripsPerDriver: 0,
  driversAboveThreshold: 0,
  activeVehicles: 0,
  avgTripsPerVehicle: 0,
  vehiclesBelowThreshold: 0,
  totalHours: 0,
  avgHoursPerDriver: 0,
  overallOnTime: 0,
}

const YEAR_OPTIONS = [new Date().getFullYear(), new Date().getFullYear() - 1, new Date().getFullYear() - 2]

const TRIP_TYPES = [
  { value: 'point_to_point', label: 'Điểm đến điểm' },
  { value: 'business', label: 'Công tác' },
  { value: 'door_to_door', label: 'Đưa đón' },
  { value: 'cargo', label: 'Hàng hóa' },
]

const DRIVER_BAR_COLORS = [
  '#7a0029', '#9b0036', '#b5103d', '#c03058',
  '#ca506c', '#d47080', '#dd8898', '#e6a0b0', '#eebbca',
]

const VEHICLE_BAR_COLORS = ['#1b3a5c', '#1e4d8c', '#225499', '#b8860b', '#0f766e', '#6b21a8', '#94a3b8']

const TRIP_TYPE_LABELS = ['Điểm-điểm', 'Công tác', 'Đưa đón', 'Hàng hóa']
const TRIP_TYPE_COLORS = ['#9b0036', '#1b3a5c', '#b8860b', '#0f766e']
const TYPE_EXPORT_SHORT = ['CT', 'D2D', 'P2P', 'HH']

const QUARTER_RANGES = { q1: [0, 3], q2: [3, 6], q3: [6, 9], q4: [9, 12] }
const MONTH_LABELS = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']

const CHART_GRID = { left: '2%', right: '3%', top: 16, bottom: 28, containLabel: true }
const CHART_AXIS_LABEL_X = { fontSize: 10, color: '#64748b' }
const CHART_AXIS_LABEL_Y = { fontSize: 9, color: '#94a3b8' }
const CHART_AXIS_LINE = { lineStyle: { color: '#e2e8f0' } }
const CHART_SPLIT_LINE = { lineStyle: { color: '#f1f5f9', type: 'dashed' } }

const filters = reactive({
  year: new Date().getFullYear(),
  quarter: '',
  driverId: '',
  vehiclePlate: '',
  tripType: '',
})

const FREQ_FILTER_BAR_VIS_IDS = ['year', 'quarter', 'driverId', 'vehiclePlate', 'tripType']
const FREQ_FILTER_BAR_VIS_DEFAULTS = Object.fromEntries(FREQ_FILTER_BAR_VIS_IDS.map((id) => [id, false]))
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(FREQ_FILTER_BAR_VIS_IDS, FREQ_FILTER_BAR_VIS_DEFAULTS)

const filterMenuRef = ref(null)
const freqFilterBarRef = ref(null)
useDetailsAutoCloseWithin(freqFilterBarRef)

const filterBarVisibilityOptions = computed(() =>
  FREQ_FILTER_BAR_VIS_IDS.map((id) => ({
    id,
    labelKey: `driver_freq.filter_vis_${id}`,
  })),
)

const driverFilterSummary = computed(() => {
  const id = filters.driverId
  if (!id) return ''
  return filterOptions.value.drivers?.find((d) => String(d.id) === String(id))?.name ?? String(id)
})

const tripTypeFilterSummary = computed(() => {
  const hit = TRIP_TYPES.find((tt) => tt.value === filters.tripType)
  return hit?.label ?? filters.tripType
})

function onDriverFreqFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

const selectedDriverCode = ref('')
const reportData = ref(null)
const loading = ref(false)
const loadError = ref('')
const exporting = ref('')

const driversList = computed(() => reportData.value?.drivers ?? [])
const filterOptions = computed(() => reportData.value?.filter_options ?? { drivers: [], vehicles: [] })
const quarterlyData = computed(() => reportData.value?.quarterly ?? [0, 0, 0, 0])
const monthlyData = computed(() => reportData.value?.monthly ?? Array(12).fill(0))
const vehiclesList = computed(() => reportData.value?.vehicles ?? [])

const filteredDrivers = computed(() => {
  if (filters.driverId) {
    return driversList.value.filter((d) => String(d.id) === String(filters.driverId))
  }
  return driversList.value
})

const kpi = computed(() => reportData.value?.kpi ?? EMPTY_KPI)

const yearComparison = computed(() => reportData.value?.year_comparison ?? {})

const yearDeltaLabel = computed(() => {
  const pct = yearComparison.value.trips_delta_pct
  const prev = yearComparison.value.previous_year
  if (pct === null || pct === undefined || !prev) return ''
  const sign = pct > 0 ? '+' : ''
  return t('driver_freq.kpi_total_trips_vs', { pct: `${sign}${pct}`, prev })
})

const yearDeltaClass = computed(() => {
  const pct = yearComparison.value.trips_delta_pct
  if (pct === null || pct === undefined) return 'freq-kpi__badge--muted'
  if (pct >= 0) return 'freq-kpi__badge--up'
  return 'freq-kpi__badge--down'
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.quarter) n++
  if (filters.driverId) n++
  if (filters.vehiclePlate) n++
  if (filters.tripType) n++
  return n
})

function resetFilters() {
  filters.quarter = ''
  filters.driverId = ''
  filters.vehiclePlate = ''
  filters.tripType = ''
  onFilterChange()
}

function tripTypeCounts(driver) {
  return driver.typesExport ?? driver.types ?? [0, 0, 0, 0]
}

function onTimeClass(pct) {
  if (pct >= 92) return 'text-emerald-600 dark:text-emerald-400'
  if (pct >= 86) return 'text-amber-600 dark:text-amber-400'
  return 'text-rose-600 dark:text-rose-400'
}

function buildApiParams() {
  const params = { year: filters.year }
  if (filters.quarter) params.quarter = filters.quarter
  if (filters.driverId) params.driver_id = filters.driverId
  if (filters.vehiclePlate) params.vehicle_plate = filters.vehiclePlate
  if (filters.tripType) params.trip_type = filters.tripType
  return params
}

async function fetchReport() {
  const seq = ++fetchReportSeq
  loading.value = true
  loadError.value = ''
  try {
    const data = await getDriverFrequencyReport(buildApiParams())
    if (seq !== fetchReportSeq) return
    reportData.value = data
  } catch (e) {
    if (seq !== fetchReportSeq) return
    loadError.value = e?.response?.data?.message ?? e?.message ?? 'load_failed'
    reportData.value = {
      kpi: { ...EMPTY_KPI },
      drivers: [],
      vehicles: [],
      quarterly: [0, 0, 0, 0],
      monthly: Array(12).fill(0),
      filter_options: { drivers: [], vehicles: [] },
      year_comparison: {},
    }
  } finally {
    if (seq === fetchReportSeq) loading.value = false
  }
}

let fetchReportSeq = 0
let fetchReportDebounceId = null

function scheduleFetchReport() {
  clearTimeout(fetchReportDebounceId)
  fetchReportDebounceId = setTimeout(() => {
    void fetchReport()
  }, 320)
}

onMounted(() => {
  onDriverFreqFilterBarEnter()
  fetchReport()
})

onActivated(() => {
  onDriverFreqFilterBarEnter()
})
watch(() => ({ ...filters }), scheduleFetchReport, { deep: true })

const chartDriverTrips = computed(() => {
  const list = filteredDrivers.value
  const sel = selectedDriverCode.value
  return {
    tooltip: {
      trigger: 'axis',
      formatter(params) {
        const d = list[params[0].dataIndex]
        if (!d) return ''
        return `<b>${d.name}</b><br/>Chuyến: <b>${d.trips}</b><br/>Giờ lái: ${d.hours}h`
      },
    },
    grid: CHART_GRID,
    xAxis: {
      type: 'category',
      data: list.map((d) => d.code),
      axisLabel: CHART_AXIS_LABEL_X,
      axisLine: CHART_AXIS_LINE,
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      axisLabel: CHART_AXIS_LABEL_Y,
      splitLine: CHART_SPLIT_LINE,
    },
    series: [{
      type: 'bar',
      data: list.map((d, i) => ({
        value: d.trips,
        itemStyle: {
          color: DRIVER_BAR_COLORS[i] ?? '#e6a0b0',
          opacity: sel && sel !== d.code ? 0.3 : 1,
          borderRadius: [4, 4, 0, 0],
        },
      })),
      barMaxWidth: 38,
      emphasis: { focus: 'self' },
    }],
  }
})

const chartVehicleFreq = computed(() => ({
  tooltip: {
    trigger: 'axis',
    formatter: (p) => `Xe <b>${p[0].name}</b><br/>Chuyến: <b>${p[0].value}</b>`,
  },
  grid: CHART_GRID,
  xAxis: {
    type: 'category',
    data: vehiclesList.value.map((v) => v.plate),
    axisLabel: CHART_AXIS_LABEL_X,
    axisLine: CHART_AXIS_LINE,
    axisTick: { show: false },
  },
  yAxis: {
    type: 'value',
    axisLabel: CHART_AXIS_LABEL_Y,
    splitLine: CHART_SPLIT_LINE,
  },
  series: [{
    type: 'bar',
    data: vehiclesList.value.map((v, i) => ({
      value: v.trips,
      itemStyle: { color: VEHICLE_BAR_COLORS[i % VEHICLE_BAR_COLORS.length], borderRadius: [4, 4, 0, 0] },
    })),
    barMaxWidth: 44,
    emphasis: { focus: 'self' },
  }],
}))

const chartQuarterlyTrend = computed(() => {
  const selQ = filters.quarter
  const values = quarterlyData.value
  const seriesData = values.map((val, i) => ({
    value: val,
    itemStyle: { opacity: selQ && `q${i + 1}` !== selQ ? 0.3 : 1 },
  }))
  const minVal = Math.min(...values)
  const yMin = minVal > 0 ? Math.floor(minVal * 0.85) : 0
  return {
    tooltip: {
      trigger: 'axis',
      formatter: (p) => `${p[0].name}<br/><b>${p[0].value} chuyến</b>`,
    },
    grid: { left: '2%', right: '3%', top: 16, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      data: ['Q1', 'Q2', 'Q3', 'Q4'],
      boundaryGap: false,
      axisLabel: { ...CHART_AXIS_LABEL_X, fontSize: 11 },
      axisLine: CHART_AXIS_LINE,
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      min: yMin,
      axisLabel: CHART_AXIS_LABEL_Y,
      splitLine: CHART_SPLIT_LINE,
    },
    series: [{
      type: 'line',
      data: seriesData,
      smooth: true,
      symbol: 'circle',
      symbolSize: 8,
      lineStyle: { color: '#c41849', width: 2.5 },
      itemStyle: { color: '#c41849', borderColor: '#fff', borderWidth: 2 },
      areaStyle: {
        color: {
          type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(196,24,73,0.18)' },
            { offset: 1, color: 'rgba(196,24,73,0.02)' },
          ],
        },
      },
    }],
  }
})

const chartDriverTypes = computed(() => {
  const top5 = filteredDrivers.value.slice(0, 5)
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    legend: {
      data: TRIP_TYPE_LABELS,
      bottom: 0,
      icon: 'roundRect',
      textStyle: { fontSize: 10, color: '#64748b' },
    },
    grid: { left: '2%', right: '3%', top: 10, bottom: 44, containLabel: true },
    xAxis: {
      type: 'category',
      data: top5.map((d) => d.code),
      axisLabel: { ...CHART_AXIS_LABEL_X, fontSize: 11 },
      axisLine: CHART_AXIS_LINE,
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      axisLabel: CHART_AXIS_LABEL_Y,
      splitLine: CHART_SPLIT_LINE,
    },
    series: TRIP_TYPE_LABELS.map((label, ti) => ({
      name: label,
      type: 'bar',
      stack: 'types',
      data: top5.map((d) => (d.types ?? [])[ti] ?? 0),
      itemStyle: { color: TRIP_TYPE_COLORS[ti] },
      emphasis: { focus: 'series' },
      barMaxWidth: 52,
    })),
  }
})

const chartMonthlyActivity = computed(() => {
  const range = filters.quarter ? QUARTER_RANGES[filters.quarter] : [0, 12]
  const labels = MONTH_LABELS.slice(...range)
  const data = monthlyData.value.slice(...range)
  return {
    tooltip: {
      trigger: 'axis',
      formatter: (p) => `${p[0].name}<br/><b>${p[0].value} chuyến</b>`,
    },
    grid: { left: '2%', right: '3%', top: 16, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      data: labels,
      boundaryGap: false,
      axisLabel: CHART_AXIS_LABEL_X,
      axisLine: CHART_AXIS_LINE,
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      axisLabel: CHART_AXIS_LABEL_Y,
      splitLine: CHART_SPLIT_LINE,
    },
    series: [{
      type: 'line',
      data,
      smooth: true,
      symbol: 'circle',
      symbolSize: 5,
      lineStyle: { color: '#1b3a5c', width: 2 },
      itemStyle: { color: '#1b3a5c' },
      areaStyle: {
        color: {
          type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(27,58,92,0.14)' },
            { offset: 1, color: 'rgba(27,58,92,0.01)' },
          ],
        },
      },
    }],
  }
})

function rankBadgeClass(rank) {
  if (rank === 1) return 'bg-amber-400 text-white'
  if (rank === 2) return 'bg-slate-400 text-white'
  if (rank === 3) return 'bg-amber-600/80 text-white'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
}

function toggleSelectedDriver(code) {
  selectedDriverCode.value = selectedDriverCode.value === code ? '' : code
}

function onDriverChartClick(params) {
  if (params.componentType === 'series') {
    toggleSelectedDriver(params.name)
  }
}

function onFilterChange() {
  selectedDriverCode.value = ''
}

async function doExportXlsx() {
  exporting.value = 'xlsx'
  try {
    await downloadDriverFrequencyXlsx(buildApiParams())
  } catch {
    // normalizeAxiosBlobError surfaces message in toast elsewhere if configured
  } finally {
    exporting.value = ''
  }
}

async function doExportPdf() {
  exporting.value = 'pdf'
  try {
    await downloadDriverFrequencyPdf(buildApiParams())
  } catch {
    // ignore
  } finally {
    exporting.value = ''
  }
}
</script>

<style scoped>
.freq-dash {
  --freq-va: #9a0036;
  --freq-header: #3a3a5c;
  @apply text-slate-900 dark:text-slate-100;
}

.freq-btn {
  @apply inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium shadow-sm transition disabled:opacity-50;
}

.freq-btn--xlsx {
  @apply border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200;
}

.freq-btn--pdf {
  @apply border-rose-200 bg-rose-50 text-rose-900 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200;
}

.freq-spinner {
  @apply inline-block size-4 animate-spin rounded-full border-2;
}

.freq-spinner--emerald {
  @apply border-emerald-300 border-t-emerald-700;
}

.freq-spinner--rose {
  @apply border-rose-300 border-t-rose-700;
}

.freq-select {
  @apply h-9 min-w-[5.5rem] cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200;
}

.freq-select--wide {
  @apply min-w-[8.5rem] max-w-[14rem];
}

.freq-clear-filters {
  @apply ml-auto rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-white hover:text-slate-900 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200;
}

.freq-alert {
  @apply rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100;
}

.freq-loading {
  @apply text-sm text-slate-500 dark:text-slate-400;
}

.freq-kpi {
  @apply flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
}

.freq-kpi__label {
  @apply text-xs font-medium text-slate-500 dark:text-slate-400;
}

.freq-kpi__value {
  @apply mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white;
}

.freq-kpi__value--sky { @apply text-sky-700 dark:text-sky-400; }
.freq-kpi__value--amber { @apply text-amber-700 dark:text-amber-400; }

.freq-kpi__hint {
  @apply text-xs text-slate-500 dark:text-slate-400;
}

.freq-kpi__badge {
  @apply mt-1.5 inline-flex w-fit rounded-full px-2 py-0.5 text-[11px] font-semibold;
}

.freq-kpi__badge--up {
  @apply bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300;
}

.freq-kpi__badge--down {
  @apply bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300;
}

.freq-kpi__badge--muted {
  @apply bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300;
}

.freq-kpi__foot {
  @apply mt-1 text-[11px] font-medium;
}

.freq-kpi__foot--sky { @apply text-sky-600 dark:text-sky-400; }
.freq-kpi__foot--amber { @apply text-amber-600 dark:text-amber-400; }
.freq-kpi__foot--emerald { @apply text-emerald-600 dark:text-emerald-400; }

.freq-chart-card {
  @apply overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
}

.freq-chart-title {
  @apply mb-3 border-b border-slate-100 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400;
}

.freq-panel {
  @apply overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
}

.freq-panel__head {
  @apply border-b border-slate-100 px-4 py-3 dark:border-slate-700;
}

.freq-panel__title {
  @apply text-sm font-semibold text-slate-900 dark:text-slate-100;
}

.freq-sheet {
  @apply border-collapse text-left text-sm;
}

.freq-sheet thead {
  background: var(--freq-header);
  @apply text-[11px] font-semibold uppercase tracking-wide text-white;
}

.freq-th {
  @apply border-b border-slate-600/30 px-3 py-2.5 align-top font-semibold;
}

.freq-th--center { @apply text-center; }
.freq-th--right { @apply pr-4 text-right; }

.freq-td {
  @apply border-b border-slate-100 px-3 py-2.5 align-middle text-slate-700 dark:border-slate-800 dark:text-slate-300;
}

.freq-td--center { @apply text-center; }
.freq-td--right { @apply pr-4 text-right; }

.freq-row {
  @apply cursor-pointer transition-colors hover:bg-rose-950/[0.04] dark:hover:bg-rose-950/10;
}

.freq-row--alt {
  @apply bg-slate-50/50 dark:bg-slate-900/25;
}

.freq-row--selected {
  @apply bg-rose-950/[0.05] outline outline-1 -outline-offset-1 outline-rose-900/20;
}

.freq-empty {
  @apply px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400;
}

.freq-rank {
  @apply inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold;
}

.freq-bar-track {
  @apply h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700;
}

.freq-bar-fill {
  @apply h-full rounded-full transition-all duration-500;
  background: var(--freq-va);
}

.freq-type-pill {
  @apply inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300;
}

.freq-footnote {
  @apply border-t border-slate-100 px-4 py-2.5 text-[11px] italic text-slate-400 dark:border-slate-800 dark:text-slate-500;
}
</style>

