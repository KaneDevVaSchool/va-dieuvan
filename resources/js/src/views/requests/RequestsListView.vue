<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-xl font-semibold tracking-tight text-slate-900">
            {{ t('requests_page.title') }}
          </h1>
          <span
            class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-600/20"
          >
            {{ t('requests_page.workspace_badge') }}
          </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ t('requests_page.subtitle') }}</p>
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

    <!-- KPI: 4 thẻ gọn + thẻ xu hướng rộng -->
    <div
      class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-[minmax(0,9rem)_minmax(0,9rem)_minmax(0,9rem)_minmax(0,9rem)_minmax(14rem,1fr)]"
    >
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

      <div
        class="relative col-span-2 overflow-hidden rounded-2xl border border-teal-200/70 bg-gradient-to-br from-white via-teal-50/40 to-emerald-50/30 p-0 shadow-lg shadow-teal-500/15 ring-1 ring-teal-100/60 transition hover:shadow-xl hover:shadow-teal-500/20 md:col-span-4 lg:col-span-1 lg:min-w-0 dark:border-teal-900/50 dark:from-slate-900 dark:via-teal-950/30 dark:to-emerald-950/20 dark:shadow-black/30 dark:ring-teal-900/40"
      >
        <div
          class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-400 via-emerald-400 to-teal-500 opacity-95"
          aria-hidden="true"
        />
        <div class="relative flex items-start justify-between gap-3 p-3.5 sm:p-4">
          <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wide text-teal-900/85 dark:text-teal-200/90">
              {{ t('requests_page.kpi_volume') }}
            </p>
            <p class="mt-0.5 text-[10px] font-medium text-teal-700/70 dark:text-teal-300/80">
              {{ t('requests_page.kpi_volume_hint') }}
            </p>
          </div>
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-100 to-emerald-100 text-teal-700 shadow-md shadow-teal-600/10 ring-1 ring-white/80 dark:from-teal-950/80 dark:to-emerald-950/60 dark:text-teal-200 dark:ring-teal-800/50"
          >
            <ArrowTrendingUpIcon class="h-5 w-5" aria-hidden="true" />
          </div>
        </div>
        <div class="px-3.5 pb-3.5 sm:px-4 sm:pb-4">
          <div
            class="rounded-xl border border-teal-100/80 bg-white/85 p-2 shadow-inner shadow-slate-900/5 ring-1 ring-slate-100/80 dark:border-teal-900/40 dark:bg-slate-950/50 dark:ring-slate-800/80"
          >
            <div class="h-20 w-full sm:h-24">
              <svg
                class="h-full w-full text-teal-600 dark:text-teal-400"
                viewBox="0 0 200 48"
                preserveAspectRatio="none"
                aria-hidden="true"
              >
                <defs>
                  <linearGradient id="requests-volume-spark-fill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#0d9488" stop-opacity="0.28" />
                    <stop offset="55%" stop-color="#14b8a6" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#14b8a6" stop-opacity="0" />
                  </linearGradient>
                  <linearGradient id="requests-volume-spark-line" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#0f766e" />
                    <stop offset="100%" stop-color="#14b8a6" />
                  </linearGradient>
                </defs>
                <polygon :points="sparklineAreaPoints" fill="url(#requests-volume-spark-fill)" />
                <polyline
                  :points="sparklinePoints"
                  fill="none"
                  stroke="url(#requests-volume-spark-line)"
                  stroke-width="2.25"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="drop-shadow-sm"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters: horizontal bar -->
    <AppFilterBar>
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
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
            <li v-if="filters.per_page !== 10" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('requests_page.filter_per_page') }}</span>
              <span class="font-medium">{{ filters.per_page }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
              {{ t('requests_page.filter_menu_empty') }}
            </li>
          </ul>
          <button
            type="button"
            class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="resetFilters(); closeFilterMenu()"
          >
            {{ t('requests_page.filter_clear_all') }}
          </button>
        </AppFilterFunnelMenu>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <AppFilterDropdown
            :label="t('requests_page.filter_trip_type')"
            :summary-text="filters.trip_type ? labelTripType(filters.trip_type) : t('requests_page.all')"
            summary-text-class="max-w-[10rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.trip_type === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { trip_type: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            :label="t('requests_page.filter_depart_range')"
            :summary-text="filterDepartSummary"
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
            :label="t('requests_page.filter_channel')"
            :summary-text="filters.source_channel ? labelSourceChannel(filters.source_channel) : t('requests_page.all')"
            summary-text-class="max-w-[8rem]"
            panel-class="min-w-[200px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in channelFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.source_channel === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { source_channel: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            :label="t('requests_page.filter_paper')"
            :summary-text="filters.paper_status ? labelPaperStatus(filters.paper_status) : t('requests_page.all')"
            summary-text-class="max-w-[9rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in paperFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.paper_status === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { paper_status: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            :label="t('requests_page.filter_priority')"
            :summary-text="
              filters.priority === 'urgent'
                ? t('requests_page.filter_priority_urgent')
                : t('requests_page.filter_priority_all')
            "
            summary-text-class="max-w-[9rem]"
            panel-class="min-w-[200px] py-1"
          >
            <ul class="space-y-0.5 px-1 py-1">
              <li v-for="opt in priorityFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.priority === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { priority: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
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

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

          <button
            type="button"
            role="switch"
            :aria-checked="filters.recurring_only"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1.5 text-xs font-semibold shadow-sm transition sm:text-sm"
            :class="
              filters.recurring_only
                ? 'border-indigo-300 bg-indigo-50 text-indigo-950 ring-1 ring-indigo-400/25 dark:border-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-100'
                : 'border-slate-200/90 bg-white/80 text-slate-600 hover:border-slate-300 hover:bg-white dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :title="t('requests_page.recurring_toggle')"
            @click="toggleRecurringOnly"
          >
            <ArrowPathIcon class="h-4 w-4 shrink-0 text-current opacity-80" aria-hidden="true" />
            {{ t('requests_page.recurring_filter_chip') }}
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-white/70 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-slate-100"
            :aria-expanded="extraFiltersOpen"
            @click="extraFiltersOpen = !extraFiltersOpen"
          >
            {{ t('requests_page.filter_extra') }}
            <PlusCircleIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div
        v-show="extraFiltersOpen"
        class="mt-3 flex flex-wrap items-center gap-4 border-t border-violet-100/80 pt-3 dark:border-violet-900/30"
      >
        <label class="inline-flex cursor-pointer items-center gap-2">
          <button
            type="button"
            role="switch"
            :aria-checked="filters.sla_risk_only"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border border-slate-200/80 bg-white transition focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600"
            :class="filters.sla_risk_only ? 'bg-teal-600' : 'bg-slate-200 dark:bg-slate-700'"
            @click="toggleSla"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 translate-x-0.5 translate-y-0.5 rounded-full bg-white shadow transition"
              :class="filters.sla_risk_only ? 'translate-x-5' : ''"
            />
          </button>
          <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('requests_page.sla_toggle') }}</span>
        </label>
        <label class="inline-flex cursor-pointer items-center gap-2">
          <button
            type="button"
            role="switch"
            :aria-checked="filters.recurring_only"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border border-slate-200/80 bg-white transition focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600"
            :class="filters.recurring_only ? 'bg-teal-600' : 'bg-slate-200 dark:bg-slate-700'"
            @click="toggleRecurringOnly"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 translate-x-0.5 translate-y-0.5 rounded-full bg-white shadow transition"
              :class="filters.recurring_only ? 'translate-x-5' : ''"
            />
          </button>
          <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('requests_page.recurring_toggle') }}</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ t('requests_page.filter_per_page') }}</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            @change="onFilterChange"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
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
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
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
          <button
            type="button"
            class="inline-flex h-9 items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
            :disabled="!items.length || loading"
            @click="exportRequestsCsv"
          >
            <ArrowDownTrayIcon class="h-4 w-4 text-slate-500" aria-hidden="true" />
            <span class="hidden sm:inline">{{ t('requests_page.export_csv') }}</span>
          </button>
          <button
            type="button"
            class="inline-flex h-9 items-center rounded-md border border-violet-200 bg-violet-50 px-2 text-xs font-medium text-violet-900 hover:bg-violet-100"
            @click="saveFilterPreset"
          >
            {{ t('requests_page.save_filter_preset') }}
          </button>
          <button
            type="button"
            class="inline-flex h-9 items-center rounded-md border border-slate-200 bg-white px-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="loadFilterPreset"
          >
            {{ t('requests_page.load_filter_preset') }}
          </button>
        <details ref="columnPickerRef" class="relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 [&::-webkit-details-marker]:hidden"
          >
            <ViewColumnsIcon class="h-4 w-4 text-slate-500" aria-hidden="true" />
            {{ t('requests_page.table_columns') }}
            <ChevronDownIcon class="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute right-0 top-[calc(100%+6px)] z-40 min-w-[240px] rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5"
            @click.stop
          >
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
              {{ t('requests_page.table_columns_hint') }}
            </p>
            <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700">
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
        <p v-if="emptyStateShowReset" class="mt-2 text-xs text-slate-400">
          {{ t('requests_page.empty_reset_hint') }}
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
              <th class="w-[4.5rem] min-w-[4.5rem] px-2 py-3 text-right font-semibold text-slate-700">
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
                <RouterLink
                  :to="`/requests/${r.id}`"
                  class="group block max-w-fit rounded-md outline-none ring-teal-500/40 focus-visible:ring-2"
                >
                  <div
                    class="flex items-center gap-1.5 font-semibold text-slate-900 decoration-teal-600/80 underline-offset-2 group-hover:text-teal-700 group-hover:underline"
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
                    REQ-{{ r.id }}
                  </div>
                  <div class="text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                </RouterLink>
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
              <td class="relative px-2 py-3 align-top" :class="isTrashTab ? 'text-slate-800' : ''">
                <details class="group/action-menu relative inline-block text-right">
                  <summary
                    class="inline-flex cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200/90 bg-white p-1.5 text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 [&::-webkit-details-marker]:hidden"
                  >
                    <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">{{ t('requests_page.col_actions') }}</span>
                  </summary>
                  <div
                    class="absolute right-0 top-[calc(100%+6px)] z-[60] min-w-[12.5rem] rounded-xl border border-slate-200/90 bg-white py-1 text-left text-sm shadow-lg ring-1 ring-slate-900/5"
                    @click.stop
                  >
                    <template v-if="isTrashTab && canBulkTrash">
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-teal-50 hover:text-teal-900"
                        @click="closeRowActionMenuThen(() => openBulkConfirm('restore', [r.id]))"
                      >
                        <ArrowPathIcon class="h-4 w-4 shrink-0 text-teal-600" aria-hidden="true" />
                        {{ t('requests_page.bulk_restore') }}
                      </button>
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-800 transition hover:bg-red-50"
                        @click="closeRowActionMenuThen(() => openBulkConfirm('force_delete', [r.id]))"
                      >
                        <ExclamationTriangleIcon class="h-4 w-4 shrink-0 text-red-600" aria-hidden="true" />
                        {{ t('requests_page.bulk_force_delete') }}
                      </button>
                      <div class="my-1 border-t border-slate-100" role="separator" />
                    </template>
                    <RouterLink
                      :to="`/requests/${r.id}`"
                      class="flex items-center gap-2 px-3 py-2 text-slate-700 transition hover:bg-slate-50"
                      @click="closeRowActionMenu"
                    >
                      <EyeIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
                      {{ t('requests_page.view') }}
                    </RouterLink>
                    <RouterLink
                      v-if="!isTrashTab && r.status === 'draft'"
                      :to="`/requests/${r.id}`"
                      class="flex items-center gap-2 px-3 py-2 text-slate-700 transition hover:bg-slate-50"
                      @click="closeRowActionMenu"
                    >
                      <PencilSquareIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
                      {{ t('requests_page.edit') }}
                    </RouterLink>
                  </div>
                </details>
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden" role="list">
          <li v-for="r in items" :key="`m-${r.id}`" class="px-4 py-3" :class="requestRowClass(r)">
            <RouterLink :to="`/requests/${r.id}`" class="flex items-start justify-between gap-2">
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
                  REQ-{{ r.id }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                <div class="mt-1 truncate text-sm text-slate-800">
                  {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                </div>
              </div>
              <StatusBadge class="shrink-0" :status="r.status" size="sm" />
            </RouterLink>
          </li>
        </ul>
      </div>

      <div
        v-if="!loading && items.length"
        class="sticky bottom-0 z-10 flex flex-col gap-3 border-t border-slate-200/90 bg-white/95 px-4 py-3 shadow-[0_-4px_12px_-4px_rgba(15,23,42,0.08)] backdrop-blur sm:flex-row sm:items-center sm:justify-between"
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
      </div>
    </div>

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
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ArrowTrendingUpIcon,
  TrashIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  PlusIcon,
  RectangleStackIcon,
  ClipboardDocumentCheckIcon,
  ViewColumnsIcon,
  XMarkIcon,
  AcademicCapIcon,
  CubeIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import {
  bulkForceDeleteRequests,
  bulkRestoreRequests,
  bulkSoftDeleteRequests,
  listRequests,
} from '../../api/requests'
import { showAppError, showAppErrorFromApi, showAppInfo, showAppSuccess } from '../../composables/appMessage'
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

const loading = ref(false)
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
})

