<template>
  <div class="reports-print space-y-4 md:space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 print:hidden">
      {{ loadError }}
    </div>

    <div
      class="hidden print:mb-6 print:block print:border-b print:border-slate-300 print:pb-4"
    >
      <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">
        {{ t('reports_page.print_doc_label') }}
      </p>
      <h1 class="mt-1 text-xl font-bold text-slate-900">
        {{ t('reports_page.hero_title') }}
      </h1>
      <p class="mt-1 text-sm tabular-nums text-slate-700">
        {{ rangeDisplayFormatted }}
      </p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between print:hidden">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('reports_page.hero_title') }}
        </h1>
        <p class="mt-0.5 text-xs text-slate-600 sm:text-sm">
          {{ t('reports_page.hero_subtitle') }}
        </p>
      </div>
      <div class="flex flex-col items-stretch gap-2 sm:items-end">
        <div class="flex flex-wrap items-center gap-2">
          <RouterLink
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            to="/"
          >
            {{ t('reports_page.link_dashboard') }}
          </RouterLink>
          <Button v-if="summary" variant="secondary" type="button" @click="downloadExcel">
            {{ t('reports_page.export_excel') }}
          </Button>
          <Button v-if="summary" variant="secondary" type="button" @click="printReport">
            {{ t('reports_page.export_pdf') }}
          </Button>
        </div>
        <p class="max-w-md text-xs text-slate-500">
          {{ t('reports_page.export_excel_hint') }}
        </p>
      </div>
    </div>

    <TransportReportFilters class="print:hidden" />

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-600 print:hidden">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300" />
      {{ t('reports_page.loading') }}
    </div>

    <section v-if="summary" class="space-y-2 print:break-inside-avoid" aria-labelledby="rep-kpi">
      <h2 id="rep-kpi" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
        {{ t('reports_page.section_kpi') }}
      </h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_on_time') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
            <template v-if="completionRate != null">{{ completionRate }}%</template>
            <template v-else>—</template>
          </div>
          <p class="mt-1 text-xs text-slate-500 print:hidden">
            {{ t('reports_page.kpi_on_time_hint') }}
          </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_sla_breaches') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
            {{ summary?.cargo_sla_breaches ?? 0 }}
          </div>
          <p class="mt-1 text-xs text-slate-500 print:hidden">
            {{ t('reports_page.kpi_sla_breaches_hint') }}
          </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_avg_cost') }}
          </div>
          <div class="mt-1 text-xl font-bold tabular-nums text-slate-900">
            {{ avgCostPerTripDisplay }}
          </div>
          <p class="mt-1 text-xs text-slate-500 print:hidden">
            {{ t('reports_page.kpi_avg_cost_hint') }}
          </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_trips') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
            {{ totalTrips }}
          </div>
          <p class="mt-1 text-xs text-slate-500 print:hidden">
            {{ t('reports_page.kpi_trips_hint') }}
          </p>
        </div>
      </div>
    </section>

    <section class="space-y-3 border-t border-slate-200 pt-6 md:pt-7 print:border-slate-300" aria-labelledby="rep-section-charts-ops">
      <h2 id="rep-section-charts-ops" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
        {{ t('reports_page.section_charts_trips_requests') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('reports_page.chart_trips_donut')"
          :badge="chartBadgeTripsTotal"
          persist-key="donut-status"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optDonut"
            :aria-label="t('reports_page.chart_trips_donut')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('reports_page.chart_fleet_mode')"
          persist-key="donut-fleet"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optFleet"
            :aria-label="t('reports_page.chart_fleet_mode')"
          />
        </DashboardChartSection>
      </div>

      <DashboardChartSection
        :title="t('reports_page.chart_dispatch_status')"
        persist-key="donut-dispatch"
        :expand-label="t('reports_page.chart_expand')"
        :collapse-label="t('reports_page.chart_collapse')"
      >
        <DashboardEChart
          :height="chartHeight"
          :option="optDispatchDonut"
          :aria-label="t('reports_page.chart_dispatch_status')"
        />
      </DashboardChartSection>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('reports_page.chart_trips_by_plate')"
          persist-key="bar-plates"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeightTall"
            :option="optPlates"
            :aria-label="t('reports_page.chart_trips_by_plate')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('reports_page.chart_top_requesters')"
          persist-key="bar-requesters"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeightTall"
            :option="optRequesters"
            :aria-label="t('reports_page.chart_top_requesters')"
          />
        </DashboardChartSection>
      </div>
    </section>

    <section class="space-y-3 border-t border-slate-200 pt-6 md:pt-7" aria-labelledby="rep-section-charts-time">
      <h2 id="rep-section-charts-time" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
        {{ t('reports_page.section_charts_timing') }}
      </h2>
      <DashboardChartSection
        :title="t('reports_page.chart_hour_line')"
        :hint="`${t('reports_page.chart_hint_hour')} ${t('reports_page.chart_hour_hint')}`"
        persist-key="line-hour"
        :expand-label="t('reports_page.chart_expand')"
        :collapse-label="t('reports_page.chart_collapse')"
      >
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-0.5">
          <DashboardEChart
            :height="chartHeightWide"
            :option="optHourLine"
            :aria-label="t('reports_page.chart_hour_line')"
          />
        </div>
      </DashboardChartSection>
    </section>

    <section class="space-y-3 border-t border-slate-200 pt-6 md:pt-7" aria-labelledby="rep-section-charts-costs">
      <h2 id="rep-section-charts-costs" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
        {{ t('reports_page.section_charts_costs') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('reports_page.chart_costs_bar')"
          persist-key="bar-cost-type"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optCostBar"
            :aria-label="t('reports_page.chart_costs_bar')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('reports_page.chart_costs_pipeline')"
          persist-key="bar-cost-pipeline"
          :expand-label="t('reports_page.chart_expand')"
          :collapse-label="t('reports_page.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optCostPipeline"
            :aria-label="t('reports_page.chart_costs_pipeline')"
          />
        </DashboardChartSection>
      </div>

      <DashboardChartSection
        :title="t('reports_page.chart_providers')"
        persist-key="bar-providers"
        :expand-label="t('reports_page.chart_expand')"
        :collapse-label="t('reports_page.chart_collapse')"
      >
        <DashboardEChart
          :height="chartHeight"
          :option="optProviders"
          :aria-label="t('reports_page.chart_providers')"
        />
      </DashboardChartSection>
    </section>

    <Card v-if="summary" :title="t('reports_page.card_providers')">
      <div v-if="!providerRows.length" class="text-sm text-slate-500">
        {{ t('reports_page.no_data') }}
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-slate-600">
              <th class="py-2 pr-4 font-medium">{{ t('reports_page.col_provider') }}</th>
              <th class="py-2 font-medium tabular-nums">{{ t('reports_page.col_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in providerRows" :key="i" class="border-b border-slate-100">
              <td class="py-2 pr-4 text-slate-800">{{ row.provider }}</td>
              <td class="py-2 tabular-nums font-medium text-slate-900">
                {{ formatMoney(row.total_amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import TransportReportFilters from '../../components/reports/TransportReportFilters.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import DashboardChartSection from '../../components/dashboard/DashboardChartSection.vue'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'
import { labelTripStatus, labelTripType, labelRequestStatus } from '../../util/labels'

const { t, locale } = useI18n()

const {
  loading,
  loadError,
  summary,
  summaryFilters,
  formatMoney,
  totalTrips,
  completionRate,
  chartBadgeTripsTotal,
  chartHeight,
  chartHeightWide,
  chartHeightTall,
  optDonut,
  optFleet,
  optDispatchDonut,
  optHourLine,
  optCostBar,
  optCostPipeline,
  optProviders,
  optRequesters,
  optPlates,
  reloadSummary,
  activeFilterLines,
  currentPresetLabel,
  rangeDisplayFormatted,
} = useTransportReportSummary()

const providerRows = computed(() => {
  const raw = summary.value?.confirmed_costs_by_provider
  if (!raw) return []
  return Array.isArray(raw) ? raw : []
})

const avgCostPerTripDisplay = computed(() => {
  const trips = Number(totalTrips.value ?? 0)
  const raw = summary.value?.confirmed_costs_by_type ?? {}
  const sum = Object.values(raw).reduce((a, b) => a + Number(b ?? 0), 0)
  if (!trips || trips <= 0 || !sum) return '—'
  return formatMoney(sum / trips)
})

function csvEscape(cell) {
  const s = String(cell ?? '')
  if (/[",\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

function printReport() {
  window.dispatchEvent(new Event('resize'))
  nextTick(() => {
    requestAnimationFrame(() => {
      window.print()
    })
  })
}

function downloadExcel() {
  const s = summary.value
  if (!s) return

  const lines = []
  const pushRow = (cells) => lines.push(cells.map(csvEscape).join(','))
  const section = (titleKey) => {
    lines.push('')
    lines.push(`# === ${t(titleKey)} ===`)
  }

  section('reports_page.csv_sheet_meta')
  pushRow([t('reports_page.csv_col_key'), t('reports_page.csv_col_value')])
  pushRow(['generated_at', new Date().toISOString()])
  pushRow(['locale', String(locale.value ?? '')])
  const rng = s.range ?? {}
  pushRow(['range_from', rng.from ?? ''])
  pushRow(['range_to', rng.to ?? ''])
  pushRow(['range_all_time', rng.all_time ? '1' : '0'])

  section('reports_page.csv_sheet_filters')
  pushRow([t('reports_page.csv_col_key'), t('reports_page.csv_col_value')])
  pushRow(['preset_label', currentPresetLabel.value])
  pushRow(['range_display', rangeDisplayFormatted.value])
  const sf = summaryFilters.value
  Object.entries(sf).forEach(([k, v]) => {
    if (v === undefined || v === null || v === '') return
    pushRow([k, String(v)])
  })
  activeFilterLines.value.forEach((row) => {
    pushRow([row.label, row.value])
  })

  section('reports_page.csv_sheet_trips_status')
  pushRow([t('reports_page.csv_col_label'), t('reports_page.csv_col_count')])
  const tbs = s.trips_by_status ?? {}
  Object.entries(tbs).forEach(([k, v]) => {
    pushRow([labelTripStatus(k), String(v)])
  })

  section('reports_page.csv_sheet_trips_fleet')
  pushRow([t('reports_page.csv_col_label'), t('reports_page.csv_col_count')])
  const tbf = s.trips_by_fleet_mode ?? {}
  Object.entries(tbf).forEach(([k, v]) => {
    const labMap = {
      internal: t('reports_page.fleet_internal'),
      vendor_hire: t('reports_page.fleet_vendor_hire'),
      taxi: t('reports_page.fleet_taxi'),
      unspecified: t('reports_page.fleet_unspecified'),
    }
    pushRow([labMap[k] ?? k, String(v)])
  })

  section('reports_page.csv_sheet_trips_type')
  pushRow([t('reports_page.csv_col_label'), t('reports_page.csv_col_count')])
  const tbt = s.trips_by_trip_type ?? {}
  Object.entries(tbt).forEach(([k, v]) => {
    const lab = k === 'unspecified' ? k : labelTripType(k)
    pushRow([lab, String(v)])
  })

  section('reports_page.csv_sheet_trips_hour')
  pushRow([t('reports_page.csv_col_hour'), t('reports_page.csv_col_count')])
  const tbh = s.trips_by_hour
  if (tbh && typeof tbh === 'object' && !Array.isArray(tbh)) {
    for (let h = 0; h < 24; h++) {
      pushRow([String(h), String(tbh[h] ?? 0)])
    }
  }

  section('reports_page.csv_sheet_trip_completion')
  pushRow([t('reports_page.csv_col_metric'), t('reports_page.csv_col_numeric')])
  const tc = s.trip_completion ?? {}
  pushRow(['completed', String(tc.completed ?? '')])
  pushRow(['total', String(tc.total ?? '')])
  pushRow(['rate_pct', tc.rate_pct != null ? String(tc.rate_pct) : ''])

  section('reports_page.csv_sheet_dispatch')
  pushRow([t('reports_page.csv_col_label'), t('reports_page.csv_col_count')])
  const dr = s.dispatch_requests_by_status ?? {}
  Object.entries(dr).forEach(([k, v]) => {
    pushRow([labelRequestStatus(k), String(v)])
  })

  section('reports_page.csv_sheet_costs_type')
  pushRow([t('reports_page.csv_col_label'), t('reports_page.csv_col_amount')])
  const cbt = s.confirmed_costs_by_type ?? {}
  Object.entries(cbt).forEach(([k, v]) => {
    pushRow([k, String(v)])
  })

  section('reports_page.csv_sheet_costs_pipeline')
  pushRow([t('reports_page.csv_col_status'), t('reports_page.csv_col_amount')])
  const cbp = s.costs_by_pipeline_status ?? {}
  const costLab = (k) =>
    ({
      draft: t('reports_page.cost_status_draft'),
      submitted: t('reports_page.cost_status_submitted'),
      confirmed: t('reports_page.cost_status_confirmed'),
      rejected: t('reports_page.cost_status_rejected'),
    })[k] ?? k
  Object.entries(cbp).forEach(([k, v]) => {
    pushRow([costLab(k), String(v)])
  })

  section('reports_page.csv_sheet_providers')
  pushRow([t('reports_page.csv_col_provider'), t('reports_page.csv_col_amount')])
  providerRows.value.forEach((row) => {
    pushRow([row.provider, String(row.total_amount ?? '')])
  })

  section('reports_page.csv_sheet_requesters')
  pushRow([t('reports_page.csv_col_requester'), t('reports_page.csv_col_trips')])
  const tr = s.top_requesters ?? []
  if (Array.isArray(tr)) {
    tr.forEach((row) => {
      pushRow([row.requester_label ?? '', String(row.trip_count ?? '')])
    })
  }

  section('reports_page.csv_sheet_plates')
  pushRow([t('reports_page.csv_col_plate'), t('reports_page.csv_col_count')])
  const tbp = s.trips_by_license_plate ?? {}
  if (tbp && typeof tbp === 'object') {
    Object.entries(tbp).forEach(([plate, c]) => {
      pushRow([plate, String(c)])
    })
  }

  section('reports_page.csv_sheet_distance')
  pushRow([t('reports_page.csv_col_metric'), t('reports_page.csv_col_numeric')])
  pushRow(['trip_records_distance_km', String(s.trip_records_distance_km ?? '')])

  section('reports_page.csv_sheet_sla')
  pushRow([t('reports_page.csv_col_metric'), t('reports_page.csv_col_numeric')])
  pushRow(['cargo_sla_breaches', String(s.cargo_sla_breaches ?? 0)])

  section('reports_page.csv_sheet_compliance')
  pushRow([t('reports_page.csv_col_metric'), t('reports_page.csv_col_numeric')])
  const vc = s.vehicle_compliance ?? {}
  const flatten = (prefix, obj) => {
    if (!obj || typeof obj !== 'object') return
    Object.entries(obj).forEach(([k, v]) => {
      if (v !== null && typeof v === 'object' && !Array.isArray(v)) {
        flatten(`${prefix}${k}.`, v)
      } else {
        pushRow([`${prefix}${k}`, String(v ?? '')])
      }
    })
  }
  flatten('', vc)

  const tag = locale.value === 'vi' ? 'vi' : 'en'
  const from = sf.from || 'all'
  const to = sf.to || 'all'
  const blob = new Blob(['\ufeff', lines.join('\n')], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `${tag}-dispatch-report_${from}_${to}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

onMounted(() => {
  reloadSummary()
})
</script>

<style scoped>
@media print {
  .reports-print {
    max-width: none;
    color: #0f172a;
    background: #fff;
  }
  .reports-print :deep(.overflow-hidden.rounded-xl) {
    break-inside: avoid;
    box-shadow: none !important;
    border-color: #cbd5e1 !important;
  }
  .reports-print :deep(table) {
    border-collapse: collapse;
  }
  .reports-print :deep(th),
  .reports-print :deep(td) {
    border-bottom: 1px solid #e2e8f0;
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
  }
}
@page {
  margin: 14mm 12mm;
  size: A4 portrait;
}
</style>
