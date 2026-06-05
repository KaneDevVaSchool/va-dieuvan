<template>
  <div class="cost-report-page space-y-5 pb-14 text-slate-900 dark:text-slate-100">

    <!-- â”€â”€ PAGE HEADING â”€â”€ -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('cost_report.hero_title') }}
        </h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          {{ t('cost_report.hero_sub') }}
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          :disabled="exporting"
          class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900 shadow-sm transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-900/40"
          @click="doExportXlsx"
        >
          <span v-if="exporting === 'xlsx'" class="inline-block size-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700" />
          <span v-else class="text-base leading-none">ðŸ“Š</span>
          {{ t('cost_report.btn_export_xlsx') }}
        </button>
        <button
          type="button"
          :disabled="exporting"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-900 shadow-sm transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200 dark:hover:bg-rose-900/40"
          @click="doExportPdf"
        >
          <span v-if="exporting === 'pdf'" class="inline-block size-4 animate-spin rounded-full border-2 border-rose-300 border-t-rose-700" />
          <span v-else class="text-base leading-none">ðŸ“„</span>
          {{ t('cost_report.btn_export_pdf') }}
        </button>
      </div>
    </div>

    <!-- â”€â”€ FILTER TOOLBAR â”€â”€ -->
    <AppFilterBar>
      <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">

        <!-- Funnel summary â”€ -->
        <details ref="funnelRef" class="group relative">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              <span
                v-if="activeFilterCount > 0"
                class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
              >{{ activeFilterCount }}</span>
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30">
            <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
              {{ t('dashboard_analytics.filter_applied_title') }}
            </p>
            <div class="p-3 pt-2">
              <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li v-if="filters.trip_type" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cost_report.filter_trip_type') }}</span>
                  <span class="font-medium">{{ TRIP_TYPE_LABELS[filters.trip_type] }}</span>
                </li>
                <li v-if="filters.status" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
                  <span class="font-medium">{{ STATUS_LABELS[filters.status] ?? filters.status }}</span>
                </li>
                <li v-if="filters.fleet_mode" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.filter_fleet') }}</span>
                  <span class="font-medium">{{ FLEET_LABELS[filters.fleet_mode] }}</span>
                </li>
                <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cost_report.filter_date') }}</span>
                  <span class="font-medium">{{ filters.from || 'â€¦' }} â†’ {{ filters.to || 'â€¦' }}</span>
                </li>
                <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
              </ul>
              <button
                type="button"
                class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="resetFilters"
              >{{ t('dashboard_analytics.filter_clear_all') }}</button>
            </div>
          </div>
        </details>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <!-- Date range â”€ -->
          <AppFilterDropdown
            root-class="shrink-0"
            show-chip-label
            :label="t('cost_report.filter_date')"
            :summary-text="filterDateSummary"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input v-model="filters.from" type="date" class="cr-input h-9 w-full text-sm sm:w-auto" @change="onFilterChange" />
              <span class="hidden text-slate-300 sm:inline dark:text-slate-600">â€”</span>
              <input v-model="filters.to" type="date" class="cr-input h-9 w-full text-sm sm:w-auto" @change="onFilterChange" />
            </div>
          </AppFilterDropdown>

          <!-- Trip type â”€ -->
          <AppFilterDropdown
            root-class="shrink-0"
            show-chip-label
            :label="t('cost_report.filter_trip_type')"
            :summary-text="filters.trip_type ? TRIP_TYPE_LABELS[filters.trip_type] : t('filter_bar.all')"
            panel-class="min-w-[200px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in tripTypeOptions" :key="opt.value || '_all'">
                <button type="button" class="cr-filter-btn" :class="filters.trip_type === opt.value ? 'cr-filter-btn--active' : ''" @click="applyFilter($event, { trip_type: opt.value })">
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <!-- Status â”€ -->
          <AppFilterDropdown
            root-class="shrink-0"
            show-chip-label
            :label="t('filter_bar.status')"
            :summary-text="filters.status ? (STATUS_LABELS[filters.status] ?? filters.status) : t('filter_bar.all')"
            panel-class="min-w-[200px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in statusOptions" :key="opt.value || '_all'">
                <button type="button" class="cr-filter-btn" :class="filters.status === opt.value ? 'cr-filter-btn--active' : ''" @click="applyFilter($event, { status: opt.value })">
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <!-- Fleet mode â”€ -->
          <AppFilterDropdown
            root-class="shrink-0"
            show-chip-label
            :label="t('dashboard_analytics.filter_fleet')"
            :summary-text="filters.fleet_mode ? FLEET_LABELS[filters.fleet_mode] : t('filter_bar.all')"
            panel-class="min-w-[200px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in fleetOptions" :key="opt.value || '_all'">
                <button type="button" class="cr-filter-btn" :class="filters.fleet_mode === opt.value ? 'cr-filter-btn--active' : ''" @click="applyFilter($event, { fleet_mode: opt.value })">
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>
        </div>

        <!-- Clear + refresh â”€ -->
        <div class="ml-auto flex shrink-0 items-center gap-1 pl-2 sm:gap-2 sm:pl-3">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
            :title="t('filter_bar.clear_icon')"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40" />
            </span>
          </button>
          <button
            type="button"
            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-va-800 px-3 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35 dark:ring-white/10"
            :disabled="loading"
            @click="reload"
          >
            <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            <template v-else>{{ t('cost_report.btn_refresh') }}</template>
          </button>
        </div>
      </div>
    </AppFilterBar>

    <!-- â”€â”€ KPI CARDS â”€â”€ -->
    <section aria-labelledby="cr-kpi">
      <h2 id="cr-kpi" class="mb-3 px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('cost_report.section_kpi') }}
      </h2>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('cost_report.kpi_total_amount') }}</div>
          <div class="mt-1.5 text-xl font-bold tabular-nums text-slate-900 dark:text-white">
            <template v-if="stats">{{ formatVnd(stats.total_amount) }}</template>
            <template v-else-if="loading"><span class="inline-block h-6 w-28 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></template>
            <template v-else>â€”</template>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('cost_report.kpi_count') }}</div>
          <div class="mt-1.5 text-xl font-bold tabular-nums text-slate-900 dark:text-white">
            <template v-if="stats">{{ stats.count.toLocaleString('vi-VN') }}</template>
            <template v-else-if="loading"><span class="inline-block h-6 w-16 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></template>
            <template v-else>â€”</template>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('cost_report.kpi_top_category') }}</div>
          <div class="mt-1.5 text-sm font-bold text-slate-900 dark:text-white">
            <template v-if="stats && topCategory">{{ topCategory }}</template>
            <template v-else-if="loading"><span class="inline-block h-6 w-24 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></template>
            <template v-else>â€”</template>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('cost_report.kpi_top_provider') }}</div>
          <div class="mt-1.5 text-sm font-bold text-slate-900 dark:text-white">
            <template v-if="stats && topProvider">{{ topProvider }}</template>
            <template v-else-if="loading"><span class="inline-block h-6 w-24 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></template>
            <template v-else>â€”</template>
          </div>
        </div>
      </div>
    </section>

    <!-- â”€â”€ CHARTS â”€â”€ -->
    <section v-if="stats && !loading" aria-labelledby="cr-charts" class="space-y-3">
      <h2 id="cr-charts" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('cost_report.section_charts') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <!-- By category â”€ -->
        <div class="cr-chart-card">
          <p class="cr-chart-title">{{ t('cost_report.chart_by_category') }}</p>
          <DashboardEChart :option="chartByCategory" height="220px" :aria-label="t('cost_report.chart_by_category')" />
        </div>
        <!-- By status â”€ -->
        <div class="cr-chart-card">
          <p class="cr-chart-title">{{ t('cost_report.chart_by_status') }}</p>
          <DashboardEChart :option="chartByStatus" height="220px" :aria-label="t('cost_report.chart_by_status')" />
        </div>
        <!-- By provider â”€ -->
        <div class="cr-chart-card">
          <p class="cr-chart-title">{{ t('cost_report.chart_by_provider') }}</p>
          <DashboardEChart :option="chartByProvider" height="220px" :aria-label="t('cost_report.chart_by_provider')" />
        </div>
        <!-- By month (trend) â”€ -->
        <div v-if="stats.by_month && stats.by_month.length > 1" class="cr-chart-card lg:col-span-2 xl:col-span-2">
          <p class="cr-chart-title">{{ t('cost_report.chart_trend') }}</p>
          <DashboardEChart :option="chartByMonth" height="220px" :aria-label="t('cost_report.chart_trend')" />
        </div>
        <!-- By unit â”€ -->
        <div v-if="stats.by_unit && stats.by_unit.length" class="cr-chart-card">
          <p class="cr-chart-title">{{ t('cost_report.chart_by_unit') }}</p>
          <DashboardEChart :option="chartByUnit" height="220px" :aria-label="t('cost_report.chart_by_unit')" />
        </div>
      </div>
    </section>

    <!-- â”€â”€ DETAIL TABLE â”€â”€ -->
    <section aria-labelledby="cr-table-title">
      <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <div class="flex items-center justify-between border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
          <h2 id="cr-table-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ t('cost_report.table_title') }}
            <span v-if="rows.length" class="ml-1 text-xs font-normal text-slate-500">({{ rows.length }})</span>
          </h2>
        </div>

        <div class="overflow-x-auto overscroll-x-contain">
          <table class="cr-sheet min-w-[1100px] w-full">
            <thead>
              <tr>
                <th class="cr-th w-10 text-center">{{ t('costs_page.col_no') }}</th>
                <th class="cr-th min-w-[7rem]">{{ t('costs_page.col_unit') }}</th>
                <th class="cr-th min-w-[7rem]">{{ t('costs_page.col_category') }}</th>
                <th class="cr-th min-w-[9rem]">{{ t('costs_page.col_submitter') }}</th>
                <th class="cr-th min-w-[16rem]">{{ t('costs_page.col_description') }}</th>
                <th class="cr-th min-w-[10rem]">{{ t('costs_page.col_fleet_source') }}</th>
                <th class="cr-th min-w-[9rem]">{{ t('costs_page.col_provider') }}</th>
                <th class="cr-th cr-th--money min-w-[7rem] text-right">{{ t('costs_page.col_unit_price') }}</th>
                <th class="cr-th cr-th--money min-w-[7rem] text-right">{{ t('costs_page.col_extra_fee') }}</th>
                <th class="cr-th cr-th--money min-w-[8rem] text-right">{{ t('costs_page.col_payment') }}</th>
                <th class="cr-th min-w-[8rem]">{{ t('filter_bar.status') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in rows"
                :key="row.id"
                class="cr-data-row"
                :class="[idx % 2 === 1 ? 'cr-data-row--alt' : '', row.source === 'estimate' ? 'cr-data-row--estimate' : '']"
              >
                <td class="cr-td text-center tabular-nums text-slate-500">{{ idx + 1 }}</td>
                <td class="cr-td"><span class="cr-pill">{{ row.unit || 'â€”' }}</span></td>
                <td class="cr-td">
                  <span v-if="row.category" class="cr-pill" :class="TRIP_TYPE_PILL_CLASSES[row.category] ?? ''">
                    {{ TRIP_TYPE_LABELS[row.category] ?? row.category }}
                  </span>
                  <span v-else class="text-slate-400">â€”</span>
                </td>
                <td class="cr-td">{{ row.submitter || 'â€”' }}</td>
                <td class="cr-td max-w-[22rem]">
                  <span class="line-clamp-2">{{ row.description || 'â€”' }}</span>
                  <span
                    v-if="row.source === 'estimate'"
                    class="mt-0.5 inline-flex rounded-full bg-violet-100/90 px-2 py-px text-[10px] font-semibold text-violet-900 dark:bg-violet-950/50 dark:text-violet-100"
                  >Æ¯á»›c tÃ­nh</span>
                </td>
                <td class="cr-td whitespace-nowrap"><span class="cr-pill">{{ row.fleet_source || 'â€”' }}</span></td>
                <td class="cr-td">{{ row.provider || 'â€”' }}</td>
                <td class="cr-td cr-td--money">
                  {{ row.unit_price != null && row.unit_price > 0 ? formatVnd(row.unit_price) : 'â€”' }}
                </td>
                <td class="cr-td cr-td--money">
                  {{ row.extra_fee != null && row.extra_fee > 0 ? formatVnd(row.extra_fee) : 'â€”' }}
                </td>
                <td class="cr-td cr-td--money font-semibold text-slate-900 dark:text-slate-100">{{ formatVnd(row.amount) }}</td>
                <td class="cr-td">
                  <span class="cr-pill" :class="STATUS_PILL_CLASSES[row.status] ?? 'bg-slate-100 text-slate-700'">
                    {{ row.status_label || row.status || 'â€”' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="loading" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
            <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
            {{ t('cost_report.loading') }}
          </div>
          <div v-else-if="!loading && !rows.length" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ t('cost_report.empty_table') }}
          </div>
        </div>

        <!-- Total footer â”€ -->
        <div v-if="rows.length" class="flex items-center justify-end gap-6 border-t border-slate-200/90 bg-slate-50/90 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800/50">
          <span class="text-slate-500 dark:text-slate-400">{{ t('cost_report.total_rows', { n: rows.length }) }}</span>
          <span class="font-semibold text-slate-800 dark:text-slate-100">
            {{ t('cost_report.total_amount_label') }}: <span class="tabular-nums text-va-800 dark:text-va-400">{{ formatVnd(totalAmount) }}</span>
          </span>
        </div>
      </div>
    </section>

    <!-- Export error â”€ -->
    <p v-if="exportError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200">
      {{ exportError }}
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { getTripCostReport, downloadTripCostXlsx, downloadTripCostPdf } from '../../api/reports'
import { formatVnd } from '../../util/labels'
import { showAppErrorFromApi } from '../../composables/appMessage'

const { t } = useI18n()

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Constants
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const TRIP_TYPE_LABELS = {
  point_to_point: 'Äiá»ƒm â€” Äiá»ƒm',
  business:       'CÃ´ng tÃ¡c',
  cargo:          'HÃ ng hÃ³a',
  door_to_door:   'ÄÆ°a Ä‘Ã³n',
}

const TRIP_TYPE_PILL_CLASSES = {
  point_to_point: 'bg-sky-100/80 text-sky-800',
  cargo:          'bg-amber-100/80 text-amber-800',
  business:       'bg-violet-100/80 text-violet-800',
  door_to_door:   'bg-teal-100/80 text-teal-800',
}

const STATUS_LABELS = {
  draft:     'NhÃ¡p',
  submitted: 'Chá» duyá»‡t',
  confirmed: 'ÄÃ£ duyá»‡t',
  rejected:  'Tá»« chá»‘i',
  estimate:  'Æ¯á»›c tÃ­nh phiáº¿u',
}

const STATUS_PILL_CLASSES = {
  confirmed: 'bg-emerald-100 text-emerald-800',
  rejected:  'bg-rose-100 text-rose-800',
  submitted: 'bg-blue-100 text-blue-800',
  estimate:  'bg-violet-100/90 text-violet-900',
  draft:     'bg-slate-100 text-slate-700',
}

const FLEET_LABELS = {
  internal:    'Xe ná»™i bá»™',
  vendor_hire: 'ThuÃª xe NCC',
  taxi:        'Taxi',
  unspecified: 'KhÃ´ng xÃ¡c Ä‘á»‹nh',
}

const CHART_PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#f97316']

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// State
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const loading   = ref(false)
const exporting = ref(null)    // 'xlsx' | 'pdf' | null
const exportError = ref('')

/** @type {import('vue').Ref<Record<string, any>|null>} */
const stats = ref(null)
/** @type {import('vue').Ref<Array<Record<string, any>>>} */
const rows  = ref([])

const filters = reactive({
  from:       '',
  to:         '',
  trip_type:  '',
  status:     '',
  fleet_mode: '',
})

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Filter toolbar refs (auto-close)
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const funnelRef    = ref(null)
const filterBarRef = ref(null)
useDetailsAutoClose(funnelRef)
useDetailsAutoCloseWithin(filterBarRef)

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Filter options
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const tripTypeOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...Object.entries(TRIP_TYPE_LABELS).map(([value, label]) => ({ value, label })),
])

const statusOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...Object.entries(STATUS_LABELS).map(([value, label]) => ({ value, label })),
])

const fleetOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...Object.entries(FLEET_LABELS).map(([value, label]) => ({ value, label })),
])

const filterDateSummary = computed(() => {
  if (!filters.from && !filters.to) return t('filter_bar.all')
  return `${filters.from || 'â€¦'} â†’ ${filters.to || 'â€¦'}`
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.from || filters.to) n++
  if (filters.trip_type)  n++
  if (filters.status)     n++
  if (filters.fleet_mode) n++
  return n
})

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Derived KPI
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
  return sorted[0]?.label ?? null
})

const totalAmount = computed(() => stats.value?.total_amount ?? 0)

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// ECharts options
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const tooltipMoney = {
  trigger: 'item',
  formatter: (p) => {
    const v = typeof p.value === 'number' ? p.value : (p.data?.value ?? 0)
    return `${p.name}<br/><b>${Number(v).toLocaleString('vi-VN')} â‚«</b>`
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
    tooltip: { trigger: 'axis', formatter: (p) => `${p[0].name}<br/><b>${Number(p[0].value).toLocaleString('vi-VN')} â‚«</b>` },
    grid: { left: '4%', right: '6%', top: 10, bottom: 10, containLabel: true },
    xAxis: { type: 'value', axisLabel: { formatter: (v) => `${(v / 1000000).toFixed(0)}M`, fontSize: 9 } },
    yAxis: { type: 'category', data: top.map(d => d.label), axisLabel: { fontSize: 9, overflow: 'truncate', width: 90 } },
    series: [{ type: 'bar', data: top.map((d, i) => ({ value: d.amount, itemStyle: { color: CHART_PALETTE[i % CHART_PALETTE.length] } })), barMaxWidth: 22 }],
  }
}

