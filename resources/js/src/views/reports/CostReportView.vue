<template>
  <div class="tr-rev space-y-6 pb-14 text-slate-800 dark:text-slate-200">

    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="tr-rev-hero">
        <p class="tr-rev-hero__eyebrow">{{ t('nav.bar.section_reports') }}</p>
        <h1 class="tr-rev-hero__title">
          {{ t('cost_report.hero_title') }}
        </h1>
        <p class="tr-rev-hero__sub">
          {{ t('cost_report.hero_sub') }}
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:pt-1">
        <button
          type="button"
          :disabled="exporting"
          class="tr-rev-export tr-rev-export--sheet"
          @click="doExportXlsx"
        >
          <span v-if="exporting === 'xlsx'" class="tr-rev-spinner tr-rev-spinner--teal" />
          <TableCellsIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('cost_report.btn_export_xlsx') }}
        </button>
        <button
          type="button"
          :disabled="exporting"
          class="tr-rev-export tr-rev-export--doc"
          @click="doExportPdf"
        >
          <span v-if="exporting === 'pdf'" class="tr-rev-spinner tr-rev-spinner--rose" />
          <DocumentTextIcon v-else class="size-4 shrink-0" aria-hidden="true" />
          {{ t('cost_report.btn_export_pdf') }}
        </button>
      </div>
    </header>

    <AppFilterBar>
      <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">

        <details ref="funnelRef" class="group relative">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              <span
                v-if="activeFilterCount > 0"
                class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-normal leading-none text-white"
              >{{ activeFilterCount }}</span>
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30">
            <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-normal text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
              {{ t('dashboard_analytics.filter_applied_title') }}
            </p>
            <div class="p-3 pt-2">
              <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li v-if="filters.trip_type" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cost_report.filter_trip_type') }}</span>
                  <span class="font-medium">{{ labelTripType(filters.trip_type) }}</span>
                </li>
                <li v-if="filters.status" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
                  <span class="font-medium">{{ statusLabel(filters.status) }}</span>
                </li>
                <li v-if="filters.fleet_mode" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.filter_fleet') }}</span>
                  <span class="font-medium">{{ fleetModeLabel(filters.fleet_mode) }}</span>
                </li>
                <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cost_report.filter_date') }}</span>
                  <span class="font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
                </li>
                <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
              </ul>
              <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="text-[11px] font-normal text-violet-700 dark:text-violet-300">
                  {{ t('trips_page.filter_show_controls_title') }}
                </p>
                <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                  <li v-for="fd in filterControlDefs" :key="'cost-report-vis-' + fd.id" class="flex items-start gap-2">
                    <input
                      :id="'cost-report-filter-vis-' + fd.id"
                      v-model="filterControlVisible[fd.id]"
                      type="checkbox"
                      class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                    />
                    <label
                      :for="'cost-report-filter-vis-' + fd.id"
                      class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                    >
                      {{ fd.label }}
                    </label>
                  </li>
                </ul>
              </div>
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
          <AppFilterDropdown
            v-if="filterControlVisible.date"
            root-class="shrink-0"
            show-chip-label
            :label="t('cost_report.filter_date')"
            :summary-text="filterDateSummary"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input v-model="filters.from" type="date" class="cr-input h-9 w-full text-sm sm:w-auto" @change="onFilterChange" />
              <span class="hidden text-slate-300 sm:inline dark:text-slate-600">—</span>
              <input v-model="filters.to" type="date" class="cr-input h-9 w-full text-sm sm:w-auto" @change="onFilterChange" />
            </div>
          </AppFilterDropdown>

          <AppFilterDropdown
            v-if="filterControlVisible.trip_type"
            root-class="shrink-0"
            show-chip-label
            :label="t('cost_report.filter_trip_type')"
            :summary-text="filters.trip_type ? labelTripType(filters.trip_type) : t('filter_bar.all')"
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

          <AppFilterDropdown
            v-if="filterControlVisible.status"
            root-class="shrink-0"
            show-chip-label
            :label="t('filter_bar.status')"
            :summary-text="filters.status ? statusLabel(filters.status) : t('filter_bar.all')"
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

          <AppFilterDropdown
            v-if="filterControlVisible.fleet_mode"
            root-class="shrink-0"
            show-chip-label
            :label="t('dashboard_analytics.filter_fleet')"
            :summary-text="filters.fleet_mode ? fleetModeLabel(filters.fleet_mode) : t('filter_bar.all')"
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
            class="tr-rev-refresh"
            :disabled="loading"
            @click="reload"
          >
            <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            <template v-else>{{ t('cost_report.btn_refresh') }}</template>
          </button>
        </div>
      </div>
    </AppFilterBar>

    <section aria-labelledby="cr-kpi" class="tr-rev-block">
      <h2 id="cr-kpi" class="tr-rev-block__label">
        {{ t('cost_report.section_kpi') }}
      </h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="tr-rev-kpi tr-rev-kpi--va">
          <p class="tr-rev-kpi__label">{{ t('cost_report.kpi_total_amount') }}</p>
          <p class="tr-rev-kpi__value">
            <template v-if="stats">{{ formatVnd(stats.total_amount) }}</template>
            <template v-else-if="loading"><span class="tr-rev-skel tr-rev-skel--wide" /></template>
            <template v-else>—</template>
          </p>
        </article>
        <article class="tr-rev-kpi tr-rev-kpi--sky">
          <p class="tr-rev-kpi__label">{{ t('cost_report.kpi_count') }}</p>
          <p class="tr-rev-kpi__value tr-rev-kpi__value--sm">
            <template v-if="stats">{{ stats.count.toLocaleString('vi-VN') }}</template>
            <template v-else-if="loading"><span class="tr-rev-skel" /></template>
            <template v-else>—</template>
          </p>
        </article>
        <article class="tr-rev-kpi tr-rev-kpi--amber">
          <p class="tr-rev-kpi__label">{{ t('cost_report.kpi_top_category') }}</p>
          <p class="tr-rev-kpi__text">
            <template v-if="stats && topCategory">{{ topCategory }}</template>
            <template v-else-if="loading"><span class="tr-rev-skel" /></template>
            <template v-else>—</template>
          </p>
        </article>
        <article class="tr-rev-kpi tr-rev-kpi--emerald">
          <p class="tr-rev-kpi__label">{{ t('cost_report.kpi_top_provider') }}</p>
          <p class="tr-rev-kpi__text">
            <template v-if="stats && topProvider">{{ topProvider }}</template>
            <template v-else-if="loading"><span class="tr-rev-skel" /></template>
            <template v-else>—</template>
          </p>
        </article>
      </div>
    </section>

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
      <div class="tr-rev-table-panel">
        <div ref="detailTableToolbarRef" class="tr-rev-table-panel__head">
          <h2 id="cr-table-title" class="tr-rev-table-panel__title">
            {{ t('cost_report.table_title') }}
            <span v-if="rows.length" class="tr-rev-table-panel__count">({{ rows.length }})</span>
          </h2>
          <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex shrink-0 items-center gap-1.5">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}:</span>
              <select
                v-model.number="detailPerPage"
                class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                :aria-label="t('filter_bar.per_page')"
                @change="onDetailPerPageChange"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="20">20</option>
              </select>
              <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline dark:text-slate-400" aria-hidden="true">{{ t('cost_report.per_page_unit') }}</span>
            </label>
            <details ref="columnPickerRef" class="group relative shrink-0">
              <summary
                class="flex cursor-pointer list-none items-center rounded-lg border border-slate-200/90 bg-white px-2 py-1.5 text-slate-700 shadow-sm transition hover:border-teal-200/70 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
                :title="t('cost_report.column_visibility_title')"
                :aria-label="t('cost_report.column_visibility_title')"
              >
                <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[240px] rounded-2xl border border-violet-200/50 bg-white p-3 shadow-xl ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
                @click.stop
              >
                <p class="text-[11px] font-normal text-violet-700 dark:text-violet-300">
                  {{ t('cost_report.column_visibility_title') }}
                </p>
                <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto pr-0.5">
                  <li v-for="cd in colControlDefs" :key="'cost-report-col-vis-' + cd.id" class="flex items-start gap-2">
                    <input
                      :id="'cost-report-col-vis-' + cd.id"
                      v-model="colVisible[cd.id]"
                      type="checkbox"
                      class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                    />
                    <label
                      :for="'cost-report-col-vis-' + cd.id"
                      class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                    >
                      {{ cd.label }}
                    </label>
                  </li>
                </ul>
              </div>
            </details>
          </div>
        </div>

        <div class="overflow-x-auto overscroll-x-contain">
          <table class="cr-sheet min-w-[1100px] w-full">
            <thead>
              <tr>
                <th class="cr-th w-10 text-center">{{ t('cost_report.col_no') }}</th>
                <th v-if="colVisible.unit" class="cr-th min-w-[7rem]">{{ t('cost_report.col_unit') }}</th>
                <th v-if="colVisible.category" class="cr-th min-w-[7rem]">{{ t('cost_report.col_category') }}</th>
                <th v-if="colVisible.submitter" class="cr-th min-w-[9rem]">{{ t('cost_report.col_submitter') }}</th>
                <th v-if="colVisible.description" class="cr-th min-w-[16rem]">{{ t('cost_report.col_description') }}</th>
                <th v-if="colVisible.fleet_source" class="cr-th min-w-[10rem]">{{ t('cost_report.col_fleet_source') }}</th>
                <th v-if="colVisible.provider" class="cr-th min-w-[9rem]">{{ t('cost_report.col_provider') }}</th>
                <th v-if="colVisible.unit_price" class="cr-th cr-th--money min-w-[7rem] text-right">{{ t('cost_report.col_unit_price') }}</th>
                <th v-if="colVisible.extra_fee" class="cr-th cr-th--money min-w-[7rem] text-right">{{ t('cost_report.col_extra_fee') }}</th>
                <th v-if="colVisible.payment" class="cr-th cr-th--money min-w-[8rem] text-right">{{ t('cost_report.col_payment') }}</th>
                <th v-if="colVisible.status" class="cr-th min-w-[8rem]">{{ t('filter_bar.status') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in paginatedRows"
                :key="row.id"
                class="cr-data-row"
                :class="[idx % 2 === 1 ? 'cr-data-row--alt' : '', row.source === 'estimate' ? 'cr-data-row--estimate' : '']"
              >
                <td class="cr-td text-center tabular-nums text-slate-500">{{ detailRowNo(idx) }}</td>
                <td v-if="colVisible.unit" class="cr-td"><span class="cr-pill">{{ row.unit || '—' }}</span></td>
                <td v-if="colVisible.category" class="cr-td">
                  <span v-if="row.category" class="cr-pill" :class="TRIP_TYPE_PILL_CLASSES[row.category] ?? ''">
                    {{ labelTripType(row.category) }}
                  </span>
                  <span v-else class="text-slate-400">—</span>
                </td>
                <td v-if="colVisible.submitter" class="cr-td">{{ row.submitter || '—' }}</td>
                <td v-if="colVisible.description" class="cr-td max-w-[22rem]">
                  <span class="line-clamp-2">{{ row.description || '—' }}</span>
                  <span
                    v-if="row.source === 'estimate'"
                    class="tr-rev-badge-estimate"
                  >{{ t('cost_report.badge_estimate') }}</span>
                </td>
                <td v-if="colVisible.fleet_source" class="cr-td whitespace-nowrap"><span class="cr-pill">{{ row.fleet_source || '—' }}</span></td>
                <td v-if="colVisible.provider" class="cr-td">{{ row.provider || '—' }}</td>
                <td v-if="colVisible.unit_price" class="cr-td cr-td--money">
                  {{ row.unit_price != null && row.unit_price > 0 ? formatVnd(row.unit_price) : '—' }}
                </td>
                <td v-if="colVisible.extra_fee" class="cr-td cr-td--money">
                  {{ row.extra_fee != null && row.extra_fee > 0 ? formatVnd(row.extra_fee) : '—' }}
                </td>
                <td v-if="colVisible.payment" class="cr-td cr-td--money cr-td--revenue">{{ formatVnd(row.amount) }}</td>
                <td v-if="colVisible.status" class="cr-td">
                  <span class="cr-pill" :class="STATUS_PILL_CLASSES[row.status] ?? 'bg-slate-100 text-slate-700'">
                    {{ row.status_label || statusLabel(row.status) || '—' }}
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

        <div v-if="rows.length" class="tr-rev-table-foot">
          <span class="text-sm text-slate-600 dark:text-slate-400">
            {{
              t('cost_report.pagination_of', {
                current: detailPage,
                last: detailLastPage,
              })
            }}
            <span class="text-slate-400"> · </span>
            {{ rows.length }} {{ t('cost_report.pagination_records_suffix') }}
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
                @click="detailPageStep(-1)"
              >
                {{ t('cost_report.prev') }}
              </button>
              <button
                type="button"
                class="tr-rev-page-btn"
                :disabled="loading || detailPage >= detailLastPage"
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  FunnelIcon,
  ChevronDownIcon,
  XMarkIcon,
  ViewColumnsIcon,
  TableCellsIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { getTripCostReport, downloadTripCostXlsx, downloadTripCostPdf } from '../../api/reports'
import { formatVnd, labelTripType } from '../../util/labels'
import { showAppErrorFromApi } from '../../composables/appMessage'

const { t, te } = useI18n()

const TRIP_TYPE_SLUGS = ['point_to_point', 'cargo', 'business', 'door_to_door']
const FLEET_MODES = ['internal', 'vendor_hire', 'taxi', 'unspecified']
const STATUS_FILTER_KEYS = ['draft', 'submitted', 'confirmed', 'rejected', 'estimate']

const TRIP_TYPE_PILL_CLASSES = {
  point_to_point: 'bg-sky-100/80 text-sky-800',
  cargo: 'bg-amber-100/80 text-amber-800',
  business: 'bg-violet-100/80 text-violet-800',
  door_to_door: 'bg-teal-100/80 text-teal-800',
}

const STATUS_PILL_CLASSES = {
  confirmed: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-rose-100 text-rose-800',
  submitted: 'bg-blue-100 text-blue-800',
  estimate: 'bg-violet-100/90 text-violet-900',
  draft: 'bg-slate-100 text-slate-700',
}

const CHART_PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#f97316']

const COST_REPORT_FILTER_VISIBILITY_KEY = 'va.cost_report.filter_control_visibility_v1'
const COST_REPORT_COL_VISIBILITY_KEY = 'va.cost_report.col_visibility_v1'
const COST_REPORT_DETAIL_PER_PAGE_KEY = 'va.cost_report.detail_per_page_v1'

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

const DEFAULT_DETAIL_PER_PAGE = 10

function defaultFilterControlVisibility() {
  return Object.fromEntries(FILTER_CONTROL_IDS.map((id) => [id, true]))
}

function defaultColVisibility() {
  return Object.fromEntries(COL_IDS.map((id) => [id, true]))
}

function statusLabel(s) {
  if (!s) return '—'
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

const filterControlVisible = reactive(defaultFilterControlVisibility())
const colVisible = reactive(defaultColVisibility())

const detailPage = ref(1)
const detailPerPage = ref(DEFAULT_DETAIL_PER_PAGE)

const funnelRef = ref(null)
const filterBarRef = ref(null)
const columnPickerRef = ref(null)
const detailTableToolbarRef = ref(null)
useDetailsAutoClose(funnelRef)
useDetailsAutoClose(columnPickerRef)
useDetailsAutoCloseWithin(filterBarRef)
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

const filterDateSummary = computed(() => {
  if (!filters.from && !filters.to) return t('filter_bar.all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.from || filters.to) n++
  if (filters.trip_type) n++
  if (filters.status) n++
  if (filters.fleet_mode) n++
  return n
})

const detailLastPage = computed(() => {
  const total = rows.value.length
  if (!total) return 1
  return Math.max(1, Math.ceil(total / detailPerPage.value))
})

const paginatedRows = computed(() => {
  const list = rows.value
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
  return sorted[0]?.label ?? null
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
const chartByProvider = computed(() => barHorizontalOption(stats.value?.by_provider ?? []))
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

function applyFilter(ev, patch) {
  Object.assign(filters, patch)
  const el = ev?.currentTarget
  if (el && typeof el.closest === 'function') {
    const d = el.closest('details')
    if (d) d.open = false
  }
  detailPage.value = 1
  reload()
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
  if (funnelRef.value) funnelRef.value.open = false
  detailPage.value = 1
  reload()
}

function detailPageStep(delta) {
  const next = detailPage.value + delta
  if (next < 1 || next > detailLastPage.value) return
  detailPage.value = next
}

function onDetailPerPageChange() {
  detailPage.value = 1
  try {
    localStorage.setItem(COST_REPORT_DETAIL_PER_PAGE_KEY, String(detailPerPage.value))
  } catch {
    /* ignore */
  }
}

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(COST_REPORT_FILTER_VISIBILITY_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    for (const id of FILTER_CONTROL_IDS) {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    }
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
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

function loadDetailPerPage() {
  try {
    const raw = localStorage.getItem(COST_REPORT_DETAIL_PER_PAGE_KEY)
    const n = Number(raw)
    if ([5, 10, 15, 20].includes(n)) detailPerPage.value = n
  } catch {
    /* ignore */
  }
}

watch(
  filterControlVisible,
  (v) => {
    try {
      localStorage.setItem(COST_REPORT_FILTER_VISIBILITY_KEY, JSON.stringify({ ...v }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

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
  loadFilterControlVisibility()
  loadColVisibility()
  loadDetailPerPage()
  reload()
})
</script>

<style scoped>
.tr-rev-hero {
  @apply border-l-[3px] border-teal-600/80 pl-4 dark:border-teal-500/70;
}

.tr-rev-hero__eyebrow {
  @apply text-[11px] font-normal tracking-wide text-teal-800/80 dark:text-teal-300/90;
}

.tr-rev-hero__title {
  @apply mt-0.5 text-xl font-normal tracking-tight text-slate-900 dark:text-slate-50 sm:text-2xl;
}

.tr-rev-hero__sub {
  @apply mt-1 max-w-2xl text-sm font-normal leading-relaxed text-slate-500 dark:text-slate-400;
}

.tr-rev-export {
  @apply inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-sm font-normal transition disabled:opacity-50;
}

.tr-rev-export--sheet {
  @apply border-teal-200/90 bg-teal-50/60 text-teal-900 hover:bg-teal-100/80 dark:border-teal-800 dark:bg-teal-950/30 dark:text-teal-100 dark:hover:bg-teal-900/40;
}

.tr-rev-export--doc {
  @apply border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800;
}

.tr-rev-spinner {
  @apply inline-block size-4 animate-spin rounded-full border-2;
}

.tr-rev-spinner--teal {
  @apply border-teal-200 border-t-teal-700;
}

.tr-rev-spinner--rose {
  @apply border-rose-200 border-t-rose-600;
}

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
