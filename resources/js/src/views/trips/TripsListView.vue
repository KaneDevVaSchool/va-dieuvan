<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('trips_page.hero_title') }}
        </h1>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
          {{ t('trips_page.hero_subtitle') }}
        </p>
      </div>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
      <div
        v-for="box in kpiBoxes"
        :key="box.key"
        class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50"
      >
        <div class="flex items-start justify-between gap-2">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl"
            :class="box.iconWrap"
          >
            <component :is="box.icon" class="h-5 w-5" :class="box.iconClass" aria-hidden="true" />
          </div>
        </div>
        <div class="mt-3 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">
          {{ statsLoading ? '…' : box.value }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ box.label }}
        </div>
      </div>
    </div>

    <!-- Filters (cùng phong cách tổng quan) -->
    <AppFilterBar>
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
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
            class="absolute left-0 top-[calc(100%+8px)] z-40 min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
          >
            <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
              {{ t('dashboard_analytics.filter_applied_title') }}
            </p>
            <div class="p-3 pt-2">
              <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li class="font-medium text-slate-900 dark:text-slate-100">{{ currentPresetLabel }}</li>
                <li class="tabular-nums text-slate-600 dark:text-slate-400">
                  {{ rangeDisplayFormatted }}
                  <span
                    v-if="rangeValid && rangeDaySpan > 0"
                    class="ml-1.5 inline-block rounded-md bg-violet-100/90 px-1.5 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-950/70 dark:text-violet-200"
                  >
                    {{ t('dashboard_analytics.date_range_span', { n: rangeDaySpan }) }}
                  </span>
                </li>
                <li v-for="(row, i) in activeFilterLines" :key="i" class="border-t border-slate-100 pt-2 dark:border-slate-700">
                  <span class="text-slate-500 dark:text-slate-400">{{ row.label }}:</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{ row.value }}</span>
                </li>
                <li v-if="filters.q" class="border-t border-slate-100 pt-2 dark:border-slate-700">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.search') }}:</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{ filters.q }}</span>
                </li>
                <li v-if="filters.per_page !== 20" class="border-t border-slate-100 pt-2 dark:border-slate-700">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}:</span>
                  <span class="font-medium text-slate-800 dark:text-slate-200">{{ filters.per_page }}</span>
                </li>
              </ul>
              <button
                type="button"
                class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="resetFilters"
              >
                {{ t('dashboard_analytics.filter_clear_all') }}
              </button>
            </div>
          </div>
        </details>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <details class="group relative min-w-0">
            <summary
              class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                {{ t('dashboard_analytics.filter_period_label') }}
              </span>
              <span class="max-w-[10rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                {{ currentPresetLabel }}
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
            >
              <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                <li v-for="p in presetDefs" :key="p.id">
                  <button
                    type="button"
                    :class="[
                      'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                      preset === p.id
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                    ]"
                    @click="applyPreset(p.id)"
                  >
                    {{ p.label }}
                  </button>
                </li>
              </ul>
            </div>
          </details>

          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            >
              <CalendarDaysIcon class="h-4 w-4 shrink-0 text-violet-500 dark:text-violet-400" aria-hidden="true" />
              <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                {{ t('dashboard_analytics.filter_dates_label') }}
              </span>
              <span class="flex min-w-0 max-w-[11rem] items-center gap-1.5 sm:max-w-[14rem]">
                <span class="min-w-0 truncate text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                  {{ rangeDisplayFormatted }}
                </span>
                <span
                  v-if="rangeValid && rangeDaySpan > 0"
                  class="shrink-0 rounded-md bg-violet-100/90 px-1.5 py-px text-[10px] font-bold tabular-nums text-violet-800 dark:bg-violet-950/70 dark:text-violet-200"
                >
                  {{ rangeDaySpan }}
                </span>
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+8px)] z-40 w-[min(100vw-1rem,22rem)] overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-b from-white via-white to-slate-50/95 shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/45 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/40 dark:ring-slate-950/50 sm:left-auto sm:right-0 sm:w-[20.5rem]"
            >
              <div class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-indigo-50/40 px-3 py-2.5 dark:border-violet-900/40 dark:from-violet-950/40 dark:to-indigo-950/20">
                <div class="flex items-start gap-2">
                  <CalendarDaysIcon class="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                      {{ t('dashboard_analytics.date_range_title') }}
                    </p>
                    <p class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-400">
                      {{ t('dashboard_analytics.date_range_hint') }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="p-3">
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="chip in dateQuickChips"
                    :key="chip.kind"
                    type="button"
                    class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50/80 hover:text-teal-900 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-100"
                    @click="applyQuickDateRange(chip.kind)"
                  >
                    {{ chip.label }}
                  </button>
                </div>
                <div class="mt-3 space-y-3">
                  <div class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none">
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="trips-range-from">
                      {{ t('dashboard_analytics.range_from') }}
                    </label>
                    <input
                      id="trips-range-from"
                      v-model="rangeFrom"
                      type="date"
                      :max="rangeTo || undefined"
                      class="mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      @change="onRangeFromChange"
                    />
                  </div>
                  <div class="flex items-center justify-center gap-2 px-1">
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-800 dark:bg-violet-950/80 dark:text-violet-200">
                      {{ rangeValid ? t('dashboard_analytics.date_range_span', { n: rangeDaySpan }) : '—' }}
                    </span>
                    <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                  </div>
                  <div class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none">
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="trips-range-to">
                      {{ t('dashboard_analytics.range_to') }}
                    </label>
                    <input
                      id="trips-range-to"
                      v-model="rangeTo"
                      type="date"
                      :min="rangeFrom || undefined"
                      class="mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                      @change="onRangeToChange"
                    />
                  </div>
                </div>
                <button
                  v-if="preset === 'custom'"
                  type="button"
                  class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition hover:from-teal-700 hover:to-teal-600 disabled:opacity-50 dark:shadow-teal-900/30"
                  :disabled="loading || statsLoading || !rangeValid"
                  @click="applyCustomRange"
                >
                  {{ t('dashboard_analytics.apply_range') }}
                </button>
                <p v-if="!rangeValid" class="mt-2 text-center text-xs text-rose-600 dark:text-rose-400">
                  {{ t('dashboard_analytics.range_invalid') }}
                </p>
              </div>
            </div>
          </details>

          <template v-for="fd in dimensionFilters" :key="fd.id">
            <details class="group relative min-w-0">
              <summary
                class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ fd.label }}</span>
                <span class="max-w-[9rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100 sm:max-w-[10rem]">
                  {{ fd.summary }}
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
              >
                <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                  <li v-for="opt in fd.options" :key="String(opt.value) + opt.label">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        fd.isSelected(opt.value)
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                      ]"
                      @click="fd.pick(opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </div>
            </details>
          </template>
        </div>
      </div>
    </AppFilterBar>

    <!-- Tabs + search -->
    <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="tab in typeTabs"
          :key="tab.value === '' ? 'all' : tab.value"
          type="button"
          :class="[
            'rounded-full px-3 py-1.5 text-sm font-medium transition',
            filters.trip_type === tab.value
              ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/25'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700',
          ]"
          @click="setTripTypeTab(tab.value)"
        >
          {{ tab.label }}
          <span class="tabular-nums opacity-90">({{ tab.count }})</span>
        </button>
      </div>
      <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative min-w-0 flex-1">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
          <input
            v-model="searchInput"
            type="search"
            autocomplete="off"
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50/80 pl-10 pr-3 text-sm text-slate-900 shadow-inner focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
            :placeholder="t('trips_page.search_placeholder')"
            @keydown.enter.prevent="flushSearch"
          />
        </div>
        <label class="flex shrink-0 items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
          <span>{{ t('filter_bar.per_page') }}</span>
          <select
            v-model.number="filters.per_page"
            class="h-11 rounded-xl border border-slate-200 bg-white px-2 text-sm font-medium dark:border-slate-600 dark:bg-slate-900"
            @change="onFilterChange"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
      </div>
    </div>

    <!-- List -->
    <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('trips_page.loading') }}</div>
    <div v-else class="space-y-3">
      <article
        v-for="trip in items"
        :key="trip.id"
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                :class="tripTypeIconWrap(trip.dispatch_request?.trip_type)"
              >
                <TruckIcon v-if="trip.dispatch_request?.trip_type === 'door_to_door'" class="h-6 w-6 text-sky-600 dark:text-sky-400" />
                <MapPinIcon v-else-if="trip.dispatch_request?.trip_type === 'point_to_point'" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                <BriefcaseIcon v-else-if="trip.dispatch_request?.trip_type === 'business'" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                <CubeIcon v-else class="h-6 w-6 text-violet-600 dark:text-violet-400" />
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-mono text-sm font-bold text-slate-900 dark:text-slate-100">{{ tripCode(trip.id) }}</span>
                  <span
                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                    :class="tripTypeBadgeClass(trip.dispatch_request?.trip_type)"
                  >
                    {{ tripTypeShort(trip.dispatch_request?.trip_type) }}
                  </span>
                </div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {{ t('trips_page.created_at', { time: fmtCreated(trip.created_at) }) }}
                </div>
              </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusPillClass(trip.status)]">
                {{ labelTripStatus(trip.status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid gap-4 px-4 py-4 sm:grid-cols-3">
          <div>
            <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
              <MapPinIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trips_page.col_origin') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ trip.dispatch_request?.origin || '—' }}
            </div>
            <div class="mt-0.5 text-xs tabular-nums text-slate-500 dark:text-slate-400">
              {{ fmtShort(trip.depart_at) }}
            </div>
          </div>
          <div>
            <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
              <MapPinIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trips_page.col_destination') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ trip.dispatch_request?.destination || '—' }}
            </div>
            <div class="mt-0.5 text-xs tabular-nums text-slate-500 dark:text-slate-400">
              <template v-if="trip.dispatch_request?.arrive_by || trip.arrive_by">
                {{ t('trips_page.eta_prefix') }} {{ fmtShort(trip.arrive_by || trip.dispatch_request?.arrive_by) }}
              </template>
              <template v-else>—</template>
            </div>
          </div>
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('trips_page.col_driver') }}
            </div>
            <div v-if="trip.driver" class="mt-1 flex items-center gap-2">
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200">
                {{ driverInitials(trip.driver.full_name) }}
              </div>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ trip.driver.full_name }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  <span v-if="trip.vehicle">{{ trip.vehicle.license_plate }}</span>
                  <span v-if="trip.vehicle && trip.external_driver_ref"> · </span>
                  <span v-if="trip.external_driver_ref" class="truncate">{{ trip.external_driver_ref }}</span>
                </div>
              </div>
            </div>
            <div v-else class="mt-1 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                ?
              </div>
              <span>{{ t('trips_page.unassigned') }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600 dark:text-slate-400">
            <span class="inline-flex items-center gap-1.5">
              <CubeIcon class="h-4 w-4 text-slate-400" />
              {{ cargoSummary(trip) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <ArrowsRightLeftIcon class="h-4 w-4 text-slate-400" />
              {{ distanceSummary(trip) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <ClockIcon class="h-4 w-4 text-slate-400" />
              {{ durationSummary(trip) }}
            </span>
          </div>
          <div class="flex flex-wrap justify-end gap-2">
            <RouterLink
              :to="`/trips/${trip.id}`"
              class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            >
              <EyeIcon class="h-4 w-4" />
              {{ t('trips_page.action_detail') }}
            </RouterLink>
            <a
              v-if="mapsHref(trip)"
              :href="mapsHref(trip)"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-900 hover:bg-teal-100 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-100"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4" />
              {{ t('trips_page.action_track') }}
            </a>
            <RouterLink
              v-if="canAssignTrip && needsAssign(trip)"
              :to="`/trips/${trip.id}`"
              class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600"
            >
              <UserPlusIcon class="h-4 w-4" />
              {{ t('trips_page.action_assign') }}
            </RouterLink>
            <a
              v-else-if="trip.driver?.phone"
              :href="`tel:${trip.driver.phone}`"
              class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
            >
              <PhoneIcon class="h-4 w-4" />
              {{ t('trips_page.action_contact') }}
            </a>
          </div>
        </div>
      </article>
      <div v-if="!items.length" class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
        {{ t('trips_page.empty') }}
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="(meta.total ?? 0) > 0"
      class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between"
    >
      <p class="text-slate-600 dark:text-slate-400">
        {{ t('trips_page.page_range', { from: pageFrom, to: pageTo, total: meta.total ?? 0 }) }}
      </p>
      <div class="flex flex-wrap items-center justify-end gap-1">
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) <= 1"
          :aria-label="t('trips_page.prev')"
          @click="goPage((meta.current_page ?? 1) - 1)"
        >
          <ChevronLeftIcon class="h-5 w-5" />
        </button>
        <button
          v-for="n in pageNumbers"
          :key="'p-' + n"
          type="button"
          :class="[
            'h-9 min-w-[2.25rem] rounded-lg px-2 text-sm font-medium tabular-nums',
            n === (meta.current_page ?? 1)
              ? 'bg-teal-600 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
          ]"
          @click="goPage(n)"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
          :aria-label="t('trips_page.next')"
          @click="goPage((meta.current_page ?? 1) + 1)"
        >
          <ChevronRightIcon class="h-5 w-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  ArrowsRightLeftIcon,
  BriefcaseIcon,
  CalendarDaysIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PhoneIcon,
  QueueListIcon,
  TruckIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import { getTripStats, listTrips } from '../../api/trips'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { useAuthStore } from '../../store'

const { t, locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const loading = ref(false)
const statsLoading = ref(false)
const items = ref([])
const meta = ref({})
const stats = ref({ total: 0, by_trip_type: {}, incident: 0 })

const searchInput = ref('')
const searchDebounce = ref(null)
const funnelDetailsRef = ref(null)

const TRIP_STATUS_VALUES = [
  'pending',
  'approved',
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
  'cancelled',
  'incident',
]

const filters = reactive({
  trip_type: '',
  status: '',
  source_channel: '',
  paper_status: '',
  is_urgent: false,
  fleet_mode: '',
  from: '',
  to: '',
  q: '',
  page: 1,
  per_page: 20,
})

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function subDays(d, n) {
  const x = new Date(d)
  x.setDate(x.getDate() - n)
  return x
}

function startOfQuarter(d) {
  const m = d.getMonth()
  const q0 = Math.floor(m / 3) * 3
  return new Date(d.getFullYear(), q0, 1)
}

const today = new Date()
const rangeFrom = ref(ymd(new Date(today.getFullYear(), today.getMonth(), 1)))
const rangeTo = ref(ymd(today))
const preset = ref('month')

const presetDefs = computed(() => [
  { id: 'month', label: t('dashboard_analytics.preset_month') },
  { id: 'last30', label: t('dashboard_analytics.preset_last30') },
  { id: 'last7', label: t('dashboard_analytics.preset_last7') },
  { id: 'quarter', label: t('dashboard_analytics.preset_quarter') },
  { id: 'custom', label: t('dashboard_analytics.preset_custom') },
])

const currentPresetLabel = computed(() => presetDefs.value.find((p) => p.id === preset.value)?.label ?? '')

const rangeValid = computed(() => {
  if (!rangeFrom.value || !rangeTo.value) return false
  return rangeFrom.value <= rangeTo.value
})

const rangeDaySpan = computed(() => {
  if (!rangeFrom.value || !rangeTo.value || rangeFrom.value > rangeTo.value) return 0
  const a = new Date(`${rangeFrom.value}T12:00:00`)
  const b = new Date(`${rangeTo.value}T12:00:00`)
  return Math.floor((b.getTime() - a.getTime()) / 86400000) + 1
})

function formatDisplayDate(iso) {
  if (!iso) return '…'
  const [y, m, d] = iso.split('-')
  if (!y || !m || !d) return iso
  return `${d}/${m}/${y}`
}

const rangeDisplayFormatted = computed(
  () => `${formatDisplayDate(rangeFrom.value)} — ${formatDisplayDate(rangeTo.value)}`,
)

const dateQuickChips = computed(() => [
  { kind: 'today', label: t('dashboard_analytics.date_range_quick_today') },
  { kind: 'yesterday', label: t('dashboard_analytics.date_range_quick_yesterday') },
  { kind: 'last7', label: t('dashboard_analytics.date_range_quick_last7') },
  { kind: 'month', label: t('dashboard_analytics.date_range_quick_month') },
])

function syncRangeForPreset(id) {
  const now = new Date()
  const end = ymd(now)
  if (id === 'month') {
    rangeFrom.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1))
    rangeTo.value = end
  } else if (id === 'last30') {
    rangeFrom.value = ymd(subDays(now, 29))
    rangeTo.value = end
  } else if (id === 'last7') {
    rangeFrom.value = ymd(subDays(now, 6))
    rangeTo.value = end
  } else if (id === 'quarter') {
    rangeFrom.value = ymd(startOfQuarter(now))
    rangeTo.value = end
  }
}

function applyPreset(id) {
  preset.value = id
  if (id !== 'custom') {
    syncRangeForPreset(id)
    syncFiltersFromRange()
    onFilterChange()
  }
}

function applyQuickDateRange(kind) {
  const now = new Date()
  const end = ymd(now)
  if (kind === 'today') {
    rangeFrom.value = end
    rangeTo.value = end
  } else if (kind === 'yesterday') {
    const y = ymd(subDays(now, 1))
    rangeFrom.value = y
    rangeTo.value = y
  } else if (kind === 'last7') {
    rangeFrom.value = ymd(subDays(now, 6))
    rangeTo.value = end
  } else if (kind === 'month') {
    rangeFrom.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1))
    rangeTo.value = end
  }
  preset.value = 'custom'
  syncFiltersFromRange()
  onFilterChange()
}