function lineOption(dataList) {
  return {
    tooltip: { trigger: 'axis', formatter: (p) => `${p[0].axisValue}<br/><b>${Number(p[0].value).toLocaleString('vi-VN')} â‚«</b>` },
    grid: { left: '4%', right: '4%', top: 14, bottom: 24, containLabel: true },
    xAxis: { type: 'category', data: dataList.map(d => d.key), axisLabel: { fontSize: 9, rotate: 30 } },
    yAxis: { type: 'value', axisLabel: { formatter: (v) => `${(v / 1000000).toFixed(0)}M`, fontSize: 9 } },
    series: [{
      type: 'line',
      data: dataList.map(d => d.amount),
      smooth: true,
      symbol: 'circle',
      symbolSize: 5,
      areaStyle: { opacity: 0.12 },
      lineStyle: { color: '#9a0036', width: 2 },
      itemStyle: { color: '#9a0036' },
    }],
  }
}

const chartByCategory = computed(() => pieOption(stats.value?.by_category ?? []))
const chartByStatus   = computed(() => pieOption(stats.value?.by_status ?? []))
const chartByProvider = computed(() => barHorizontalOption(stats.value?.by_provider ?? []))
const chartByMonth    = computed(() => lineOption(stats.value?.by_month ?? []))
const chartByUnit     = computed(() => barHorizontalOption(stats.value?.by_unit ?? []))

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Data loading
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function buildApiParams() {
  const p = {}
  if (filters.from)       p.from       = filters.from
  if (filters.to)         p.to         = filters.to
  if (filters.trip_type)  p.trip_type  = filters.trip_type
  if (filters.status)     p.status     = filters.status
  if (filters.fleet_mode) p.fleet_mode = filters.fleet_mode
  return p
}

