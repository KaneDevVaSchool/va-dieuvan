<template>
  <div class="space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
      {{ loadError }}
    </div>

    <!-- Header kiểu dashboard phân tích -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
          {{ t('dashboard_analytics.title') }}
        </h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
          {{ t('dashboard_analytics.subtitle') }}
        </p>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-500">
          {{ t('dashboard_analytics.data_note') }}
        </p>
      </div>
      <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-end lg:w-auto">
        <label class="flex flex-1 flex-col gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('dashboard_analytics.range_from') }}
          <input
            v-model="rangeFrom"
            type="date"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
          />
        </label>
        <label class="flex flex-1 flex-col gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('dashboard_analytics.range_to') }}
          <input
            v-model="rangeTo"
            type="date"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
          />
        </label>
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
          @click="reloadSummary"
        >
          {{ t('dashboard_analytics.apply_range') }}
        </button>
      </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative max-w-md flex-1">
        <input
          v-model="searchQ"
          type="search"
          :placeholder="t('dashboard_analytics.search_ph')"
          class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-3 text-sm shadow-sm placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
          autocomplete="off"
        />
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.hint_cross') }}
      </p>
    </div>

    <div
      v-if="crossFilter.tripStatus || crossFilter.costType"
      class="flex flex-wrap items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/80 px-3 py-2 text-sm dark:border-indigo-900/50 dark:bg-indigo-950/40"
    >
      <span class="font-medium text-indigo-900 dark:text-indigo-100">{{ t('dashboard_analytics.filter_active') }}:</span>
      <span
        v-if="crossFilter.tripStatus"
        class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-indigo-800 shadow-sm dark:bg-slate-900 dark:text-indigo-200"
      >
        {{ t('dashboard_analytics.filter_trip') }}: {{ labelTripStatus(crossFilter.tripStatus) }}
      </span>
      <span
        v-if="crossFilter.costType"
        class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-indigo-800 shadow-sm dark:bg-slate-900 dark:text-indigo-200"
      >
        {{ t('dashboard_analytics.filter_cost') }}: {{ crossFilter.costType }}
      </span>
      <button
        type="button"
        class="ml-auto text-xs font-semibold text-indigo-700 underline hover:text-indigo-900 dark:text-indigo-300"
        @click="clearCrossFilter"
      >
        {{ t('dashboard_analytics.clear_filters') }}
      </button>
    </div>

    <!-- KPI -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        class="overflow-hidden rounded-2xl shadow-md ring-1 ring-white/10"
        style="background: linear-gradient(145deg, #1a1f36 0%, #252b45 100%)"
      >
        <div class="px-4 py-4 text-white">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-300">
            {{ t('dashboard_analytics.kpi_trips_title') }}
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-bold tabular-nums">{{ effectiveTrips }}</span>
            <span v-if="crossFilter.tripStatus" class="text-xs text-emerald-300">● {{ labelTripStatus(crossFilter.tripStatus) }}</span>
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2 border-t border-white/10 pt-3 text-center text-[11px] text-slate-300">
            <div v-for="row in kpiTripBreakdown" :key="row.key">
              <div class="tabular-nums text-white">{{ row.n }}</div>
              <div class="truncate">{{ row.label }}</div>
            </div>
          </div>
        </div>
      </div>

      <Card :title="t('dashboard_analytics.kpi_cost_title')">
        <div class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ formatMoney(totalConfirmedCost) }}</div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_cost_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_sla_title')">
        <div class="text-2xl font-bold tabular-nums text-rose-600 dark:text-rose-400">{{ summary?.cargo_sla_breaches ?? 0 }}</div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_sla_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_providers_title')">
        <div class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ topProviderName }}</div>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_providers_sub') }}</p>
        <p v-if="topProviderAmount" class="mt-2 text-sm font-medium tabular-nums text-slate-700 dark:text-slate-300">
          {{ formatMoney(topProviderAmount) }}
        </p>
      </Card>
    </div>

    <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">
      {{ t('dashboard_analytics.loading') }}
    </div>

    <!-- Hàng 1: trạng thái + gauge + cảnh báo -->
    <div class="grid gap-4 lg:grid-cols-3">
      <Card :title="t('dashboard_analytics.chart_trips_donut')">
        <DashboardEChart
          height="300px"
          :option="optDonut"
          :aria-label="t('dashboard_analytics.chart_trips_donut')"
          @chart-click="onTripDonutClick"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_fleet_gauge')">
        <DashboardEChart
          height="300px"
          :option="optGauge"
          :aria-label="t('dashboard_analytics.chart_fleet_gauge')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-3 flex items-center justify-between">
          <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('dashboard_analytics.alerts_title') }}</span>
          <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950 dark:text-rose-300">3 {{ t('dashboard_analytics.alerts_new') }}</span>
        </div>
        <ul class="space-y-2 text-sm">
          <li class="rounded-lg border border-rose-100 bg-rose-50/80 px-3 py-2 dark:border-rose-900/40 dark:bg-rose-950/30">
            <div class="font-medium text-rose-900 dark:text-rose-200">{{ t('dashboard_analytics.alert_critical') }}</div>
            <div class="text-xs text-rose-800/90 dark:text-rose-300/90">{{ t('dashboard_analytics.alert_critical_body') }}</div>
          </li>
          <li class="rounded-lg border border-amber-100 bg-amber-50/80 px-3 py-2 dark:border-amber-900/40 dark:bg-amber-950/30">
            <div class="font-medium text-amber-900 dark:text-amber-200">{{ t('dashboard_analytics.alert_warn') }}</div>
            <div class="text-xs text-amber-900/80 dark:text-amber-200/90">{{ t('dashboard_analytics.alert_warn_body') }}</div>
          </li>
          <li class="rounded-lg border border-sky-100 bg-sky-50/80 px-3 py-2 dark:border-sky-900/40 dark:bg-sky-950/30">
            <div class="font-medium text-sky-900 dark:text-sky-200">{{ t('dashboard_analytics.alert_info') }}</div>
            <div class="text-xs text-sky-900/80 dark:text-sky-200/90">{{ t('dashboard_analytics.alert_info_body') }}</div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Chi phí -->
    <div class="grid gap-4 xl:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_costs_bar')">
        <DashboardEChart
          height="280px"
          :option="optCostBar"
          :aria-label="t('dashboard_analytics.chart_costs_bar')"
          @chart-click="onCostBarClick"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_costs_treemap')">
        <DashboardEChart
          height="280px"
          :option="optTreemap"
          :aria-label="t('dashboard_analytics.chart_costs_treemap')"
          @chart-click="onTreemapClick"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
    </div>

    <!-- Mật độ giờ + hoạt động -->
    <div class="grid gap-4 xl:grid-cols-5">
      <div class="xl:col-span-3">
        <Card :title="t('dashboard_analytics.chart_hourly_area')">
          <div class="rounded-lg bg-slate-900 p-1 dark:ring-1 dark:ring-slate-700">
            <DashboardEChart
              height="320px"
              class="[&_.echarts-tooltip]:!text-slate-900"
              :option="optAreaDark"
              :aria-label="t('dashboard_analytics.chart_hourly_area')"
              @chart-dblclick="clearCrossFilter"
            />
          </div>
        </Card>
      </div>
      <div class="xl:col-span-2">
        <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="mb-3 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('dashboard_analytics.activity_title') }}</span>
            <RouterLink class="text-xs font-medium text-sky-600 hover:text-sky-800 dark:text-sky-400" to="/audit-logs">
              {{ t('dashboard_analytics.activity_view_all') }}
            </RouterLink>
          </div>
          <ul class="space-y-3">
            <li
              v-for="item in filteredActivity"
              :key="item.id"
              class="flex gap-3 text-sm"
            >
              <span
                class="mt-0.5 h-8 w-8 shrink-0 rounded-full text-center text-lg leading-8"
                :class="item.iconBg"
              >
                {{ item.icon }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="text-slate-800 dark:text-slate-200">{{ item.text }}</div>
                <div class="text-xs text-slate-500">{{ item.ago }}</div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- NCC + mix xe -->
    <div class="grid gap-4 xl:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_providers')">
        <DashboardEChart
          height="300px"
          :option="optProviders"
          :aria-label="t('dashboard_analytics.chart_providers')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_vehicle_mix')">
        <DashboardEChart
          height="300px"
          :option="optVehicleMix"
          :aria-label="t('dashboard_analytics.chart_vehicle_mix')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
    </div>

    <!-- Sankey + heatmap -->
    <div class="grid gap-4 xl:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_sankey')">
        <DashboardEChart
          height="340px"
          :option="optSankey"
          :aria-label="t('dashboard_analytics.chart_sankey')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_heatmap')">
        <DashboardEChart
          height="340px"
          :option="optHeatmap"
          :aria-label="t('dashboard_analytics.chart_heatmap')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
    </div>

    <!-- Radar + combo -->
    <div class="grid gap-4 xl:grid-cols-2">
      <Card :title="t('dashboard_analytics.chart_radar')">
        <DashboardEChart
          height="320px"
          :option="optRadar"
          :aria-label="t('dashboard_analytics.chart_radar')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
      <Card :title="t('dashboard_analytics.chart_combo')">
        <DashboardEChart
          height="320px"
          :option="optCombo"
          :aria-label="t('dashboard_analytics.chart_combo')"
          @chart-dblclick="clearCrossFilter"
        />
      </Card>
    </div>

    <!-- Thao tác nhanh -->
    <Card :title="t('dashboard_analytics.quick_title')">
      <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
        <RouterLink class="font-medium text-sky-600 underline hover:text-sky-800 dark:text-sky-400" to="/reports">
          {{ t('dashboard_analytics.reports_link') }}
        </RouterLink>
      </p>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/dispatcher">
          <div class="font-semibold text-slate-900 dark:text-white">Bảng điều vận</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Hàng đợi chuyến & lịch tài xế</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/trips">
          <div class="font-semibold text-slate-900 dark:text-white">Danh sách chuyến</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lọc & chi tiết từng chuyến</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/dispatch-requests/new">
          <div class="font-semibold text-slate-900 dark:text-white">Tạo yêu cầu</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">BR-001, preset, idempotency</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/requests">
          <div class="font-semibold text-slate-900 dark:text-white">Danh sách yêu cầu</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Trạng thái & kênh</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/pricing">
          <div class="font-semibold text-slate-900 dark:text-white">Bảng giá tham chiếu</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Xe khách & hàng hóa</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/cargo">
          <div class="font-semibold text-slate-900 dark:text-white">Hàng hóa & SLA</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Theo dõi trễ hạn</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/costs">
          <div class="font-semibold text-slate-900 dark:text-white">Chi phí chuyến</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nhập & đối soát</div>
        </RouterLink>
        <RouterLink class="rounded-lg border border-slate-200 bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800" to="/audit-logs">
          <div class="font-semibold text-slate-900 dark:text-white">Activity log</div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Truy vết thao tác</div>
        </RouterLink>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from '../components/ui/Card.vue'
import DashboardEChart from '../components/dashboard/DashboardEChart.vue'
import { getSummary } from '../api/reports'
import { labelTripStatus } from '../util/labels'
import {
  sumTrips,
  effectiveTripTotal,
  hourlyDensityForTotal,
  tripsStatusDonutOption,
  fleetGaugeOption,
  costsByTypeBarOption,
  providersHorizontalBarOption,
  hourlyAreaOption,
  costsTreemapOption,
  sankeyFlowOption,
  dispatchHeatmapOption,
  opsRadarOption,
  vehicleMixStackedOption,
  fuelComboOption,
} from '../util/transportDashboardCharts'

const { t } = useI18n()

function ymd(d) {
  const x = new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const today = new Date()
const rangeFrom = ref(ymd(new Date(today.getFullYear(), today.getMonth(), 1)))
const rangeTo = ref(ymd(today))

const loading = ref(false)
const loadError = ref('')
const summary = ref(null)
const searchQ = ref('')

const crossFilter = reactive({
  tripStatus: null,
  costType: null,
})

function clearCrossFilter() {
  crossFilter.tripStatus = null
  crossFilter.costType = null
}

function formatMoney(v) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat('vi-VN').format(n) + ' VND'
}

const tripLabelMap = computed(() => {
  const o = summary.value?.trips_by_status ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = labelTripStatus(k)
  }
  return map
})

