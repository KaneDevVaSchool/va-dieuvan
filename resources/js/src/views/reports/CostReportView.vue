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
      <button type="button" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#9b0036] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#7a0029] focus:outline-none focus:ring-2 focus:ring-[#9b0036]/40" @click="onSuggestBonus">
        {{ t('driver_freq.btn_suggest_bonus') }}
        <ArrowTopRightOnSquareIcon class="size-4 shrink-0" aria-hidden="true" />
      </button>
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
      <select v-model="filters.driverCode" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_drivers') }}</option>
        <option v-for="d in MOCK_DRIVERS" :key="d.code" :value="d.code">{{ d.name }}</option>
      </select>
      <select v-model="filters.vehiclePlate" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_vehicles') }}</option>
        <option v-for="v in MOCK_VEHICLES" :key="v.plate" :value="v.plate">{{ v.plate }}</option>
      </select>
      <select v-model="filters.tripType" class="h-9 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-teal-300 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" @change="onFilterChange">
        <option value="">{{ t('driver_freq.all_trip_types') }}</option>
        <option v-for="tt in TRIP_TYPES" :key="tt.value" :value="tt.value">{{ tt.label }}</option>
      </select>
    </div>

    <!-- ============================================================
         KPI CARDS
         ============================================================ -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_trips') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ kpi.totalTrips.toLocaleString('vi-VN') }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_trips_sub', { year: filters.year }) }}</p>
        <span class="mt-1.5 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
          +12% vs 2024
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_drivers') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-sky-700 dark:text-sky-400">{{ kpi.activeDrivers }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_drivers_avg', { avg: kpi.avgTripsPerDriver }) }}</p>
        <span class="mt-1 text-[11px] text-sky-600 dark:text-sky-400">
          ≥ 60 chuyến: {{ kpi.driversAboveThreshold }} người
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_vehicles') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ kpi.activeVehicles }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_active_vehicles_avg', { avg: kpi.avgTripsPerVehicle }) }}</p>
        <span class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
          {{ kpi.vehiclesBelowThreshold }} xe dưới ngưỡng
        </span>
      </div>

      <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_hours') }}</p>
        <p class="mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ kpi.totalHours.toLocaleString('vi-VN') }}h</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('driver_freq.kpi_total_hours_avg', { avg: kpi.avgHoursPerDriver }) }}</p>
        <span class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">
          Đúng giờ {{ kpi.overallOnTime }}%
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
              <tr
                v-for="(driver, idx) in filteredDrivers"
                :key="driver.code"
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
                <p class="text-[10px] text-slate-500">{{ driver.trips }} chuyến · {{ driver.hours }}h</p>
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
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'

const { t } = useI18n()

// ============================================================
// MOCK DATA — replace with getTripFrequencyReport(filters) when API is ready
// ============================================================

const MOCK_DRIVERS = [
  { code: 'NVA', name: 'Nguyễn Văn An',   trips: 142, hours: 312, onTime: 96, kpi: 94, bonus: 'A', types: [40, 35, 50, 17] },
  { code: 'TMĐ', name: 'Trần Minh Đức',   trips: 128, hours: 285, onTime: 93, kpi: 88, bonus: 'B', types: [38, 30, 42, 18] },
  { code: 'LHN', name: 'Lê Hoàng Nam',    trips: 115, hours: 258, onTime: 91, kpi: 83, bonus: 'B', types: [35, 28, 38, 14] },
  { code: 'PVB', name: 'Phạm Văn Bình',   trips:  98, hours: 220, onTime: 89, kpi: 76, bonus: 'B', types: [28, 25, 33, 12] },
  { code: 'VTH', name: 'Võ Thị Hương',    trips:  87, hours: 195, onTime: 95, kpi: 74, bonus: 'C', types: [25, 22, 28, 12] },
  { code: 'ĐQT', name: 'Đặng Quốc Toàn', trips:  79, hours: 178, onTime: 88, kpi: 67, bonus: 'C', types: [22, 20, 26, 11] },
  { code: 'HVT', name: 'Hoàng Văn Tú',    trips:  72, hours: 162, onTime: 86, kpi: 62, bonus: 'C', types: [20, 18, 24, 10] },
  { code: 'NTM', name: 'Nguyễn Thị Mai',  trips:  68, hours: 152, onTime: 90, kpi: 59, bonus: 'C', types: [20, 17, 22,  9] },
  { code: 'TVH', name: 'Trịnh Văn Hùng',  trips:  58, hours: 130, onTime: 84, kpi: 51, bonus: 'C', types: [17, 15, 19,  7] },
]

const MOCK_VEHICLES = [
  { plate: '12345', trips: 185, color: '#1b3a5c' },
  { plate: '67890', trips: 165, color: '#1e4d8c' },
  { plate: '11223', trips: 155, color: '#225499' },
  { plate: '44556', trips: 130, color: '#1b3a5c' },
  { plate: '77889', trips: 120, color: '#b8860b' },
  { plate: '22334', trips:  88, color: '#b8860b' },
  { plate: '55678', trips:  25, color: '#94a3b8' },
]

const MOCK_QUARTERLY = [195, 220, 240, 192]
const MOCK_MONTHLY   = [65, 68, 72, 78, 82, 75, 88, 91, 95, 88, 96, 103]

