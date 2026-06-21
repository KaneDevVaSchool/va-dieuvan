<template>
  <div class="freq-dash space-y-5 pb-14">

    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-700/80">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
          {{ t('driver_freq.hero_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          {{ t('driver_freq.hero_sub') }}
        </p>
      </div>
    </div>

    <DriverFrequencyFilters
      :filters="filters"
      :filter-control-visible="filterControlVisible"
      :filter-control-defs="filterControlDefs"
      :year-options="YEAR_OPTIONS"
      :driver-options="filterOptions.drivers"
      :vehicle-options="filterOptions.vehicles"
      :trip-type-options="tripTypeOptions"
      :has-visible-bar-filters="hasVisibleBarFilters"
      :show-filter-panel="showFilterPanelDd"
      :loading="loading"
      :can-export="canExport"
      :exporting="exporting"
      @export-xlsx="doExportXlsx"
      @export-pdf="doExportPdf"
      @reload="fetchReport"
      @reset-filters="resetFilters"
      @patch-filter="onPatchFilter"
      @toggle-filter-panel="toggleFilterPanel"
      @close-filter-panel="closeFilterPanel"
      @toggle-filter-control="onToggleFilterControl"
    />

    <div
      v-if="loadError"
      role="alert"
      class="freq-alert"
    >
      {{ t('driver_freq.load_error') }}
    </div>

    <DriverFrequencySummaryBar
      :kpi="kpi"
      :loading="loading"
      :year="filters.year"
      :year-delta-label="yearDeltaLabel"
    />

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
      <div ref="rankingToolbarRef" class="freq-panel lg:col-span-3">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <div class="mb-2 flex flex-wrap items-center gap-2">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ t('driver_freq.section_ranking') }}
              <span v-if="filteredDrivers.length" class="ml-1 text-xs font-normal text-slate-400">({{ filteredDrivers.length }})</span>
            </h2>
          </div>
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <DatagridToolbarSearch
                v-model="searchQ"
                input-id="driver-freq-ranking-search"
                :placeholder="t('driver_freq.search_placeholder')"
                stretch
                inline-actions
                hide-label
                input-height="h-10"
              />
            </div>
            <div v-if="canExport" class="ml-auto flex shrink-0 items-center gap-2">
              <details ref="exportMenuRef" class="group relative">
                <summary class="list-none [&::-webkit-details-marker]:hidden">
                  <DatagridToolbarActionButton
                    icon="export"
                    :disabled="!!exporting || loading"
                    test-id="driver-freq-toolbar-export"
                    @click.prevent
                  >
                    {{ t('driver_freq.toolbar_export') }}
                  </DatagridToolbarActionButton>
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
                  @click.stop
                >
                  <button
                    type="button"
                    class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                    data-testid="driver-freq-export-xlsx"
                    :disabled="!!exporting || loading"
                    @click="doExportXlsx"
                  >
                    {{ t('driver_freq.btn_export_xlsx') }}
                  </button>
                  <button
                    type="button"
                    class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                    data-testid="driver-freq-export-pdf"
                    :disabled="!!exporting || loading"
                    @click="doExportPdf"
                  >
                    {{ t('driver_freq.btn_export_pdf') }}
                  </button>
                </div>
              </details>
            </div>
          </div>
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DriverFrequencyFilters from '../../components/reports/DriverFrequencyFilters.vue'
import DriverFrequencySummaryBar from '../../components/reports/DriverFrequencySummaryBar.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
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

const TRIP_TYPE_SLUGS = ['point_to_point', 'business', 'door_to_door', 'cargo']

const FILTER_CONTROL_IDS = ['year', 'quarter', 'driverId', 'vehiclePlate', 'tripType']
const DF_FILTER_VIS_KEY = 'driver-freq-report-filter-vis.v2'

function loadFilterControlVisibility() {
  const defaults = {
    year: true,
    quarter: false,
    driverId: false,
    vehiclePlate: false,
    tripType: false,
  }
  try {
    const raw = localStorage.getItem(DF_FILTER_VIS_KEY)
    if (raw) {
      const parsed = JSON.parse(raw)
      return { ...defaults, ...parsed }
    }
  } catch {
    /* ignore */
  }
  return { ...defaults }
}

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

const filterControlVisible = reactive(loadFilterControlVisibility())
const showFilterPanelDd = ref(false)
const searchQ = ref('')
const exportMenuRef = ref(null)
const rankingToolbarRef = ref(null)
useDetailsAutoClose(exportMenuRef)
useDetailsAutoCloseWithin(rankingToolbarRef)

const filterControlDefs = computed(() => [
  { id: 'year', label: t('driver_freq.filter_vis_year') },
  { id: 'quarter', label: t('driver_freq.filter_vis_quarter') },
  { id: 'driverId', label: t('driver_freq.filter_vis_driverId') },
  { id: 'vehiclePlate', label: t('driver_freq.filter_vis_vehiclePlate') },
  { id: 'tripType', label: t('driver_freq.filter_vis_tripType') },
])

const tripTypeOptions = computed(() => [
  { value: '', label: t('driver_freq.filter_trip_type') },
  ...TRIP_TYPE_SLUGS.map((value) => ({
    value,
    label: t(`notify.trip_type_${value}`),
  })),
])

const hasVisibleBarFilters = computed(() =>
  FILTER_CONTROL_IDS.some((id) => filterControlVisible[id] === true),
)

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
  let list = driversList.value
  if (filters.driverId) {
    list = list.filter((d) => String(d.id) === String(filters.driverId))
  }
  const q = searchQ.value.trim().toLowerCase()
  if (q) {
    list = list.filter((d) => {
      const hay = [d.name, d.code, d.employeeCode].filter(Boolean).join(' ').toLowerCase()
      return hay.includes(q)
    })
  }
  return list
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

function onPatchFilter(patch) {
  Object.assign(filters, patch)
  onFilterChange()
}

function toggleFilterPanel() {
  showFilterPanelDd.value = !showFilterPanelDd.value
}

function closeFilterPanel() {
  showFilterPanelDd.value = false
}

function onToggleFilterControl(id, checked) {
  filterControlVisible[id] = checked
  try {
    localStorage.setItem(DF_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
  } catch {
    /* ignore */
  }
}

function resetFilters() {
  filters.quarter = ''
  filters.driverId = ''
  filters.vehiclePlate = ''
  filters.tripType = ''
  searchQ.value = ''
  showFilterPanelDd.value = false
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
  fetchReport()
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

.freq-alert {
  @apply rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100;
}

.freq-chart-card {
  @apply overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
}

.freq-chart-title {
  @apply mb-3 border-b border-slate-100 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400;
}

.freq-panel {
  @apply overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
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

