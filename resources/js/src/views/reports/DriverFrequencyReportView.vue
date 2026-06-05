<template>
  <div class="freq-dash space-y-5 pb-14">

    <!-- ============================================================
         HEADER
         ============================================================ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#9b0036] text-sm font-extrabold tracking-wide text-white shadow-md" aria-hidden="true">VA</div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            {{ t('driver_freq.hero_title') }}
          </h1>
          <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ t('driver_freq.hero_sub') }}
          </p>
        </div>
      </div>
      <div v-if="canExport" class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          :disabled="!!exporting || loading"
          class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900 shadow-sm transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
          @click="doExportXlsx"
        >
          <span v-if="exporting === 'xlsx'" class="inline-block size-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700" />
          <TableCellsIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('driver_freq.btn_export_xlsx') }}
        </button>
        <button
          type="button"
          :disabled="!!exporting || loading"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-900 shadow-sm transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200"
          @click="doExportPdf"
        >
          <span v-if="exporting === 'pdf'" class="inline-block size-4 animate-spin rounded-full border-2 border-rose-300 border-t-rose-700" />
          <DocumentTextIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('driver_freq.btn_export_pdf') }}
        </button>
      </div>
    </div>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/80 bg-gradient-to-r from-slate-50 via-violet-50/30 to-indigo-50/20 px-3 py-2.5 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
      <select v-model.number="filters.year" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option v-for="y in YEAR_OPTIONS" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filters.quarter" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_quarters') }}</option>
        <option value="q1">{{ t('driver_freq.filter_quarter_q1') }}</option>
        <option value="q2">{{ t('driver_freq.filter_quarter_q2') }}</option>
        <option value="q3">{{ t('driver_freq.filter_quarter_q3') }}</option>
        <option value="q4">{{ t('driver_freq.filter_quarter_q4') }}</option>
      </select>
      <select v-model="filters.driverId" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_drivers') }}</option>
        <option v-for="d in filterOptions.drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
      </select>
      <select v-model="filters.vehiclePlate" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_vehicles') }}</option>
        <option v-for="v in filterOptions.vehicles" :key="v.id" :value="v.plate">{{ v.plate }}</option>
      </select>
      <select v-model="filters.tripType" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_trip_types') }}</option>
        <option v-for="tt in TRIP_TYPES" :key="tt.value" :value="tt.value">{{ tt.label }}</option>
      </select>
    </div>

    <!-- ============================================================
         KPI CARDS
         ============================================================ -->
    <p v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('driver_freq.loading') }}</p>
    <div v-else class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_trips') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ kpi.totalTrips.toLocaleString('vi-VN') }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_trips_sub', { year: filters.year }) }}</p>
        <span
          v-if="yearDeltaLabel"
          class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
          :class="yearDeltaClass"
        >
          {{ yearDeltaLabel }}
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_drivers') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-sky-700 dark:text-sky-400">{{ kpi.activeDrivers }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_drivers_avg', { avg: kpi.avgTripsPerDriver }) }}</p>
        <span class="mt-1 text-[11px] text-sky-600 dark:text-sky-400">
          {{ t('driver_freq.kpi_active_drivers_above', { n: kpi.driversAboveThreshold }) }}
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_vehicles') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ kpi.activeVehicles }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_vehicles_avg', { avg: kpi.avgTripsPerVehicle }) }}</p>
        <span class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
          {{ t('driver_freq.kpi_active_vehicles_below', { n: kpi.vehiclesBelowThreshold }) }}
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_hours') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ kpi.totalHours.toLocaleString('vi-VN') }}h</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_hours_avg', { avg: kpi.avgHoursPerDriver }) }}</p>
        <span class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">
          {{ t('driver_freq.kpi_total_hours_ontime', { pct: kpi.overallOnTime }) }}
        </span>
      </div>
    </div>

    <!-- ============================================================
         ROW 1: Two bar charts
         ============================================================ -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('driver_freq.chart_driver_trips') }}</p>
        <DashboardEChart
          :option="chartDriverTrips"
          height="260px"
          :aria-label="t('driver_freq.chart_driver_trips')"
          @chart-click="onDriverChartClick"
        />
      </div>
      <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('driver_freq.chart_vehicle_freq') }}</p>
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
      <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50 lg:col-span-3">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ t('driver_freq.section_ranking') }}
          </h2>
        </div>
        <div class="overflow-x-auto overscroll-x-contain">
          <table class="w-full min-w-[500px] border-collapse text-left text-sm">
            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
              <tr>
                <th class="w-10 border-b border-slate-200/90 px-3 py-2.5 text-center align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_rank') }}</th>
                <th class="min-w-[9rem] border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_driver') }}</th>
                <th class="min-w-[7rem] border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_trips') }}</th>
                <th class="min-w-[5rem] border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_hours') }}</th>
                <th class="min-w-[5rem] border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_ontime') }}</th>
                <th class="min-w-[6rem] border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_trip_types') }}</th>
                <th class="min-w-[4.5rem] border-b border-slate-200/90 px-3 py-2.5 pr-4 text-right align-top font-semibold dark:border-slate-700">{{ t('driver_freq.col_kpi') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && filteredDrivers.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                  {{ t('driver_freq.empty_ranking') }}
                </td>
              </tr>
              <tr
                v-for="(driver, idx) in filteredDrivers"
                :key="driver.id"
                class="cursor-pointer border-b border-slate-100 transition-colors hover:bg-red-950/5 dark:border-slate-800 dark:hover:bg-red-950/10"
                :class="[
                  idx % 2 === 1 ? 'bg-slate-50/40 dark:bg-slate-900/20' : '',
                  selectedDriverCode === driver.code
                    ? 'bg-red-950/5 outline outline-1 -outline-offset-1 outline-red-900/20'
                    : '',
                ]"
                @click="toggleSelectedDriver(driver.code)"
              >
                <td class="px-3 py-2.5 align-middle text-center text-slate-700 dark:text-slate-300">
                  <span
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                    :class="rankBadgeClass(idx + 1)"
                  >{{ idx + 1 }}</span>
                </td>
                <td class="px-3 py-2.5 align-middle font-semibold text-slate-900 dark:text-slate-100">{{ driver.name }}</td>
                <td class="px-3 py-2.5 align-middle text-slate-700 dark:text-slate-300">
                  <div class="flex flex-col gap-1.5">
                    <span class="font-bold tabular-nums">{{ driver.trips }}</span>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                      <div
                        class="h-full rounded-full bg-[#9b0036] transition-all duration-500"
                        :style="{ width: `${(driver.trips / (filteredDrivers[0]?.trips || 1)) * 100}%` }"
                      />
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2.5 align-middle tabular-nums text-slate-600 dark:text-slate-300">{{ driver.hours }}h</td>
                <td class="px-3 py-2.5 align-middle text-slate-700 dark:text-slate-300">
                  <span
                    class="font-semibold"
                    :class="driver.onTime >= 92 ? 'text-emerald-600 dark:text-emerald-400'
                           : driver.onTime >= 86 ? 'text-amber-600 dark:text-amber-400'
                           : 'text-rose-600 dark:text-rose-400'"
                  >{{ driver.onTime }}%</span>
                </td>
                <td class="px-3 py-2.5 align-middle text-slate-700 dark:text-slate-300">
                  <span class="text-[15px] leading-none text-amber-400 dark:text-amber-300">
                    {{ starsForKpi(driver.kpi) }}
                  </span>
                </td>
                <td class="px-3 py-2.5 pr-4 text-right align-middle text-slate-700 dark:text-slate-300">
                  <span
                    class="inline-flex min-w-[2rem] items-center justify-center rounded-full px-2 py-0.5 text-sm font-bold"
                    :class="kpiBadgeClass(driver.kpi)"
                  >{{ driver.kpi }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="px-4 py-2.5 text-[11px] italic text-slate-400 dark:text-slate-500">
          {{ t('driver_freq.kpi_formula') }}
        </p>
      </div>

      <!-- Right column: Bonus panel + Quarterly trend (stacked) -->
      <div class="flex flex-col gap-4 lg:col-span-2">

        <!-- Bonus preview panel -->
        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ t('driver_freq.section_bonus') }}
            </h2>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-slate-700">
            <div
              v-for="driver in filteredDrivers"
              :key="driver.code"
              class="flex items-center gap-3 px-4 py-2 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/40"
            >
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white shadow-sm"
                :style="{ backgroundColor: driverAvatarColor(driver.code) }"
              >{{ driver.code }}</div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">{{ driver.name }}</p>
                <p class="text-[10px] text-slate-500">{{ driver.trips }} {{ t('driver_freq.trips_unit') }} · {{ driver.hours }}{{ t('driver_freq.hours_unit') }}</p>
              </div>
              <div class="flex flex-col items-end gap-0.5">
                <span
                  class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                  :class="bonusTierClass(driver.bonus)"
                >{{ t(`driver_freq.bonus_tier_${driver.bonus.toLowerCase()}`) }}</span>
                <span
                  class="text-xs font-bold tabular-nums"
                  :class="kpiBadgeClass(driver.kpi)"
                >{{ driver.kpi }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Quarterly trend chart -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('driver_freq.chart_quarterly') }}</p>
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
      <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('driver_freq.chart_driver_types') }}</p>
        <DashboardEChart
          :option="chartDriverTypes"
          height="280px"
          :aria-label="t('driver_freq.chart_driver_types')"
        />
      </div>
      <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('driver_freq.chart_monthly') }}</p>
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
import { DocumentTextIcon, TableCellsIcon } from '@heroicons/vue/24/outline'
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

