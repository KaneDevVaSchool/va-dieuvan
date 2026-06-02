<template>
  <div class="w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="p2pStepTo('p2pPolicyHub', workflowTermId)"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.trips_title') }}
        </h1>
      </div>
    </header>

    <div class="relative z-40">
      <AppFilterBar>
        <div ref="p2pTripsFilterBarRef" class="relative flex flex-nowrap items-center gap-1 sm:gap-2">
          <details ref="funnelDetailsRef" class="group relative shrink-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 [&::-webkit-details-marker]:hidden"
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
              class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[280px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl dark:border-violet-800/40 dark:bg-slate-900"
            >
              <p
                class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
              >
                {{ t('dashboard_analytics.filter_applied_title') }}
              </p>
              <div class="p-3 pt-2">
                <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                  <li v-if="filters.q.trim()" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.trips_search_placeholder') }}</span>
                    <span class="max-w-[12rem] truncate font-medium" :title="filters.q">{{ filters.q }}</span>
                  </li>
                  <li v-if="filters.p2p_policy_term_id" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_p2p_term') }}</span>
                    <span class="max-w-[12rem] truncate font-medium">{{ filterLabel('p2p_policy_term_id') }}</span>
                  </li>
                  <li v-if="filters.policy_route_id" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_route') }}</span>
                    <span class="max-w-[12rem] truncate font-medium">{{ filterLabel('policy_route_id') }}</span>
                  </li>
                  <li v-if="filters.run_date_from" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_run_date_from') }}</span>
                    <span class="font-medium">{{ filters.run_date_from }}</span>
                  </li>
                  <li v-if="filters.run_date_to" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_run_date_to') }}</span>
                    <span class="font-medium">{{ filters.run_date_to }}</span>
                  </li>
                  <li v-if="filters.leg" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_leg') }}</span>
                    <span class="font-medium">{{ legLabel(filters.leg) }}</span>
                  </li>
                  <li v-if="filters.trip_status" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_trip_status') }}</span>
                    <span class="font-medium">{{ labelTripStatus(filters.trip_status) }}</span>
                  </li>
                  <li v-if="filters.has_trip" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_has_trip') }}</span>
                    <span class="font-medium">{{ hasTripLabel(filters.has_trip) }}</span>
                  </li>
                  <li v-if="filters.reminder_status" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.filter_reminder_status') }}</span>
                    <span class="font-medium">{{ reminderStatusLabel(filters.reminder_status) }}</span>
                  </li>
                  <li v-if="filters.per_page !== P2P_TRIPS_DEFAULT_PER_PAGE" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
                    <span class="font-medium">{{ filters.per_page }}</span>
                  </li>
                  <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
                    {{ t('p2p_policy_page.no_filters') }}
                  </li>
                </ul>
                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                    {{ t('p2p_policy_page.filter_show_title') }}
                  </p>
                  <ul class="mt-2 max-h-[min(40vh,280px)] space-y-2 overflow-y-auto pr-0.5">
                    <li v-for="fd in filterDefs" :key="'trips-vis-' + fd.id" class="flex items-start gap-2">
                      <input
                        :id="'p2p-trips-filter-vis-' + fd.id"
                        v-model="visibility[fd.id]"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600"
                      />
                      <label
                        :for="'p2p-trips-filter-vis-' + fd.id"
                        class="cursor-pointer text-sm text-slate-700 dark:text-slate-300"
                      >
                        {{ t(fd.labelKey) }}
                      </label>
                    </li>
                  </ul>
                </div>
                <button
                  type="button"
                  class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                  @click="onClearFilters"
                >
                  {{ t('p2p_policy_page.clear_filters') }}
                </button>
              </div>
            </div>
          </details>

          <details ref="columnPickerRef" class="group relative shrink-0">
            <summary
              class="flex cursor-pointer list-none items-center rounded-xl border border-white/90 bg-white/95 p-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 [&::-webkit-details-marker]:hidden"
              :title="t('p2p_policy_page.col_visibility_title')"
              :aria-label="t('p2p_policy_page.col_visibility_title')"
            >
              <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600 dark:text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+8px)] z-[110] min-w-[240px] rounded-2xl border border-violet-200/50 bg-white p-3 shadow-xl ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900"
              @click.stop
            >
              <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                {{ t('p2p_policy_page.col_visibility_title') }}
              </p>
              <ul class="mt-2 max-h-[min(50vh,320px)] space-y-2 overflow-y-auto pr-0.5">
                <li v-for="cd in P2P_TRIPS_COL_DEFS" :key="'trips-col-vis-' + cd.id" class="flex items-start gap-2">
                  <input
                    :id="'p2p-trips-col-vis-' + cd.id"
                    v-model="colVisible[cd.id]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600"
                  />
                  <label
                    :for="'p2p-trips-col-vis-' + cd.id"
                    class="cursor-pointer text-sm text-slate-700 dark:text-slate-300"
                  >
                    {{ t(cd.labelKey) }}
                  </label>
                </li>
              </ul>
            </div>
          </details>

          <div class="hidden h-6 w-px shrink-0 bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

          <div class="flex min-w-0 flex-1 flex-nowrap items-center gap-1.5 overflow-x-auto pb-0.5 sm:gap-2">
            <AppFilterDropdown
              v-if="visibility.p2p_policy_term_id"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_p2p_term')"
              :summary-text="filterLabel('p2p_policy_term_id')"
              summary-text-class="max-w-[9rem] sm:max-w-[11rem]"
              panel-class="min-w-[240px] max-h-[min(50vh,280px)] overflow-y-auto py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li>
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="!filters.p2p_policy_term_id ? activeOptClass : inactiveOptClass"
                    @click="setTermFilter('', $event)"
                  >
                    {{ t('p2p_policy_page.filter_any') }}
                  </button>
                </li>
                <li v-for="pt in p2pTerms" :key="pt.id">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="String(filters.p2p_policy_term_id) === String(pt.id) ? activeOptClass : inactiveOptClass"
                    @click="setTermFilter(pt.id, $event)"
                  >
                    {{ p2pTermLabel(pt) }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="visibility.policy_route_id"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_route')"
              :summary-text="filterLabel('policy_route_id')"
              summary-text-class="max-w-[11rem]"
              panel-class="min-w-[220px] max-h-[min(50vh,280px)] overflow-y-auto py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li>
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="!filters.policy_route_id ? activeOptClass : inactiveOptClass"
                    @click="setRouteFilter('', $event)"
                  >
                    {{ t('p2p_policy_page.filter_any') }}
                  </button>
                </li>
                <li v-for="r in routes" :key="r.id">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="String(filters.policy_route_id) === String(r.id) ? activeOptClass : inactiveOptClass"
                    @click="setRouteFilter(r.id, $event)"
                  >
                    {{ r.name }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <details v-if="visibility.run_date_from" class="group relative shrink-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-white/90 bg-white/95 px-2 py-1.5 text-sm shadow-sm ring-1 ring-slate-200/50 [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95"
              >
                <span class="max-w-[10rem] truncate font-medium text-slate-900 dark:text-white">{{
                  filters.run_date_from || t('p2p_policy_page.filter_run_date_from')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
              </summary>
              <div class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <input
                  v-model="filters.run_date_from"
                  type="date"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                />
              </div>
            </details>

            <details v-if="visibility.run_date_to" class="group relative shrink-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-white/90 bg-white/95 px-2 py-1.5 text-sm shadow-sm ring-1 ring-slate-200/50 [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95"
              >
                <span class="max-w-[10rem] truncate font-medium text-slate-900 dark:text-white">{{
                  filters.run_date_to || t('p2p_policy_page.filter_run_date_to')
                }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
              </summary>
              <div class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <input
                  v-model="filters.run_date_to"
                  type="date"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                />
              </div>
            </details>

            <AppFilterDropdown
              v-if="visibility.leg"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_leg')"
              :summary-text="filterLabel('leg')"
              panel-class="min-w-[180px] py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in legOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="filters.leg === opt.value ? activeOptClass : inactiveOptClass"
                    @click="setLegFilter(opt.value, $event)"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="visibility.trip_status"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_trip_status')"
              :summary-text="filterLabel('trip_status')"
              panel-class="min-w-[200px] py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li>
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="!filters.trip_status ? activeOptClass : inactiveOptClass"
                    @click="setTripStatusFilter('', $event)"
                  >
                    {{ t('p2p_policy_page.filter_any') }}
                  </button>
                </li>
                <li v-for="st in tripStatusOptions" :key="st">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="filters.trip_status === st ? activeOptClass : inactiveOptClass"
                    @click="setTripStatusFilter(st, $event)"
                  >
                    {{ labelTripStatus(st) }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="visibility.has_trip"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_has_trip')"
              :summary-text="filterLabel('has_trip')"
              panel-class="min-w-[200px] py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in hasTripOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="filters.has_trip === opt.value ? activeOptClass : inactiveOptClass"
                    @click="setHasTripFilter(opt.value, $event)"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="visibility.reminder_status"
              root-class="shrink-0"
              :label="t('p2p_policy_page.filter_reminder_status')"
              :summary-text="filterLabel('reminder_status')"
              panel-class="min-w-[200px] py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in reminderOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="filters.reminder_status === opt.value ? activeOptClass : inactiveOptClass"
                    @click="setReminderFilter(opt.value, $event)"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>
          </div>

          <div class="ml-auto flex shrink-0 pl-2 sm:pl-3">
            <button
              type="button"
              class="inline-flex rounded-lg p-2 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:hover:bg-white/10"
              :title="t('p2p_policy_page.clear_filters')"
              :aria-label="t('p2p_policy_page.clear_filters')"
              @click="onClearFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>

        <div class="mt-2 w-full px-0.5 sm:px-1">
          <input
            v-model="filters.q"
            type="search"
            class="w-full rounded-lg border-0 bg-white/90 px-3 py-2.5 text-sm shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-900/90 dark:ring-slate-700 dark:placeholder:text-slate-500"
            :placeholder="t('p2p_policy_page.trips_search_placeholder')"
            :aria-label="t('p2p_policy_page.trips_search_placeholder')"
          />
        </div>
      </AppFilterBar>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700 dark:bg-slate-900/80">
      <div
        v-if="meta.total > 0 || loading"
        class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50"
      >
        <div class="flex flex-wrap items-center gap-3">
          <label class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
            <span class="whitespace-nowrap">{{ t('filter_bar.per_page') }}</span>
            <select
              v-model.number="filters.per_page"
              class="h-9 rounded-md border-0 bg-white px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
              :aria-label="t('filter_bar.per_page')"
              @change="onPerPageChange"
            >
              <option v-for="n in P2P_TRIPS_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
            </select>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.rows') }}</span>
          </label>
          <p class="text-sm text-slate-600 dark:text-slate-400">
            {{
              t('p2p_policy_page.trips_pagination_summary', {
                from: pageFrom,
                to: pageTo,
                total: meta.total,
              })
            }}
          </p>
        </div>
        <div v-if="(meta.last_page ?? 1) > 1" class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            {{ t('p2p_policy_page.routes_page_prev') }}
          </button>
          <button
            v-for="p in pageNumbers"
            :key="'top-p-' + p"
            type="button"
            class="min-w-[2rem] rounded-lg px-2 py-1.5 text-sm"
            :class="
              p === meta.current_page
                ? 'bg-teal-600 font-semibold text-white'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :disabled="loading"
            @click="goPage(p)"
          >
            {{ p }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('p2p_policy_page.routes_page_next') }}
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
            <tr>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_run_date') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_route') }}</th>
              <th v-if="colVisible.depart" class="px-3 py-2.5">{{ t('p2p_policy_page.col_depart') }}</th>
              <th v-if="colVisible.driver" class="px-3 py-2.5">{{ t('p2p_policy_page.col_driver') }}</th>
              <th v-if="colVisible.vehicle" class="px-3 py-2.5">{{ t('p2p_policy_page.col_vehicle') }}</th>
              <th v-if="colVisible.passengers" class="px-3 py-2.5">{{ t('p2p_policy_page.col_passengers') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_trip_status') }}</th>
              <th v-if="colVisible.reminder" class="px-3 py-2.5">{{ t('p2p_policy_page.col_reminder') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_trip_link') }}</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in legGroups" :key="'leg-' + group.leg">
              <tr v-if="group.rows.length" class="border-t border-slate-200 bg-gradient-to-r from-slate-100/90 to-violet-50/40 dark:border-slate-700 dark:from-slate-800/90 dark:to-violet-950/30">
                <td :colspan="tableColspan" class="px-3 py-0">
                  <button
                    type="button"
                    class="flex w-full items-center gap-2 py-2.5 text-left text-sm font-semibold text-slate-800 dark:text-slate-100"
                    :aria-expanded="legGroupOpen[group.leg]"
                    @click="toggleLegGroup(group.leg)"
                  >
                    <ChevronRightIcon
                      class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
                      :class="legGroupOpen[group.leg] ? 'rotate-90' : ''"
                      aria-hidden="true"
                    />
                    <span
                      class="inline-flex rounded-full px-2.5 py-0.5 text-xs"
                      :class="
                        group.leg === 'morning'
                          ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-200'
                          : 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200'
                      "
                    >
                      {{ legLabel(group.leg) }}
                    </span>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                      {{ t('p2p_policy_page.trips_group_count', { count: group.rows.length }) }}
                    </span>
                  </button>
                </td>
              </tr>
              <template v-if="legGroupOpen[group.leg]">
                <tr
                  v-for="(row, idx) in group.rows"
                  :key="row.id"
                  class="border-t border-slate-100 transition-colors hover:bg-violet-50/40 dark:border-slate-800 dark:hover:bg-violet-950/20"
                  :class="idx % 2 === 1 ? 'bg-slate-50/40 dark:bg-slate-900/20' : ''"
                >
                  <td class="whitespace-nowrap px-3 py-2.5 text-slate-800 dark:text-slate-200">
                    {{ formatIsoDate(row.run_date, p2pPolicyDateLocale(locale)) }}
                  </td>
                  <td class="px-3 py-2.5">
                    <div class="font-medium text-slate-900 dark:text-white">{{ row.route_name || '—' }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                      {{ campusLine(row) }}
                    </div>
                  </td>
                  <td v-if="colVisible.depart" class="whitespace-nowrap px-3 py-2.5 text-slate-700 dark:text-slate-300">
                    {{ formatIsoDateTime(row.depart_at, p2pPolicyDateLocale(locale)) }}
                  </td>
                  <td v-if="colVisible.driver" class="px-3 py-2.5 text-slate-700 dark:text-slate-300">{{ row.driver?.full_name || '—' }}</td>
                  <td v-if="colVisible.vehicle" class="px-3 py-2.5 font-mono text-xs text-slate-700 dark:text-slate-300">
                    {{ row.vehicle?.license_plate || '—' }}
                  </td>
                  <td v-if="colVisible.passengers" class="px-3 py-2.5 text-center text-slate-700 dark:text-slate-300">
                    {{ row.passenger_count ?? '—' }}
                  </td>
                  <td class="px-3 py-2.5">
                    <span
                      v-if="row.trip_status"
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      :class="tripStatusAdminPillClass(row.trip_status)"
                    >
                      {{ labelTripStatus(row.trip_status) }}
                    </span>
                    <span v-else class="text-slate-400">—</span>
                  </td>
                  <td v-if="colVisible.reminder" class="px-3 py-2.5 text-xs">
                    <span
                      v-if="row.trip_id && row.depart_reminder_sent_at"
                      class="text-emerald-700 dark:text-emerald-400"
                    >
                      {{ t('p2p_policy_page.trips_reminder_sent') }}
                    </span>
                    <span v-else-if="row.trip_id" class="text-slate-500">{{ t('p2p_policy_page.trips_reminder_pending') }}</span>
                    <span v-else>—</span>
                  </td>
                  <td class="px-3 py-2.5">
                    <RouterLink
                      v-if="row.trip_id"
                      :to="staffPath(`/trips/${row.trip_id}`)"
                      class="text-sm font-semibold text-teal-800 underline hover:text-teal-950 dark:text-teal-300"
                    >
                      #{{ row.trip_id }}
                    </RouterLink>
                    <span v-else class="text-xs text-slate-400">{{ t('p2p_policy_page.trips_no_trip') }}</span>
                  </td>
                </tr>
              </template>
            </template>
            <tr v-if="!loading && !items.length">
              <td :colspan="tableColspan" class="px-3 py-10 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading" class="flex items-center justify-center gap-2 border-t border-slate-100 py-8 text-sm text-slate-500 dark:border-slate-800">
          <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
          {{ t('common.processing') }}
        </div>
      </div>

      <div
        v-if="meta.total > 0"
        class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50"
      >
        <p class="text-sm text-slate-600 dark:text-slate-400">
          {{
            t('p2p_policy_page.trips_pagination_summary', {
              from: pageFrom,
              to: pageTo,
              total: meta.total,
            })
          }}
        </p>
        <div v-if="(meta.last_page ?? 1) > 1" class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            {{ t('p2p_policy_page.routes_page_prev') }}
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            type="button"
            class="min-w-[2rem] rounded-lg px-2 py-1.5 text-sm"
            :class="
              p === meta.current_page
                ? 'bg-teal-600 font-semibold text-white'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :disabled="loading"
            @click="goPage(p)"
          >
            {{ p }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('p2p_policy_page.routes_page_next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, ChevronRightIcon, FunnelIcon, ViewColumnsIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'
import { listP2pPolicyTerms, listPolicyRoutes, listPolicyTripSlots } from '../../api/p2pPolicy'
import {
  P2P_TRIPS_COL_DEFS,
  P2P_TRIPS_DEFAULT_PER_PAGE,
  P2P_TRIPS_PER_PAGE_OPTIONS,
  useP2pPolicyTripSlotFilters,
} from '../../composables/useP2pPolicyTripSlotFilters'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose'
import { p2pStepTo, p2pWorkflowQuery, resolveP2pTermIdFromRoute } from '../../composables/useP2pPolicyWorkflow'
import { formatIsoDate, formatIsoDateTime } from '../../util/datetime'
import { formatP2pTermLabel, p2pPolicyDateLocale } from '../../util/p2pPolicyTermDisplay'
import { labelTripStatus } from '../../util/labels'
import { tripStatusAdminPillClass } from '../../constants/tripStatus'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const funnelDetailsRef = ref(null)
const columnPickerRef = ref(null)
const p2pTripsFilterBarRef = ref(null)
useDetailsAutoClose(funnelDetailsRef)
useDetailsAutoClose(columnPickerRef)
useDetailsAutoCloseWithin(p2pTripsFilterBarRef)

const LEG_GROUP_ORDER = ['morning', 'afternoon']
const legGroupOpen = reactive({
  morning: true,
  afternoon: true,
})

const activeOptClass =
  'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
const inactiveOptClass = 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'

const { filters, visibility, colVisible, apiParams, activeFilterCount, clearFilters, resetPage, filterDefs } =
  useP2pPolicyTripSlotFilters()

const workflowTermId = computed(() => {
  const fromFilter = filters.p2p_policy_term_id
  if (fromFilter !== '' && fromFilter != null) return Number(fromFilter)
  return resolveP2pTermIdFromRoute(route)
})

const items = ref([])
const meta = ref({ total: 0, current_page: 1, per_page: P2P_TRIPS_DEFAULT_PER_PAGE, last_page: 1 })
const loading = ref(false)
const routes = ref([])
const p2pTerms = ref([])
const listReady = ref(false)

let searchDebounce = null

const tripStatusOptions = ['approved', 'assigned', 'in_progress', 'completed', 'cancelled']

const legOptions = computed(() => [
  { value: '', label: t('p2p_policy_page.filter_any') },
  { value: 'morning', label: t('p2p_policy_page.leg_morning') },
  { value: 'afternoon', label: t('p2p_policy_page.leg_afternoon') },
])

const hasTripOptions = computed(() => [
  { value: '', label: t('p2p_policy_page.filter_any') },
  { value: 'yes', label: t('p2p_policy_page.has_trip_yes') },
  { value: 'no', label: t('p2p_policy_page.has_trip_no') },
])

const reminderOptions = computed(() => [
  { value: '', label: t('p2p_policy_page.filter_any') },
  { value: 'sent', label: t('p2p_policy_page.reminder_sent') },
  { value: 'pending', label: t('p2p_policy_page.reminder_pending') },
])

const tableColspan = computed(() => {
  let n = 4
  for (const cd of P2P_TRIPS_COL_DEFS) {
    if (colVisible[cd.id]) n++
  }
  return n
})

const legGroups = computed(() => {
  const buckets = { morning: [], afternoon: [] }
  for (const row of items.value) {
    const leg = row.leg === 'afternoon' ? 'afternoon' : 'morning'
    buckets[leg].push(row)
  }
  return LEG_GROUP_ORDER.map((leg) => ({ leg, rows: buckets[leg] }))
})

function toggleLegGroup(leg) {
  legGroupOpen[leg] = !legGroupOpen[leg]
}

const pageFrom = computed(() => {
  if (!meta.value.total) return 0
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const span = 5
  let start = Math.max(1, cur - Math.floor(span / 2))
  let end = Math.min(last, start + span - 1)
  start = Math.max(1, end - span + 1)
  const nums = []
  for (let p = start; p <= end; p++) nums.push(p)
  return nums
})

function closeParentDetails(ev) {
  const d = ev?.currentTarget?.closest?.('details')
  if (d) d.open = false
}

function p2pTermLabel(pt) {
  return formatP2pTermLabel(pt, p2pPolicyDateLocale(locale))
}

function legLabel(leg) {
  if (leg === 'afternoon') return t('p2p_policy_page.leg_afternoon')
  return t('p2p_policy_page.leg_morning')
}

function hasTripLabel(value) {
  if (value === 'yes') return t('p2p_policy_page.has_trip_yes')
  if (value === 'no') return t('p2p_policy_page.has_trip_no')
  return t('p2p_policy_page.filter_any')
}

function reminderStatusLabel(value) {
  if (value === 'sent') return t('p2p_policy_page.reminder_sent')
  if (value === 'pending') return t('p2p_policy_page.reminder_pending')
  return t('p2p_policy_page.filter_any')
}

function campusLine(row) {
  const o = row.origin_campus?.name || row.origin_campus?.code
  const d = row.dest_campus?.name || row.dest_campus?.code
  if (o && d) return `${o} → ${d}`
  return o || d || '—'
}

function filterLabel(id) {
  if (id === 'p2p_policy_term_id' && filters.p2p_policy_term_id) {
    const pt = p2pTerms.value.find((x) => String(x.id) === String(filters.p2p_policy_term_id))
    return pt ? p2pTermLabel(pt) : '—'
  }
  if (id === 'policy_route_id' && filters.policy_route_id) {
    return routes.value.find((r) => String(r.id) === String(filters.policy_route_id))?.name ?? '—'
  }
  if (id === 'leg' && filters.leg) return legLabel(filters.leg)
  if (id === 'trip_status' && filters.trip_status) return labelTripStatus(filters.trip_status)
  if (id === 'has_trip' && filters.has_trip) return hasTripLabel(filters.has_trip)
  if (id === 'reminder_status' && filters.reminder_status) return reminderStatusLabel(filters.reminder_status)
  if (id === 'run_date_from' || id === 'run_date_to') return filters[id] || t('p2p_policy_page.filter_any')
  return t('p2p_policy_page.filter_any')
}

async function reload() {
  if (!listReady.value) return
  loading.value = true
  try {
    const res = await listPolicyTripSlots(apiParams.value)
    items.value = res.items ?? []
    meta.value = res.meta ?? {
      total: 0,
      current_page: 1,
      per_page: filters.per_page,
      last_page: 1,
    }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  const last = meta.value.last_page ?? 1
  filters.page = Math.min(Math.max(1, p), last)
}

function onPerPageChange() {
  resetPage()
}

function onFilterChange() {
  resetPage()
  syncWorkflowQuery()
}

function setTermFilter(id, ev) {
  filters.p2p_policy_term_id = id === '' ? '' : id
  closeParentDetails(ev)
  onTermFilterChange()
}

function setRouteFilter(id, ev) {
  filters.policy_route_id = id === '' ? '' : id
  closeParentDetails(ev)
  onFilterChange()
}

function setLegFilter(value, ev) {
  filters.leg = value
  closeParentDetails(ev)
  onFilterChange()
}

function setTripStatusFilter(value, ev) {
  filters.trip_status = value
  closeParentDetails(ev)
  onFilterChange()
}

function setHasTripFilter(value, ev) {
  filters.has_trip = value
  closeParentDetails(ev)
  onFilterChange()
}

function setReminderFilter(value, ev) {
  filters.reminder_status = value
  closeParentDetails(ev)
  onFilterChange()
}

function onTermFilterChange() {
  resetPage()
  loadRouteOptions().then(() => {
    syncWorkflowQuery()
  })
}

function onClearFilters() {
  clearFilters()
  if (funnelDetailsRef.value) funnelDetailsRef.value.open = false
  syncWorkflowQuery()
}

function syncWorkflowQuery() {
  const termId = filters.p2p_policy_term_id || ''
  router.replace({
    name: route.name,
    query: p2pWorkflowQuery(termId),
  })
}

async function loadRouteOptions() {
  const params = { per_page: 100 }
  if (filters.p2p_policy_term_id) {
    params.p2p_policy_term_id = filters.p2p_policy_term_id
  }
  const r = await listPolicyRoutes(params)
  routes.value = r.items ?? []
}

onMounted(async () => {
  const pt = await listP2pPolicyTerms({ per_page: 50 })
  p2pTerms.value = pt.items ?? []

  const qTerm = resolveP2pTermIdFromRoute(route)
  if (qTerm) {
    filters.p2p_policy_term_id = qTerm
  }

  await loadRouteOptions()
  syncWorkflowQuery()
  listReady.value = true
  await reload()
})

watch(
  () => filters.p2p_policy_term_id,
  async () => {
    if (!listReady.value) return
    await loadRouteOptions()
    syncWorkflowQuery()
  },
)

watch(
  () => ({ ...apiParams.value }),
  () => {
    if (!listReady.value) return
    reload()
  },
)

watch(
  () => filters.q,
  () => {
    if (!listReady.value) return
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(() => resetPage(), 300)
  },
)
</script>
