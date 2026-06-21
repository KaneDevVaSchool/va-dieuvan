<template>
  <div class="tr-rev space-y-6 pb-14 text-slate-800 dark:text-slate-200">

    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 dark:border-slate-700/80">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
          {{ t('cost_report.hero_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          {{ t('cost_report.hero_sub') }}
        </p>
      </div>
    </div>

    <CostReportFilters
      :filters="filters"
      :filter-control-visible="filterControlVisible"
      :filter-control-defs="filterControlDefs"
      :trip-type-options="tripTypeOptions"
      :status-options="statusOptions"
      :fleet-options="fleetOptions"
      :has-visible-bar-filters="hasVisibleBarFilters"
      :show-filter-panel="showFilterPanelDd"
      :loading="loading"
      :exporting="exporting"
      @export-xlsx="doExportXlsx"
      @export-pdf="doExportPdf"
      @reset-filters="resetFilters"
      @patch-filter="onPatchFilter"
      @toggle-filter-panel="toggleFilterPanel"
      @close-filter-panel="closeFilterPanel"
      @toggle-filter-control="onToggleFilterControl"
    />

    <CostReportSummaryBar
      :stats="stats"
      :loading="loading"
      :top-category="topCategory"
      :top-provider="topProvider"
    />

    <section
      v-if="stats && !loading"
      aria-labelledby="cr-charts"
      class="tr-rev-block tr-rev-block--divider"
    >
      <h2 id="cr-charts" class="tr-rev-block__label">
        {{ t('cost_report.section_charts') }}
      </h2>

      <div class="tr-rev-chart-panel" aria-labelledby="cr-charts-breakdown">
        <p id="cr-charts-breakdown" class="tr-rev-chart-panel__group">
          {{ t('cost_report.section_charts_breakdown') }}
        </p>
        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:divide-x lg:divide-slate-200/80 dark:lg:divide-slate-700">
          <div class="tr-rev-chart-cell">
            <p class="tr-rev-chart-caption">{{ t('cost_report.chart_by_category') }}</p>
            <DashboardEChart :option="chartByCategory" height="240px" :aria-label="t('cost_report.chart_by_category')" />
          </div>
          <div class="tr-rev-chart-cell">
            <p class="tr-rev-chart-caption">{{ t('cost_report.chart_by_status') }}</p>
            <DashboardEChart :option="chartByStatus" height="240px" :aria-label="t('cost_report.chart_by_status')" />
          </div>
        </div>
      </div>

      <div class="tr-rev-chart-panel mt-4" aria-labelledby="cr-charts-compare">
        <p id="cr-charts-compare" class="tr-rev-chart-panel__group">
          {{ t('cost_report.section_charts_compare') }}
        </p>
        <div
          class="grid grid-cols-1 gap-0"
          :class="showTrendChart ? 'lg:grid-cols-2 lg:divide-x lg:divide-slate-200/80 dark:lg:divide-slate-700' : ''"
        >
          <div class="tr-rev-chart-cell">
            <p class="tr-rev-chart-caption">{{ t('cost_report.chart_by_provider') }}</p>
            <DashboardEChart :option="chartByProvider" height="240px" :aria-label="t('cost_report.chart_by_provider')" />
          </div>
          <div v-if="showTrendChart" class="tr-rev-chart-cell">
            <p class="tr-rev-chart-caption">{{ t('cost_report.chart_trend') }}</p>
            <DashboardEChart :option="chartByMonth" height="240px" :aria-label="t('cost_report.chart_trend')" />
          </div>
        </div>
      </div>
    </section>

    <section aria-labelledby="cr-table-title" class="tr-rev-block tr-rev-block--divider">
      <div
        ref="detailTableToolbarRef"
        class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <div class="mb-2 flex flex-wrap items-center gap-2">
            <h2 id="cr-table-title" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ t('cost_report.table_title') }}
              <span class="ml-1 text-xs font-normal text-slate-400">({{ filteredRows.length }})</span>
            </h2>
          </div>
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <DatagridToolbarSearch
                v-model="searchQ"
                input-id="cost-report-list-search"
                :placeholder="t('cost_report.search_placeholder')"
                stretch
                inline-actions
                hide-label
                input-height="h-10"
              />
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <FilterVisibilityDropdown
                :open="showColPanelDd"
                :title="t('cost_report.column_visibility_title')"
                @close="closeColPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="columns"
                    :active="showColPanelDd"
                    test-id="cost-report-toolbar-columns"
                    @click="toggleColPanel"
                  >
                    {{ t('cost_report.toolbar_columns') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="cd in colControlDefs" :key="'cost-report-col-vis-' + cd.id" class="flex items-start gap-2">
                  <input
                    :id="'cost-report-col-vis-' + cd.id"
                    v-model="colVisible[cd.id]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                    :data-testid="`cost-report-col-vis-${cd.id}`"
                  />
                  <label
                    :for="'cost-report-col-vis-' + cd.id"
                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                  >
                    {{ cd.label }}
                  </label>
                </li>
              </FilterVisibilityDropdown>
            </div>
            <div class="ml-auto flex shrink-0 flex-wrap items-center gap-2">
              <details ref="exportMenuRef" class="group relative">
                <summary class="list-none [&::-webkit-details-marker]:hidden">
                  <DatagridToolbarActionButton
                    icon="export"
                    :disabled="!!exporting"
                    test-id="cost-report-toolbar-export"
                    @click.prevent
                  >
                    {{ t('cost_report.toolbar_export') }}
                  </DatagridToolbarActionButton>
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
                  @click.stop
                >
                  <button
                    type="button"
                    class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                    data-testid="cost-report-export-xlsx"
                    :disabled="!!exporting"
                    @click="doExportXlsx"
                  >
                    {{ t('cost_report.btn_export_xlsx') }}
                  </button>
                  <button
                    type="button"
                    class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                    data-testid="cost-report-export-pdf"
                    :disabled="!!exporting"
                    @click="doExportPdf"
                  >
                    {{ t('cost_report.btn_export_pdf') }}
                  </button>
                </div>
              </details>
            </div>
          </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
          <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
          {{ t('cost_report.loading') }}
        </div>
        <div v-else-if="!filteredRows.length" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
          {{ t('cost_report.empty_table') }}
        </div>
        <div v-else class="space-y-3 px-3 py-4 sm:px-4 md:space-y-4">
          <CostReportRecordCard
            v-for="(row, idx) in paginatedRows"
            :key="row.id"
            :row="row"
            :row-no="detailRowNo(idx)"
            :col-visible="colVisible"
            :status-label-fn="statusLabel"
          />
        </div>

        <div v-if="filteredRows.length" class="tr-rev-table-foot">
          <span class="text-sm text-slate-600 dark:text-slate-400">
            {{
              t('cost_report.pagination_of', {
                current: detailPage,
                last: detailLastPage,
              })
            }}
            <span class="text-slate-400"> · </span>
            {{ filteredRows.length }} {{ t('cost_report.pagination_records_suffix') }}
          </span>
          <div class="flex flex-wrap items-center gap-4">
            <span class="text-sm text-slate-500 dark:text-slate-400">
              {{ t('cost_report.total_amount_label') }}:
              <span class="tabular-nums text-teal-800 dark:text-teal-300">{{ formatVnd(totalAmount) }}</span>
            </span>
            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="tr-rev-page-btn"
                :disabled="loading || detailPage <= 1"
                data-testid="cost-report-prev-page"
                @click="detailPageStep(-1)"
              >
                {{ t('cost_report.prev') }}
              </button>
              <button
                type="button"
                class="tr-rev-page-btn"
                :disabled="loading || detailPage >= detailLastPage"
                data-testid="cost-report-next-page"
                @click="detailPageStep(1)"
              >
                {{ t('cost_report.next') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <p v-if="exportError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200">
      {{ exportError }}
    </p>
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CostReportFilters from '../../components/reports/CostReportFilters.vue'
import CostReportSummaryBar from '../../components/reports/CostReportSummaryBar.vue'
import CostReportRecordCard from '../../components/reports/CostReportRecordCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { labelReportProvider } from '../../composables/useCostReportPresentation'
import { getTripCostReport, downloadTripCostXlsx, downloadTripCostPdf } from '../../api/reports'
import { formatVnd, labelTripType } from '../../util/labels'
import { showAppErrorFromApi } from '../../composables/appMessage'

const { t, te } = useI18n()

const TRIP_TYPE_SLUGS = ['point_to_point', 'cargo', 'business', 'door_to_door']
const FLEET_MODES = ['internal', 'vendor_hire', 'taxi', 'unspecified']
const STATUS_FILTER_KEYS = ['draft', 'submitted', 'confirmed', 'rejected', 'estimate']

const CHART_PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#f97316']

const COST_REPORT_COL_VISIBILITY_KEY = 'va.cost_report.col_visibility_v1'
const COST_REPORT_FILTER_VIS_KEY = 'va.cost_report.filter_visibility.v2'

const FILTER_CONTROL_IDS = ['date', 'trip_type', 'status', 'fleet_mode']
const COL_IDS = [
  'unit',
  'category',
  'submitter',
  'description',
  'fleet_source',
  'provider',
  'unit_price',
  'extra_fee',
  'payment',
  'status',
]

const DEFAULT_DETAIL_PER_PAGE = 5

function defaultFilterControlVisibility() {
  return Object.fromEntries(FILTER_CONTROL_IDS.map((id) => [id, false]))
}

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(COST_REPORT_FILTER_VIS_KEY)
    if (!raw) return defaultFilterControlVisibility()
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    for (const id of FILTER_CONTROL_IDS) {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    }
    return base
  } catch {
    return defaultFilterControlVisibility()
  }
}

function defaultColVisibility() {
  return Object.fromEntries(COL_IDS.map((id) => [id, true]))
}

function statusLabel(s) {
  if (!s) return t('cost_report.empty_status')
  if (s === 'estimate') return t('cost_report.badge_estimate')
  const key = `dashboard_analytics.cost_status_${s}`
  return te(key) ? t(key) : s
}

function fleetModeLabel(mode) {
  if (!mode) return t('filter_bar.all')
  const key = `dashboard_analytics.fleet_${mode}`
  return te(key) ? t(key) : mode
}

function tableColLabel(colId) {
  const keys = {
    unit: 'col_unit',
    category: 'col_category',
    submitter: 'col_submitter',
    description: 'col_description',
    fleet_source: 'col_fleet_source',
    provider: 'col_provider',
    unit_price: 'col_unit_price',
    extra_fee: 'col_extra_fee',
    payment: 'col_payment',
    status: 'col_status',
  }
  const k = keys[colId]
  if (k === 'col_status') return t('filter_bar.status')
  return k ? t(`cost_report.${k}`) : colId
}

const loading = ref(false)
const exporting = ref(null)
const exportError = ref('')

/** @type {import('vue').Ref<Record<string, any>|null>} */
const stats = ref(null)
/** @type {import('vue').Ref<Array<Record<string, any>>>} */
const rows = ref([])

const filters = reactive({
  from: '',
  to: '',
  trip_type: '',
  status: '',
  fleet_mode: '',
})

const filterControlVisible = reactive(loadFilterControlVisibility())
const colVisible = reactive(defaultColVisibility())

const searchQ = ref('')
const showFilterPanelDd = ref(false)
const showColPanelDd = ref(false)

const detailPage = ref(1)
const detailPerPage = ref(DEFAULT_DETAIL_PER_PAGE)

const exportMenuRef = ref(null)
const detailTableToolbarRef = ref(null)
useDetailsAutoClose(exportMenuRef)
useDetailsAutoCloseWithin(detailTableToolbarRef)

const filterControlDefs = computed(() => [
  { id: 'date', label: t('cost_report.filter_date') },
  { id: 'trip_type', label: t('cost_report.filter_trip_type') },
  { id: 'status', label: t('filter_bar.status') },
  { id: 'fleet_mode', label: t('dashboard_analytics.filter_fleet') },
])

const colControlDefs = computed(() => COL_IDS.map((id) => ({ id, label: tableColLabel(id) })))

const tripTypeOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...TRIP_TYPE_SLUGS.map((value) => ({ value, label: labelTripType(value) })),
])

const statusOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...STATUS_FILTER_KEYS.map((value) => ({ value, label: statusLabel(value) })),
])

const fleetOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...FLEET_MODES.map((value) => ({ value, label: fleetModeLabel(value) })),
])


const filteredRows = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  const list = rows.value
  if (!q) return list
  return list.filter((row) => {
    const hay = [
      row.description,
      row.submitter,
      row.provider,
      row.fleet_source,
      row.unit,
      labelTripType(row.category),
      statusLabel(row.status),
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  })
})

const hasVisibleBarFilters = computed(() =>
  FILTER_CONTROL_IDS.some((id) => filterControlVisible[id] === true),
)

const detailLastPage = computed(() => {
  const total = filteredRows.value.length
  if (!total) return 1
  return Math.max(1, Math.ceil(total / detailPerPage.value))
})

const paginatedRows = computed(() => {
  const list = filteredRows.value
  if (!list.length) return []
  const page = Math.min(detailPage.value, detailLastPage.value)
  const start = (page - 1) * detailPerPage.value
  return list.slice(start, start + detailPerPage.value)
})

function detailRowNo(idx) {
  const page = Math.min(detailPage.value, detailLastPage.value)
  return (page - 1) * detailPerPage.value + idx + 1
}

const topCategory = computed(() => {
  const list = stats.value?.by_category ?? []
  if (!list.length) return null
  const sorted = [...list].sort((a, b) => b.amount - a.amount)
  return sorted[0]?.label ?? null
})

