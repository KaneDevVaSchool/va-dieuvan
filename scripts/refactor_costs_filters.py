# -*- coding: utf-8 -*-
"""Replace CostsListView template: hero, KPI section, funnel + per-control visibility filters."""
from pathlib import Path

p = Path(__file__).resolve().parents[1] / "resources/js/src/views/costs/CostsListView.vue"
s = p.read_text(encoding="utf-8")
i0 = s.find("<template>")
i_bar = s.find("    <AppFilterBar>", i0)
i_bar_end = s.find("</AppFilterBar>", i_bar) + len("</AppFilterBar>")
assert i0 >= 0 and i_bar >0 and i_bar_end > i_bar

new_block = r"""<template>
  <div class="costs-page space-y-4 pb-12 text-slate-900 md:space-y-5 dark:text-slate-100">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('costs_page.hero_title') }}
        </h1>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
          {{ t('costs_page.hero_subtitle') }}
        </p>
      </div>
    </div>

    <section class="space-y-3" aria-labelledby="costs-section-kpis">
      <h2 id="costs-section-kpis" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('costs_page.section_kpis') }}
      </h2>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div
          class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.kpi_total') }}</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ meta.total ?? 0 }}</p>
          <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">{{ t('costs_page.kpi_total_hint') }}</p>
        </div>
        <div
          class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.kpi_submitted') }}</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-800 dark:text-amber-300">{{ countOnPage('submitted') }}</p>
          <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">{{ t('costs_page.kpi_page_hint') }}</p>
        </div>
        <div
          class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.kpi_confirmed') }}</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800 dark:text-emerald-300">{{ countOnPage('confirmed') }}</p>
          <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">{{ t('costs_page.kpi_page_hint') }}</p>
        </div>
        <div
          class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-700 dark:bg-slate-900/50"
        >
          <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.kpi_rejected') }}</p>
          <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-800 dark:text-rose-300">{{ countOnPage('rejected') }}</p>
          <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">{{ t('costs_page.kpi_page_hint') }}</p>
        </div>
      </div>
    </section>

    <section class="space-y-3" aria-labelledby="costs-section-filters">
      <h2 id="costs-section-filters" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('costs_page.section_filters') }}
      </h2>
      <div class="relative z-40">
    <AppFilterBar>
      <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <details ref="funnelDetailsRef" class="group relative">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
              <span
                v-if="activeFilterCount > 0"
                class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
              >
                {{ activeFilterCount }}
              </span>
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
          >
            <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
              {{ t('dashboard_analytics.filter_applied_title') }}
            </p>
            <div class="p-3 pt-2">
              <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li v-if="filters.status" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
                  <span class="font-medium">{{ statusLabel(filters.status) }}</span>
                </li>
                <li v-if="filters.type" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_cost_type') }}</span>
                  <span class="font-medium">{{ typeLabel(filters.type) }}</span>
                </li>
                <li v-if="filters.trip_id" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_trip') }}</span>
                  <span class="max-w-[12rem] truncate text-right font-medium" :title="tripFilterSummaryFull">{{
                    tripFilterSummaryFull
                  }}</span>
                </li>
                <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_recorded_date') }}</span>
                  <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
                </li>
                <li v-if="filters.per_page !== DEFAULT_PER_PAGE" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
                  <span class="font-medium">{{ filters.per_page }}</span>
                </li>
                <li v-if="searchQ.trim()" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_search_page') }}</span>
                  <span class="max-w-[10rem] truncate font-medium" :title="searchQ">{{ searchQ }}</span>
                </li>
                <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
              </ul>
              <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                  {{ t('trips_page.filter_show_controls_title') }}
                </p>
                <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                  {{ t('trips_page.filter_show_controls_hint') }}
                </p>
                <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                  <li v-for="fd in filterControlDefs" :key="'costs-vis-' + fd.id" class="flex items-start gap-2">
                    <input
                      :id="'costs-filter-vis-' + fd.id"
                      v-model="filterControlVisible[fd.id]"
                      type="checkbox"
                      class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                    />
                    <label
                      :for="'costs-filter-vis-' + fd.id"
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
                @click="resetFilters()"
              >
                {{ t('dashboard_analytics.filter_clear_all') }}
              </button>
            </div>
          </div>
        </details>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <AppFilterDropdown
            v-if="filterControlVisible.status"
            root-class="shrink-0"
            :label="t('filter_bar.status')"
            :summary-text="filters.status ? statusLabel(filters.status) : t('filter_bar.all')"
            summary-text-class="max-w-[10rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.status === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { status: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            v-if="filterControlVisible.type"
            root-class="shrink-0"
            :label="t('costs_page.filter_cost_type')"
            :summary-text="filters.type ? typeLabel(filters.type) : t('filter_bar.all')"
            summary-text-class="max-w-[10rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in typeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.type === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { type: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            v-if="filterControlVisible.date"
            root-class="shrink-0"
            :label="t('costs_page.filter_recorded_date')"
            :summary-text="filterDateSummary"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input
                v-model="filters.from"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
              <span class="hidden text-slate-300 dark:text-slate-600 sm:inline">—</span>
              <input
                v-model="filters.to"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
            </div>
          </AppFilterDropdown>

          <AppFilterDropdown
            v-if="filterControlVisible.trip"
            root-class="shrink-0"
            :label="t('costs_page.filter_trip')"
            :summary-text="tripFilterSummaryShort"
            :summary-title="tripFilterSummaryFull"
            summary-text-class="max-w-[11rem]"
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max sm:min-w-[280px]"
          >
            <input
              v-model="filterTripSearch"
              type="search"
              class="costs-input mb-2 h-9 w-full text-sm"
              :placeholder="t('costs_page.trip_search_ph')"
              autocomplete="off"
              @click.stop
            />
            <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-0.5 py-0.5">
              <li>
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    !filters.trip_id
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { trip_id: '' })"
                >
                  {{ t('costs_page.trip_all') }}
                </button>
              </li>
              <li v-for="tripRow in filteredTripsForFilter" :key="tripRow.id">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    String(filters.trip_id) === String(tripRow.id)
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { trip_id: String(tripRow.id) })"
                >
                  {{ formatTripPickerLabel(tripRow) }}
                </button>
              </li>
            </ul>
            <p
              v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForFilter.length"
              class="mt-2 text-[11px] text-amber-800 dark:text-amber-200"
            >
              {{ t('costs_page.trip_no_match') }}
            </p>
            <p v-else-if="tripsForModalLoading" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
              {{ t('costs_page.trip_loading') }}
            </p>
            <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
              {{ t('costs_page.trip_empty_scope') }}
            </p>
          </AppFilterDropdown>

          <input
            v-if="filterControlVisible.search"
            v-model="searchQ"
            type="search"
            :aria-label="t('costs_page.filter_search_page')"
            :placeholder="t('costs_page.filter_search_page') + '…'"
            :title="t('costs_page.filter_search_page')"
            class="costs-input h-9 w-[9.5rem] shrink-0 text-sm sm:w-44"
          />

          <label v-if="filterControlVisible.per_page" class="inline-flex shrink-0 items-center gap-1.5">
            <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
            <select
              v-model.number="filters.per_page"
              class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('filter_bar.per_page')"
              @change="onPerPageChange"
            >
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline dark:text-slate-400" aria-hidden="true">{{ t('costs_page.per_page_unit') }}</span>
          </label>
        </div>

        <div
          class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:gap-2 sm:pl-3 dark:border-violet-900/40"
        >
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
            :title="t('filter_bar.clear_icon')"
            :aria-label="t('filter_bar.clear_icon')"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon
                class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
              />
            </span>
          </button>
          <button
            type="button"
            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-va-800 px-3 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35 dark:ring-white/10"
            @click="openAddCostModal"
          >
            {{ t('costs_page.add_cost') }}
          </button>
        </div>
      </div>
    </AppFilterBar>
      </div>
    </section>
"""

s = s[:i0] + new_block + s[i_bar_end:]
p.write_text(s, encoding="utf-8")
print("template ok", p)