function onRangeFromChange() {
  if (rangeFrom.value && rangeTo.value && rangeFrom.value > rangeTo.value) {
    rangeTo.value = rangeFrom.value
  }
  preset.value = 'custom'
  syncFiltersFromRange()
  onFilterChange()
}

function onRangeToChange() {
  if (rangeFrom.value && rangeTo.value && rangeFrom.value > rangeTo.value) {
    rangeFrom.value = rangeTo.value
  }
  preset.value = 'custom'
  syncFiltersFromRange()
  onFilterChange()
}

function applyCustomRange() {
  syncFiltersFromRange()
  onFilterChange()
}

function syncFiltersFromRange() {
  filters.from = rangeFrom.value
  filters.to = rangeTo.value
}

const runStatusOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_all') },
  ...TRIP_STATUS_VALUES.map((s) => ({ value: s, label: labelTripStatus(s) })),
])

const dimensionFilters = computed(() => {
  const fa = t('dashboard_analytics.filter_all')
  const channelOpts = [
    { value: '', label: fa },
    { value: 'portal', label: t('labels.source_channel.portal') },
    { value: 'zalo', label: t('labels.source_channel.zalo') },
    { value: 'paper', label: t('labels.source_channel.paper') },
  ]
  const paperOpts = [
    { value: '', label: fa },
    { value: 'pending', label: t('labels.paper_status.pending') },
    { value: 'received', label: t('labels.paper_status.received') },
    { value: 'digitally_signed', label: t('labels.paper_status.digitally_signed') },
  ]
  const fleetOpts = [
    { value: '', label: fa },
    { value: 'internal', label: t('dashboard_analytics.fleet_internal') },
    { value: 'vendor_hire', label: t('dashboard_analytics.fleet_vendor_hire') },
    { value: 'taxi', label: t('dashboard_analytics.fleet_taxi') },
    { value: 'unspecified', label: t('dashboard_analytics.fleet_unspecified') },
  ]
  return [
    {
      id: 'run',
      label: t('dashboard_analytics.filter_trip_run_status'),
      summary: filters.status ? labelTripStatus(filters.status) : fa,
      options: runStatusOptions.value,
      isSelected: (v) => (v === '' ? !filters.status : filters.status === v),
      pick: (v) => {
        filters.status = v || ''
        onFilterChange()
      },
    },
    {
      id: 'channel',
      label: t('dashboard_analytics.filter_channel'),
      summary: filters.source_channel
        ? t(`labels.source_channel.${filters.source_channel}`)
        : fa,
      options: channelOpts,
      isSelected: (v) => (v === '' ? !filters.source_channel : filters.source_channel === v),
      pick: (v) => {
        filters.source_channel = v || ''
        onFilterChange()
      },
    },
    {
      id: 'paper',
      label: t('dashboard_analytics.filter_paper'),
      summary: filters.paper_status ? t(`labels.paper_status.${filters.paper_status}`) : fa,
      options: paperOpts,
      isSelected: (v) => (v === '' ? !filters.paper_status : filters.paper_status === v),
      pick: (v) => {
        filters.paper_status = v || ''
        onFilterChange()
      },
    },
    {
      id: 'fleet',
      label: t('dashboard_analytics.filter_fleet'),
      summary: filters.fleet_mode
        ? {
            internal: t('dashboard_analytics.fleet_internal'),
            vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
            taxi: t('dashboard_analytics.fleet_taxi'),
            unspecified: t('dashboard_analytics.fleet_unspecified'),
          }[filters.fleet_mode] ?? filters.fleet_mode
        : fa,
      options: fleetOpts,
      isSelected: (v) => (v === '' ? !filters.fleet_mode : filters.fleet_mode === v),
      pick: (v) => {
        filters.fleet_mode = v || ''
        onFilterChange()
      },
    },
    {
      id: 'urgent',
      label: t('dashboard_analytics.filter_urgent'),
      summary: filters.is_urgent ? t('dashboard_analytics.filter_urgent_only') : fa,
      options: [
        { value: '', label: fa },
        { value: '1', label: t('dashboard_analytics.filter_urgent_only') },
      ],
      isSelected: (v) => (v === '' ? !filters.is_urgent : v === '1' && filters.is_urgent),
      pick: (v) => {
        filters.is_urgent = v === '1'
        onFilterChange()
      },
    },
  ]
})

