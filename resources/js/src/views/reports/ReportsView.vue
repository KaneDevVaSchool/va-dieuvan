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

    <div class="print:hidden">
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
        {{ t('reports_page.hero_title') }}
      </h1>
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
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_sla_breaches') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
            {{ summary?.cargo_sla_breaches ?? 0 }}
          </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_avg_cost') }}
          </div>
          <div class="mt-1 text-xl font-bold tabular-nums text-slate-900">
            {{ avgCostPerTripDisplay }}
          </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs font-medium text-slate-600">
            {{ t('reports_page.kpi_trips') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-slate-900">
            {{ totalTrips }}
          </div>
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
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import TransportReportFilters from '../../components/reports/TransportReportFilters.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import DashboardChartSection from '../../components/dashboard/DashboardChartSection.vue'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'
const { t } = useI18n()

const {
  loading,
  loadError,
  summary,
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