async function reload() {
  loading.value = true
  try {
    const res = await getTripCostReport(buildApiParams())
    stats.value = res.stats
    rows.value  = res.rows ?? []
  } catch (e) {
    showAppErrorFromApi(e, t('cost_report.load_error'))
  } finally {
    loading.value = false
  }
}

function applyFilter(ev, patch) {
  Object.assign(filters, patch)
  const el = ev?.currentTarget
  if (el && typeof el.closest === 'function') {
    const d = el.closest('details')
    if (d) d.open = false
  }
  reload()
}

function onFilterChange() {
  reload()
}

function resetFilters() {
  filters.from       = ''
  filters.to         = ''
  filters.trip_type  = ''
  filters.status     = ''
  filters.fleet_mode = ''
  if (funnelRef.value) funnelRef.value.open = false
  reload()
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Export
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

onMounted(() => reload())
</script>

<style scoped>
.cost-report-page {
  @apply text-slate-900 dark:text-slate-100;
}

.cr-chart-card {
  @apply overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50;
}

.cr-chart-title {
  @apply mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400;
}

.cr-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100;
}

.cr-filter-btn {
  @apply flex w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800;
}

.cr-filter-btn--active {
  @apply bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100;
}

.cr-sheet {
  @apply border-collapse text-left text-sm;
}

.cr-sheet thead {
  @apply bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400;
}

.cr-th {
  @apply border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700;
}

.cr-th--money {
  @apply text-right;
}

.cr-td {
  @apply border-b border-slate-100 px-3 py-2.5 align-top text-slate-800 dark:border-slate-800 dark:text-slate-200;
}

.cr-data-row {
  @apply transition-colors hover:bg-teal-50/40 dark:hover:bg-teal-950/20;
}

.cr-data-row--alt {
  @apply bg-slate-50/40 dark:bg-slate-900/20;
}

.cr-data-row--estimate {
  @apply bg-violet-50/35 hover:bg-violet-50/55 dark:bg-violet-950/20 dark:hover:bg-violet-950/30;
}

.cr-td--money {
  @apply text-right tabular-nums;
}

.cr-pill {
  @apply inline-flex max-w-full items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300;
}
</style>