const MOCK_KPI = {
  totalTrips: 847,
  activeDrivers: 12,
  avgTripsPerDriver: 70.6,
  driversAboveThreshold: 8,
  activeVehicles: 7,
  avgTripsPerVehicle: 121,
  vehiclesBelowThreshold: 2,
  totalHours: 3241,
  avgHoursPerDriver: 270,
  overallOnTime: 91.3,
}

// ============================================================
// CONSTANTS
// ============================================================

const YEAR_OPTIONS = [2025, 2024, 2023]

const TRIP_TYPES = [
  { value: 'point_to_point', label: 'Điểm đến điểm' },
  { value: 'business',       label: 'Công tác' },
  { value: 'door_to_door',   label: 'Đưa đón' },
  { value: 'cargo',          label: 'Hàng hóa' },
]

const DRIVER_BAR_COLORS = [
  '#7a0029', '#9b0036', '#b5103d', '#c03058',
  '#ca506c', '#d47080', '#dd8898', '#e6a0b0', '#eebbca',
]

const DRIVER_AVATAR_COLORS = [
  '#9b0036', '#1b3a5c', '#0f766e', '#6b21a8',
  '#0369a1', '#b45309', '#c41849', '#15803d', '#475569',
]

const TRIP_TYPE_LABELS = ['Điểm-điểm', 'Công tác', 'Đưa đón', 'Hàng hóa']
const TRIP_TYPE_COLORS = ['#9b0036', '#1b3a5c', '#b8860b', '#0f766e']

const QUARTER_RANGES = { q1: [0, 3], q2: [3, 6], q3: [6, 9], q4: [9, 12] }
const MONTH_LABELS = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12']

const CHART_GRID = { left: '2%', right: '3%', top: 16, bottom: 28, containLabel: true }
const CHART_AXIS_LABEL_X = { fontSize: 10, color: '#64748b' }
const CHART_AXIS_LABEL_Y = { fontSize: 9, color: '#94a3b8' }
const CHART_AXIS_LINE = { lineStyle: { color: '#e2e8f0' } }
const CHART_SPLIT_LINE = { lineStyle: { color: '#f1f5f9', type: 'dashed' } }

// ============================================================
// STATE
// ============================================================

const filters = reactive({
  year: 2025,
  quarter: '',
  driverCode: '',
  vehiclePlate: '',
  tripType: '',
})

const selectedDriverCode = ref('')

// ============================================================
// COMPUTED — DATA
// ============================================================

const filteredDrivers = computed(() => {
  if (filters.driverCode) return MOCK_DRIVERS.filter((d) => d.code === filters.driverCode)
  return MOCK_DRIVERS
})

const kpi = computed(() => {
  if (filters.driverCode) {
    const d = MOCK_DRIVERS.find((dr) => dr.code === filters.driverCode)
    if (d) {
      return {
        totalTrips: d.trips,
        activeDrivers: 1,
        avgTripsPerDriver: d.trips,
        driversAboveThreshold: d.trips >= 60 ? 1 : 0,
        activeVehicles: MOCK_KPI.activeVehicles,
        avgTripsPerVehicle: MOCK_KPI.avgTripsPerVehicle,
        vehiclesBelowThreshold: MOCK_KPI.vehiclesBelowThreshold,
        totalHours: d.hours,
        avgHoursPerDriver: d.hours,
        overallOnTime: d.onTime,
      }
    }
  }
  return MOCK_KPI
})

// ============================================================
// COMPUTED — ECHARTS OPTIONS
// ============================================================

const chartDriverTrips = computed(() => {
  const sel = selectedDriverCode.value
  return {
    tooltip: {
      trigger: 'axis',
      formatter(params) {
        const d = MOCK_DRIVERS[params[0].dataIndex]
        return `<b>${d.name}</b><br/>Chuyến: <b>${d.trips}</b><br/>Giờ lái: ${d.hours}h`
      },
    },
    grid: CHART_GRID,
    xAxis: {
      type: 'category',
      data: MOCK_DRIVERS.map((d) => d.code),
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
      data: MOCK_DRIVERS.map((d, i) => ({
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
    data: MOCK_VEHICLES.map((v) => v.plate),
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
    data: MOCK_VEHICLES.map((v) => ({
      value: v.trips,
      itemStyle: { color: v.color, borderRadius: [4, 4, 0, 0] },
    })),
    barMaxWidth: 44,
    emphasis: { focus: 'self' },
  }],
}))

const chartQuarterlyTrend = computed(() => {
  const selQ = filters.quarter
  const seriesData = MOCK_QUARTERLY.map((val, i) => ({
    value: val,
    itemStyle: { opacity: selQ && `q${i + 1}` !== selQ ? 0.3 : 1 },
  }))
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
      min: 150,
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
  const top5 = MOCK_DRIVERS.slice(0, 5)
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
      data: top5.map((d) => d.types[ti]),
      itemStyle: { color: TRIP_TYPE_COLORS[ti] },
      emphasis: { focus: 'series' },
      barMaxWidth: 52,
    })),
  }
})

const chartMonthlyActivity = computed(() => {
  const range = filters.quarter ? QUARTER_RANGES[filters.quarter] : [0, 12]
  const labels = MONTH_LABELS.slice(...range)
  const data = MOCK_MONTHLY.slice(...range)
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

// ============================================================
// HELPERS
// ============================================================

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
  const idx = MOCK_DRIVERS.findIndex((d) => d.code === code)
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

function onSuggestBonus() {
  // placeholder — future: open bonus suggestion modal / navigate
}
</script>

