<template>
  <div class="costs-page space-y-4 pb-12 text-slate-900 md:space-y-5 dark:text-slate-100">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('costs_page.hero_title') }}
        </h1>
      </div>
    </div>

    <div
      class="flex gap-1 border-b border-slate-200 bg-slate-50/90 px-1 pt-1 dark:border-slate-700 dark:bg-slate-900/60"
      role="tablist"
      :aria-label="t('costs_page.hero_title')"
    >
      <button
        type="button"
        role="tab"
        :aria-selected="activeTab === 'all_trips'"
        class="min-h-[2.75rem] flex-1 rounded-t-lg px-3 py-2 text-center text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900 sm:flex-none sm:px-5 sm:text-sm"
        :class="
          activeTab === 'all_trips'
            ? 'bg-white text-teal-800 shadow-[0_-1px_0_0_white] dark:bg-slate-950 dark:text-teal-300 dark:shadow-[0_-1px_0_0_rgb(15,23,42)]'
            : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200'
        "
        @click="activeTab = 'all_trips'"
      >
        {{ t('costs_page.tab_all_trips') }}
      </button>
      <button
        type="button"
        role="tab"
        :aria-selected="activeTab === 'standalone'"
        class="min-h-[2.75rem] flex-1 rounded-t-lg px-3 py-2 text-center text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900 sm:flex-none sm:px-5 sm:text-sm"
        :class="
          activeTab === 'standalone'
            ? 'bg-white text-teal-800 shadow-[0_-1px_0_0_white] dark:bg-slate-950 dark:text-teal-300 dark:shadow-[0_-1px_0_0_rgb(15,23,42)]'
            : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200'
        "
        @click="activeTab = 'standalone'"
      >
        {{ t('costs_page.tab_standalone') }}
        <span
          v-if="standaloneTabTotal > 0"
          class="ml-1 inline-block min-w-[1.125rem] rounded-full bg-amber-100 px-1 py-px text-[10px] font-bold tabular-nums text-amber-900 dark:bg-amber-950/70 dark:text-amber-200"
        >
          {{ standaloneTabTotal }}
        </span>
      </button>
      <button
        type="button"
        role="tab"
        :aria-selected="activeTab === 'business_personnel'"
        class="min-h-[2.75rem] flex-1 rounded-t-lg px-3 py-2 text-center text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1 dark:focus-visible:ring-offset-slate-900 sm:flex-none sm:px-5 sm:text-sm"
        :class="
          activeTab === 'business_personnel'
            ? 'bg-white text-teal-800 shadow-[0_-1px_0_0_white] dark:bg-slate-950 dark:text-teal-300 dark:shadow-[0_-1px_0_0_rgb(15,23,42)]'
            : 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200'
        "
        @click="activeTab = 'business_personnel'"
      >
        {{ t('costs_page.tab_business_personnel') }}
        <span
          v-if="bpMeta.total > 0"
          class="ml-1 inline-block min-w-[1.125rem] rounded-full bg-violet-100 px-1 py-px text-[10px] font-bold tabular-nums text-violet-800 dark:bg-violet-950/70 dark:text-violet-200"
        >
          {{ bpMeta.total }}
        </span>
      </button>
    </div>

    <template v-if="activeTab === 'all_trips' || activeTab === 'standalone'">
    <section class="space-y-3" aria-labelledby="costs-section-filters">
      <h2 id="costs-section-filters" class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('costs_page.section_filters') }}
      </h2>
      <div class="relative z-40">
    <AppFilterBar>
      <div ref="costsFilterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
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
                <li v-if="filters.trip_type" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_trip_type') }}</span>
                  <span class="font-medium">{{ labelTripType(filters.trip_type) }}</span>
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
                <li v-if="filters.amount_min || filters.amount_max" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_amount_range') }}</span>
                  <span class="text-right font-medium">{{ amountRangeSummary }}</span>
                </li>
                <li v-if="filters.provider" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.filter_provider') }}</span>
                  <span class="max-w-[12rem] truncate text-right font-medium">{{ providerFilterSummary }}</span>
                </li>
                <li v-if="filters.fleet_mode" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.filter_fleet') }}</span>
                  <span class="max-w-[12rem] truncate text-right font-medium">{{ fleetModeFilterSummary }}</span>
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

        <details ref="columnPickerRef" class="group relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center rounded-xl border border-white/90 bg-white/95 p-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            :title="t('costs_page.column_visibility_title')"
            :aria-label="t('costs_page.column_visibility_title')"
          >
            <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600 dark:text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+8px)] z-[110] min-w-[240px] rounded-2xl border border-violet-200/50 bg-white p-3 shadow-xl ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
            @click.stop
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
              {{ t('costs_page.column_visibility_title') }}
            </p>
            <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto pr-0.5">
              <li v-for="cd in colControlDefs" :key="'costs-col-vis-' + cd.id" class="flex items-start gap-2">
                <input
                  :id="'costs-col-vis-' + cd.id"
                  v-model="colVisible[cd.id]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                />
                <label
                  :for="'costs-col-vis-' + cd.id"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ cd.label }}
                </label>
              </li>
            </ul>
          </div>
        </details>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <label v-if="filterControlVisible.status" class="inline-flex min-w-0 shrink-0 items-center gap-1.5">
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
            <select
              v-model="filters.status"
              class="h-9 max-w-[min(100%,11rem)] rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('filter_bar.status')"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>

          <label v-if="filterControlVisible.type" class="inline-flex min-w-0 shrink-0 items-center gap-1.5">
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('costs_page.filter_cost_type') }}</span>
            <select
              v-model="filters.type"
              class="h-9 max-w-[min(100%,11rem)] rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('costs_page.filter_cost_type')"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in typeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>

          <label
            v-if="filterControlVisible.trip_type && activeTab !== 'standalone'"
            class="inline-flex min-w-0 shrink-0 items-center gap-1.5"
          >
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('costs_page.filter_trip_type') }}</span>
            <select
              v-model="filters.trip_type"
              class="h-9 max-w-[min(100%,11rem)] rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('costs_page.filter_trip_type')"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>

          <AppFilterDropdown
            v-if="filterControlVisible.date"
            root-class="shrink-0"
            show-chip-label
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
            v-if="filterControlVisible.trip && activeTab !== 'standalone'"
            root-class="shrink-0"
            show-chip-label
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

          <AppFilterDropdown
            v-if="filterControlVisible.amount_range"
            root-class="shrink-0"
            show-chip-label
            :label="t('costs_page.filter_amount_range')"
            :summary-text="amountRangeSummary"
            summary-text-class="max-w-[10rem]"
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2">
              <label class="text-xs text-slate-600 dark:text-slate-400">
                {{ t('costs_page.filter_amount_min') }}
                <input
                  :value="formatVndDigitsInput(String(filters.amount_min).replace(/\D/g, ''))"
                  type="text"
                  inputmode="numeric"
                  class="costs-input mt-1 h-9 w-full text-sm"
                  autocomplete="off"
                  @input="onAmountFilterInput('amount_min', $event)"
                  @click.stop
                />
              </label>
              <label class="text-xs text-slate-600 dark:text-slate-400">
                {{ t('costs_page.filter_amount_max') }}
                <input
                  :value="formatVndDigitsInput(String(filters.amount_max).replace(/\D/g, ''))"
                  type="text"
                  inputmode="numeric"
                  class="costs-input mt-1 h-9 w-full text-sm"
                  autocomplete="off"
                  @input="onAmountFilterInput('amount_max', $event)"
                  @click.stop
                />
              </label>
            </div>
          </AppFilterDropdown>

          <label
            v-if="filterControlVisible.provider && activeTab !== 'standalone'"
            class="inline-flex min-w-0 shrink-0 items-center gap-1.5"
          >
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('costs_page.filter_provider') }}</span>
            <select
              v-model="filters.provider"
              class="h-9 max-w-[min(100%,11rem)] rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('costs_page.filter_provider')"
            >
              <option v-for="opt in providerFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>

          <label
            v-if="filterControlVisible.fleet_mode && activeTab !== 'standalone'"
            class="inline-flex min-w-0 shrink-0 items-center gap-1.5"
          >
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('dashboard_analytics.filter_fleet') }}</span>
            <select
              v-model="filters.fleet_mode"
              class="h-9 max-w-[min(100%,11rem)] rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('dashboard_analytics.filter_fleet')"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in fleetModeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>

          <label
            v-if="filterControlVisible.search"
            class="inline-flex shrink-0 items-center gap-1.5"
          >
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('costs_page.filter_search_page') }}</span>
            <input
              v-model="searchQ"
              type="search"
              :aria-label="t('costs_page.filter_search_page')"
              :placeholder="t('costs_page.filter_search_page') + '…'"
              :title="t('costs_page.filter_search_page')"
              class="costs-input h-9 w-[9.5rem] text-sm sm:w-44"
            />
          </label>

          <label v-if="filterControlVisible.per_page" class="inline-flex shrink-0 items-center gap-1.5">
            <span class="whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
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
          class="ml-auto flex shrink-0 items-center gap-1 pl-2 sm:gap-2 sm:pl-3"
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
            v-if="showAddCostButton"
            type="button"
            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-va-800 px-3 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35 dark:ring-white/10"
            @click="openAddCostModal"
          >
            {{ activeTab === 'standalone' ? t('costs_page.add_standalone_cost') : t('costs_page.add_cost') }}
          </button>
        </div>
      </div>
    </AppFilterBar>
      </div>
    </section>

    <!-- Bảng -->
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
      <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ activeTab === 'standalone' ? t('costs_page.table_title_standalone') : t('costs_page.table_title') }}
        </h2>
        <p v-if="activeTab === 'standalone'" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          {{ t('costs_page.standalone_intro') }}
        </p>
      </div>
      <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
        <table class="costs-sheet min-w-[1100px] w-full">
          <thead>
            <tr>
              <th class="costs-th w-10 text-center">{{ t('costs_page.col_no') }}</th>
              <th v-if="colVisible.unit" class="costs-th min-w-[7rem]">{{ t('costs_page.col_unit') }}</th>
              <th v-if="colVisible.category" class="costs-th min-w-[7rem]">{{ t('costs_page.col_category') }}</th>
              <th v-if="colVisible.submitter" class="costs-th min-w-[8rem]">{{ t('costs_page.col_submitter') }}</th>
              <th v-if="colVisible.description" class="costs-th min-w-[14rem]">{{ t('costs_page.col_description') }}</th>
              <th v-if="colVisible.fleet_source" class="costs-th min-w-[8rem] whitespace-nowrap">{{ t('costs_page.col_fleet_source') }}</th>
              <th v-if="colVisible.provider" class="costs-th min-w-[8rem]">{{ t('costs_page.col_provider') }}</th>
              <th v-if="colVisible.advance" class="costs-th costs-th--money min-w-[7rem] text-right">{{ t('costs_page.col_advance') }}</th>
              <th v-if="colVisible.unit_price" class="costs-th costs-th--money min-w-[6.5rem] text-right">{{ t('costs_page.col_unit_price') }}</th>
              <th v-if="colVisible.extra_fee" class="costs-th costs-th--money min-w-[6rem] text-right">{{ t('costs_page.col_extra_fee') }}</th>
              <th v-if="colVisible.payment" class="costs-th costs-th--money min-w-[8rem] text-right">{{ t('costs_page.col_payment') }}</th>
              <th v-if="colVisible.time" class="costs-th min-w-[6.5rem] whitespace-nowrap">{{ t('costs_page.col_time') }}</th>
              <th v-if="colVisible.owner" class="costs-th min-w-[8rem]">{{ t('costs_page.col_owner') }}</th>
              <th v-if="colVisible.receipt" class="costs-th min-w-[6rem]">{{ t('costs_page.col_receipt') }}</th>
              <th v-if="colVisible.legal_entity" class="costs-th min-w-[7rem]">{{ t('costs_page.col_legal_entity') }}</th>
              <th v-if="colVisible.notes" class="costs-th min-w-[6rem]">{{ t('costs_page.col_notes') }}</th>
              <th v-if="colVisible.trip" class="costs-th min-w-[5rem]">{{ t('costs_page.col_trip') }}</th>
              <th v-if="colVisible.trip_type" class="costs-th min-w-[7rem] whitespace-nowrap">{{ t('costs_page.col_trip_type') }}</th>
              <th v-if="canReconcileCosts && colVisible.actions" class="costs-th min-w-[9rem] whitespace-nowrap">{{ t('costs_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(c, idx) in displayedItems"
              :key="costRowKey(c)"
              class="costs-data-row"
              :class="[
                idx % 2 === 1 ? 'costs-data-row--alt' : '',
                isWizardEstimateLine(c) ? 'costs-data-row--estimate' : '',
              ]"
            >
              <td class="costs-td text-center tabular-nums text-slate-500 dark:text-slate-400">{{ rowIndex(idx) }}</td>
              <td v-if="colVisible.unit" class="costs-td">
                <span class="costs-pill">{{ unitLabel }}</span>
              </td>
              <td v-if="colVisible.category" class="costs-td">
                <span
                  v-if="tripTypeFromCost(c)"
                  class="costs-pill"
                  :class="tripTypePillClass(tripTypeFromCost(c))"
                >{{ labelTripType(tripTypeFromCost(c)) }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="colVisible.submitter" class="costs-td">{{ costSubmitterLabel(c) }}</td>
              <td v-if="colVisible.description" class="costs-td max-w-[20rem]">
                <span class="line-clamp-2" :title="c.description || ''">{{ c.description || '—' }}</span>
                <span
                  v-if="isWizardEstimateLine(c)"
                  class="mt-0.5 inline-flex rounded-full bg-violet-100/90 px-2 py-px text-[10px] font-semibold text-violet-900 dark:bg-violet-950/50 dark:text-violet-100"
                >
                  {{ estimateKindLabel(c) }}
                </span>
              </td>
              <td v-if="colVisible.fleet_source" class="costs-td whitespace-nowrap">
                <span class="costs-pill">{{ fleetModeLabel(tripFleetModeFromCost(c)) }}</span>
              </td>
              <td v-if="colVisible.provider" class="costs-td">{{ costProviderName(c) || '—' }}</td>
              <td v-if="colVisible.advance" class="costs-td costs-td--money text-right text-slate-400">—</td>
              <td v-if="colVisible.unit_price" class="costs-td costs-td--money">
                {{ isWizardEstimateLine(c) && Number(c.unit_price) > 0 ? formatVnd(c.unit_price) : '—' }}
              </td>
              <td v-if="colVisible.extra_fee" class="costs-td costs-td--money">
                {{ isWizardEstimateLine(c) && Number(c.extra_fee) > 0 ? formatVnd(c.extra_fee) : '—' }}
              </td>
              <td v-if="colVisible.payment" class="costs-td costs-td--money font-semibold text-slate-900 dark:text-slate-100">
                {{ formatVnd(c.amount) }}
              </td>
              <td v-if="colVisible.time" class="costs-td whitespace-nowrap tabular-nums text-slate-600 dark:text-slate-400">{{ formatDateDMY(c.created_at) }}</td>
              <td v-if="colVisible.owner" class="costs-td">
                <span v-if="c.confirmer?.name" class="costs-pill">{{ c.confirmer.name }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="colVisible.receipt" class="costs-td">
                <a
                  v-if="c.receipt_url"
                  :href="c.receipt_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-va-800 underline decoration-va-800/30 underline-offset-2 hover:text-va-900"
                  >{{ t('costs_page.view_receipt') }}</a
                >
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="colVisible.legal_entity" class="costs-td text-slate-500">{{ LEGAL_ENTITY_PLACEHOLDER }}</td>
              <td v-if="colVisible.notes" class="costs-td max-w-[12rem] text-slate-600">
                <span v-if="c.rejection_reason" class="line-clamp-2 text-rose-700" :title="c.rejection_reason">{{
                  c.rejection_reason
                }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="colVisible.trip" class="costs-td">
                <RouterLink
                  v-if="c.trip_id"
                  class="font-semibold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
                  :to="`/trips/${c.trip_id}`"
                  >#{{ c.trip_id }}</RouterLink
                >
                <span
                  v-else-if="!isWizardEstimateLine(c)"
                  class="costs-pill bg-amber-100/90 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100"
                >{{ t('costs_page.badge_standalone') }}</span>
                <span v-else>—</span>
              </td>
              <td v-if="colVisible.trip_type" class="costs-td">
                <span
                  class="costs-pill"
                  :class="isWizardEstimateLine(c) ? 'costs-pill--estimate' : 'costs-pill--type'"
                >{{ isWizardEstimateLine(c) ? estimateKindLabel(c) : typeLabel(c.type) }}</span>
              </td>
              <td v-if="canReconcileCosts && colVisible.actions" class="costs-td">
                <div v-if="!isWizardEstimateLine(c)" class="flex flex-wrap gap-1">
                  <template v-if="isCostPendingDecision(c)">
                    <button
                      type="button"
                      class="rounded-md bg-emerald-600 px-2 py-1 text-[11px] font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                      :disabled="decidingId != null"
                      @click="quickApproveCost(c)"
                    >
                      {{ t('costs_page.action_approve') }}
                    </button>
                    <button
                      type="button"
                      class="rounded-md border border-rose-300 bg-white px-2 py-1 text-[11px] font-semibold text-rose-800 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-200 dark:hover:bg-rose-950/40"
                      :disabled="decidingId != null"
                      @click="quickRejectCost(c)"
                    >
                      {{ t('costs_page.action_reject') }}
                    </button>
                  </template>
                  <button
                    type="button"
                    class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"
                    :disabled="deletingCostId != null"
                    @click="openDeleteModal(c)"
                  >
                    {{ t('costs_page.action_delete') }}
                  </button>
                </div>
                <span v-else class="text-[11px] text-slate-400">—</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!tableBusy && !hasTableRows" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
          {{ t('costs_page.empty_table') }}
        </div>
        <div v-if="tableBusy && !hasTableRows" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
          <span
            class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700"
            aria-hidden="true"
          />
          {{ t('costs_page.loading_table') }}
        </div>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200/90 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50">
        <span class="text-sm text-slate-600 dark:text-slate-400">
          {{
            t('costs_page.pagination_of', {
              current: meta.current_page ?? 1,
              last: meta.last_page ?? 1,
            })
          }}
          <span class="text-slate-400"> · </span>
          {{ meta.total ?? 0 }} {{ t('costs_page.pagination_records_suffix') }}
        </span>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="page(-1)"
          >
            {{ t('costs_page.prev') }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="page(1)"
          >
            {{ t('costs_page.next') }}
          </button>
        </div>
      </div>
    </div>
    </template>

    <section v-else class="space-y-3" aria-labelledby="costs-section-business-personnel">
      <AppFilterBar>
        <div class="flex flex-wrap items-center gap-2">
          <AppFilterDropdown
            root-class="shrink-0"
            show-chip-label
            :label="t('costs_page.filter_recorded_date')"
            :summary-text="bpFilterDateSummary"
            panel-class="min-w-[260px] p-3"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <label class="flex flex-1 flex-col gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('payments_hub.label_start_date') }}
                <input v-model="bpFilters.from" type="date" class="costs-input h-9 w-full text-sm" @change="onBpFiltersChange" />
              </label>
              <span class="hidden text-slate-400 sm:inline">—</span>
              <label class="flex flex-1 flex-col gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                {{ t('payments_hub.label_end_date') }}
                <input v-model="bpFilters.to" type="date" class="costs-input h-9 w-full text-sm" @change="onBpFiltersChange" />
              </label>
            </div>
          </AppFilterDropdown>

          <AppFilterDropdown
            root-class="shrink-0 min-w-0 max-w-full"
            show-chip-label
            :label="t('costs_page.filter_trip')"
            :summary-text="bpTripFilterSummaryShort"
            summary-text-class="max-w-[10rem]"
            panel-class="w-[min(100vw-1.5rem,320px)] p-2"
          >
            <input
              v-model="bpFilterTripSearch"
              type="search"
              class="costs-input mb-2 h-9 w-full text-sm"
              :placeholder="t('costs_page.trip_search_ph')"
              @click.stop
            />
            <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto">
              <li>
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    !bpFilters.trip_id
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyBpTripFilter($event, '')"
                >
                  {{ t('costs_page.trip_all') }}
                </button>
              </li>
              <li v-for="tripRow in filteredTripsForBpFilter" :key="tripRow.id">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    String(bpFilters.trip_id) === String(tripRow.id)
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyBpTripFilter($event, String(tripRow.id))"
                >
                  {{ formatTripPickerLabel(tripRow) }}
                </button>
              </li>
            </ul>
            <p v-if="tripOptionsRaw.length === 0 && !tripsForModalLoading" class="px-2 py-2 text-[11px] text-slate-500">
              {{ t('costs_page.trip_empty_scope') }}
            </p>
          </AppFilterDropdown>

          <button
            type="button"
            class="costs-btn-ghost h-9 shrink-0"
            :title="t('dashboard_analytics.filter_clear_all')"
            :aria-label="t('dashboard_analytics.filter_clear_all')"
            @click="resetBpFilters"
          >
            {{ t('dashboard_analytics.filter_clear_all') }}
          </button>
        </div>
      </AppFilterBar>

      <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
          <h3
            id="costs-section-business-personnel"
            class="text-sm font-semibold text-slate-900 dark:text-slate-100"
          >
            {{ t('costs_page.section_business_personnel') }}
            <span v-if="bpMeta.total != null" class="ml-1 font-normal text-slate-500 dark:text-slate-400">({{ bpMeta.total }})</span>
          </h3>
        </div>
        <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
          <table class="costs-sheet min-w-[900px] w-full">
            <thead>
              <tr>
                <th class="costs-th w-10 text-center">{{ t('costs_page.col_no') }}</th>
                <th class="costs-th min-w-[5rem]">{{ t('costs_page.col_trip') }}</th>
                <th class="costs-th min-w-[8rem]">{{ t('costs_page.col_personnel') }}</th>
                <th class="costs-th min-w-[12rem]">{{ t('costs_page.col_route') }}</th>
                <th class="costs-th costs-th--money min-w-[6.5rem] text-right">{{ t('costs_page.col_unit_price') }}</th>
                <th class="costs-th costs-th--money min-w-[6rem] text-right">{{ t('costs_page.col_extra_fee') }}</th>
                <th class="costs-th costs-th--money min-w-[7rem] text-right">{{ t('costs_page.col_payment') }}</th>
                <th class="costs-th min-w-[7rem]">{{ t('costs_page.col_submitter') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in bpLines"
                :key="`${row.trip_id}-${row.line_no}`"
                class="costs-data-row"
                :class="idx % 2 === 1 ? 'costs-data-row--alt' : ''"
              >
                <td class="costs-td text-center tabular-nums text-slate-500 dark:text-slate-400">{{ idx + 1 }}</td>
                <td class="costs-td">
                  <RouterLink
                    :to="{ name: 'tripDetail', params: { id: row.trip_id } }"
                    class="font-semibold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
                  >
                    #{{ row.trip_id }}
                  </RouterLink>
                </td>
                <td class="costs-td">
                  <span class="line-clamp-2">{{ row.personnel_label || '—' }}</span>
                  <span v-if="row.guests" class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">{{ row.guests }}</span>
                </td>
                <td class="costs-td text-slate-700 dark:text-slate-300">
                  <span v-if="row.pickup || row.dropoff">{{ row.pickup || '…' }} → {{ row.dropoff || '…' }}</span>
                  <span v-else-if="row.request_origin || row.request_destination">
                    {{ row.request_origin || '…' }} → {{ row.request_destination || '…' }}
                  </span>
                  <span v-else>—</span>
                </td>
                <td class="costs-td costs-td--money">{{ formatVnd(row.unit_price) }}</td>
                <td class="costs-td costs-td--money">{{ formatVnd(row.extra_fee) }}</td>
                <td class="costs-td costs-td--money font-semibold text-slate-900 dark:text-slate-100">{{ formatVnd(row.amount_total) }}</td>
                <td class="costs-td">{{ row.requester_name || '—' }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="bpLoading && !bpLines.length" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
            <span
              class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700"
              aria-hidden="true"
            />
            {{ t('costs_page.business_personnel_loading') }}
          </div>
          <div v-else-if="!bpLoading && !bpLines.length" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ t('costs_page.business_personnel_empty') }}
          </div>
        </div>
      </div>
    </section>

    <Teleport to="body">
      <div
        v-if="addCostModalOpen"
        class="fixed inset-0 z-[100] flex items-end justify-center p-3 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="costs-add-title"
      >
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]" aria-hidden="true" @click="closeAddCostModal" />
        <div
          class="relative z-10 flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/10 max-sm:rounded-t-2xl max-sm:rounded-b-none"
          @click.stop
        >
          <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
              <h2 id="costs-add-title" class="text-base font-semibold text-slate-900">
                {{ addCostTripOptional ? t('costs_page.modal_add_standalone_title') : t('costs_page.modal_add_title') }}
              </h2>
              <p v-if="addCostTripOptional" class="mt-1 text-xs text-slate-500">{{ t('costs_page.modal_add_standalone_hint') }}</p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
              :aria-label="t('costs_page.modal_close_aria')"
              @click="closeAddCostModal"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submitCost">
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-5 py-4">
            <div class="grid gap-4">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">
                  {{ t('costs_page.modal_trip_label') }}
                  <span v-if="!addCostTripOptional" class="text-rose-600">*</span>
                  <span v-else class="font-normal text-slate-500">({{ t('costs_page.modal_trip_optional') }})</span>
                </label>
                <input
                  v-model="tripPickerSearch"
                  type="search"
                  class="costs-input mb-2 w-full"
                  :placeholder="t('costs_page.modal_trip_search_ph')"
                  autocomplete="off"
                />
                <select
                  v-model="costForm.trip_id"
                  class="costs-input w-full font-medium"
                  :required="!addCostTripOptional && !tripsForModalLoading"
                  :disabled="tripsForModalLoading"
                >
                  <option v-if="addCostTripOptional" value="">
                    {{ t('costs_page.modal_trip_none') }}
                  </option>
                  <option v-else disabled value="">
                    {{ tripsForModalLoading ? t('costs_page.modal_trip_loading') : t('costs_page.modal_trip_pick') }}
                  </option>
                  <option v-for="t in filteredTripsForPicker" :key="t.id" :value="String(t.id)">
                    {{ formatTripPickerLabel(t) }}
                  </option>
                </select>
                <p
                  v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForPicker.length"
                  class="mt-1 text-[11px] text-amber-800"
                >
                  {{ t('costs_page.modal_trip_no_keyword') }}
                </p>
                <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-1 text-[11px] text-slate-500">
                  {{ t('costs_page.modal_trip_empty_scope') }}
                </p>
              </div>
              <div>
                <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                  <label class="block text-xs font-medium text-slate-700">{{ t('costs_page.modal_cost_type') }}</label>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-teal-200 bg-teal-50 px-2 py-1 text-[11px] font-medium text-teal-900 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-200 dark:hover:bg-teal-900/60"
                    @click="openQuickAddType"
                  >
                    <PlusCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('costs_page.modal_add_type_quick') }}
                  </button>
                </div>
                <select v-model="costForm.type" class="costs-input w-full">
                  <option v-for="opt in modalCostTypeOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">{{
                  t('costs_page.modal_amount_label', { currency: costForm.currency })
                }}</label>
                <input
                  :value="costAmountDisplay"
                  type="text"
                  inputmode="numeric"
                  autocomplete="off"
                  required
                  class="costs-input w-full"
                  :placeholder="t('costs_page.modal_amount_ph')"
                  @input="onCostAmountInput"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700">{{ t('costs_page.modal_desc_label') }}</label>
                <input
                  v-model="costForm.description"
                  type="text"
                  class="costs-input w-full"
                  :placeholder="t('costs_page.modal_desc_ph')"
                />
              </div>
            </div>
            <p v-if="costMsg" class="mt-3 text-sm" :class="costMsgIsError ? 'text-rose-700' : 'text-emerald-800'">
              {{ costMsg }}
            </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
              <button type="button" class="costs-btn-ghost" :disabled="submitting" @click="closeAddCostModal">{{
                t('app.cancel')
              }}</button>
              <button type="submit" class="costs-btn-primary" :disabled="submitting || tripsForModalLoading">
                <span
                  v-if="submitting"
                  class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                />
                {{ t('costs_page.modal_submit') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="rejectModalOpen && rejectTarget"
        class="fixed inset-0 z-[105] flex items-end justify-center p-3 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="costs-reject-title"
      >
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]" aria-hidden="true" @click="closeRejectModal" />
        <div
          class="relative z-10 flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-rose-200/90 bg-white shadow-2xl ring-1 ring-rose-900/10 dark:border-rose-900/50 dark:bg-slate-900 max-sm:rounded-t-2xl max-sm:rounded-b-none"
          @click.stop
        >
          <div
            class="flex shrink-0 items-start gap-3 border-b border-rose-100 bg-gradient-to-r from-rose-50/90 via-white to-white px-5 py-4 dark:border-rose-900/40 dark:from-rose-950/40 dark:via-slate-900 dark:to-slate-900"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 shadow-inner dark:bg-rose-950/60 dark:text-rose-200"
              aria-hidden="true"
            >
              <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h2 id="costs-reject-title" class="text-base font-semibold text-rose-950 dark:text-rose-100">
                {{ t('costs_page.reject_modal_title') }}
              </h2>
              <p
                class="mt-2 rounded-lg border border-rose-200/80 bg-white/80 px-3 py-2 text-xs font-medium text-rose-900 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-100"
              >
                {{ t('costs_page.reject_modal_summary', { id: rejectTarget.id, trip: rejectTarget.trip_id ?? '—' }) }}
                <span v-if="rejectTarget.amount != null" class="mt-1 block tabular-nums text-slate-600 dark:text-slate-300">
                  {{ formatVnd(rejectTarget.amount) }}
                </span>
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-100/80 hover:text-rose-800 dark:hover:bg-rose-950/50 dark:hover:text-rose-200"
              :aria-label="t('costs_page.modal_close_aria')"
              @click="closeRejectModal"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-5 py-4">
            <label class="mb-1.5 block text-xs font-medium text-slate-700 dark:text-slate-300">{{
              t('costs_page.reject_modal_reason_label')
            }}</label>
            <textarea
              v-model="rejectReasonInput"
              rows="4"
              class="costs-input min-h-[6rem] w-full resize-y text-sm"
              :placeholder="t('costs_page.reject_modal_reason_placeholder')"
              autocomplete="off"
            />
            </div>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 dark:border-slate-700">
              <button type="button" class="costs-btn-ghost" :disabled="decidingId != null" @click="closeRejectModal">
                {{ t('costs_page.reject_modal_cancel') }}
              </button>
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50 dark:bg-rose-700 dark:hover:bg-rose-600"
                :disabled="decidingId != null"
                @click="submitRejectModal"
              >
                <span
                  v-if="decidingId != null"
                  class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                />
                {{ t('costs_page.reject_modal_submit') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="deleteModalOpen && deleteTarget"
        class="fixed inset-0 z-[105] flex items-end justify-center p-3 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="costs-delete-title"
      >
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]" aria-hidden="true" @click="closeDeleteModal" />
        <div
          class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl border border-rose-200/90 bg-white shadow-2xl ring-1 ring-rose-900/10 dark:border-rose-900/50 dark:bg-slate-900 max-sm:rounded-t-2xl max-sm:rounded-b-none"
          @click.stop
        >
          <div
            class="flex items-start gap-3 border-b border-rose-100 bg-gradient-to-r from-rose-50/90 via-white to-white px-5 py-4 dark:border-rose-900/40 dark:from-rose-950/40 dark:via-slate-900 dark:to-slate-900"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 shadow-inner dark:bg-rose-950/60 dark:text-rose-200"
              aria-hidden="true"
            >
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h2 id="costs-delete-title" class="text-sm font-semibold text-rose-950 dark:text-rose-100">
                {{ t('costs_page.delete_modal_title') }}
              </h2>
              <p
                class="mt-2 rounded-lg border border-rose-200/80 bg-white/80 px-3 py-2 text-xs font-medium text-rose-900 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-100"
              >
                {{ t('costs_page.delete_modal_summary', { id: deleteTarget.id, trip: deleteTarget.trip_id ?? '—' }) }}
                <span v-if="deleteTarget.amount != null" class="mt-1 block tabular-nums text-slate-600 dark:text-slate-300">
                  {{ formatVnd(deleteTarget.amount) }}
                </span>
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-100/80 hover:text-rose-800 dark:hover:bg-rose-950/50 dark:hover:text-rose-200"
              :aria-label="t('costs_page.modal_close_aria')"
              @click="closeDeleteModal"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <div class="flex items-center justify-end gap-2 px-5 py-4">
            <button type="button" class="costs-btn-ghost" :disabled="deletingCostId != null" @click="closeDeleteModal">
              {{ t('costs_page.delete_modal_cancel') }}
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50 dark:bg-rose-700 dark:hover:bg-rose-600"
              :disabled="deletingCostId != null"
              @click="submitDeleteModal"
            >
              <span
                v-if="deletingCostId != null"
                class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
              />
              {{ t('costs_page.delete_modal_submit') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="quickAddTypeOpen"
        class="fixed inset-0 z-[110] flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="costs-quick-type-title"
      >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[1px]" aria-hidden="true" @click="quickAddTypeOpen = false" />
        <div
          class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-900/10"
          @click.stop
        >
          <div class="border-b border-slate-100 px-4 py-3">
            <h2 id="costs-quick-type-title" class="text-sm font-semibold text-slate-900">{{ t('costs_page.quick_type_title') }}</h2>
          </div>
          <div class="space-y-3 p-4">
            <label class="block text-xs font-medium text-slate-700">{{ t('costs_page.quick_type_name') }}</label>
            <input
              v-model="quickAddTypeLabel"
              type="text"
              class="costs-input w-full"
              :placeholder="t('costs_page.quick_type_ph')"
              maxlength="80"
              @keydown.enter.prevent="submitQuickAddType"
            />
            <p v-if="quickAddTypeError" class="text-xs text-rose-600">{{ quickAddTypeError }}</p>
            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="costs-btn-ghost text-sm" @click="quickAddTypeOpen = false">{{ t('app.cancel') }}</button>
              <button type="button" class="costs-btn-primary text-sm" @click="submitQuickAddType">{{
                t('costs_page.quick_type_save')
              }}</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, FunnelIcon, PlusCircleIcon, ViewColumnsIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import {
  listTripCosts,
  listBusinessPersonnelCostLines,
  listWizardEstimateLines,
  submitTripCost,
  submitStandaloneTripCost,
  decideTripCost,
  deleteTripCost,
} from '../../api/costs'
import { listTrips } from '../../api/trips'
import { newIdempotencyKey } from '../../util/idempotency'
import { formatVnd, formatVndDigitsInput, labelTripType } from '../../util/labels'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'

const { t, te, locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()

/** @type {import('vue').Ref<'all_trips' | 'standalone' | 'business_personnel'>} */
const activeTab = ref('all_trips')

const standaloneTabTotal = ref(0)

const DEFAULT_PER_PAGE = 25

const unitLabel = computed(() => t('costs_page.unit_label_const'))
const LEGAL_ENTITY_PLACEHOLDER = '—'

const BUILTIN_COST_TYPES = ['fuel', 'toll', 'parking', 'other']
const EXTRA_TYPES_STORAGE_KEY = 'va.costs.extra_types_v1'
const COSTS_FILTER_CONTROL_VISIBILITY_KEY = 'va.costs.filter_control_visibility_v1'
const COSTS_COL_VISIBILITY_KEY = 'va.costs.col_visibility_v1'
const FILTER_CONTROL_IDS = ['status', 'type', 'trip_type', 'date', 'trip', 'search', 'per_page', 'amount_range', 'provider', 'fleet_mode']
const COL_IDS = [
  'unit',
  'category',
  'submitter',
  'description',
  'fleet_source',
  'provider',
  'advance',
  'unit_price',
  'extra_fee',
  'payment',
  'time',
  'owner',
  'receipt',
  'legal_entity',
  'notes',
  'trip',
  'trip_type',
  'actions',
]

function defaultFilterControlVisibility() {
  return Object.fromEntries(FILTER_CONTROL_IDS.map((id) => [id, true]))
}

function defaultColVisibility() {
  return Object.fromEntries(COL_IDS.map((id) => [id, true]))
}

const extraCostTypes = ref([])

function typeLabel(slug) {
  if (!slug) return '—'
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  const hit = extraCostTypes.value.find((x) => x.slug === slug)
  return hit?.label ?? slug
}

function isWizardEstimateLine(c) {
  return Boolean(c?.is_wizard_estimate_line)
}

function costRowKey(c) {
  if (isWizardEstimateLine(c)) return `wiz-${c.trip_id}-${c.line_key}`
  return c.id
}

function mapWizardLineToCostRow(line) {
  return {
    id: `wiz-${line.trip_id}-${line.line_key}`,
    is_wizard_estimate_line: true,
    line_key: line.line_key,
    estimate_kind: line.estimate_kind,
    trip_id: line.trip_id,
    trip: line.trip,
    amount: line.amount,
    unit_price: line.unit_price,
    extra_fee: line.extra_fee,
    description: line.description,
    type: line.type || 'wizard_estimate',
    status: 'estimate',
    created_at: line.created_at,
    requester_name: line.requester_name,
  }
}

function estimateKindLabel(c) {
  const k = c?.estimate_kind
  if (k === 'business_row') return t('costs_page.cost_type_estimate_e2')
  if (k === 'passenger_row') return t('costs_page.cost_type_estimate_passenger')
  if (k === 'cargo_row') return t('costs_page.cost_type_estimate_cargo')
  return typeLabel('wizard_estimate')
}

function estimatePassesListFilters(c) {
  if (filters.status && filters.status !== 'submitted') return false
  if (filters.type && filters.type !== 'wizard_estimate') return false
  return passesClientRowFiltersForCost(c)
}

const TRIP_TYPE_SLUGS = ['point_to_point', 'cargo', 'business', 'door_to_door']

function tripTypeFromCost(c) {
  return c?.trip?.dispatch_request?.trip_type ?? c?.trip?.dispatchRequest?.trip_type ?? null
}

function dispatchRequestFromCost(c) {
  return c?.trip?.dispatch_request ?? c?.trip?.dispatchRequest ?? null
}

/** Người đề xuất phiếu (công tác); chi phí phát sinh vẫn hiển thị người ghi nhận. */
function costSubmitterLabel(c) {
  if (isWizardEstimateLine(c)) {
    return c.requester_name || dispatchRequestFromCost(c)?.requester?.name || '—'
  }
  const dr = dispatchRequestFromCost(c)
  const requester = dr?.requester?.name
  if (tripTypeFromCost(c) === 'business' && requester) return requester
  return c.creator?.name || '—'
}

function parseAmountFilterDigits(val) {
  const d = String(val ?? '').replace(/\D/g, '')
  if (!d) return null
  const n = Number(d)
  return Number.isFinite(n) && n >= 0 ? n : null
}

function rowAmountMatchesFilter(amount) {
  const min = parseAmountFilterDigits(filters.amount_min)
  const max = parseAmountFilterDigits(filters.amount_max)
  const a = Number(amount ?? 0)
  if (min != null && a < min) return false
  if (max != null && a > max) return false
  return true
}

function costProviderName(c) {
  return String(c?.trip?.transport_provider?.name ?? c?.trip?.transportProvider?.name ?? '').trim()
}

function tripFleetModeFromCost(c) {
  const trip = c?.trip
  if (!trip) return 'unspecified'
  const providerId = trip.transport_provider_id ?? trip.transportProvider?.id ?? null
  const vehicleId = trip.vehicle_id ?? trip.vehicle?.id ?? null
  const providerType = trip.transport_provider?.type ?? trip.transportProvider?.type ?? null
  if (providerId) {
    if (providerType === 'taxi') return 'taxi'
    return 'vendor_hire'
  }
  if (vehicleId) return 'internal'
  return 'unspecified'
}

function fleetModeLabel(mode) {
  const map = {
    internal: 'dashboard_analytics.fleet_internal',
    vendor_hire: 'dashboard_analytics.fleet_vendor_hire',
    taxi: 'dashboard_analytics.fleet_taxi',
    unspecified: 'dashboard_analytics.fleet_unspecified',
  }
  const key = map[mode]
  return key && te(key) ? t(key) : mode || '—'
}

function providerFilterMatchesRecordedCost(c) {
  const p = filters.provider
  if (!p) return true
  if (p === '__none__') return !costProviderName(c)
  return costProviderName(c) === p
}

const TRIP_TYPE_PILL_CLASSES = {
  point_to_point: 'bg-sky-100/80 text-sky-800',
  cargo: 'bg-amber-100/80 text-amber-800',
  business: 'bg-violet-100/80 text-violet-800',
  door_to_door: 'bg-teal-100/80 text-teal-800',
}

function tripTypePillClass(slug) {
  return TRIP_TYPE_PILL_CLASSES[slug] ?? ''
}

const modalCostTypeOptions = computed(() => {
  const rows = BUILTIN_COST_TYPES.map((value) => ({ value, label: typeLabel(value) }))
  for (const x of extraCostTypes.value) {
    if (!rows.some((r) => r.value === x.slug)) {
      rows.push({ value: x.slug, label: x.label })
    }
  }
  return rows
})

const loading = ref(false)
const estimatesLoading = ref(false)
const items = ref([])
const estimateItems = ref([])
const meta = ref({})

const bpLoading = ref(false)
const bpLines = ref([])
const bpMeta = ref({ total: 0 })
const bpFilterTripSearch = ref('')
const bpFilters = reactive({
  trip_id: '',
  from: '',
  to: '',
})
const searchQ = ref('')
const funnelDetailsRef = ref(null)
const columnPickerRef = ref(null)
const costsFilterBarRef = ref(null)
useDetailsAutoClose(funnelDetailsRef)
useDetailsAutoClose(columnPickerRef)
useDetailsAutoCloseWithin(costsFilterBarRef)

const filterControlVisible = reactive(defaultFilterControlVisibility())
const colVisible = reactive(defaultColVisibility())

function tableColLabel(colId) {
  const keys = {
    unit: 'col_unit',
    category: 'col_category',
    submitter: 'col_submitter',
    description: 'col_description',
    fleet_source: 'col_fleet_source',
    provider: 'col_provider',
    advance: 'col_advance',
    unit_price: 'col_unit_price',
    extra_fee: 'col_extra_fee',
    payment: 'col_payment',
    time: 'col_time',
    owner: 'col_owner',
    receipt: 'col_receipt',
    legal_entity: 'col_legal_entity',
    notes: 'col_notes',
    trip: 'col_trip',
    trip_type: 'col_trip_type',
    actions: 'col_actions',
  }
  const k = keys[colId]
  return k ? t(`costs_page.${k}`) : colId
}

const filterControlDefs = computed(() => [
  { id: 'status', label: t('filter_bar.status') },
  { id: 'type', label: t('costs_page.filter_cost_type') },
  { id: 'trip_type', label: t('costs_page.filter_trip_type') },
  { id: 'date', label: t('costs_page.filter_recorded_date') },
  { id: 'trip', label: t('costs_page.filter_trip') },
  { id: 'amount_range', label: t('costs_page.filter_amount_range') },
  { id: 'provider', label: t('costs_page.filter_provider') },
  { id: 'fleet_mode', label: t('dashboard_analytics.filter_fleet') },
  { id: 'search', label: t('costs_page.filter_search_page') },
  { id: 'per_page', label: t('filter_bar.per_page') },
])

const addCostModalOpen = ref(false)
const addCostTripOptional = ref(false)
const tripPickerSearch = ref('')
const filterTripSearch = ref('')
const tripOptionsRaw = ref([])
const tripsForModalLoading = ref(false)
const costMsgIsError = ref(false)

const decidingId = ref(null)
const rejectModalOpen = ref(false)
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const rejectTarget = ref(null)
const rejectReasonInput = ref('')

const deleteModalOpen = ref(false)
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const deleteTarget = ref(null)
const deletingCostId = ref(null)

const filters = reactive({
  status: '',
  type: '',
  trip_type: '',
  trip_id: '',
  from: '',
  to: '',
  provider: '',
  fleet_mode: '',
  amount_min: '',
  amount_max: '',
  page: 1,
  per_page: DEFAULT_PER_PAGE,
})

function hydrateCostStatusFromRoute() {
  if (!('status' in route.query)) {
    filters.status = 'submitted'
    return
  }
  const s = route.query.status
  const v = Array.isArray(s) ? s[0] : s
  filters.status = typeof v === 'string' ? v : ''
}

function isCostPendingDecision(c) {
  if (isWizardEstimateLine(c)) return false
  const s = String(c?.status ?? '').toLowerCase()
  return s === 'submitted' || s === 'draft'
}

async function quickApproveCost(c) {
  if (!c?.id || decidingId.value != null) return
  decidingId.value = c.id
  try {
    await decideTripCost(c.id, { decision: 'confirm' })
    await reload()
  } catch (e) {
    showAppErrorFromApi(e, t('costs_page.decide_err'))
  } finally {
    decidingId.value = null
  }
}

function openRejectModal(c) {
  if (!c?.id || decidingId.value != null) return
  rejectTarget.value = c
  rejectReasonInput.value = ''
  rejectModalOpen.value = true
}

function closeRejectModal() {
  rejectModalOpen.value = false
  rejectTarget.value = null
  rejectReasonInput.value = ''
}

async function submitRejectModal() {
  const c = rejectTarget.value
  if (!c?.id || decidingId.value != null) return
  decidingId.value = c.id
  try {
    await decideTripCost(c.id, {
      decision: 'reject',
      reason: rejectReasonInput.value.trim() || undefined,
    })
    closeRejectModal()
    await reload()
  } catch (e) {
    showAppErrorFromApi(e, t('costs_page.decide_err'))
  } finally {
    decidingId.value = null
  }
}

function quickRejectCost(c) {
  openRejectModal(c)
}

function openDeleteModal(c) {
  if (!c?.id || isWizardEstimateLine(c)) return
  deleteTarget.value = c
  deleteModalOpen.value = true
}

function closeDeleteModal() {
  deleteModalOpen.value = false
  deleteTarget.value = null
}

async function submitDeleteModal() {
  const c = deleteTarget.value
  if (!c?.id || deletingCostId.value != null) return
  deletingCostId.value = c.id
  try {
    await deleteTripCost(c.id)
    closeDeleteModal()
    await reload()
  } catch (e) {
    showAppErrorFromApi(e, t('costs_page.delete_err'))
  } finally {
    deletingCostId.value = null
  }
}

const costForm = ref({ trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' })
/** Chỉ chữ số — dùng format VND khi gõ */
const costAmountDigits = ref('')
const quickAddTypeOpen = ref(false)
const quickAddTypeLabel = ref('')
const quickAddTypeError = ref('')
const submitting = ref(false)
const costMsg = ref('')

const canReconcileCosts = computed(() => auth.hasPermission('trip.cost.reconcile'))
const canSubmitTripCost = computed(() => auth.hasPermission('trip.record.create'))
const canSubmitStandaloneCost = computed(() =>
  auth.hasAnyPermission(['trip.record.create', 'trip.cost.reconcile']),
)
const showAddCostButton = computed(() => {
  if (activeTab.value === 'standalone') return canSubmitStandaloneCost.value
  return canSubmitTripCost.value
})
const colControlDefs = computed(() => {
  const ids = COL_IDS.filter((id) => id !== 'actions' || canReconcileCosts.value)
  return ids.map((id) => ({ id, label: tableColLabel(id) }))
})
const costAmountDisplay = computed(() => formatVndDigitsInput(costAmountDigits.value))

watch(extraCostTypes, (v) => {
  try {
    localStorage.setItem(EXTRA_TYPES_STORAGE_KEY, JSON.stringify(v))
  } catch {
    /* ignore */
  }
}, { deep: true })

watch(
  filterControlVisible,
  (v) => {
    try {
      localStorage.setItem(COSTS_FILTER_CONTROL_VISIBILITY_KEY, JSON.stringify({ ...v }))
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
      localStorage.setItem(COSTS_COL_VISIBILITY_KEY, JSON.stringify({ ...v }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

const statusFilterOptions = computed(() => {
  const fa = t('requests_page.filter_option_any')
  const keys = ['draft', 'submitted', 'confirmed', 'rejected']
  return [
    { value: '', label: fa },
    ...keys.map((value) => ({
      value,
      label: te(`dashboard_analytics.cost_status_${value}`) ? t(`dashboard_analytics.cost_status_${value}`) : value,
    })),
  ]
})

const typeFilterOptions = computed(() => {
  const rows = [{ value: '', label: t('requests_page.filter_option_any') }]
  rows.push({ value: 'wizard_estimate', label: typeLabel('wizard_estimate') })
  for (const value of BUILTIN_COST_TYPES) {
    rows.push({ value, label: typeLabel(value) })
  }
  for (const x of extraCostTypes.value) {
    if (!rows.some((r) => r.value === x.slug)) {
      rows.push({ value: x.slug, label: x.label })
    }
  }
  return rows
})

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_option_any') },
  ...TRIP_TYPE_SLUGS.map((value) => ({ value, label: labelTripType(value) })),
])

const fleetModeFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_option_any') },
  { value: 'internal', label: fleetModeLabel('internal') },
  { value: 'vendor_hire', label: fleetModeLabel('vendor_hire') },
  { value: 'taxi', label: fleetModeLabel('taxi') },
  { value: 'unspecified', label: fleetModeLabel('unspecified') },
])

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.type) n++
  if (filters.trip_type) n++
  if (filters.trip_id) n++
  if (filters.from || filters.to) n++
  if (filters.provider) n++
  if (filters.fleet_mode) n++
  if (filters.amount_min || filters.amount_max) n++
  if (filters.per_page !== DEFAULT_PER_PAGE) n++
  if (searchQ.value.trim()) n++
  return n
})

const amountRangeSummary = computed(() => {
  if (!filters.amount_min && !filters.amount_max) return t('dashboard_analytics.filter_all')
  const min = filters.amount_min ? formatVndDigitsInput(String(filters.amount_min).replace(/\D/g, '')) : '…'
  const max = filters.amount_max ? formatVndDigitsInput(String(filters.amount_max).replace(/\D/g, '')) : '…'
  return t('costs_page.amount_range_summary', { min, max })
})

const providerFilterOptions = computed(() => {
  const names = new Set()
  let hasEmpty = false
  for (const c of items.value) {
    const name = costProviderName(c)
    if (name) names.add(name)
    else hasEmpty = true
  }
  const rows = [{ value: '', label: t('costs_page.filter_provider_all') }]
  if (hasEmpty) rows.push({ value: '__none__', label: t('costs_page.filter_provider_none') })
  for (const name of [...names].sort((a, b) => a.localeCompare(b, 'vi'))) {
    rows.push({ value: name, label: name })
  }
  return rows
})

const providerFilterSummary = computed(() => {
  if (!filters.provider) return t('dashboard_analytics.filter_all')
  const hit = providerFilterOptions.value.find((o) => o.value === filters.provider)
  return hit?.label ?? filters.provider
})

const fleetModeFilterSummary = computed(() => {
  if (!filters.fleet_mode) return t('dashboard_analytics.filter_all')
  return fleetModeLabel(filters.fleet_mode)
})

function passesClientRowFiltersForCost(c) {
  if (!providerFilterMatchesRecordedCost(c)) return false
  if (!rowAmountMatchesFilter(c.amount)) return false
  return true
}

const filterDateSummary = computed(() => {
  if (!filters.from && !filters.to) return t('dashboard_analytics.filter_all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

function tripMatchesSearch(tripRow, qRaw) {
  const q = qRaw.trim().toLowerCase()
  if (!q) return true
  const dr = tripRow.dispatch_request ?? tripRow.dispatchRequest
  const id = String(tripRow.id)
  const o = String(dr?.origin ?? '').toLowerCase()
  const d = String(dr?.destination ?? '').toLowerCase()
  return id.includes(q) || o.includes(q) || d.includes(q)
}

const filteredTripsForPicker = computed(() => {
  const q = tripPickerSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((tripRow) => tripMatchesSearch(tripRow, q))
})

const filteredTripsForFilter = computed(() => {
  const q = filterTripSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((tripRow) => tripMatchesSearch(tripRow, q))
})

const filteredTripsForBpFilter = computed(() => {
  const q = bpFilterTripSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((tripRow) => tripMatchesSearch(tripRow, q))
})

const bpFilterDateSummary = computed(() => {
  if (!bpFilters.from && !bpFilters.to) return t('filter_bar.all')
  return `${bpFilters.from || '…'} → ${bpFilters.to || '…'}`
})

const bpTripFilterSummaryShort = computed(() => {
  if (!bpFilters.trip_id) return t('filter_bar.all')
  const id = Number(bpFilters.trip_id)
  const tr = tripOptionsRaw.value.find((tripRow) => Number(tripRow.id) === id)
  if (tr) {
    const dr = tr.dispatch_request ?? tr.dispatchRequest
    const o = (dr?.origin ?? '—').trim().slice(0, 22)
    const d = (dr?.destination ?? '—').trim().slice(0, 22)
    return `#${tr.id} · ${o} → ${d}`
  }
  return `#${bpFilters.trip_id}`
})

const selectedFilterTrip = computed(() => {
  if (!filters.trip_id) return null
  const id = Number(filters.trip_id)
  if (!Number.isFinite(id)) return null
  return tripOptionsRaw.value.find((tripRow) => Number(tripRow.id) === id) ?? null
})

const tripFilterSummaryFull = computed(() => {
  if (!filters.trip_id) return t('costs_page.trip_all')
  const tr = selectedFilterTrip.value
  return tr ? formatTripPickerLabel(tr) : t('costs_page.trip_filter_chip', { id: filters.trip_id })
})

const tripFilterSummaryShort = computed(() => {
  if (!filters.trip_id) return t('dashboard_analytics.filter_all')
  const tr = selectedFilterTrip.value
  if (tr) {
    const dr = tr.dispatch_request ?? tr.dispatchRequest
    const o = (dr?.origin ?? '—').trim().slice(0, 22)
    const d = (dr?.destination ?? '—').trim().slice(0, 22)
    return `#${tr.id} · ${o} → ${d}`
  }
  return `#${filters.trip_id}`
})

const displayedItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  const est =
    activeTab.value === 'standalone'
      ? []
      : estimateItems.value.filter((c) => estimatePassesListFilters(c))
  let list = items.value.filter((c) => passesClientRowFiltersForCost(c))
  const merged = activeTab.value === 'standalone' ? list : [...est, ...list]
  if (!q) return merged
  return merged.filter((c) => {
    const d = String(c.description ?? '').toLowerCase()
    const creator = String(c.creator?.name ?? '').toLowerCase()
    const submitter = String(costSubmitterLabel(c) ?? '').toLowerCase()
    const ty = String(c.type ?? '').toLowerCase()
    const kind = String(c.estimate_kind ?? '').toLowerCase()
    const trip = String(c.trip_id ?? '')
    const prov = costProviderName(c).toLowerCase()
    const fleet = fleetModeLabel(tripFleetModeFromCost(c)).toLowerCase()
    return (
      d.includes(q) ||
      creator.includes(q) ||
      submitter.includes(q) ||
      ty.includes(q) ||
      kind.includes(q) ||
      trip.includes(q) ||
      prov.includes(q) ||
      fleet.includes(q)
    )
  })
})

const hasTableRows = computed(() => displayedItems.value.length > 0)
const tableBusy = computed(() => loading.value || estimatesLoading.value)

function rowIndex(idx) {
  const page = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? DEFAULT_PER_PAGE
  return (page - 1) * per + idx + 1
}

function loadExtraCostTypesFromStorage() {
  try {
    const raw = localStorage.getItem(EXTRA_TYPES_STORAGE_KEY)
    const arr = raw ? JSON.parse(raw) : []
    extraCostTypes.value = Array.isArray(arr) ? arr.filter((x) => x?.slug && x?.label) : []
  } catch {
    extraCostTypes.value = []
  }
}

function slugifyCostTypeLabel(label) {
  const s = String(label)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .slice(0, 64)
  return s || `loai_${Date.now()}`
}

function openQuickAddType() {
  quickAddTypeError.value = ''
  quickAddTypeLabel.value = ''
  quickAddTypeOpen.value = true
}

function submitQuickAddType() {
  quickAddTypeError.value = ''
  const label = quickAddTypeLabel.value.trim()
  if (!label) {
    quickAddTypeError.value = t('costs_page.err_type_name')
    return
  }
  let slug = slugifyCostTypeLabel(label)
  if (BUILTIN_COST_TYPES.includes(slug)) {
    slug = `${slug}_${Date.now()}`
  }
  if (extraCostTypes.value.some((x) => x.slug === slug)) {
    costForm.value.type = slug
    quickAddTypeOpen.value = false
    quickAddTypeLabel.value = ''
    return
  }
  extraCostTypes.value = [...extraCostTypes.value, { slug, label }]
  costForm.value.type = slug
  quickAddTypeOpen.value = false
  quickAddTypeLabel.value = ''
}

function onCostAmountInput(e) {
  const el = e?.target
  if (!el) return
  const raw = String(el.value ?? '').replace(/\D/g, '')
  if (raw.length > 15) {
    costAmountDigits.value = raw.slice(0, 15)
  } else {
    costAmountDigits.value = raw
  }
}

function statusLabel(s) {
  if (!s) return '—'
  const key = `dashboard_analytics.cost_status_${s}`
  return te(key) ? t(key) : s
}

function formatDateDMY(iso) {
  if (!iso) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(iso).toLocaleDateString(loc, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return '—'
  }
}

function formatTripPickerLabel(tripRow) {
  const dr = tripRow.dispatch_request ?? tripRow.dispatchRequest
  const o = (dr?.origin ?? '—').trim().slice(0, 40)
  const d = (dr?.destination ?? '—').trim().slice(0, 40)
  const dep = formatDateDMY(tripRow.depart_at)
  const typSlug = dr?.trip_type ?? null
  const typStr = typSlug ? ` [${labelTripType(typSlug)}]` : ''
  return `#${tripRow.id}${typStr} · ${o} → ${d} · ${dep}`
}

async function loadTripPickerOptions() {
  tripsForModalLoading.value = true
  try {
    const res = await listTrips({ per_page: 100, page: 1 })
    tripOptionsRaw.value = res.items ?? []
  } catch {
    tripOptionsRaw.value = []
  } finally {
    tripsForModalLoading.value = false
  }
}

async function openAddCostModal() {
  costMsg.value = ''
  costMsgIsError.value = false
  tripPickerSearch.value = ''
  costAmountDigits.value = ''
  addCostTripOptional.value = activeTab.value === 'standalone'
  costForm.value = { trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' }
  addCostModalOpen.value = true
  await loadTripPickerOptions()
}

function closeAddCostModal() {
  addCostModalOpen.value = false
  costMsg.value = ''
  costMsgIsError.value = false
}

watch([addCostModalOpen, rejectModalOpen, deleteModalOpen], () => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = addCostModalOpen.value || rejectModalOpen.value || deleteModalOpen.value ? 'hidden' : ''
})

let escapeCloseModal = null
watch(addCostModalOpen, (open) => {
  if (typeof window === 'undefined') return
  if (escapeCloseModal) {
    window.removeEventListener('keydown', escapeCloseModal)
    escapeCloseModal = null
  }
  if (open) {
    escapeCloseModal = (e) => {
      if (e.key === 'Escape') closeAddCostModal()
    }
    window.addEventListener('keydown', escapeCloseModal)
  }
})

let escapeCloseRejectModal = null
watch(rejectModalOpen, (open) => {
  if (typeof window === 'undefined') return
  if (escapeCloseRejectModal) {
    window.removeEventListener('keydown', escapeCloseRejectModal)
    escapeCloseRejectModal = null
  }
  if (open) {
    escapeCloseRejectModal = (e) => {
      if (e.key === 'Escape') closeRejectModal()
    }
    window.addEventListener('keydown', escapeCloseRejectModal)
  }
})

let escapeCloseDeleteModal = null
watch(deleteModalOpen, (open) => {
  if (typeof window === 'undefined') return
  if (escapeCloseDeleteModal) {
    window.removeEventListener('keydown', escapeCloseDeleteModal)
    escapeCloseDeleteModal = null
  }
  if (open) {
    escapeCloseDeleteModal = (e) => {
      if (e.key === 'Escape') closeDeleteModal()
    }
    window.addEventListener('keydown', escapeCloseDeleteModal)
  }
})

onUnmounted(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = ''
  if (typeof window !== 'undefined') {
    if (escapeCloseModal) window.removeEventListener('keydown', escapeCloseModal)
    if (escapeCloseRejectModal) window.removeEventListener('keydown', escapeCloseRejectModal)
    if (escapeCloseDeleteModal) window.removeEventListener('keydown', escapeCloseDeleteModal)
  }
})

function closeParentDetails(ev) {
  const el = ev?.currentTarget
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function closeFunnelMenu() {
  const el = funnelDetailsRef.value
  if (el && 'open' in el) el.open = false
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  filters.page = 1
  closeParentDetails(ev)
  reload()
}

function applyClientFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  closeParentDetails(ev)
}

function onAmountFilterInput(field, ev) {
  const raw = String(ev?.target?.value ?? '').replace(/\D/g, '')
  filters[field] = raw
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  filters.page = 1
  reload()
}

function onPerPageChange() {
  filters.page = 1
  reload()
}

function onSimpleFilterSelectChange() {
  filters.page = 1
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.type = ''
  filters.trip_type = ''
  filters.trip_id = ''
  filters.from = ''
  filters.to = ''
  filters.provider = ''
  filters.fleet_mode = ''
  filters.amount_min = ''
  filters.amount_max = ''
  filters.page = 1
  filters.per_page = DEFAULT_PER_PAGE
  searchQ.value = ''
  filterTripSearch.value = ''
  closeFunnelMenu()
  reload()
}

async function refreshStandaloneTabTotal() {
  try {
    const q = { standalone: 1, per_page: 1, page: 1 }
    if (filters.status) q.status = filters.status
    const res = await listTripCosts(q)
    standaloneTabTotal.value = Number(res.meta?.total ?? 0)
  } catch {
    standaloneTabTotal.value = 0
  }
}

async function reload() {
  loading.value = true
  estimatesLoading.value = true
  try {
    const p = { ...filters }
    for (const k of ['provider', 'amount_min', 'amount_max']) {
      delete p[k]
    }
    Object.keys(p).forEach((k) => (p[k] === '' || p[k] === null ? delete p[k] : null))
    if (activeTab.value === 'standalone') {
      p.standalone = 1
      delete p.trip_id
      delete p.trip_type
      delete p.fleet_mode
    }
    const estimateParams = {
      trip_id: p.trip_id,
      trip_type: p.trip_type,
      from: p.from,
      to: p.to,
      fleet_mode: p.fleet_mode,
    }
    Object.keys(estimateParams).forEach((k) =>
      estimateParams[k] === undefined || estimateParams[k] === '' ? delete estimateParams[k] : null,
    )
    if (activeTab.value === 'standalone') {
      const res = await listTripCosts(p)
      items.value = res.items ?? []
      meta.value = res.meta ?? {}
      estimateItems.value = []
      standaloneTabTotal.value = Number(res.meta?.total ?? 0)
    } else {
      const [res, estRes] = await Promise.all([
        listTripCosts(p),
        listWizardEstimateLines(estimateParams),
      ])
      items.value = res.items ?? []
      meta.value = res.meta ?? {}
      estimateItems.value = (estRes.items ?? []).map(mapWizardLineToCostRow)
      void refreshStandaloneTabTotal()
    }
  } finally {
    loading.value = false
    estimatesLoading.value = false
  }
}

async function reloadBp() {
  bpLoading.value = true
  try {
    const p = { ...bpFilters }
    Object.keys(p).forEach((k) => (p[k] === '' || p[k] === null ? delete p[k] : null))
    const res = await listBusinessPersonnelCostLines(p)
    bpLines.value = res.items ?? []
    bpMeta.value = res.meta ?? { total: (res.items ?? []).length }
  } finally {
    bpLoading.value = false
  }
}

function onBpFiltersChange() {
  reloadBp()
}

function applyBpTripFilter(ev, tripId) {
  bpFilters.trip_id = tripId
  closeParentDetails(ev)
  reloadBp()
}

function resetBpFilters() {
  bpFilters.trip_id = ''
  bpFilters.from = ''
  bpFilters.to = ''
  bpFilterTripSearch.value = ''
  reloadBp()
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

async function submitCost() {
  costMsg.value = ''
  costMsgIsError.value = false
  const tripRaw = String(costForm.value.trip_id ?? '').trim()
  const tid = tripRaw ? Number(tripRaw) : null
  if (!addCostTripOptional.value && !tid) {
    costMsg.value = t('costs_page.err_pick_trip')
    costMsgIsError.value = true
    return
  }
  const amt = Number(costAmountDigits.value)
  if (!costAmountDigits.value || !Number.isFinite(amt) || amt <= 0) {
    costMsg.value = t('costs_page.err_amount')
    costMsgIsError.value = true
    return
  }
  submitting.value = true
  try {
    const body = {
      type: costForm.value.type,
      amount: amt,
      description: costForm.value.description || null,
      currency: costForm.value.currency || 'VND',
    }
    if (tid) {
      await submitTripCost(tid, body, { idempotencyKey: newIdempotencyKey() })
    } else {
      await submitStandaloneTripCost(body, { idempotencyKey: newIdempotencyKey() })
    }
    closeAddCostModal()
    await reload()
  } catch (e) {
    costMsgIsError.value = true
    costMsg.value = e?.response?.data?.message ?? t('costs_page.err_submit')
  } finally {
    submitting.value = false
  }
}

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(COSTS_FILTER_CONTROL_VISIBILITY_KEY)
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
    const raw = localStorage.getItem(COSTS_COL_VISIBILITY_KEY)
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

watch(
  () => route.query.status,
  () => {
    hydrateCostStatusFromRoute()
    filters.page = 1
    reload()
  },
)

watch(activeTab, (tab) => {
  if (tab === 'business_personnel') {
    reloadBp()
    return
  }
  if (tab === 'standalone' || tab === 'all_trips') {
    filters.page = 1
    reload()
  }
})

onMounted(async () => {
  loadFilterControlVisibility()
  loadColVisibility()
  loadExtraCostTypesFromStorage()
  hydrateCostStatusFromRoute()
  await loadTripPickerOptions()
  await reload()
})
</script>

<style scoped>
.costs-page {
  @apply text-slate-900 dark:text-slate-100;
}

.costs-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500;
}

.costs-btn-primary {
  @apply inline-flex items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-btn-ghost {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800;
}

.costs-sheet {
  @apply border-collapse text-left text-sm;
}

.costs-sheet thead {
  @apply bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400;
}

.costs-sheet .costs-th {
  @apply border-b border-slate-200/90 px-3 py-2.5 align-top font-semibold dark:border-slate-700;
}

.costs-sheet .costs-th--money {
  @apply text-right;
}

.costs-sheet .costs-td {
  @apply border-b border-slate-100 px-3 py-2.5 align-top text-slate-800 dark:border-slate-800 dark:text-slate-200;
}

.costs-data-row {
  @apply transition-colors hover:bg-teal-50/40 dark:hover:bg-teal-950/20;
}

.costs-data-row--alt {
  @apply bg-slate-50/40 dark:bg-slate-900/20;
}

.costs-data-row--estimate {
  @apply bg-violet-50/35 hover:bg-violet-50/55 dark:bg-violet-950/20 dark:hover:bg-violet-950/30;
}

.costs-td--money {
  @apply text-right tabular-nums;
}

.costs-pill {
  @apply inline-flex max-w-full items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300;
}

.costs-pill--type {
  @apply bg-slate-200/90 text-slate-800 dark:bg-slate-700 dark:text-slate-100;
}

.costs-pill--estimate {
  @apply bg-violet-100/90 text-violet-900 dark:bg-violet-950/50 dark:text-violet-100;
}
</style>