const effectiveTrips = computed(() =>
  effectiveTripTotal(summary.value?.trips_by_status, crossFilter.tripStatus),
)

const kpiTripBreakdown = computed(() => {
  const raw = summary.value?.trips_by_status ?? {}
  const keys = Object.keys(raw)
  const rows = keys.map((key) => ({
    key,
    label: labelTripStatus(key),
    n: Number(raw[key] ?? 0),
  }))
  rows.sort((a, b) => b.n - a.n)
  const top = rows.slice(0, 3)
  while (top.length < 3) {
    top.push({ key: `pad-${top.length}`, label: '—', n: 0 })
  }
  return top
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

const hourlyPairs = computed(() => hourlyDensityForTotal(effectiveTrips.value))

const chartT = (key) => t(`dashboard_analytics.${key}`)

const optDonut = computed(() =>
  tripsStatusDonutOption({
    tripsByStatus: summary.value?.trips_by_status,
    labelMap: tripLabelMap.value,
    selectedStatus: crossFilter.tripStatus,
  }),
)

const optGauge = computed(() =>
  fleetGaugeOption({
    availabilityPct: 72 + Math.min(18, Math.floor(sumTrips(summary.value?.trips_by_status) / 25)),
    t: chartT,
  }),
)

const optCostBar = computed(() =>
  costsByTypeBarOption({
    costsByType: summary.value?.confirmed_costs_by_type,
    selectedCostType: crossFilter.costType,
    formatMoney,
  }),
)

const optTreemap = computed(() =>
  costsTreemapOption({
    costsByType: summary.value?.confirmed_costs_by_type,
    selectedCostType: crossFilter.costType,
    formatMoney,
  }),
)

const optAreaDark = computed(() => {
  const base = hourlyAreaOption({ hourlyPairs: hourlyPairs.value, t: chartT })
  return {
    ...base,
    backgroundColor: 'transparent',
    xAxis: { ...base.xAxis, axisLabel: { ...base.xAxis.axisLabel, color: '#94a3b8' } },
    yAxis: {
      ...base.yAxis,
      nameTextStyle: { color: '#94a3b8', fontSize: 10 },
      axisLabel: { color: '#94a3b8', fontSize: 10 },
      splitLine: { lineStyle: { color: 'rgba(148,163,184,0.2)' } },
    },
    series: base.series.map((s) => ({
      ...s,
      areaStyle: s.areaStyle,
      lineStyle: { ...s.lineStyle, color: '#60a5fa' },
    })),
  }
})

const optProviders = computed(() =>
  providersHorizontalBarOption({
    rows: summary.value?.confirmed_costs_by_provider,
    formatMoney,
  }),
)

const optVehicleMix = computed(() =>
  vehicleMixStackedOption({ effectiveTotal: effectiveTrips.value, t: chartT }),
)

const optSankey = computed(() => sankeyFlowOption({ t: chartT }))

const optHeatmap = computed(() => dispatchHeatmapOption({ t: chartT }))

const optRadar = computed(() =>
  opsRadarOption({
    slaBreaches: summary.value?.cargo_sla_breaches,
    totalTrips: sumTrips(summary.value?.trips_by_status),
    t: chartT,
  }),
)

const optCombo = computed(() => fuelComboOption({ t: chartT }))

const activitySeed = computed(() => [
  {
    id: '1',
    status: 'completed',
    icon: '✓',
    iconBg: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    text: t('dashboard_analytics.act_1'),
    ago: t('dashboard_analytics.min_ago', { n: 10 }),
  },
  {
    id: '2',
    status: 'in_progress',
    icon: '👤',
    iconBg: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    text: t('dashboard_analytics.act_2'),
    ago: t('dashboard_analytics.min_ago', { n: 45 }),
  },
  {
    id: '3',
    status: 'pending',
    icon: '📄',
    iconBg: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    text: t('dashboard_analytics.act_3'),
    ago: t('dashboard_analytics.hour_ago', { n: 1 }),
  },
  {
    id: '4',
    status: 'completed',
    icon: '🎫',
    iconBg: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    text: t('dashboard_analytics.act_4'),
    ago: t('dashboard_analytics.hour_ago', { n: 2 }),
  },
  {
    id: '5',
    status: 'in_progress',
    icon: '⛽',
    iconBg: 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
    text: t('dashboard_analytics.act_5'),
    ago: t('dashboard_analytics.hour_ago', { n: 3 }),
  },
])

const filteredActivity = computed(() => {
  let list = activitySeed.value
  if (crossFilter.tripStatus) {
    list = list.filter((x) => x.status === crossFilter.tripStatus)
  }
  const q = searchQ.value.trim().toLowerCase()
  if (q) {
    list = list.filter((x) => x.text.toLowerCase().includes(q))
  }
  return list
})

function onTripDonutClick(params) {
  const raw = params?.data?.rawStatus
  if (!raw) return
  crossFilter.tripStatus = crossFilter.tripStatus === raw ? null : raw
}

function onCostBarClick(params) {
  const name = params?.name
  if (!name) return
  crossFilter.costType = crossFilter.costType === name ? null : name
}

function onTreemapClick(params) {
  const name = params?.name ?? params?.data?.name
  if (!name) return
  crossFilter.costType = crossFilter.costType === name ? null : name
}

async function reloadSummary() {
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

onMounted(reloadSummary)
</script>