const activeFilterLines = computed(() => {
  const rows = []
  if (filters.status) {
    rows.push({ label: t('dashboard_analytics.filter_trip_run_status'), value: labelTripStatus(filters.status) })
  }
  if (filters.source_channel) {
    rows.push({ label: t('dashboard_analytics.filter_channel'), value: t(`labels.source_channel.${filters.source_channel}`) })
  }
  if (filters.paper_status) {
    rows.push({ label: t('dashboard_analytics.filter_paper'), value: t(`labels.paper_status.${filters.paper_status}`) })
  }
  if (filters.fleet_mode) {
    const map = {
      internal: t('dashboard_analytics.fleet_internal'),
      vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
      taxi: t('dashboard_analytics.fleet_taxi'),
      unspecified: t('dashboard_analytics.fleet_unspecified'),
    }
    rows.push({ label: t('dashboard_analytics.filter_fleet'), value: map[filters.fleet_mode] ?? filters.fleet_mode })
  }
  if (filters.is_urgent) {
    rows.push({ label: t('dashboard_analytics.filter_urgent'), value: t('dashboard_analytics.filter_urgent_only') })
  }
  return rows
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.source_channel) n++
  if (filters.paper_status) n++
  if (filters.fleet_mode) n++
  if (filters.is_urgent) n++
  if (filters.q) n++
  if (filters.per_page !== 20) n++
  return n
})