const DRIVER_AVATAR_COLORS = [
  '#9b0036', '#1b3a5c', '#0f766e', '#6b21a8',
  '#0369a1', '#b45309', '#c41849', '#15803d', '#475569',
]

const TRIP_TYPE_LABELS = ['Điểm-điểm', 'Công tác', 'Đưa đón', 'Hàng hóa']
const TRIP_TYPE_COLORS = ['#9b0036', '#1b3a5c', '#b8860b', '#0f766e']

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
  if (pct === null || pct === undefined) return 'bg-slate-100 text-slate-600'
  if (pct >= 0) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
  return 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300'
})

function buildApiParams() {
  const params = { year: filters.year }
  if (filters.quarter) params.quarter = filters.quarter
  if (filters.driverId) params.driver_id = filters.driverId
  if (filters.vehiclePlate) params.vehicle_plate = filters.vehiclePlate
  if (filters.tripType) params.trip_type = filters.tripType
  return params
}

async function fetchReport() {
  loading.value = true
  loadError.value = ''
  try {
    reportData.value = await getDriverFrequencyReport(buildApiParams())
  } catch (e) {
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
    loading.value = false
  }
}

onMounted(fetchReport)
watch(() => ({ ...filters }), fetchReport, { deep: true })

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

function starsForKpi(score) {
  if (score >= 88) return '★★★★★'
  if (score >= 73) return '★★★★☆'
  if (score >= 58) return '★★★☆☆'
  return '★★☆☆☆'
}

function rankBadgeClass(rank) {
  if (rank === 1) return 'bg-amber-400 text-white'
  if (rank === 2) return 'bg-slate-400 text-white'
  if (rank === 3) return 'bg-amber-600/80 text-white'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
}

function kpiBadgeClass(score) {
  if (score >= 88) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'
  if (score >= 70) return 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300'
  if (score >= 55) return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function bonusTierClass(tier) {
  if (tier === 'A') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300'
  if (tier === 'B') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function driverAvatarColor(code) {
  const idx = driversList.value.findIndex((d) => d.code === code)
  return idx >= 0 ? (DRIVER_AVATAR_COLORS[idx] ?? '#475569') : '#475569'
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