const topProvider = computed(() => {
  const list = stats.value?.by_provider ?? []
  if (!list.length) return null
  const sorted = [...list].sort((a, b) => b.amount - a.amount)
  const raw = sorted[0]?.label ?? null
  return raw ? labelReportProvider(raw, t, te) : null
})

const totalAmount = computed(() => stats.value?.total_amount ?? 0)

const tooltipMoney = {
  trigger: 'item',
  formatter: (p) => {
    const v = typeof p.value === 'number' ? p.value : (p.data?.value ?? 0)
    return `${p.name}<br/>${Number(v).toLocaleString('vi-VN')} đ`
  },
}

function pieOption(dataList) {
  return {
    tooltip: tooltipMoney,
    legend: { bottom: 0, type: 'scroll', textStyle: { fontSize: 10 } },
    series: [{
      type: 'pie',
      radius: ['35%', '65%'],
      center: ['50%', '44%'],
      data: dataList.map((d, i) => ({ name: d.label, value: d.amount, itemStyle: { color: CHART_PALETTE[i % CHART_PALETTE.length] } })),
      label: { formatter: '{d}%', fontSize: 10 },
      emphasis: { scale: true, scaleSize: 8 },
    }],
  }
}

function barHorizontalOption(dataList, limit = 10) {
  const top = [...dataList].sort((a, b) => b.amount - a.amount).slice(0, limit)
  return {
    tooltip: { trigger: 'axis', formatter: (p) => `${p[0].name}<br/>${Number(p[0].value).toLocaleString('vi-VN')} đ` },
    grid: { left: '4%', right: '6%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { formatter: (v) => `${(v / 1000000).toFixed(0)}M`, fontSize: 9 } },
    yAxis: { type: 'category', data: top.map((d) => d.label), axisLabel: { fontSize: 9, overflow: 'truncate', width: 90 } },
    series: [{ type: 'bar', data: top.map((d, i) => ({ value: d.amount, itemStyle: { color: CHART_PALETTE[i % CHART_PALETTE.length] } })), barMaxWidth: 22 }],
  }
}

function lineOption(dataList) {
  return {
    tooltip: { trigger: 'axis', formatter: (p) => `${p[0].axisValue}<br/>${Number(p[0].value).toLocaleString('vi-VN')} đ` },
    grid: { left: '4%', right: '4%', top: 14, bottom: 24, containLabel: true },
    xAxis: { type: 'category', data: dataList.map((d) => d.key), axisLabel: { fontSize: 9, rotate: 30 } },
    yAxis: { type: 'value', axisLabel: { formatter: (v) => `${(v / 1000000).toFixed(0)}M`, fontSize: 9 } },
    series: [{
      type: 'line',
      data: dataList.map((d) => d.amount),
      smooth: true,
      symbol: 'circle',
      symbolSize: 5,
      areaStyle: { opacity: 0.12 },
      lineStyle: { color: '#9a0036', width: 2 },
      itemStyle: { color: '#9a0036' },
    }],
  }
}

const showTrendChart = computed(() => (stats.value?.by_month?.length ?? 0) > 1)

const chartByCategory = computed(() => pieOption(stats.value?.by_category ?? []))
const chartByStatus = computed(() => pieOption(stats.value?.by_status ?? []))
const chartByProvider = computed(() => {
  const list = (stats.value?.by_provider ?? []).map((d) => ({
    ...d,
    label: labelReportProvider(d.label, t, te),
  }))
  return barHorizontalOption(list)
})
const chartByMonth = computed(() => lineOption(stats.value?.by_month ?? []))

function buildApiParams() {
  const p = {}
  if (filters.from) p.from = filters.from
  if (filters.to) p.to = filters.to
  if (filters.trip_type) p.trip_type = filters.trip_type
  if (filters.status) p.status = filters.status
  if (filters.fleet_mode) p.fleet_mode = filters.fleet_mode
  return p
}

async function reload() {
  loading.value = true
  try {
    const res = await getTripCostReport(buildApiParams())
    stats.value = res.stats
    rows.value = res.rows ?? []
    detailPage.value = 1
  } catch (e) {
    showAppErrorFromApi(e, t('cost_report.load_error'))
  } finally {
    loading.value = false
  }
}

function onPatchFilter(patch) {
  Object.assign(filters, patch)
  onFilterChange()
}

function toggleFilterPanel() {
  showColPanelDd.value = false
  showFilterPanelDd.value = !showFilterPanelDd.value
}

function closeFilterPanel() {
  showFilterPanelDd.value = false
}

function toggleColPanel() {
  showFilterPanelDd.value = false
  showColPanelDd.value = !showColPanelDd.value
}

function closeColPanel() {
  showColPanelDd.value = false
}

function onToggleFilterControl(id, checked) {
  filterControlVisible[id] = checked
  try {
    localStorage.setItem(COST_REPORT_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
  } catch {
    /* ignore */
  }
}

function onFilterChange() {
  detailPage.value = 1
  reload()
}

function resetFilters() {
  filters.from = ''
  filters.to = ''
  filters.trip_type = ''
  filters.status = ''
  filters.fleet_mode = ''
  searchQ.value = ''
  showFilterPanelDd.value = false
  detailPage.value = 1
  reload()
}

function detailPageStep(delta) {
  const next = detailPage.value + delta
  if (next < 1 || next > detailLastPage.value) return
  detailPage.value = next
}

function loadColVisibility() {
  try {
    const raw = localStorage.getItem(COST_REPORT_COL_VISIBILITY_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultColVisibility()
    for (const id of COL_IDS) {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    }
    Object.assign(colVisible, base)
  } catch {
    /* ignore */
  }
}

watch(searchQ, () => {
  detailPage.value = 1
})

watch(
  colVisible,
  (v) => {
    try {
      localStorage.setItem(COST_REPORT_COL_VISIBILITY_KEY, JSON.stringify({ ...v }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

watch(detailLastPage, (last) => {
  if (detailPage.value > last) detailPage.value = last
})

async function doExportXlsx() {
  if (exporting.value) return
  exportError.value = ''
  exporting.value = 'xlsx'
  try {
    await downloadTripCostXlsx(buildApiParams())
  } catch (e) {
    exportError.value = e?.response?.data?.message ?? t('cost_report.export_error')
  } finally {
    exporting.value = null
  }
}

async function doExportPdf() {
  if (exporting.value) return
  exportError.value = ''
  exporting.value = 'pdf'
  try {
    await downloadTripCostPdf(buildApiParams())
  } catch (e) {
    exportError.value = e?.response?.data?.message ?? t('cost_report.export_error')
  } finally {
    exporting.value = null
  }
}

onMounted(() => {
  loadColVisibility()
  reload()
})

onActivated(() => {
  /* filter visibility persisted in localStorage */
})
</script>

<style scoped>
.tr-rev-refresh {
  @apply inline-flex h-9 shrink-0 items-center justify-center rounded-full bg-teal-800 px-4 text-sm font-normal text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600/30 disabled:opacity-50 dark:bg-teal-700 dark:hover:bg-teal-600;
}

.tr-rev-block__label {
  @apply mb-3 text-sm font-normal text-slate-500 dark:text-slate-400;
}

.tr-rev-block--divider {
  @apply border-t border-dashed border-slate-200/90 pt-6 dark:border-slate-700/80;
}

.tr-rev-kpi {
  @apply rounded-xl border border-slate-200/70 bg-gradient-to-br from-white to-slate-50/80 px-4 py-3.5 dark:border-slate-700/80 dark:from-slate-900/60 dark:to-slate-950/40;
  border-left-width: 3px;
}

.tr-rev-kpi--va { border-left-color: rgb(154 0 54 / 0.55); }
.tr-rev-kpi--sky { border-left-color: rgb(14 165 233 / 0.55); }
.tr-rev-kpi--amber { border-left-color: rgb(245 158 11 / 0.55); }
.tr-rev-kpi--emerald { border-left-color: rgb(16 185 129 / 0.55); }

.tr-rev-kpi__label {
  @apply text-xs font-normal text-slate-500 dark:text-slate-400;
}

.tr-rev-kpi__value {
  @apply mt-1 text-2xl font-light tabular-nums tracking-tight text-slate-900 dark:text-slate-50;
}

.tr-rev-kpi__value--sm {
  @apply text-xl;
}

.tr-rev-kpi__text {
  @apply mt-1 text-sm font-normal text-slate-800 dark:text-slate-200;
}

.tr-rev-skel {
  @apply inline-block h-5 w-20 animate-pulse rounded bg-slate-200 dark:bg-slate-700;
}

.tr-rev-skel--wide {
  @apply w-32;
}

.tr-rev-chart-panel {
  @apply overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:border-slate-700 dark:bg-slate-900/40;
}

.tr-rev-chart-panel__group {
  @apply border-b border-slate-100 bg-slate-50/80 px-4 py-2 text-xs font-normal text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400;
}

.tr-rev-chart-cell {
  @apply px-4 py-3;
}

.tr-rev-chart-caption {
  @apply mb-2 text-xs font-normal text-slate-600 dark:text-slate-400;
}

.tr-rev-table-panel {
  @apply overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:border-slate-700 dark:bg-slate-900/40;
}

.tr-rev-table-panel__head {
  @apply flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 px-4 py-3 dark:border-slate-700;
}

.tr-rev-table-panel__title {
  @apply text-sm font-normal text-slate-800 dark:text-slate-100;
}

.tr-rev-table-panel__count {
  @apply ml-1 text-xs text-slate-400;
}

.tr-rev-table-foot {
  @apply flex flex-col gap-3 border-t border-slate-200/80 bg-slate-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/30;
}

.tr-rev-page-btn {
  @apply rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-normal text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800;
}

.tr-rev-badge-estimate {
  @apply mt-0.5 inline-flex rounded-md border border-violet-200/80 bg-violet-50/90 px-1.5 py-px text-[10px] font-normal text-violet-800 dark:border-violet-800/50 dark:bg-violet-950/40 dark:text-violet-200;
}

.cr-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100;
}

.cr-filter-btn {
  @apply flex w-full rounded-lg px-3 py-2 text-left text-sm font-normal text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800;
}

.cr-filter-btn--active {
  @apply bg-teal-50 text-teal-900 dark:bg-teal-950/50 dark:text-teal-100;
}

.cr-sheet {
  @apply border-collapse text-left text-sm font-normal;
}

.cr-sheet thead {
  @apply bg-slate-100/90 text-xs font-normal text-slate-600 dark:bg-slate-800/60 dark:text-slate-400;
}

.cr-th {
  @apply border-b border-slate-200/80 px-3 py-2.5 align-top font-normal dark:border-slate-700;
}

.cr-th--money {
  @apply text-right;
}

.cr-td {
  @apply border-b border-slate-100 px-3 py-2.5 align-top font-normal text-slate-700 dark:border-slate-800 dark:text-slate-300;
}

.cr-data-row {
  @apply transition-colors hover:bg-teal-50/30 dark:hover:bg-teal-950/15;
}

.cr-data-row--alt {
  @apply bg-slate-50/30 dark:bg-slate-900/15;
}

.cr-data-row--estimate {
  @apply bg-violet-50/25 hover:bg-violet-50/40 dark:bg-violet-950/15 dark:hover:bg-violet-950/25;
}

.cr-td--money {
  @apply text-right tabular-nums;
}

.cr-td--revenue {
  @apply text-teal-900 dark:text-teal-200;
}

.cr-pill {
  @apply inline-flex max-w-full items-center rounded-md border border-slate-200/80 bg-white px-2 py-0.5 text-[11px] font-normal text-slate-600 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-300;
}
</style>