const byType = computed(() => stats.value.by_trip_type ?? {})

const typeTabs = computed(() => {
  const bt = byType.value
  const total = stats.value.total ?? 0
  return [
    { value: '', label: t('trips_page.tab_all'), count: total },
    { value: 'door_to_door', label: t('trips_page.tab_d2d'), count: bt.door_to_door ?? 0 },
    { value: 'point_to_point', label: t('trips_page.tab_p2p'), count: bt.point_to_point ?? 0 },
    { value: 'business', label: t('trips_page.tab_business'), count: bt.business ?? 0 },
    { value: 'cargo', label: t('trips_page.tab_cargo'), count: bt.cargo ?? 0 },
  ]
})

const kpiBoxes = computed(() => {
  const bt = byType.value
  return [
    {
      key: 'total',
      label: t('trips_page.kpi_total'),
      value: stats.value.total ?? 0,
      icon: QueueListIcon,
      iconWrap: 'bg-slate-100 dark:bg-slate-800',
      iconClass: 'text-slate-600 dark:text-slate-300',
    },
    {
      key: 'd2d',
      label: t('trips_page.kpi_d2d'),
      value: bt.door_to_door ?? 0,
      icon: TruckIcon,
      iconWrap: 'bg-sky-100 dark:bg-sky-950/50',
      iconClass: 'text-sky-600 dark:text-sky-400',
    },
    {
      key: 'p2p',
      label: t('trips_page.kpi_p2p'),
      value: bt.point_to_point ?? 0,
      icon: MapPinIcon,
      iconWrap: 'bg-emerald-100 dark:bg-emerald-950/50',
      iconClass: 'text-emerald-600 dark:text-emerald-400',
    },
    {
      key: 'biz',
      label: t('trips_page.kpi_business'),
      value: bt.business ?? 0,
      icon: BriefcaseIcon,
      iconWrap: 'bg-amber-100 dark:bg-amber-950/50',
      iconClass: 'text-amber-600 dark:text-amber-400',
    },
    {
      key: 'issue',
      label: t('trips_page.kpi_issues'),
      value: stats.value.incident ?? 0,
      icon: ExclamationTriangleIcon,
      iconWrap: 'bg-rose-100 dark:bg-rose-950/50',
      iconClass: 'text-rose-600 dark:text-rose-400',
    },
  ]
})

