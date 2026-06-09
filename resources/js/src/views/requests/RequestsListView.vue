<template>
  <div
    class="space-y-6"
    :class="!loading && items.length ? 'pb-[4.75rem] sm:pb-[4.25rem]' : ''"
  >
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">
          {{ t('requests_page.title') }}
        </h1>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
          <input
            v-model="searchInput"
            type="search"
            :placeholder="t('requests_page.search_placeholder')"
            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            @input="onSearchInput"
            @keydown.enter="applySearchNow"
          />
        </div>
        <RouterLink
          to="/dispatch-requests/new"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('requests_page.create') }}
        </RouterLink>
      </div>
    </div>

    <!-- KPI: 4 thẻ gọn -->
    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
      <div class="rounded-lg border border-slate-200/80 bg-white p-2.5 shadow-sm sm:p-3">
        <div class="flex items-start justify-between gap-1.5">
          <div class="min-w-0">
            <p class="text-[10px] font-medium uppercase leading-tight tracking-wide text-slate-500">
              {{ t('requests_page.kpi_total') }}
            </p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900 sm:text-2xl">
              {{ formatInt(stats.total) }}
            </p>
            <p v-if="stats.month_trend_pct != null" class="mt-0.5 text-[10px] text-teal-700 leading-snug">
              {{ trendLabel(stats.month_trend_pct) }}
            </p>
            <p v-else class="mt-0.5 text-[10px] leading-snug text-slate-400">{{ t('requests_page.kpi_no_trend') }}</p>
          </div>
          <div class="shrink-0 rounded-md bg-slate-100 p-1.5 text-slate-600">
            <RectangleStackIcon class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200/80 bg-white p-2.5 shadow-sm sm:p-3">
        <div class="flex items-start justify-between gap-1.5">
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-medium uppercase leading-tight tracking-wide text-slate-500">
              {{ t('requests_page.tab_pending') }}
            </p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900 sm:text-2xl">
              {{ formatInt(approvalPendingCount) }}
            </p>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-amber-400 transition-all"
                :style="{ width: pendingShareOfTotalPct + '%' }"
              />
            </div>
          </div>
          <div class="shrink-0 rounded-md bg-amber-50 p-1.5 text-amber-700">
            <ClockIcon class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200/80 bg-white p-2.5 shadow-sm sm:p-3">
        <div class="flex items-start justify-between gap-1.5">
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-medium uppercase leading-tight tracking-wide text-slate-500">
              {{ t('requests_page.tab_approved') }}
            </p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900 sm:text-2xl">
              {{ formatInt(approvalApprovedCount) }}
            </p>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-teal-500 transition-all"
                :style="{ width: approvedShareOfTotalPct + '%' }"
              />
            </div>
          </div>
          <div class="shrink-0 rounded-md bg-teal-50 p-1.5 text-teal-700">
            <ClipboardDocumentCheckIcon class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200/80 bg-white p-2.5 shadow-sm sm:p-3">
        <div class="flex items-start justify-between gap-1.5">
          <div class="min-w-0">
            <p class="text-[10px] font-medium uppercase leading-tight tracking-wide text-slate-500">
              {{ t('requests_page.kpi_sla_risk') }}
            </p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900 sm:text-2xl">
              {{ formatInt(stats.sla_risk) }}
            </p>
            <span
              v-if="stats.sla_risk > 0"
              class="mt-1 inline-flex max-w-full items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-medium text-amber-800 ring-1 ring-amber-200"
            >
              {{ t('requests_page.kpi_action_required') }}
            </span>
            <span v-else class="mt-1 inline-flex text-[10px] text-emerald-700">{{ t('requests_page.kpi_sla_ok') }}</span>
          </div>
          <div class="shrink-0 rounded-md bg-amber-50 p-1.5 text-amber-700">
            <ExclamationTriangleIcon class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters: horizontal bar -->
    <AppFilterBar>
      <div ref="requestsFilterBarRef" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <details ref="columnPickerRef" class="group relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            :aria-label="t('requests_page.table_columns')"
          >
            <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600 dark:text-slate-400" aria-hidden="true" />
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[240px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
            @click.stop
          >
            <ul class="max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700 dark:text-slate-300">
              <li v-for="opt in requestColumnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                <input
                  :id="`req-col-${opt.id}`"
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                  :checked="requestColumnVisible[opt.id] !== false"
                  @change="setRequestColumn(opt.id, $event.target.checked)"
                />
                <label :for="`req-col-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
              </li>
            </ul>
          </div>
        </details>

        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('requests_page.filter_menu_title') }}
          </p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-if="activeTab !== 'all'" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_tab') }}</span>
              <span class="max-w-[11rem] truncate text-right font-medium">{{ activeTabSummaryLabel }}</span>
            </li>
            <li v-if="searchInput.trim()" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_search_keyword') }}</span>
              <span class="max-w-[11rem] truncate text-right font-medium" :title="searchInput">{{ searchInput }}</span>
            </li>
            <li v-if="filters.trip_type" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_trip_type') }}</span>
              <span class="font-medium">{{ labelTripType(filters.trip_type) }}</span>
            </li>
            <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_depart_range') }}</span>
              <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
            </li>
            <li v-if="filters.source_channel" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_channel') }}</span>
              <span class="font-medium">{{ labelSourceChannel(filters.source_channel) }}</span>
            </li>
            <li v-if="filters.paper_status" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_paper') }}</span>
              <span class="font-medium">{{ labelPaperStatus(filters.paper_status) }}</span>
            </li>
            <li v-if="filters.request_status" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_request_status') }}</span>
              <span class="font-medium">{{ labelRequestStatus(filters.request_status) }}</span>
            </li>
            <li v-if="filters.trip_status_filter" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_trip_status') }}</span>
              <span class="font-medium">{{ labelTripStatus(filters.trip_status_filter) }}</span>
            </li>
            <li v-if="filters.sort && filters.sort !== 'created_desc'" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.sort_label') }}</span>
              <span class="font-medium">{{ sortLabel(filters.sort) }}</span>
            </li>
            <li v-if="filters.priority === 'urgent'" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_priority') }}</span>
              <span class="font-medium">{{ t('requests_page.filter_priority_urgent') }}</span>
            </li>
            <li v-if="filters.sla_risk_only" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.sla_toggle') }}</span>
              <span class="font-medium">{{ t('requests_page.filter_on') }}</span>
            </li>
            <li v-if="filters.recurring_only" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.recurring_toggle') }}</span>
              <span class="font-medium">{{ t('requests_page.filter_on') }}</span>
            </li>
            <li v-if="filters.extracurricular_only" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.extracurricular_toggle') }}</span>
              <span class="font-medium">{{ t('requests_page.filter_on') }}</span>
            </li>
            <li v-if="filters.student_count_submitted === true" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_vis_student_count') }}</span>
              <span class="font-medium">{{ t('requests_page.student_count_submitted_chip') }}</span>
            </li>
            <li v-if="filters.student_count_submitted === false" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_vis_student_count') }}</span>
              <span class="font-medium">{{ t('requests_page.student_count_pending_chip') }}</span>
            </li>
            <li v-if="filters.per_page !== 10" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_per_page') }}</span>
              <span class="font-medium">{{ filters.per_page }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
              {{ t('requests_page.filter_menu_empty') }}
            </li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
              {{ t('requests_page.filter_show_controls_title') }}
            </p>
            <p class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
              {{ t('requests_page.filter_show_controls_hint') }}
            </p>
            <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
              <li v-for="opt in filterBarVisibilityOptions" :key="'vis-' + opt.id" class="flex items-start gap-2">
                <input
                  :id="`requests-filter-vis-${opt.id}`"
                  v-model="filterBarVisible[opt.id]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
                />
                <label
                  :for="`requests-filter-vis-${opt.id}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ t(opt.labelKey) }}
                </label>
              </li>
            </ul>
          </div>
          <button
            type="button"
            class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="resetFilters(); closeFilterMenu()"
          >
            {{ t('requests_page.filter_clear_all') }}
          </button>
        </AppFilterFunnelMenu>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
          :title="t('requests_page.filter_clear')"
          @click="resetFilters"
        >
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" />
            <XMarkIcon
              class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
            />
          </span>
        </button>

        <div class="ml-auto flex shrink-0 items-center">
          <button
            type="button"
            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200 dark:hover:bg-slate-800"
            :disabled="exportingCsv || !(meta.total ?? 0)"
            :title="t('requests_page.export_csv_aria')"
            :aria-label="t('requests_page.export_csv_aria')"
            @click="exportRequestsCsv"
          >
            <ArrowDownTrayIcon
              class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400"
              :class="exportingCsv ? 'animate-pulse' : ''"
              aria-hidden="true"
            />
            <span class="hidden sm:inline">{{
              exportingCsv ? t('requests_page.export_csv_busy') : t('requests_page.export_csv')
            }}</span>
          </button>
        </div>
      </div>

      <div
        v-if="hasVisibleBarFilters"
        class="mt-2 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-2 border-t border-violet-100/80 pt-2 dark:border-violet-900/30 sm:gap-x-3"
      >
          <select
            v-if="filterBarVisible.trip_type"
            v-model="filters.trip_type"
            class="h-9 max-w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.trip_type
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_trip_type')"
            @change="onFilterChange"
          >
            <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <AppFilterDropdown
            v-if="filterBarVisible.depart"
            :panel-title="t('requests_page.filter_depart_range')"
            :show-chip-label="false"
            :label="t('requests_page.filter_depart_range')"
            :summary-text="filterDepartSummary"
            :active="!!(filters.from || filters.to)"
            :aria-label="t('requests_page.filter_depart_range')"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-3">
              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                  @click="applyDepartRangePreset('week', $event)"
                >
                  {{ t('requests_page.filter_depart_this_week') }}
                </button>
                <button
                  type="button"
                  class="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                  @click="applyDepartRangePreset('month', $event)"
                >
                  {{ t('requests_page.filter_depart_this_month') }}
                </button>
              </div>
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
            </div>
          </AppFilterDropdown>

          <select
            v-if="filterBarVisible.channel"
            v-model="filters.source_channel"
            class="h-9 max-w-[min(100%,10rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.source_channel
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_channel')"
            @change="onFilterChange"
          >
            <option v-for="opt in channelFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.paper"
            v-model="filters.paper_status"
            class="h-9 max-w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.paper_status
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_paper')"
            @change="onFilterChange"
          >
            <option v-for="opt in paperFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.priority"
            v-model="filters.priority"
            class="h-9 max-w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.priority
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_priority')"
            @change="onFilterChange"
          >
            <option v-for="opt in priorityFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.request_status"
            v-model="filters.request_status"
            class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.request_status
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_request_status')"
            @change="onRequestStatusFilterChange"
          >
            <option v-for="opt in requestStatusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.trip_status"
            v-model="filters.trip_status_filter"
            class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.trip_status_filter
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_trip_status')"
            @change="onTripStatusFilterChange"
          >
            <option v-for="opt in tripStatusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <button
            v-if="filterBarVisible.sla_risk"
            type="button"
            role="switch"
            :aria-checked="filters.sla_risk_only"
            class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1.5 text-xs font-semibold shadow-sm transition sm:text-sm"
            :class="
              filters.sla_risk_only
                ? 'border-amber-300 bg-amber-50 text-amber-950 ring-1 ring-amber-400/25 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-100'
                : 'border-slate-200/90 bg-white/80 text-slate-600 hover:border-slate-300 hover:bg-white dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :title="t('requests_page.sla_toggle')"
            @click="toggleSla"
          >
            <ExclamationTriangleIcon class="h-4 w-4 shrink-0 opacity-80" aria-hidden="true" />
            {{ t('requests_page.sla_filter_chip') }}
          </button>

          <select
            v-if="filterBarVisible.sort"
            :value="filters.sort"
            class="h-9 max-w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.sort && filters.sort !== 'created_desc'
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.sort_label')"
            @change="onSortChange($event.target.value)"
          >
            <option v-for="opt in sortSelectOptionsWithLabel" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.per_page"
            v-model.number="filters.per_page"
            class="h-9 max-w-[min(100%,9rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="filters.per_page !== 10 ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400'"
            :aria-label="t('requests_page.filter_per_page')"
            @change="onFilterChange"
          >
            <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.recurring"
            :value="filters.recurring_only ? '1' : ''"
            class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="filters.recurring_only ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400'"
            :aria-label="t('requests_page.filter_vis_recurring')"
            @change="onRecurringFilterSelect($event.target.value)"
          >
            <option v-for="opt in recurringFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.extracurricular"
            :value="extracurricularFilterSelectValue"
            class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="filters.extracurricular_only ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400'"
            :aria-label="t('requests_page.filter_vis_extracurricular')"
            @change="onExtracurricularFilterSelect($event.target.value)"
          >
            <option v-for="opt in extracurricularFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>

          <select
            v-if="filterBarVisible.student_count && filters.extracurricular_only"
            :value="studentCountFilterSelectValue"
            class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            :class="
              filters.student_count_submitted === true || filters.student_count_submitted === false
                ? 'text-slate-900 dark:text-slate-100'
                : 'text-slate-600 dark:text-slate-400'
            "
            :aria-label="t('requests_page.filter_vis_student_count')"
            @change="onStudentCountFilterSelect($event.target.value)"
          >
            <option v-for="opt in studentCountFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
      </div>
    </AppFilterBar>

    <!-- Status tabs -->
    <div class="-mx-1 overflow-x-auto pb-1">
      <nav class="flex min-w-max gap-1 border-b border-slate-200 px-1" aria-label="Tabs">
        <button
          v-for="tab in tabDefs"
          :key="tab.id"
          type="button"
          class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'border-teal-600 text-teal-800'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
          "
          @click="setTab(tab.id)"
        >
          {{ tab.label }}
          <span class="ml-1.5 tabular-nums text-slate-400">({{ formatInt(tabCount(tab.id)) }})</span>
        </button>
      </nav>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div
        class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/50 px-3 py-2"
      >
        <div v-if="canBulkTrash" class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
          <span v-if="selectedIds.length" class="text-sm text-slate-600">
            {{ t('requests_page.selected_count', { n: selectedIds.length }) }}
          </span>
          <button
            v-if="!isTrashTab"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-medium text-rose-800 shadow-sm transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!selectedIds.length || bulkSubmitting"
            @click="openBulkConfirm('delete')"
          >
            <TrashIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ t('requests_page.bulk_move_trash') }}
          </button>
          <template v-else>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-white px-3 py-1.5 text-sm font-medium text-teal-900 shadow-sm transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!selectedIds.length || bulkSubmitting"
              @click="openBulkConfirm('restore')"
            >
              <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('requests_page.bulk_restore') }}
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-900 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!selectedIds.length || bulkSubmitting"
              @click="openBulkConfirm('force_delete')"
            >
              <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('requests_page.bulk_force_delete') }}
            </button>
          </template>
        </div>
        <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
          <label class="flex items-center gap-1.5 text-xs text-slate-600">
            <span class="hidden sm:inline">{{ t('requests_page.sort_label') }}</span>
            <select
              :value="filters.sort"
              class="h-9 max-w-[11rem] rounded-md border-0 bg-white px-2 text-xs font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              @change="onSortChange($event.target.value)"
            >
              <option v-for="opt in sortSelectOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </label>
        </div>
      </div>

      <div v-if="loading" class="border-t border-slate-100 px-4 py-5">
        <div class="mb-3 h-4 w-40 animate-pulse rounded bg-slate-100" />
        <div class="space-y-2">
          <div v-for="n in 7" :key="n" class="h-11 animate-pulse rounded-lg bg-slate-100" />
        </div>
      </div>
      <div v-else-if="!items.length" class="border-t border-slate-100 px-4 py-10 text-center">
        <p class="text-sm text-slate-500">
          {{ isTrashTab ? t('requests_page.empty_trash') : t('requests_page.empty') }}
        </p>
        <button
          v-if="emptyStateShowReset"
          type="button"
          class="mt-4 inline-flex items-center justify-center rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-900 hover:bg-teal-100"
          @click="resetFilters"
        >
          {{ t('requests_page.empty_clear_filters') }}
        </button>
      </div>
      <div v-else>
        <ExtracurricularRequestsDataTable
          v-if="filters.extracurricular_only"
          ref="extracurricularTableRef"
          class="border-t border-violet-100/80 p-4"
          :requests="items"
          variant="staff"
          @refresh="reload"
          @clone="onExtracurricularClone"
        />
        <template v-else>
        <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead class="bg-slate-50/80">
            <tr>
              <th v-if="canBulkTrash" class="w-10 px-3 py-3">
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                  :checked="allSelectableOnPageChecked"
                  :disabled="!selectableIdsOnPage.length"
                  :aria-label="t('requests_page.col_select')"
                  @change="onToggleHeaderCheckbox"
                />
              </th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_id') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_trip') }}</th>
              <th v-if="requestColOn('type_channel')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_type_channel') }}
              </th>
              <th v-if="requestColOn('timeline')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_timeline') }}
              </th>
              <th v-if="requestColOn('sla')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_sla') }}
              </th>
              <th v-if="requestColOn('depart_at')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_depart_at') }}
              </th>
              <th v-if="requestColOn('arrive_by')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_arrive_by') }}
              </th>
              <th v-if="requestColOn('paper')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_paper') }}
              </th>
              <th v-if="requestColOn('requester')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_requester') }}
              </th>
              <th v-if="requestColOn('urgent')" class="px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_urgent') }}
              </th>
              <th v-if="requestColOn('notes')" class="min-w-[8rem] px-3 py-3 font-semibold text-slate-700">
                {{ t('requests_page.col_notes') }}
              </th>
              <th class="min-w-[7.5rem] px-2 py-3 text-right font-semibold text-slate-700">
                {{ t('requests_page.col_actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="r in items"
              :key="r.id"
              class="transition"
              :class="requestRowClass(r)"
            >
              <td v-if="canBulkTrash" class="px-3 py-3 align-top" :class="isTrashTab ? 'text-slate-700' : ''">
                <input
                  v-if="isTrashTab || canDeleteRow(r)"
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                  :checked="selectedIds.includes(r.id)"
                  @change="toggleRowSelected(r.id, $event.target.checked)"
                />
              </td>
              <td class="px-3 py-3 align-top">
                <div class="max-w-fit">
                  <div
                    class="flex items-center gap-1.5 font-semibold text-slate-900"
                  >
                    <span
                      v-if="r.dispatch_request_template_id"
                      class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold uppercase text-indigo-900 ring-1 ring-indigo-600/20 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-500/30"
                    >
                      <ArrowPathIcon class="h-3.5 w-3.5 shrink-0 text-indigo-700 dark:text-indigo-300" aria-hidden="true" />
                      {{ t('requests_page.badge_recurring') }}
                    </span>
                    <ExclamationTriangleIcon
                      v-if="r.is_urgent"
                      class="h-4 w-4 shrink-0 text-amber-600"
                      aria-hidden="true"
                    />
                    <RouterLink
                      :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                      class="text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400"
                    >
                      REQ-{{ r.id }}
                    </RouterLink>
                  </div>
                  <div class="text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                </div>
              </td>
              <td class="max-w-xs px-3 py-3 align-top">
                <div class="flex gap-2">
                  <component :is="tripTypeIcon(r.trip_type)" class="mt-0.5 h-5 w-5 shrink-0 text-teal-600" />
                  <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">
                      {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                    </div>
                    <div class="text-xs text-slate-500">
                      <span v-if="r.passenger_count">{{ t('requests_page.passengers', { n: r.passenger_count }) }}</span>
                      <span v-else-if="r.trip_type === 'cargo'">{{ t('requests_page.cargo') }}</span>
                      <span v-else>{{ t('requests_page.no_passenger_info') }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td v-if="requestColOn('type_channel')" class="px-3 py-3 align-top">
                <div>{{ labelTripType(r.trip_type) }}</div>
                <div class="text-xs text-slate-500">{{ labelSourceChannel(r.source_channel) }}</div>
              </td>
              <td v-if="requestColOn('timeline')" class="px-3 py-3 align-top">
                <StatusBadge :status="r.status" size="sm" />
                <div class="mt-1 text-xs text-slate-500">{{ tripTimelineHint(r) }}</div>
              </td>
              <td v-if="requestColOn('sla')" class="px-3 py-3 align-top text-xs">
                <span v-if="slaCell(r).kind === 'ok'" class="inline-flex items-center gap-1 text-emerald-700">
                  <CheckCircleIcon class="h-4 w-4" />
                  {{ t('requests_page.sla_on_track') }}
                </span>
                <span v-else-if="slaCell(r).kind === 'warn'" class="inline-flex items-center gap-1 text-amber-800">
                  <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                  {{ slaCell(r).text }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="requestColOn('depart_at')" class="whitespace-nowrap px-3 py-3 align-top text-xs text-slate-700">
                {{ formatDepartDate(r.depart_at) }}
              </td>
              <td v-if="requestColOn('arrive_by')" class="whitespace-nowrap px-3 py-3 align-top text-xs text-slate-700">
                {{ formatDepartDate(r.arrive_by) }}
              </td>
              <td v-if="requestColOn('paper')" class="max-w-[10rem] px-3 py-3 align-top text-xs">
                <div>{{ labelPaperStatus(r.paper_status) }}</div>
                <div v-if="r.paper_reference" class="mt-0.5 truncate text-slate-500" :title="r.paper_reference">
                  {{ r.paper_reference }}
                </div>
              </td>
              <td v-if="requestColOn('requester')" class="max-w-[10rem] px-3 py-3 align-top text-xs text-slate-700">
                <span class="truncate">{{ r.requester?.name ?? '—' }}</span>
              </td>
              <td v-if="requestColOn('urgent')" class="px-3 py-3 align-top">
                <span
                  v-if="r.is_urgent"
                  class="inline-flex rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800"
                >
                  {{ t('requests_page.filter_priority_urgent') }}
                </span>
                <span v-else class="text-xs text-slate-400">—</span>
              </td>
              <td v-if="requestColOn('notes')" class="max-w-xs px-3 py-3 align-top text-xs text-slate-600">
                <p class="line-clamp-2">{{ requestNotesListCell(r) }}</p>
              </td>
              <td class="px-2 py-3 align-top text-right" :class="isTrashTab ? 'text-slate-800' : ''">
                <div class="inline-flex flex-wrap items-center justify-end gap-1">
                  <AppRowActionsMenu
                    align="end"
                    :aria-label="t('requests_page.col_actions')"
                    :trigger-sr-only="t('requests_page.col_actions')"
                    root-class="text-right"
                  >
                    <RouterLink
                      v-if="!isTrashTab"
                      role="menuitem"
                      :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                    >
                      {{ t('requests_page.view') }}
                    </RouterLink>
                    <template v-if="isTrashTab && canBulkTrash">
                      <button
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-teal-50 hover:text-teal-900"
                        @click="openBulkConfirm('restore', [r.id])"
                      >
                        <ArrowPathIcon class="h-4 w-4 shrink-0 text-teal-600" aria-hidden="true" />
                        {{ t('requests_page.bulk_restore') }}
                      </button>
                      <button
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-800 transition hover:bg-red-50"
                        @click="openBulkConfirm('force_delete', [r.id])"
                      >
                        <ExclamationTriangleIcon class="h-4 w-4 shrink-0 text-red-600" aria-hidden="true" />
                        {{ t('requests_page.bulk_force_delete') }}
                      </button>
                      <div class="my-1 border-t border-slate-100" role="separator" />
                    </template>
                    <button
                      v-if="!isTrashTab && r.status === 'draft'"
                      type="button"
                      role="menuitem"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50"
                      @click="openDraftEditor(r.id)"
                    >
                      <PencilSquareIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
                      {{ t('requests_page.edit') }}
                    </button>
                  </AppRowActionsMenu>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden" role="list">
          <li v-for="r in items" :key="`m-${r.id}`" class="px-4 py-3" :class="requestRowClass(r)">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900">
                  <span
                    v-if="r.dispatch_request_template_id"
                    class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold uppercase text-indigo-900 ring-1 ring-indigo-600/20 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-500/30"
                  >
                    <ArrowPathIcon class="h-3.5 w-3.5 shrink-0 text-indigo-700 dark:text-indigo-300" aria-hidden="true" />
                    {{ t('requests_page.badge_recurring') }}
                  </span>
                  <ExclamationTriangleIcon
                    v-if="r.is_urgent"
                    class="h-4 w-4 shrink-0 text-amber-600"
                    aria-hidden="true"
                  />
                  <RouterLink
                    :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                    class="underline decoration-slate-300 underline-offset-2 hover:text-va-800"
                  >
                    REQ-{{ r.id }}
                  </RouterLink>
                </div>
                <div class="mt-0.5 text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                <div class="mt-1 truncate text-sm text-slate-800">
                  {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                </div>
              </div>
              <StatusBadge class="shrink-0" :status="r.status" size="sm" />
            </div>
          </li>
        </ul>
        </template>
      </div>
    </div>

    <nav
      v-if="!loading && items.length"
      class="sticky bottom-0 z-30 -mx-3 flex flex-col gap-3 border-t border-slate-200/90 bg-white/95 px-3 py-3 shadow-[0_-4px_12px_-4px_rgba(15,23,42,0.08)] backdrop-blur supports-[padding:max(0px)]:pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:-mx-4 sm:flex-row sm:items-center sm:justify-between sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8"
      :aria-label="t('requests_page.filter_per_page')"
    >
      <p class="text-sm text-slate-500">
        {{
          t('requests_page.pagination_summary', {
            from: pageFrom,
            to: pageTo,
            total: meta.total ?? 0,
          })
        }}
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1 || loading" @click="goPage((meta.current_page ?? 1) - 1)">
          {{ t('requests_page.prev') }}
        </Button>
        <div class="flex items-center gap-1">
          <button
            v-for="p in pageNumbers"
            :key="p"
            type="button"
            class="min-w-[2.25rem] rounded-md px-2 py-1.5 text-sm"
            :class="
              p === meta.current_page
                ? 'bg-teal-600 font-medium text-white'
                : 'text-slate-600 hover:bg-slate-100'
            "
            @click="goPage(p)"
          >
            {{ p }}
          </button>
        </div>
        <Button
          variant="secondary"
          :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1) || loading"
          @click="goPage((meta.current_page ?? 1) + 1)"
        >
          {{ t('requests_page.next') }}
        </Button>
      </div>
    </nav>

    <Teleport to="body">
      <div
        v-if="bulkConfirmOpen"
        class="fixed inset-0 z-[190] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="'bulk-confirm-title'"
        @click.self="closeBulkConfirm"
      >
        <div
          class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5"
        >
          <div
            class="flex items-start gap-3 border-b border-slate-100 px-5 py-4"
            :class="
              bulkConfirmKind === 'force_delete'
                ? 'bg-red-50/90'
                : bulkConfirmKind === 'delete'
                  ? 'bg-rose-50/80'
                  : 'bg-teal-50/80'
            "
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="
                bulkConfirmKind === 'force_delete'
                  ? 'bg-red-100 text-red-800'
                  : bulkConfirmKind === 'delete'
                    ? 'bg-rose-100 text-rose-700'
                    : 'bg-teal-100 text-teal-800'
              "
            >
              <ExclamationTriangleIcon
                v-if="bulkConfirmKind === 'force_delete'"
                class="h-5 w-5"
                aria-hidden="true"
              />
              <TrashIcon v-else-if="bulkConfirmKind === 'delete'" class="h-5 w-5" aria-hidden="true" />
              <ArrowPathIcon v-else class="h-5 w-5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <h2 id="bulk-confirm-title" class="text-base font-semibold text-slate-900">
                {{
                  bulkConfirmKind === 'force_delete'
                    ? t('requests_page.bulk_force_delete_modal_title')
                    : bulkConfirmKind === 'delete'
                      ? t('requests_page.bulk_delete_modal_title')
                      : t('requests_page.bulk_restore_modal_title')
                }}
              </h2>
              <p class="mt-1 text-sm leading-relaxed text-slate-600">
                {{
                  bulkConfirmKind === 'force_delete'
                    ? t('requests_page.bulk_force_delete_confirm')
                    : bulkConfirmKind === 'delete'
                      ? t('requests_page.bulk_delete_confirm')
                      : t('requests_page.bulk_restore_confirm')
                }}
              </p>
            </div>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
              @click="closeBulkConfirm"
            >
              {{ t('requests_page.bulk_confirm_cancel') }}
            </button>
            <button
              type="button"
              class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2"
              :class="
                bulkConfirmKind === 'force_delete'
                  ? 'bg-red-700 hover:bg-red-800 focus:ring-red-500'
                  : bulkConfirmKind === 'delete'
                    ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
                    : 'bg-teal-600 hover:bg-teal-700 focus:ring-teal-500'
              "
              :disabled="bulkSubmitting"
              @click="submitBulkConfirm"
            >
              {{
                bulkConfirmKind === 'force_delete'
                  ? t('requests_page.bulk_delete_permanently_confirm')
                  : t('requests_page.bulk_confirm_submit')
              }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  TrashIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  RectangleStackIcon,
  ClipboardDocumentCheckIcon,
  ViewColumnsIcon,
  XMarkIcon,
  AcademicCapIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import {
  bulkForceDeleteRequests,
  bulkRestoreRequests,
  bulkSoftDeleteRequests,
  cloneDispatchRequest,
  listRequests,
} from '../../api/requests'
import ExtracurricularRequestsDataTable from '../../components/requests/ExtracurricularRequestsDataTable.vue'
import { showAppError, showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import { useAuthStore } from '../../store'
import {
  labelPaperStatus,
  labelRequestStatus,
  labelSourceChannel,
  labelTripStatus,
  labelTripType,
} from '../../util/labels'
import { isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const extracurricularTableRef = ref(null)

const loading = ref(false)
const exportingCsv = ref(false)
const items = ref([])
const meta = ref({})
const stats = ref({
  total: 0,
  by_status: {},
  trips_in_progress: 0,
  trips_completed: 0,
  sla_risk: 0,
  month_trend_pct: null,
  volume_trend: [],
  trashed_total: 0,
  ops: {},
})

const activeTab = ref('all')
const searchInput = ref('')
let searchDebounce = null
const filterMenuRef = ref(null)
const canBulkTrash = computed(
  () =>
    auth.hasPermission('trip.view_all') ||
    auth.hasPermission('request.approve') ||
    auth.hasPermission('request.cancel_own') ||
    auth.hasPermission('request.update_own') ||
    auth.hasPermission('request.create'),
)

const selectedIds = ref([])
const bulkSubmitting = ref(false)
const bulkConfirmOpen = ref(false)
/** @type {import('vue').Ref<'delete' | 'restore' | 'force_delete' | null>} */
const bulkConfirmKind = ref(null)
const columnPickerRef = ref(null)
const requestsFilterBarRef = ref(null)
useDetailsAutoCloseWithin(requestsFilterBarRef)

const REQUEST_FILTER_BAR_VIS_IDS = [
  'trip_type',
  'depart',
  'channel',
  'paper',
  'priority',
  'request_status',
  'trip_status',
  'sla_risk',
  'sort',
  'per_page',
  'recurring',
  'extracurricular',
  'student_count',
]
const REQUEST_FILTER_BAR_VIS_DEFAULTS = Object.fromEntries(
  REQUEST_FILTER_BAR_VIS_IDS.map((id) => [id, false]),
)

const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(REQUEST_FILTER_BAR_VIS_IDS, REQUEST_FILTER_BAR_VIS_DEFAULTS)

function onRequestsFilterBarEnter() {
  resetFilterBarVisibility()
}

const filterBarVisibilityOptions = computed(() => [
  { id: 'trip_type', labelKey: 'requests_page.filter_vis_trip_type' },
  { id: 'depart', labelKey: 'requests_page.filter_vis_depart' },
  { id: 'channel', labelKey: 'requests_page.filter_vis_channel' },
  { id: 'paper', labelKey: 'requests_page.filter_vis_paper' },
  { id: 'priority', labelKey: 'requests_page.filter_vis_priority' },
  { id: 'request_status', labelKey: 'requests_page.filter_vis_request_status' },
  { id: 'trip_status', labelKey: 'requests_page.filter_vis_trip_status' },
  { id: 'sla_risk', labelKey: 'requests_page.filter_vis_sla_risk' },
  { id: 'sort', labelKey: 'requests_page.filter_vis_sort' },
  { id: 'per_page', labelKey: 'requests_page.filter_vis_per_page' },
  { id: 'recurring', labelKey: 'requests_page.filter_vis_recurring' },
  { id: 'extracurricular', labelKey: 'requests_page.filter_vis_extracurricular' },
  { id: 'student_count', labelKey: 'requests_page.filter_vis_student_count' },
])

const REQUEST_TRIP_STATUS_FILTER_VALUES = [
  'pending',
  'approved',
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
  'cancelled',
  'incident',
]

const REQUEST_STATUS_FILTER_VALUES = [
  'draft',
  'pending',
  'price_filled',
  'approved',
  'rejected',
  'cancelled',
]

const REQUEST_COL_STORAGE_KEY = 'va-requests-cols-v1'
const REQUEST_COL_DEFAULTS = {
  type_channel: true,
  timeline: true,
  sla: true,
  depart_at: false,
  paper: false,
  requester: false,
  notes: false,
  urgent: false,
  arrive_by: false,
}

function loadRequestColumnPrefs() {
  try {
    const raw = localStorage.getItem(REQUEST_COL_STORAGE_KEY)
    if (!raw) return { ...REQUEST_COL_DEFAULTS }
    return { ...REQUEST_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...REQUEST_COL_DEFAULTS }
  }
}

const requestColumnVisible = ref(loadRequestColumnPrefs())
watch(
  requestColumnVisible,
  (v) => {
    try {
      localStorage.setItem(REQUEST_COL_STORAGE_KEY, JSON.stringify(v))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function requestColOn(id) {
  if (id === 'id' || id === 'trip' || id === 'actions') return true
  return requestColumnVisible.value[id] !== false
}

/** Chỉ hiển thị ghi chú do người dùng nhập; bản cũ lưu BM.03 trong `notes` thì để trống (xem chi tiết). */
function requestNotesListCell(r) {
  const n = String(r?.notes ?? '').trim()
  if (!n) return '—'
  if (isLegacyBm03NotesBlock(n)) return '—'
  return n
}

function setRequestColumn(id, checked) {
  requestColumnVisible.value = { ...requestColumnVisible.value, [id]: checked }
}

const requestColumnToggleOptions = computed(() => [
  { id: 'type_channel', labelKey: 'requests_page.col_type_channel' },
  { id: 'timeline', labelKey: 'requests_page.col_timeline' },
  { id: 'sla', labelKey: 'requests_page.col_sla' },
  { id: 'depart_at', labelKey: 'requests_page.col_depart_at' },
  { id: 'arrive_by', labelKey: 'requests_page.col_arrive_by' },
  { id: 'paper', labelKey: 'requests_page.col_paper' },
  { id: 'requester', labelKey: 'requests_page.col_requester' },
  { id: 'urgent', labelKey: 'requests_page.col_urgent' },
  { id: 'notes', labelKey: 'requests_page.col_notes' },
])

function canDeleteRow(r) {
  const u = auth.user
  if (!u) return false
  if (auth.hasPermission('trip.view_all') || auth.hasPermission('request.approve')) return true
  if (Number(r.requester_id) !== Number(u.id)) return false
  if (auth.hasPermission('request.cancel_own') || auth.hasPermission('request.update_own')) {
    return ['draft', 'cancelled'].includes(r.status)
  }
  if (auth.hasPermission('request.create')) return r.status === 'draft'
  return false
}

const isTrashTab = computed(() => activeTab.value === 'trash')

/** Hàng gấp: viền trái + nền cảnh báo (cột Gấp có thể tắt). */
function requestRowClass(r) {
  if (isTrashTab.value) {
    if (r.is_urgent) {
      return 'border-l-4 border-l-amber-500 bg-amber-50/60 text-slate-700'
    }
    return 'border-l-2 border-l-slate-300 bg-slate-50/90 text-slate-500'
  }
  if (r.is_urgent) {
    return 'border-l-4 border-l-amber-500 bg-amber-50/60 hover:bg-amber-50/90'
  }
  return 'hover:bg-slate-50/80'
}

const selectableIdsOnPage = computed(() => {
  if (!canBulkTrash.value) return []
  if (isTrashTab.value) return items.value.map((r) => r.id)
  return items.value.filter((r) => canDeleteRow(r)).map((r) => r.id)
})

const allSelectableOnPageChecked = computed(
  () =>
    selectableIdsOnPage.value.length > 0 &&
    selectableIdsOnPage.value.every((id) => selectedIds.value.includes(id)),
)

function toggleRowSelected(id, checked) {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value = [...selectedIds.value, id]
  } else {
    selectedIds.value = selectedIds.value.filter((x) => x !== id)
  }
}

function onToggleHeaderCheckbox(ev) {
  const on = ev.target.checked
  selectedIds.value = on ? [...selectableIdsOnPage.value] : []
}

watch(activeTab, () => {
  selectedIds.value = []
})

watch(items, () => {
  selectedIds.value = selectedIds.value.filter((id) => items.value.some((r) => r.id === id))
})

const REQUEST_SORT_VALUES = ['created_desc', 'created_asc', 'depart_desc', 'depart_asc', 'id_desc']

const REQUEST_TAB_IDS = [
  'all',
  'draft',
  'pending',
  'approved',
  'rejected',
  'cancelled',
  'trip_in_progress',
  'trip_completed',
  'trash',
]

const filters = reactive({
  q: '',
  trip_type: '',
  source_channel: '',
  paper_status: '',
  from: '',
  to: '',
  priority: '',
  request_status: '',
  trip_status_filter: '',
  sla_risk_only: false,
  recurring_only: false,
  extracurricular_only: false,
  student_count_submitted: undefined,
  per_page: 10,
  page: 1,
  sort: 'created_desc',
})

const activeFilterCount = computed(() => {
  let n = 0
  if (activeTab.value !== 'all') n++
  if (searchInput.value.trim()) n++
  if (filters.trip_type) n++
  if (filters.from || filters.to) n++
  if (filters.source_channel) n++
  if (filters.paper_status) n++
  if (filters.priority === 'urgent') n++
  if (filters.request_status) n++
  if (filters.trip_status_filter) n++
  if (filters.sla_risk_only) n++
  if (filters.recurring_only) n++
  if (filters.extracurricular_only) n++
  if (filters.student_count_submitted === true || filters.student_count_submitted === false) n++
  if (filters.per_page !== 10) n++
  if (filters.sort && filters.sort !== 'created_desc') n++
  return n
})

const emptyStateShowReset = computed(
  () => !loading.value && !items.value.length && activeFilterCount.value > 0,
)

const sortSelectOptions = computed(() =>
  REQUEST_SORT_VALUES.map((value) => ({
    value,
    label: sortLabel(value),
  })),
)

const sortSelectOptionsWithLabel = computed(() => [
  { value: 'created_desc', label: t('requests_page.sort_label') },
  ...REQUEST_SORT_VALUES.filter((v) => v !== 'created_desc').map((value) => ({
    value,
    label: sortLabel(value),
  })),
])

const perPageFilterOptions = computed(() => [
  { value: 10, label: t('requests_page.filter_per_page') },
  { value: 20, label: '20' },
  { value: 50, label: '50' },
  { value: 100, label: '100' },
])

function sortLabel(sortVal) {
  const k = {
    created_desc: 'requests_page.sort_created_desc',
    created_asc: 'requests_page.sort_created_asc',
    depart_desc: 'requests_page.sort_depart_desc',
    depart_asc: 'requests_page.sort_depart_asc',
    id_desc: 'requests_page.sort_id_desc',
  }[sortVal]
  return k ? t(k) : sortVal
}

function onSortChange(val) {
  if (!REQUEST_SORT_VALUES.includes(val)) return
  filters.sort = val
  filters.page = 1
  const q = { ...route.query }
  if (val !== 'created_desc') q.sort = val
  else delete q.sort
  delete q.page
  router.replace({ query: q })
}

function csvEscapeCell(val) {
  const s = String(val ?? '')
  if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

function requestRowsToCsvLines(rows) {
  const headers = [
    t('requests_page.col_id'),
    t('requests_page.col_trip'),
    t('requests_page.col_timeline'),
    t('requests_page.col_depart_at'),
    t('requests_page.col_urgent'),
    t('requests_page.col_type_channel'),
  ]
  return [
    headers.map(csvEscapeCell).join(','),
    ...rows.map((r) =>
      [
        csvEscapeCell(`REQ-${r.id}`),
        csvEscapeCell(`${r.origin ?? '—'} → ${r.destination ?? '—'}`),
        csvEscapeCell(labelRequestStatus(r.status)),
        csvEscapeCell(formatDepartDate(r.depart_at)),
        r.is_urgent ? '1' : '',
        csvEscapeCell(`${labelTripType(r.trip_type)} / ${labelSourceChannel(r.source_channel)}`),
      ].join(','),
    ),
  ]
}

async function fetchAllFilteredRequestRows() {
  const base = buildListParams()
  delete base.page
  delete base.per_page
  const perPage = 100
  const all = []
  let page = 1
  let lastPage = 1
  do {
    const res = await listRequests({ ...base, page, per_page: perPage })
    const chunk = res.items ?? []
    all.push(...chunk)
    lastPage = res.meta?.last_page ?? 1
    page += 1
  } while (page <= lastPage)
  return all
}

async function exportRequestsCsv() {
  if (exportingCsv.value || !(meta.value.total ?? 0)) return
  exportingCsv.value = true
  try {
    const rows = await fetchAllFilteredRequestRows()
    if (!rows.length) return
    const lines = requestRowsToCsvLines(rows)
    const blob = new Blob([`\uFEFF${lines.join('\n')}`], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `requests-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    showAppErrorFromApi(e, t('requests_page.export_csv_fail'))
  } finally {
    exportingCsv.value = false
  }
}

const filterDepartSummary = computed(() => {
  if (!filters.from && !filters.to) return t('requests_page.filter_depart_range')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_trip_type') },
  { value: 'door_to_door', label: labelTripType('door_to_door') },
  { value: 'point_to_point', label: labelTripType('point_to_point') },
  { value: 'business', label: labelTripType('business') },
  { value: 'cargo', label: labelTripType('cargo') },
])

const channelFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_channel') },
  { value: 'portal', label: labelSourceChannel('portal') },
  { value: 'zalo', label: labelSourceChannel('zalo') },
  { value: 'paper', label: labelSourceChannel('paper') },
])

const paperFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_paper') },
  { value: 'pending', label: labelPaperStatus('pending') },
  { value: 'received', label: labelPaperStatus('received') },
  { value: 'digitally_signed', label: labelPaperStatus('digitally_signed') },
])

const priorityFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_priority') },
  { value: 'urgent', label: t('requests_page.filter_priority_urgent') },
])

const recurringFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_vis_recurring') },
  { value: '1', label: t('requests_page.recurring_toggle') },
])

const extracurricularFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_vis_extracurricular') },
  { value: '1', label: t('requests_page.extracurricular_toggle') },
])

const extracurricularFilterSelectValue = computed(() => (filters.extracurricular_only ? '1' : ''))

const studentCountFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_vis_student_count') },
  { value: 'submitted', label: t('requests_page.student_count_submitted_chip') },
  { value: 'pending', label: t('requests_page.student_count_pending_chip') },
])

const studentCountFilterSelectValue = computed(() => {
  if (filters.student_count_submitted === true) return 'submitted'
  if (filters.student_count_submitted === false) return 'pending'
  return ''
})

const requestStatusFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_request_status') },
  ...REQUEST_STATUS_FILTER_VALUES.map((value) => ({
    value,
    label: labelRequestStatus(value),
  })),
])

const tripStatusFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_trip_status') },
  ...REQUEST_TRIP_STATUS_FILTER_VALUES.map((value) => ({
    value,
    label: labelTripStatus(value),
  })),
])

const tabDefs = computed(() => [
  { id: 'all', label: t('requests_page.tab_all') },
  { id: 'draft', label: t('requests_page.tab_draft') },
  { id: 'pending', label: t('requests_page.tab_pending') },
  { id: 'approved', label: t('requests_page.tab_approved') },
  { id: 'rejected', label: t('requests_page.tab_rejected') },
  { id: 'cancelled', label: t('requests_page.tab_cancelled') },
  { id: 'trip_in_progress', label: t('requests_page.tab_trip_running') },
  { id: 'trip_completed', label: t('requests_page.tab_trip_done') },
  { id: 'trash', label: t('requests_page.tab_trash') },
])

const activeTabSummaryLabel = computed(() => {
  const d = tabDefs.value.find((x) => x.id === activeTab.value)
  return d ? d.label : activeTab.value
})

function buildRouteQueryFromState() {
  const out = {}
  const id = activeTab.value
  if (id === 'trash') out.trash = '1'
  else if (id === 'trip_in_progress') out.trip_status = 'in_progress'
  else if (id === 'trip_completed') out.trip_status = 'completed'
  else if (id !== 'all') out.status = id

  if (filters.trip_type) out.trip_type = filters.trip_type
  if (filters.source_channel) out.source_channel = filters.source_channel
  if (filters.paper_status) out.paper_status = filters.paper_status

  const sq = searchInput.value.trim()
  if (sq) out.q = sq

  if (filters.sort && filters.sort !== 'created_desc') out.sort = filters.sort
  if (filters.page > 1) out.page = String(filters.page)

  return out
}

const approvalPendingCount = computed(() => Number(stats.value.by_status?.pending ?? 0))
const approvalApprovedCount = computed(() => Number(stats.value.by_status?.approved ?? 0))
const pendingShareOfTotalPct = computed(() => {
  const t = stats.value.total || 0
  const p = approvalPendingCount.value
  if (t <= 0) return 0
  return Math.min(100, Math.round((p / t) * 100))
})
const approvedShareOfTotalPct = computed(() => {
  const t = stats.value.total || 0
  const a = approvalApprovedCount.value
  if (t <= 0) return 0
  return Math.min(100, Math.round((a / t) * 100))
})

const pageFrom = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  if (total === 0) return 0
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const window = 3
  const start = Math.max(1, cur - 1)
  const end = Math.min(last, start + window - 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function trendLabel(pct) {
  if (pct > 0) return t('requests_page.trend_up', { pct })
  if (pct < 0) return t('requests_page.trend_down', { pct: Math.abs(pct) })
  return t('requests_page.trend_flat')
}

function formatShortDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
  } catch {
    return String(v)
  }
}

function formatDepartDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return String(v)
  }
}

function tripTypeIcon(type) {
  if (type === 'cargo') return CubeIcon
  if (type === 'business') return AcademicCapIcon
  return MapPinIcon
}

function tripTimelineHint(r) {
  if (r.trip?.status) {
    return labelTripStatus(r.trip.status)
  }
  if (r.status === 'pending') return t('requests_page.hint_await_assign')
  if (r.status === 'approved' && !r.trip) return t('requests_page.hint_no_trip')
  return labelPaperStatus(r.paper_status) || '—'
}

/** @param {Record<string, unknown>} r */
function slaCell(r) {
  if (r.status !== 'pending') {
    return { kind: 'neutral' }
  }
  const depart = r.depart_at ? new Date(r.depart_at) : null
  if (r.is_urgent) {
    return { kind: 'warn', text: t('requests_page.sla_urgent') }
  }
  if (depart) {
    const hours = (depart.getTime() - Date.now()) / 36e5
    if (hours > 0 && hours <= 48) {
      return {
        kind: 'warn',
        text: t('requests_page.sla_depart_hours', { h: Math.max(1, Math.round(hours)) }),
      }
    }
  }
  return { kind: 'ok' }
}

function tabCount(tabId) {
  const b = stats.value.by_status || {}
  switch (tabId) {
    case 'all':
      return stats.value.total ?? 0
    case 'draft':
      return b.draft ?? 0
    case 'pending':
      return b.pending ?? 0
    case 'approved':
      return b.approved ?? 0
    case 'rejected':
      return b.rejected ?? 0
    case 'cancelled':
      return b.cancelled ?? 0
    case 'trip_in_progress':
      return stats.value.trips_in_progress ?? 0
    case 'trip_completed':
      return stats.value.trips_completed ?? 0
    case 'trash':
      return stats.value.trashed_total ?? 0
    default:
      return 0
  }
}

function buildListParams() {
  const params = { ...filters }
  delete params.priority
  params.q = searchInput.value.trim() || undefined
  if (filters.priority === 'urgent') {
    params.is_urgent = true
  }

  delete params.request_status
  delete params.trip_status_filter

  if (activeTab.value === 'trash') {
    params.only_trashed = 1
    params.status = undefined
    params.trip_status = undefined
  } else if (activeTab.value === 'trip_in_progress') {
    params.trip_status = 'in_progress'
    params.status = undefined
  } else if (activeTab.value === 'trip_completed') {
    params.trip_status = 'completed'
    params.status = undefined
  } else if (filters.request_status) {
    params.status = filters.request_status
    params.trip_status = filters.trip_status_filter || undefined
  } else if (activeTab.value !== 'all') {
    params.status = activeTab.value
    params.trip_status = undefined
  } else {
    params.status = undefined
    params.trip_status = filters.trip_status_filter || undefined
  }

  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  if (params.sla_risk_only === false) delete params.sla_risk_only
  if (params.recurring_only === false) delete params.recurring_only
  if (params.extracurricular_only === false) delete params.extracurricular_only
  if (params.student_count_submitted !== true && params.student_count_submitted !== false) {
    delete params.student_count_submitted
  }
  if (params.only_trashed === false) delete params.only_trashed
  if (params.sort === 'created_desc') delete params.sort

  return params
}

function syncRoutePageAfterReset() {
  if (!route.query.page) return
  const q = { ...route.query }
  delete q.page
  router.replace({ query: q })
}

function onFilterChange() {
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function onRequestStatusFilterChange() {
  if (filters.request_status) {
    activeTab.value = 'all'
  }
  onFilterChange()
}

function onTripStatusFilterChange() {
  if (filters.trip_status_filter) {
    activeTab.value = 'all'
  }
  onFilterChange()
}

function onRecurringFilterSelect(raw) {
  const on = raw === '1'
  if (filters.recurring_only === on) return
  filters.recurring_only = on
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function onExtracurricularFilterSelect(raw) {
  const on = raw === '1'
  filters.extracurricular_only = on
  if (!on) {
    filters.student_count_submitted = undefined
  }
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function onStudentCountFilterSelect(raw) {
  if (raw === 'submitted') {
    filters.student_count_submitted = true
  } else if (raw === 'pending') {
    filters.student_count_submitted = false
  } else {
    filters.student_count_submitted = undefined
  }
  filters.page = 1
  syncRoutePageAfterReset()
  reload()
}

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  onFilterChange()
}

function isoDateLocal(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function applyDepartRangePreset(kind, ev) {
  const now = new Date()
  if (kind === 'week') {
    const day = now.getDay()
    const diffToMon = day === 0 ? -6 : 1 - day
    const mon = new Date(now)
    mon.setDate(now.getDate() + diffToMon)
    const sun = new Date(mon)
    sun.setDate(mon.getDate() + 6)
    filters.from = isoDateLocal(mon)
    filters.to = isoDateLocal(sun)
  } else if (kind === 'month') {
    const first = new Date(now.getFullYear(), now.getMonth(), 1)
    const last = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    filters.from = isoDateLocal(first)
    filters.to = isoDateLocal(last)
  }
  closeParentDetails(ev)
  onFilterChange()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

async function reload() {
  loading.value = true
  try {
    const params = buildListParams()
    const res = await listRequests(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    if (res.stats) {
      stats.value = { ...stats.value, ...res.stats }
    }
  } finally {
    loading.value = false
  }
}

async function openDraftEditor(id) {
  await router.push({ name: 'dispatchRequestNew', query: { replace: String(id) } })
}

function openBulkConfirm(kind, explicitIds = null) {
  if (Array.isArray(explicitIds) && explicitIds.length) {
    selectedIds.value = [...explicitIds]
  }
  if (!selectedIds.value.length) return
  bulkConfirmKind.value = kind
  bulkConfirmOpen.value = true
}

function closeBulkConfirm() {
  if (bulkSubmitting.value) return
  bulkConfirmOpen.value = false
  bulkConfirmKind.value = null
}

async function submitBulkConfirm() {
  const kind = bulkConfirmKind.value
  if (!kind || !selectedIds.value.length) {
    closeBulkConfirm()
    return
  }
  bulkSubmitting.value = true
  try {
    if (kind === 'delete') {
      const res = await bulkSoftDeleteRequests({ ids: [...selectedIds.value] })
      showAppSuccess(t('requests_page.bulk_deleted', { n: res.deleted ?? 0 }))
    } else if (kind === 'restore') {
      const res = await bulkRestoreRequests({ ids: [...selectedIds.value] })
      showAppSuccess(t('requests_page.bulk_restored', { n: res.restored ?? 0 }))
    } else if (kind === 'force_delete') {
      const res = await bulkForceDeleteRequests({ ids: [...selectedIds.value] })
      showAppSuccess(t('requests_page.bulk_force_deleted', { n: res.deleted ?? 0 }))
    }
    selectedIds.value = []
    bulkConfirmOpen.value = false
    bulkConfirmKind.value = null
    await reload()
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    bulkSubmitting.value = false
  }
}

function goPage(p) {
  const q = { ...route.query }
  if (p <= 1) delete q.page
  else q.page = String(p)
  router.replace({ query: q })
}

function setTab(id) {
  activeTab.value = id
  filters.request_status = ''
  filters.trip_status_filter = ''
  filters.page = 1
  const q = { ...route.query }
  delete q.page
  delete q.status
  delete q.trip_status
  delete q.trash
  if (id === 'trash') {
    q.trash = '1'
  } else if (id === 'trip_in_progress') q.trip_status = 'in_progress'
  else if (id === 'trip_completed') q.trip_status = 'completed'
  else if (id !== 'all') q.status = id
  router.replace({ query: q })
}

function toggleSla() {
  filters.sla_risk_only = !filters.sla_risk_only
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function toggleRecurringOnly() {
  filters.recurring_only = !filters.recurring_only
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function toggleExtracurricularOnly() {
  filters.extracurricular_only = !filters.extracurricular_only
  if (!filters.extracurricular_only) {
    filters.student_count_submitted = undefined
  }
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function toggleStudentCountSubmittedFilter() {
  filters.student_count_submitted = filters.student_count_submitted === true ? undefined : true
  filters.page = 1
  syncRoutePageAfterReset()
  reload()
}

function toggleStudentCountPendingFilter() {
  filters.student_count_submitted = filters.student_count_submitted === false ? undefined : false
  filters.page = 1
  syncRoutePageAfterReset()
  reload()
}

async function onExtracurricularClone(req) {
  if (!req?.id) return
  extracurricularTableRef.value?.setCloneBusy?.(req.id, true)
  try {
    const dr = await cloneDispatchRequest(req.id)
    await router.push({ name: 'dispatchRequestNew', query: { replace: String(dr.id) } })
  } catch (e) {
    showAppErrorFromApi(e, t('request_detail.reset_clone_fail'))
  } finally {
    extracurricularTableRef.value?.setCloneBusy?.(req.id, false)
  }
}

function resetFilters() {
  activeTab.value = 'all'
  filters.trip_type = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.from = ''
  filters.to = ''
  filters.priority = ''
  filters.request_status = ''
  filters.trip_status_filter = ''
  filters.sla_risk_only = false
  filters.recurring_only = false
  filters.extracurricular_only = false
  filters.per_page = 10
  filters.page = 1
  filters.sort = 'created_desc'
  searchInput.value = ''
  const hadQuery = Object.keys(route.query).length > 0
  router.replace({ query: {} })
  if (!hadQuery) reload()
}

function applySearchNow() {
  if (searchDebounce) clearTimeout(searchDebounce)
  filters.page = 1
  const hadPage = !!route.query.page
  syncRoutePageAfterReset()
  if (!hadPage) reload()
}

function applyRouteQuery() {
  const q = route.query
  if (q.trash === '1' || q.trash === 'true') {
    activeTab.value = 'trash'
  } else if (typeof q.trip_status === 'string') {
    if (q.trip_status === 'in_progress') activeTab.value = 'trip_in_progress'
    else if (q.trip_status === 'completed') activeTab.value = 'trip_completed'
  } else if (typeof q.status === 'string' && ['draft', 'pending', 'price_filled', 'approved', 'rejected', 'cancelled'].includes(q.status)) {
    activeTab.value = q.status
  } else {
    activeTab.value = 'all'
  }
  if (typeof q.trip_type === 'string') filters.trip_type = q.trip_type
  if (typeof q.source_channel === 'string') filters.source_channel = q.source_channel
  if (typeof q.paper_status === 'string') filters.paper_status = q.paper_status
  if (typeof q.q === 'string') {
    searchInput.value = q.q
  }
  if (typeof q.sort === 'string' && REQUEST_SORT_VALUES.includes(q.sort)) {
    filters.sort = q.sort
  } else {
    filters.sort = 'created_desc'
  }
  const pg = parseInt(q.page, 10)
  filters.page = Number.isFinite(pg) && pg >= 1 ? pg : 1
}

function onSearchInput() {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    filters.page = 1
    const hadPage = !!route.query.page
    syncRoutePageAfterReset()
    if (!hadPage) reload()
  }, 400)
}

watch(
  () => route.query,
  () => {
    applyRouteQuery()
    reload()
  },
  { deep: true },
)

onMounted(() => {
  onRequestsFilterBarEnter()
  applyRouteQuery()
  reload()
})

onActivated(() => {
  onRequestsFilterBarEnter()
})

onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