const activeTab = ref('all')
const searchInput = ref('')
let searchDebounce = null
const filterMenuRef = ref(null)
const extraFiltersOpen = ref(false)

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
  sla_risk_only: false,
  recurring_only: false,
  per_page: 10,
  page: 1,
  sort: 'created_desc',
})

const FILTER_PRESET_STORAGE_KEY = 'va-requests-filter-preset-v1'

const activeFilterCount = computed(() => {
  let n = 0
  if (activeTab.value !== 'all') n++
  if (searchInput.value.trim()) n++
  if (filters.trip_type) n++
  if (filters.from || filters.to) n++
  if (filters.source_channel) n++
  if (filters.paper_status) n++
  if (filters.priority === 'urgent') n++
  if (filters.sla_risk_only) n++
  if (filters.recurring_only) n++
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

function exportRequestsCsv() {
  if (!items.value.length) return
  const headers = [
    t('requests_page.col_id'),
    t('requests_page.col_trip'),
    t('requests_page.col_timeline'),
    t('requests_page.col_depart_at'),
    t('requests_page.col_urgent'),
    t('requests_page.col_type_channel'),
  ]
  const lines = [
    headers.map(csvEscapeCell).join(','),
    ...items.value.map((r) =>
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
  const blob = new Blob([`\uFEFF${lines.join('\n')}`], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `requests-${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function saveFilterPreset() {
  try {
    const payload = {
      trip_type: filters.trip_type,
      source_channel: filters.source_channel,
      paper_status: filters.paper_status,
      from: filters.from,
      to: filters.to,
      priority: filters.priority,
      sla_risk_only: filters.sla_risk_only,
      recurring_only: filters.recurring_only,
      per_page: filters.per_page,
      sort: filters.sort,
      q: searchInput.value.trim(),
      tab: activeTab.value,
    }
    localStorage.setItem(FILTER_PRESET_STORAGE_KEY, JSON.stringify(payload))
    showAppSuccess(t('requests_page.preset_saved'), t('requests_page.preset_toast_title'))
  } catch {
    showAppError(t('requests_page.preset_save_failed'))
  }
}

function loadFilterPreset() {
  try {
    const raw = localStorage.getItem(FILTER_PRESET_STORAGE_KEY)
    if (!raw) {
      showAppInfo(t('requests_page.preset_none'))
      return
    }
    const o = JSON.parse(raw)
    if (typeof o.trip_type === 'string') filters.trip_type = o.trip_type
    if (typeof o.source_channel === 'string') filters.source_channel = o.source_channel
    if (typeof o.paper_status === 'string') filters.paper_status = o.paper_status
    if (typeof o.from === 'string') filters.from = o.from
    if (typeof o.to === 'string') filters.to = o.to
    if (typeof o.priority === 'string') filters.priority = o.priority
    if (typeof o.sla_risk_only === 'boolean') filters.sla_risk_only = o.sla_risk_only
    if (typeof o.recurring_only === 'boolean') filters.recurring_only = o.recurring_only
    if (typeof o.per_page === 'number' && [10, 20, 50, 100].includes(o.per_page)) filters.per_page = o.per_page
    if (typeof o.sort === 'string' && REQUEST_SORT_VALUES.includes(o.sort)) filters.sort = o.sort
    if ('q' in o) searchInput.value = typeof o.q === 'string' ? o.q : ''
    if ('tab' in o && REQUEST_TAB_IDS.includes(o.tab)) activeTab.value = o.tab
    filters.page = 1
    router.replace({ query: buildRouteQueryFromState() })
    showAppSuccess(t('requests_page.preset_loaded'), t('requests_page.preset_toast_title'))
  } catch {
    showAppError(t('requests_page.preset_load_failed'))
  }
}

const filterDepartSummary = computed(() => {
  if (!filters.from && !filters.to) return t('requests_page.all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('requests_page.all') },
  { value: 'door_to_door', label: labelTripType('door_to_door') },
  { value: 'point_to_point', label: labelTripType('point_to_point') },
  { value: 'business', label: labelTripType('business') },
  { value: 'cargo', label: labelTripType('cargo') },
])

const channelFilterOptions = computed(() => [
  { value: '', label: t('requests_page.all') },
  { value: 'portal', label: labelSourceChannel('portal') },
  { value: 'zalo', label: labelSourceChannel('zalo') },
  { value: 'paper', label: labelSourceChannel('paper') },
])

const paperFilterOptions = computed(() => [
  { value: '', label: t('requests_page.all') },
  { value: 'pending', label: labelPaperStatus('pending') },
  { value: 'received', label: labelPaperStatus('received') },
  { value: 'digitally_signed', label: labelPaperStatus('digitally_signed') },
])

const priorityFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_priority_all') },
  { value: 'urgent', label: t('requests_page.filter_priority_urgent') },
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

/** Query đồng bộ tab, ô tìm & filter có trong URL — tránh applyRouteQuery ghi đè tab sau tải preset. */
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

const SPARK_W = 200
const SPARK_H = 40
const SPARK_VB_H = 48

function buildSparklineCoords(pts) {
  if (!pts?.length) {
    return [
      { x: 0, y: SPARK_H },
      { x: SPARK_W, y: SPARK_H },
    ]
  }
  const max = Math.max(...pts, 1)
  return pts.map((v, i) => {
    const x = (i / Math.max(pts.length - 1, 1)) * SPARK_W
    const y = SPARK_H - (v / max) * (SPARK_H - 4) - 2
    return { x, y }
  })
}

const sparklinePoints = computed(() => {
  const coords = buildSparklineCoords(stats.value.volume_trend)
  return coords.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
})

/** Vùng tô dưới đường (polygon khép xuống đáy viewBox) */
const sparklineAreaPoints = computed(() => {
  const coords = buildSparklineCoords(stats.value.volume_trend)
  if (coords.length < 2) {
    return `0,${SPARK_VB_H} ${SPARK_W},${SPARK_VB_H}`
  }
  const top = coords.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
  return `0,${SPARK_VB_H} ${top} ${SPARK_W},${SPARK_VB_H}`
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
  } else if (activeTab.value !== 'all') {
    params.status = activeTab.value
    params.trip_status = undefined
  } else {
    params.status = undefined
    params.trip_status = undefined
  }

  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  if (params.sla_risk_only === false) delete params.sla_risk_only
  if (params.recurring_only === false) delete params.recurring_only
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

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  closeParentDetails(ev)
  onFilterChange()
}

function onFilterDropdownChange(ev) {
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

function closeRowActionMenu(ev) {
  const d = ev?.target?.closest?.('details')
  if (d) d.open = false
}

function closeRowActionMenuThen(fn) {
  return (ev) => {
    const d = ev?.currentTarget?.closest?.('details')
    if (d) d.open = false
    fn()
  }
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

function resetFilters() {
  activeTab.value = 'all'
  filters.trip_type = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.from = ''
  filters.to = ''
  filters.priority = ''
  filters.sla_risk_only = false
  filters.recurring_only = false
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
  applyRouteQuery()
  reload()
})

onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
