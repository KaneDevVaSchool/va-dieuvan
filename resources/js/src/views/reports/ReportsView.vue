<template>
  <div class="space-y-4 md:space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
      {{ loadError }}
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('reports_page.hero_title') }}
        </h1>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
          {{ t('reports_page.hero_subtitle') }}
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          to="/"
        >
          {{ t('reports_page.link_dashboard') }}
        </RouterLink>
        <Button
          v-if="summary"
          variant="secondary"
          type="button"
          class="border-teal-200/80 bg-teal-50/80 text-teal-900 hover:bg-teal-50 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-100 dark:hover:bg-teal-950/60"
          @click="downloadCsv"
        >
          {{ t('reports_page.csv') }}
        </Button>
      </div>
    </div>

    <TransportReportFilters />

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <section v-if="summary" class="space-y-2" aria-labelledby="rep-kpi">
      <h2 id="rep-kpi" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('reports_page.section_kpi') }}
      </h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          class="rounded-xl border border-slate-200/90 bg-gradient-to-br from-slate-900 to-slate-800 p-4 text-white shadow-lg shadow-slate-900/20 dark:from-slate-950 dark:to-slate-900 dark:border-slate-700"
        >
          <div class="text-[11px] font-medium uppercase tracking-wide text-teal-300/90">
            {{ t('reports_page.kpi_on_time') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums">
            <template v-if="completionRate != null">{{ completionRate }}%</template>
            <template v-else>—</template>
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            {{ t('reports_page.kpi_on_time_hint') }}
          </p>
        </div>
        <div
          class="rounded-xl border border-slate-200/90 bg-gradient-to-br from-slate-900 to-slate-800 p-4 text-white shadow-lg shadow-slate-900/20 dark:from-slate-950 dark:to-slate-900 dark:border-slate-700"
        >
          <div class="text-[11px] font-medium uppercase tracking-wide text-violet-300/90">
            {{ t('reports_page.kpi_sla_breaches') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums text-rose-300">
            {{ summary?.cargo_sla_breaches ?? 0 }}
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            {{ t('reports_page.kpi_sla_breaches_hint') }}
          </p>
        </div>
        <div
          class="rounded-xl border border-slate-200/90 bg-gradient-to-br from-slate-900 to-slate-800 p-4 text-white shadow-lg shadow-slate-900/20 dark:from-slate-950 dark:to-slate-900 dark:border-slate-700"
        >
          <div class="text-[11px] font-medium uppercase tracking-wide text-amber-300/90">
            {{ t('reports_page.kpi_avg_cost') }}
          </div>
          <div class="mt-1 text-xl font-bold tabular-nums">
            {{ avgCostPerTripDisplay }}
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            {{ t('reports_page.kpi_avg_cost_hint') }}
          </p>
        </div>
        <div
          class="rounded-xl border border-slate-200/90 bg-gradient-to-br from-slate-900 to-slate-800 p-4 text-white shadow-lg shadow-slate-900/20 dark:from-slate-950 dark:to-slate-900 dark:border-slate-700"
        >
          <div class="text-[11px] font-medium uppercase tracking-wide text-emerald-300/90">
            {{ t('reports_page.kpi_trips') }}
          </div>
          <div class="mt-1 text-2xl font-bold tabular-nums">
            {{ totalTrips }}
          </div>
          <p class="mt-1 text-[11px] text-slate-400">
            {{ t('reports_page.kpi_trips_hint') }}
          </p>
        </div>
      </div>
    </section>

    <p v-if="summary" class="text-center text-[11px] text-slate-400 dark:text-slate-500">
      {{ t('dashboard_analytics.chart_toolbar_hint') }}
    </p>

    <section class="space-y-3 border-t border-slate-200/80 pt-6 md:pt-7 dark:border-slate-800" aria-labelledby="rep-section-charts-ops">
      <h2 id="rep-section-charts-ops" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.section_charts_trips_requests') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_trips_donut')"
          :hint="t('dashboard_analytics.chart_hint_trips_donut')"
          :badge="chartBadgeTripsTotal"
          persist-key="donut-status"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optDonut"
            :aria-label="t('dashboard_analytics.chart_trips_donut')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_fleet_mode')"
          :hint="t('dashboard_analytics.chart_hint_fleet')"
          persist-key="donut-fleet"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optFleet"
            :aria-label="t('dashboard_analytics.chart_fleet_mode')"
          />
        </DashboardChartSection>
      </div>

      <DashboardChartSection
        :title="t('dashboard_analytics.chart_dispatch_status')"
        :hint="t('dashboard_analytics.chart_hint_dispatch')"
        persist-key="donut-dispatch"
        :expand-label="t('dashboard_analytics.chart_expand')"
        :collapse-label="t('dashboard_analytics.chart_collapse')"
      >
        <DashboardEChart
          :height="chartHeight"
          :option="optDispatchDonut"
          :aria-label="t('dashboard_analytics.chart_dispatch_status')"
        />
      </DashboardChartSection>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_trips_by_plate')"
          :hint="t('dashboard_analytics.chart_hint_plates')"
          persist-key="bar-plates"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeightTall"
            :option="optPlates"
            :aria-label="t('dashboard_analytics.chart_trips_by_plate')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_top_requesters')"
          :hint="t('dashboard_analytics.chart_hint_requesters')"
          persist-key="bar-requesters"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeightTall"
            :option="optRequesters"
            :aria-label="t('dashboard_analytics.chart_top_requesters')"
          />
        </DashboardChartSection>
      </div>
    </section>

    <section class="space-y-3 border-t border-slate-200/80 pt-6 md:pt-7 dark:border-slate-800" aria-labelledby="rep-section-charts-time">
      <h2 id="rep-section-charts-time" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.section_charts_timing') }}
      </h2>
      <DashboardChartSection
        :title="t('dashboard_analytics.chart_hour_line')"
        :hint="`${t('dashboard_analytics.chart_hint_hour')} ${t('dashboard_analytics.chart_hour_hint')}`"
        persist-key="line-hour"
        :expand-label="t('dashboard_analytics.chart_expand')"
        :collapse-label="t('dashboard_analytics.chart_collapse')"
      >
        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-0.5 dark:border-slate-700 dark:bg-slate-950/40">
          <DashboardEChart
            :height="chartHeightWide"
            :option="optHourLine"
            :aria-label="t('dashboard_analytics.chart_hour_line')"
          />
        </div>
      </DashboardChartSection>
    </section>

    <section class="space-y-3 border-t border-slate-200/80 pt-6 md:pt-7 dark:border-slate-800" aria-labelledby="rep-section-charts-costs">
      <h2 id="rep-section-charts-costs" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.section_charts_costs') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_costs_bar')"
          :hint="t('dashboard_analytics.chart_hint_costs_bar')"
          persist-key="bar-cost-type"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optCostBar"
            :aria-label="t('dashboard_analytics.chart_costs_bar')"
          />
        </DashboardChartSection>
        <DashboardChartSection
          :title="t('dashboard_analytics.chart_costs_pipeline')"
          :hint="t('dashboard_analytics.chart_hint_cost_pipeline')"
          persist-key="bar-cost-pipeline"
          :expand-label="t('dashboard_analytics.chart_expand')"
          :collapse-label="t('dashboard_analytics.chart_collapse')"
        >
          <DashboardEChart
            :height="chartHeight"
            :option="optCostPipeline"
            :aria-label="t('dashboard_analytics.chart_costs_pipeline')"
          />
        </DashboardChartSection>
      </div>

      <DashboardChartSection
        :title="t('dashboard_analytics.chart_providers')"
        :hint="t('dashboard_analytics.chart_hint_providers')"
        persist-key="bar-providers"
        :expand-label="t('dashboard_analytics.chart_expand')"
        :collapse-label="t('dashboard_analytics.chart_collapse')"
      >
        <DashboardEChart
          :height="chartHeight"
          :option="optProviders"
          :aria-label="t('dashboard_analytics.chart_providers')"
        />
      </DashboardChartSection>
    </section>

    <Card v-if="summary" :title="t('reports_page.card_providers')">
      <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
        {{ t('reports_page.providers_hint') }}
      </p>
      <div v-if="!providerRows.length" class="text-sm text-slate-500 dark:text-slate-400">
        {{ t('reports_page.no_data') }}
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500 dark:border-slate-700 dark:text-slate-400">
              <th class="py-2 pr-4 font-medium">{{ t('reports_page.col_provider') }}</th>
              <th class="py-2 font-medium tabular-nums">{{ t('reports_page.col_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, i) in providerRows"
              :key="i"
              class="border-b border-slate-100 dark:border-slate-800"
            >
              <td class="py-2 pr-4 text-slate-800 dark:text-slate-200">{{ row.provider }}</td>
              <td class="py-2 tabular-nums font-medium text-slate-900 dark:text-slate-100">
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
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import TransportReportFilters from '../../components/reports/TransportReportFilters.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import DashboardChartSection from '../../components/dashboard/DashboardChartSection.vue'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'

const { t } = useI18n()

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

function downloadCsv() {
  if (!summary.value) return
  const lines = []
  const r = summary.value.range ?? {}
  lines.push(['section', 'key', 'value'].map(csvEscape).join(','))
  lines.push(['meta', 'from', r.from ?? ''].map(csvEscape).join(','))
  lines.push(['meta', 'to', r.to ?? ''].map(csvEscape).join(','))

  const trips = summary.value.trips_by_status ?? {}
  Object.entries(trips).forEach(([k, v]) => {
    lines.push(['trips_by_status', k, v].map(csvEscape).join(','))
  })

  const types = summary.value.confirmed_costs_by_type ?? {}
  Object.entries(types).forEach(([k, v]) => {
    lines.push(['confirmed_costs_by_type', k, v].map(csvEscape).join(','))
  })

  providerRows.value.forEach((row) => {
    lines.push(['confirmed_costs_by_provider', row.provider, row.total_amount].map(csvEscape).join(','))
  })

  lines.push(['cargo_sla_breaches', 'count', summary.value.cargo_sla_breaches ?? 0].map(csvEscape).join(','))

  const blob = new Blob(['\ufeff', lines.join('\n')], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  const sf = summaryFilters.value
  a.href = URL.createObjectURL(blob)
  a.download = `bao-cao-dieu-van-${sf.from || 'tu'}_${sf.to || 'den'}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

onMounted(() => {
  reloadSummary()
})
</script>