const canAssignTrip = computed(() => auth.hasPermission('trip.assign'))

const pageFrom = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return (cur - 1) * pp + 1
})

const pageTo = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return Math.min(cur * pp, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const delta = 2
  const start = Math.max(1, cur - delta)
  const end = Math.min(last, cur + delta)
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

function tripCode(id) {
  return `TRP-${String(id).padStart(4, '0')}`
}

function tripTypeShort(tt) {
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return t('trips_page.badge_business_short')
  if (tt === 'cargo') return t('trips_page.badge_cargo_short')
  return '—'
}

function tripTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (tt === 'point_to_point') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (tt === 'business') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  if (tt === 'cargo') return 'bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function tripTypeIconWrap(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 dark:bg-sky-950/40'
  if (tt === 'point_to_point') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (tt === 'business') return 'bg-amber-100 dark:bg-amber-950/40'
  return 'bg-violet-100 dark:bg-violet-950/40'
}

function statusPillClass(s) {
  const map = {
    pending: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
    approved: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
    assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
    driver_confirmed: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    in_progress: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    completed: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    incident: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800'
}

function fmtCreated(v) {
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return new Date(v).toLocaleString(loc, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function fmtShort(v) {
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return new Date(v).toLocaleString(loc, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function driverInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function cargoSummary(trip) {
  const pc = trip.dispatch_request?.passenger_count
  if (pc != null && pc !== '') {
    return t('trips_page.meta_passengers', { n: pc })
  }
  return labelTripType(trip.dispatch_request?.trip_type)
}

function distanceSummary(trip) {
  const km = trip.record?.distance_km
  if (km != null && Number(km) > 0) {
    return t('trips_page.meta_km', { n: Math.round(Number(km) * 10) / 10 })
  }
  return '—'
}

function durationSummary(trip) {
  const a = trip.depart_at ? new Date(trip.depart_at).getTime() : null
  const bRaw = trip.arrive_by || trip.dispatch_request?.arrive_by
  const b = bRaw ? new Date(bRaw).getTime() : null
  if (!a || !b || b <= a) return '—'
  const mins = Math.round((b - a) / 60000)
  const h = Math.floor(mins / 60)
  const m = mins % 60
  if (h && m) return t('trips_page.meta_duration_hm', { h, m })
  if (h) return t('trips_page.meta_duration_h', { h })
  return t('trips_page.meta_duration_m', { m })
}

function mapsHref(trip) {
  const o = trip.dispatch_request?.origin
  const d = trip.dispatch_request?.destination
  if (!o || !d) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
}

function needsAssign(trip) {
  return !trip.driver_id && !['completed', 'cancelled'].includes(trip.status)
}

function setTripTypeTab(v) {
  filters.trip_type = v
  onFilterChange()
}

function applyStatusFromRoute() {
  const s = route.query.status
  filters.status = typeof s === 'string' && s ? s : ''
}

function listParams() {
  syncFiltersFromRange()
  const p = {
    trip_type: filters.trip_type || undefined,
    status: filters.status || undefined,
    source_channel: filters.source_channel || undefined,
    paper_status: filters.paper_status || undefined,
    fleet_mode: filters.fleet_mode || undefined,
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q || undefined,
    page: filters.page,
    per_page: filters.per_page,
  }
  if (filters.is_urgent) p.is_urgent = 1
  Object.keys(p).forEach((k) => {
    if (p[k] === '' || p[k] === undefined || p[k] === null) delete p[k]
  })
  return p
}

function statsParams() {
  const p = listParams()
  delete p.trip_type
  delete p.page
  delete p.per_page
  return p
}

async function reloadStats() {
  if (!rangeValid.value) return
  statsLoading.value = true
  try {
    stats.value = await getTripStats(statsParams())
  } catch {
    stats.value = { total: 0, by_trip_type: {}, incident: 0 }
  } finally {
    statsLoading.value = false
  }
}

async function reload() {
  if (!rangeValid.value) return
  loading.value = true
  try {
    const res = await listTrips(listParams())
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function onFilterChange() {
  filters.page = 1
  reloadStats()
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.is_urgent = false
  filters.fleet_mode = ''
  filters.trip_type = ''
  filters.q = ''
  searchInput.value = ''
  filters.per_page = 20
  filters.page = 1
  preset.value = 'month'
  syncRangeForPreset('month')
  syncFiltersFromRange()
  applyStatusFromRoute()
  if (funnelDetailsRef.value) funnelDetailsRef.value.open = false
  reloadStats()
  reload()
}

function goPage(n) {
  const last = meta.value.last_page ?? 1
  if (n < 1 || n > last) return
  filters.page = n
  reload()
}

function flushSearch() {
  filters.q = searchInput.value.trim()
  onFilterChange()
}

watch(searchInput, () => {
  clearTimeout(searchDebounce.value)
  searchDebounce.value = setTimeout(() => {
    const next = searchInput.value.trim()
    if (next !== filters.q) {
      filters.q = next
      onFilterChange()
    }
  }, 320)
})

watch(
  () => route.query.status,
  () => {
    applyStatusFromRoute()
    filters.page = 1
    reloadStats()
    reload()
  },
)

onMounted(() => {
  syncRangeForPreset('month')
  syncFiltersFromRange()
  applyStatusFromRoute()
  reloadStats()
  reload()
})
</script>
