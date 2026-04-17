<template>
  <div class="space-y-4 md:space-y-5">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
      {{ loadError }}
    </div>

    <div class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
            {{ t('dashboard_analytics.title') }}
          </h1>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            {{ t('dashboard_analytics.subtitle') }}
          </p>
        </div>
        <RouterLink
          class="shrink-0 text-sm font-medium text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300"
          to="/reports"
        >
          {{ t('dashboard_analytics.reports_link') }} →
        </RouterLink>
      </div>

      <!-- Truy cập nhanh: mũi tên hai bên, ẩn thanh cuộn -->
      <div>
        <h2 class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('dashboard_analytics.quick_title') }}
        </h2>
        <div class="relative -mx-0.5 mt-2 flex items-stretch gap-1 sm:-mx-1 sm:gap-2">
          <button
            type="button"
            class="flex h-auto min-h-[5.5rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-teal-200/70 hover:bg-white hover:text-teal-700 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-teal-800 dark:hover:text-teal-400 sm:w-9"
            :disabled="!quickCanScrollLeft"
            :aria-label="t('dashboard_analytics.quick_scroll_prev')"
            @click="scrollQuickLinks(-1)"
          >
            <ChevronLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
          <div
            ref="quickScrollRef"
            class="dash-quick-scroll min-h-[5.5rem] min-w-0 flex-1 overflow-x-auto overflow-y-hidden scroll-smooth"
            @scroll.passive="updateQuickScrollState"
          >
            <div class="flex h-full flex-nowrap items-stretch gap-2 px-0.5 py-0.5 sm:gap-3">
              <RouterLink
                v-for="item in quickLinks"
                :key="item.to"
                :to="item.to"
                class="flex w-[6.5rem] shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border border-slate-200/90 bg-white/90 px-2.5 py-3.5 text-center shadow-sm transition hover:border-teal-200 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-teal-800 sm:w-32 md:w-36"
              >
                <component :is="item.icon" class="h-7 w-7 shrink-0 text-teal-600 dark:text-teal-400 sm:h-8 sm:w-8" aria-hidden="true" />
                <span class="w-full text-center line-clamp-2 text-[11px] font-medium leading-tight text-slate-800 dark:text-slate-100 sm:text-xs">
                  {{ item.title }}
                </span>
              </RouterLink>
            </div>
          </div>
          <button
            type="button"
            class="flex h-auto min-h-[5.5rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-teal-200/70 hover:bg-white hover:text-teal-700 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-teal-800 dark:hover:text-teal-400 sm:w-9"
            :disabled="!quickCanScrollRight"
            :aria-label="t('dashboard_analytics.quick_scroll_next')"
            @click="scrollQuickLinks(1)"
          >
            <ChevronRightIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
        </div>
      </div>

      <AppFilterBar>
        <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <details class="group relative">
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
              </ul>
              <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                  {{ t('dashboard_analytics.filter_optional_title') }}
                </p>
                <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                  {{ t('dashboard_analytics.filter_optional_hint') }}
                </p>
                <ul class="mt-2 max-h-[min(50vh,240px)] space-y-2 overflow-y-auto pr-0.5">
                  <li v-for="fd in dimensionFilters" :key="fd.id" class="flex items-start gap-2">
                    <input
                      :id="'dash-bar-vis-' + fd.id"
                      v-model="dimensionFilterBarVisible[fd.id]"
                      type="checkbox"
                      class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                    />
                    <label
                      :for="'dash-bar-vis-' + fd.id"
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
                    <div
                      class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner shadow-slate-200/20 dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                    >
                      <label
                        class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                        for="dash-range-from"
                      >
                        {{ t('dashboard_analytics.range_from') }}
                      </label>
                      <input
                        id="dash-range-from"
                        v-model="rangeFrom"
                        type="date"
                        :max="rangeTo || undefined"
                        class="dash-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
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
                    <div
                      class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner shadow-slate-200/20 dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                    >
                      <label
                        class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                        for="dash-range-to"
                      >
                        {{ t('dashboard_analytics.range_to') }}
                      </label>
                      <input
                        id="dash-range-to"
                        v-model="rangeTo"
                        type="date"
                        :min="rangeFrom || undefined"
                        class="dash-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        @change="onRangeToChange"
                      />
                    </div>
                  </div>
                  <button
                    v-if="preset === 'custom'"
                    type="button"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition hover:from-teal-700 hover:to-teal-600 disabled:opacity-50 dark:shadow-teal-900/30"
                    :disabled="loading || !rangeValid"
                    @click="reloadSummary"
                  >
                    {{ t('dashboard_analytics.apply_range') }}
                  </button>
                  <p v-if="!rangeValid" class="mt-2 text-center text-xs text-rose-600 dark:text-rose-400">
                    {{ t('dashboard_analytics.range_invalid') }}
                  </p>
                </div>
              </div>
            </details>
            <template v-for="fd in visibleDimensionFilters" :key="fd.id">
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
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <!-- KPI hàng 1 -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card :title="t('dashboard_analytics.kpi_trips_title')">
        <div class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white md:text-3xl">{{ totalTrips }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_trips_sub') }}</p>
        <div class="mt-2 max-h-24 space-y-1 overflow-y-auto border-t border-slate-100 pt-2 text-[11px] text-slate-600 dark:border-slate-700 dark:text-slate-300 md:max-h-28">
          <div v-for="row in tripStatusRows" :key="row.key" class="flex justify-between gap-2 tabular-nums">
            <span class="truncate text-slate-500 dark:text-slate-400">{{ row.label }}</span>
            <span class="shrink-0 font-medium text-slate-900 dark:text-slate-100">{{ row.n }}</span>
          </div>
          <div v-if="!tripStatusRows.length" class="text-slate-400 dark:text-slate-500">—</div>
        </div>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_cost_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">{{ formatMoney(totalConfirmedCost) }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_cost_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_sla_title')">
        <div class="text-xl font-bold tabular-nums text-rose-600 dark:text-rose-400 md:text-2xl">{{ summary?.cargo_sla_breaches ?? 0 }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_sla_sub') }}</p>
      </Card>

      <Card :title="t('dashboard_analytics.kpi_providers_title')">
        <div class="truncate text-base font-semibold text-slate-900 dark:text-white md:text-lg">{{ topProviderName }}</div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_providers_sub') }}</p>
        <p v-if="topProviderAmount" class="mt-1.5 text-sm font-medium tabular-nums text-slate-700 dark:text-slate-300">
          {{ formatMoney(topProviderAmount) }}
        </p>
      </Card>
    </div>

    <!-- KPI hàng 2 -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <Card :title="t('dashboard_analytics.kpi_completion_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">
          <template v-if="completionRate != null">{{ completionRate }}%</template>
          <template v-else>—</template>
        </div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
          {{ completedTrips }} / {{ totalTripsInRange }} · {{ t('dashboard_analytics.kpi_completion_sub') }}
        </p>
      </Card>
      <Card :title="t('dashboard_analytics.kpi_distance_title')">
        <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white md:text-2xl">
          {{ formatDistanceKm(summary?.trip_records_distance_km) }}
        </div>
        <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.kpi_distance_sub') }}</p>
      </Card>
    </div>

    <!-- Tuân thủ xe -->
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-4">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
        {{ t('dashboard_analytics.section_compliance') }}
      </h2>
      <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="box in complianceBoxes"
          :key="box.key"
          class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950/50"
        >
          <div class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ box.title }}</div>
          <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] tabular-nums">
            <span class="text-rose-600 dark:text-rose-400">{{ t('dashboard_analytics.compliance_overdue') }}: {{ box.overdue }}</span>
            <span v-if="box.soon != null" class="text-amber-700 dark:text-amber-400">{{ t('dashboard_analytics.compliance_due_30d') }}: {{ box.soon }}</span>
            <span v-if="box.stale != null" class="text-slate-600 dark:text-slate-400">{{ box.staleLabel }}: {{ box.stale }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ t('dashboard_analytics.section_recent_trips') }}
          </h2>
          <RouterLink
            class="text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400"
            to="/trips"
          >
            {{ t('dashboard_analytics.recent_see_all') }} →
          </RouterLink>
        </div>
        <div v-if="recentLoading" class="text-xs text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.loading') }}</div>
        <ul v-else-if="recentTrips.length" class="divide-y divide-slate-100 dark:divide-slate-800">
          <li v-for="tr in recentTrips" :key="tr.id" class="py-2 first:pt-0">
            <RouterLink
              :to="`/trips/${tr.id}`"
              class="group flex flex-col gap-0.5 rounded-lg py-0.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <div class="truncate text-sm font-medium text-slate-900 group-hover:text-teal-700 dark:text-slate-100 dark:group-hover:text-teal-400">
                  {{ tr.dispatch_request?.origin ?? '—' }}
                  <span class="text-slate-400">→</span>
                  {{ tr.dispatch_request?.destination ?? '—' }}
                </div>
                <div class="text-xs tabular-nums text-slate-500 dark:text-slate-400">
                  {{ formatDepartShort(tr.depart_at) }}
                </div>
              </div>
              <span class="shrink-0 text-xs font-medium text-slate-600 dark:text-slate-300">
                {{ labelTripStatus(tr.status) }}
              </span>
            </RouterLink>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
      </div>
      <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ t('dashboard_analytics.section_recent_requests') }}
          </h2>
          <RouterLink
            class="text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400"
            to="/requests"
          >
            {{ t('dashboard_analytics.recent_see_all') }} →
          </RouterLink>
        </div>
        <div v-if="recentLoading" class="text-xs text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.loading') }}</div>
        <ul v-else-if="recentRequests.length" class="divide-y divide-slate-100 dark:divide-slate-800">
          <li v-for="rq in recentRequests" :key="rq.id" class="py-2 first:pt-0">
            <RouterLink
              :to="`/requests/${rq.id}`"
              class="group flex flex-col gap-0.5 rounded-lg py-0.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <div class="truncate text-sm font-medium text-slate-900 group-hover:text-teal-700 dark:text-slate-100 dark:group-hover:text-teal-400">
                  #{{ rq.id }} · {{ rq.origin ?? '—' }}
                  <span class="text-slate-400">→</span>
                  {{ rq.destination ?? '—' }}
                </div>
                <div class="text-xs tabular-nums text-slate-500 dark:text-slate-400">
                  {{ formatDepartShort(rq.depart_at) }}
                </div>
              </div>
              <span class="shrink-0 text-xs font-medium text-slate-600 dark:text-slate-300">
                {{ labelRequestStatus(rq.status) }}
              </span>
            </RouterLink>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
      </div>
    </div>

    <p class="text-center text-[11px] text-slate-400 dark:text-slate-500">
      {{ t('dashboard_analytics.chart_toolbar_hint') }}
    </p>

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

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <DashboardChartSection
        :title="t('dashboard_analytics.chart_trip_type')"
        :hint="t('dashboard_analytics.chart_hint_trip_type')"
        persist-key="bar-trip-type"
        :expand-label="t('dashboard_analytics.chart_expand')"
        :collapse-label="t('dashboard_analytics.chart_collapse')"
      >
        <DashboardEChart
          :height="chartHeight"
          :option="optTripType"
          :aria-label="t('dashboard_analytics.chart_trip_type')"
        />
      </DashboardChartSection>
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
    </div>

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

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
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
  </div>
</template>

<script setup>
import { computed, markRaw, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CalendarDaysIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  DocumentMagnifyingGlassIcon,
  FunnelIcon,
  PlusCircleIcon,
  Square2StackIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import Card from '../components/ui/Card.vue'
import AppFilterBar from '../components/filters/AppFilterBar.vue'
import DashboardEChart from '../components/dashboard/DashboardEChart.vue'
import DashboardChartSection from '../components/dashboard/DashboardChartSection.vue'
import { getSummary } from '../api/reports'
import { listTrips } from '../api/trips'
import { listRequests, normalizeRequestListParams } from '../api/requests'
import { labelTripStatus, labelTripType, labelRequestStatus } from '../util/labels'
import {
  sumTrips,
  normalizeTripsByHour,
  tripsStatusDonutOption,
  fleetModeDonutOption,
  statusDonutOption,
  costsByTypeBarOption,
  costsPipelineBarOption,
  providersHorizontalBarOption,
  topRequestersBarOption,
  licensePlateTripsBarOption,
  tripsByHourLineOption,
  tripsByTripTypeBarOption,
} from '../util/transportDashboardCharts'

const { t, locale } = useI18n()

function formatDisplayDate(ymdStr) {
  if (!ymdStr || typeof ymdStr !== 'string') return '—'
  const p = ymdStr.split('-').map((x) => Number(x))
  if (p.length !== 3 || Number.isNaN(p[0])) return ymdStr
  const tag = locale.value === 'vi' ? 'vi-VN' : 'en-GB'
  try {
    return new Intl.DateTimeFormat(tag, { day: '2-digit', month: 'short', year: 'numeric' }).format(
      new Date(p[0], p[1] - 1, p[2]),
    )
  } catch {
    return ymdStr
  }
}

function ymd(d) {
  const x = new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function subDays(base, n) {
  const x = new Date(base)
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

const filterTripType = ref('')
const filterSourceChannel = ref('')
const filterPaperStatus = ref('')
const filterIsUrgent = ref(false)
const filterTripRunStatus = ref('')
const filterFleetMode = ref('')

const DIMENSION_BAR_VISIBLE_KEY = 'dash-dimension-bar-visible'
const DIMENSION_FILTER_IDS = ['trip_type', 'channel', 'paper', 'urgent', 'trip_run', 'fleet']

function defaultDimensionBarVisibility() {
  return Object.fromEntries(DIMENSION_FILTER_IDS.map((id) => [id, true]))
}

const dimensionFilterBarVisible = ref(defaultDimensionBarVisibility())

const quickScrollRef = ref(null)
const quickCanScrollLeft = ref(false)
const quickCanScrollRight = ref(false)

const loading = ref(false)
const loadError = ref('')
const summary = ref(null)

const recentTrips = ref([])
const recentRequests = ref([])
const recentLoading = ref(false)

const TRIP_RUN_STATUS_KEYS = [
  'pending',
  'approved',
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
  'cancelled',
  'incident',
]

const chartHeight = ref('260px')
const chartHeightWide = ref('280px')
const chartHeightTall = ref('300px')

function updateChartHeights() {
  const narrow = typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches
  chartHeight.value = narrow ? '220px' : '260px'
  chartHeightWide.value = narrow ? '240px' : '300px'
  chartHeightTall.value = narrow ? '260px' : '320px'
}

function updateQuickScrollState() {
  const el = quickScrollRef.value
  if (!el) {
    quickCanScrollLeft.value = false
    quickCanScrollRight.value = false
    return
  }
  const { scrollLeft, scrollWidth, clientWidth } = el
  quickCanScrollLeft.value = scrollLeft > 2
  quickCanScrollRight.value = scrollLeft + clientWidth < scrollWidth - 2
}

function scrollQuickLinks(direction) {
  const el = quickScrollRef.value
  if (!el) return
  const step = Math.max(160, Math.floor(el.clientWidth * 0.82))
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

function loadDimensionBarVisibility() {
  try {
    const raw = sessionStorage.getItem(DIMENSION_BAR_VISIBLE_KEY)
    if (!raw) return
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return
    const next = defaultDimensionBarVisibility()
    for (const id of DIMENSION_FILTER_IDS) {
      if (typeof parsed[id] === 'boolean') next[id] = parsed[id]
    }
    dimensionFilterBarVisible.value = next
  } catch {
    /* ignore */
  }
}

const presetDefs = computed(() => [
  { id: 'month', label: t('dashboard_analytics.preset_month') },
  { id: 'last30', label: t('dashboard_analytics.preset_last30') },
  { id: 'last7', label: t('dashboard_analytics.preset_last7') },
  { id: 'quarter', label: t('dashboard_analytics.preset_quarter') },
  { id: 'custom', label: t('dashboard_analytics.preset_custom') },
])

const currentPresetLabel = computed(() => presetDefs.value.find((p) => p.id === preset.value)?.label ?? '')

const quickLinks = computed(() => [
  { to: '/dispatcher', title: t('dashboard_analytics.quick_dispatcher'), icon: markRaw(Square2StackIcon) },
  { to: '/trips', title: t('dashboard_analytics.quick_trips'), icon: markRaw(TruckIcon) },
  { to: '/dispatch-requests/new', title: t('dashboard_analytics.quick_new_request'), icon: markRaw(PlusCircleIcon) },
  { to: '/requests', title: t('dashboard_analytics.quick_requests'), icon: markRaw(ClipboardDocumentListIcon) },
  { to: '/resources/list', title: t('dashboard_analytics.quick_resources'), icon: markRaw(UserGroupIcon) },
  { to: '/cargo', title: t('dashboard_analytics.quick_cargo'), icon: markRaw(CubeIcon) },
  { to: '/costs', title: t('dashboard_analytics.quick_costs'), icon: markRaw(BanknotesIcon) },
  { to: '/pricing', title: t('dashboard_analytics.quick_pricing'), icon: markRaw(TableCellsIcon) },
  { to: '/audit-logs', title: t('dashboard_analytics.quick_audit'), icon: markRaw(DocumentMagnifyingGlassIcon) },
])

const emptyChartLabel = computed(() => t('dashboard_analytics.chart_empty'))

const summaryFilters = computed(() => {
  const o = {
    from: rangeFrom.value,
    to: rangeTo.value,
  }
  if (filterTripType.value) o.trip_type = filterTripType.value
  if (filterSourceChannel.value) o.source_channel = filterSourceChannel.value
  if (filterPaperStatus.value) o.paper_status = filterPaperStatus.value
  if (filterIsUrgent.value) o.is_urgent = 1
  if (filterTripRunStatus.value) o.trip_status = filterTripRunStatus.value
  if (filterFleetMode.value) o.fleet_mode = filterFleetMode.value
  return o
})

const tripListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  if (o.trip_status) {
    o.status = o.trip_status
    delete o.trip_status
  }
  return o
})

const requestListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  delete o.fleet_mode
  return o
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filterTripType.value) n++
  if (filterSourceChannel.value) n++
  if (filterPaperStatus.value) n++
  if (filterIsUrgent.value) n++
  if (filterTripRunStatus.value) n++
  if (filterFleetMode.value) n++
  return n
})

const activeFilterLines = computed(() => {
  const rows = []
  if (filterTripType.value) {
    rows.push({ label: t('dashboard_analytics.filter_trip_type'), value: labelTripType(filterTripType.value) })
  }
  if (filterSourceChannel.value) {
    rows.push({
      label: t('dashboard_analytics.filter_channel'),
      value: t(`labels.source_channel.${filterSourceChannel.value}`),
    })
  }
  if (filterPaperStatus.value) {
    rows.push({
      label: t('dashboard_analytics.filter_paper'),
      value: t(`labels.paper_status.${filterPaperStatus.value}`),
    })
  }
  if (filterIsUrgent.value) {
    rows.push({ label: t('dashboard_analytics.filter_urgent'), value: t('dashboard_analytics.filter_urgent_only') })
  }
  if (filterTripRunStatus.value) {
    rows.push({
      label: t('dashboard_analytics.filter_trip_run_status'),
      value: labelTripStatus(filterTripRunStatus.value),
    })
  }
  if (filterFleetMode.value) {
    const map = {
      internal: t('dashboard_analytics.fleet_internal'),
      vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
      taxi: t('dashboard_analytics.fleet_taxi'),
      unspecified: t('dashboard_analytics.fleet_unspecified'),
    }
    rows.push({ label: t('dashboard_analytics.filter_fleet'), value: map[filterFleetMode.value] ?? filterFleetMode.value })
  }
  return rows
})

const dimensionFilters = computed(() => {
  const fa = t('dashboard_analytics.filter_all')
  const tripTypeOpts = [
    { value: '', label: fa },
    { value: 'door_to_door', label: labelTripType('door_to_door') },
    { value: 'point_to_point', label: labelTripType('point_to_point') },
    { value: 'business', label: labelTripType('business') },
    { value: 'cargo', label: labelTripType('cargo') },
  ]
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
  const urgentOpts = [
    { value: '', label: fa },
    { value: '1', label: t('dashboard_analytics.filter_urgent_only') },
  ]
  const runStatusOpts = [{ value: '', label: fa }].concat(
    TRIP_RUN_STATUS_KEYS.map((k) => ({ value: k, label: labelTripStatus(k) })),
  )
  const fleetOpts = [
    { value: '', label: fa },
    { value: 'internal', label: t('dashboard_analytics.fleet_internal') },
    { value: 'vendor_hire', label: t('dashboard_analytics.fleet_vendor_hire') },
    { value: 'taxi', label: t('dashboard_analytics.fleet_taxi') },
    { value: 'unspecified', label: t('dashboard_analytics.fleet_unspecified') },
  ]

  return [
    {
      id: 'trip_type',
      label: t('dashboard_analytics.filter_trip_type'),
      summary: filterTripType.value ? labelTripType(filterTripType.value) : fa,
      options: tripTypeOpts,
      isSelected: (v) => (v === '' ? !filterTripType.value : filterTripType.value === v),
      pick: (v) => {
        filterTripType.value = v || ''
        reloadSummary()
      },
    },
    {
      id: 'channel',
      label: t('dashboard_analytics.filter_channel'),
      summary: filterSourceChannel.value ? t(`labels.source_channel.${filterSourceChannel.value}`) : fa,
      options: channelOpts,
      isSelected: (v) => (v === '' ? !filterSourceChannel.value : filterSourceChannel.value === v),
      pick: (v) => {
        filterSourceChannel.value = v || ''
        reloadSummary()
      },
    },
    {
      id: 'paper',
      label: t('dashboard_analytics.filter_paper'),
      summary: filterPaperStatus.value ? t(`labels.paper_status.${filterPaperStatus.value}`) : fa,
      options: paperOpts,
      isSelected: (v) => (v === '' ? !filterPaperStatus.value : filterPaperStatus.value === v),
      pick: (v) => {
        filterPaperStatus.value = v || ''
        reloadSummary()
      },
    },
    {
      id: 'urgent',
      label: t('dashboard_analytics.filter_urgent'),
      summary: filterIsUrgent.value ? t('dashboard_analytics.filter_urgent_only') : fa,
      options: urgentOpts,
      isSelected: (v) => (v === '' ? !filterIsUrgent.value : filterIsUrgent.value),
      pick: (v) => {
        filterIsUrgent.value = v === '1'
        reloadSummary()
      },
    },
    {
      id: 'trip_run',
      label: t('dashboard_analytics.filter_trip_run_status'),
      summary: filterTripRunStatus.value ? labelTripStatus(filterTripRunStatus.value) : fa,
      options: runStatusOpts,
      isSelected: (v) => (v === '' ? !filterTripRunStatus.value : filterTripRunStatus.value === v),
      pick: (v) => {
        filterTripRunStatus.value = v || ''
        reloadSummary()
      },
    },
    {
      id: 'fleet',
      label: t('dashboard_analytics.filter_fleet'),
      summary: filterFleetMode.value
        ? {
            internal: t('dashboard_analytics.fleet_internal'),
            vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
            taxi: t('dashboard_analytics.fleet_taxi'),
            unspecified: t('dashboard_analytics.fleet_unspecified'),
          }[filterFleetMode.value] ?? filterFleetMode.value
        : fa,
      options: fleetOpts,
      isSelected: (v) => (v === '' ? !filterFleetMode.value : filterFleetMode.value === v),
      pick: (v) => {
        filterFleetMode.value = v || ''
        reloadSummary()
      },
    },
  ]
})

const visibleDimensionFilters = computed(() =>
  dimensionFilters.value.filter((fd) => dimensionFilterBarVisible.value[fd.id] !== false),
)

const rangeValid = computed(() => {
  if (!rangeFrom.value || !rangeTo.value) return false
  return rangeFrom.value <= rangeTo.value
})

const rangeDisplayFormatted = computed(
  () => `${formatDisplayDate(rangeFrom.value)} — ${formatDisplayDate(rangeTo.value)}`,
)

const rangeDaySpan = computed(() => {
  if (!rangeFrom.value || !rangeTo.value || rangeFrom.value > rangeTo.value) return 0
  const a = new Date(`${rangeFrom.value}T12:00:00`)
  const b = new Date(`${rangeTo.value}T12:00:00`)
  return Math.floor((b.getTime() - a.getTime()) / 86400000) + 1
})

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
    reloadSummary()
  }
}

function resetFilters() {
  filterTripType.value = ''
  filterSourceChannel.value = ''
  filterPaperStatus.value = ''
  filterIsUrgent.value = false
  filterTripRunStatus.value = ''
  filterFleetMode.value = ''
  applyPreset('month')
}

function formatDepartShort(s) {
  if (s == null || s === '') return '—'
  return String(s).replace('T', ' ').slice(0, 16)
}

async function loadRecentLists() {
  if (!rangeValid.value) return
  recentLoading.value = true
  try {
    const tRes = await listTrips({ ...tripListQuery.value, per_page: 10, page: 1 })
    const rRes = await listRequests(normalizeRequestListParams({ ...requestListQuery.value, per_page: 10, page: 1 }))
    recentTrips.value = tRes.items ?? []
    recentRequests.value = rRes.items ?? []
  } catch {
    recentTrips.value = []
    recentRequests.value = []
  } finally {
    recentLoading.value = false
  }
}

function onManualDateChange() {
  preset.value = 'custom'
}

function onRangeFromChange() {
  if (rangeFrom.value && rangeTo.value && rangeFrom.value > rangeTo.value) {
    rangeTo.value = rangeFrom.value
  }
  onManualDateChange()
}

function onRangeToChange() {
  if (rangeFrom.value && rangeTo.value && rangeTo.value < rangeFrom.value) {
    rangeFrom.value = rangeTo.value
  }
  onManualDateChange()
}

function applyQuickDateRange(kind) {
  if (kind === 'month') {
    applyPreset('month')
    return
  }
  const now = new Date()
  if (kind === 'today') {
    const d = ymd(now)
    rangeFrom.value = d
    rangeTo.value = d
  } else if (kind === 'yesterday') {
    const d = ymd(subDays(now, 1))
    rangeFrom.value = d
    rangeTo.value = d
  } else if (kind === 'last7') {
    rangeFrom.value = ymd(subDays(now, 6))
    rangeTo.value = ymd(now)
  }
  preset.value = 'custom'
  reloadSummary()
}

function formatMoney(v) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat('vi-VN').format(n) + ' VND'
}

function formatDistanceKm(v) {
  const n = Number(v ?? 0)
  if (!Number.isFinite(n) || n <= 0) return '—'
  return `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Math.round(n))} km`
}

const tripLabelMap = computed(() => {
  const o = summary.value?.trips_by_status ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = labelTripStatus(k)
  }
  return map
})

const tripTypeLabelMap = computed(() => {
  const o = summary.value?.trips_by_trip_type ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = k === 'unspecified' ? t('dashboard_analytics.trip_type_unspecified') : labelTripType(k)
  }
  return map
})

const fleetLabelMap = computed(() => ({
  internal: t('dashboard_analytics.fleet_internal'),
  vendor_hire: t('dashboard_analytics.fleet_vendor_hire'),
  taxi: t('dashboard_analytics.fleet_taxi'),
  unspecified: t('dashboard_analytics.fleet_unspecified'),
}))

const dispatchStatusLabelMap = computed(() => {
  const o = summary.value?.dispatch_requests_by_status ?? {}
  const map = {}
  for (const k of Object.keys(o)) {
    map[k] = labelRequestStatus(k)
  }
  return map
})

const costPipelineLabelMap = computed(() => ({
  draft: t('dashboard_analytics.cost_status_draft'),
  submitted: t('dashboard_analytics.cost_status_submitted'),
  confirmed: t('dashboard_analytics.cost_status_confirmed'),
  rejected: t('dashboard_analytics.cost_status_rejected'),
}))

const totalTrips = computed(() => sumTrips(summary.value?.trips_by_status))

const chartBadgeTripsTotal = computed(() => (totalTrips.value > 0 ? String(totalTrips.value) : ''))

const totalTripsInRange = computed(() => Number(summary.value?.trip_completion?.total ?? totalTrips.value))

const completedTrips = computed(() => Number(summary.value?.trip_completion?.completed ?? 0))

const completionRate = computed(() => {
  const r = summary.value?.trip_completion?.rate_pct
  return r != null ? Number(r) : null
})

const tripStatusRows = computed(() => {
  const raw = summary.value?.trips_by_status ?? {}
  return Object.entries(raw)
    .map(([key, v]) => ({
      key,
      label: labelTripStatus(key),
      n: Number(v ?? 0),
    }))
    .filter((r) => r.n > 0)
    .sort((a, b) => b.n - a.n)
})

const totalConfirmedCost = computed(() => {
  const o = summary.value?.confirmed_costs_by_type ?? {}
  return Object.values(o).reduce((a, b) => a + Number(b ?? 0), 0)
})

const topProvider = computed(() => {
  const list = summary.value?.confirmed_costs_by_provider
  if (!Array.isArray(list) || !list.length) return null
  return list[0]
})

const topProviderName = computed(() => topProvider.value?.provider ?? '—')
const topProviderAmount = computed(() => topProvider.value?.total_amount ?? 0)

const complianceBoxes = computed(() => {
  const vc = summary.value?.vehicle_compliance ?? {}
  const ins = vc.inspection ?? {}
  const insu = vc.insurance ?? {}
  const road = vc.road_fee ?? {}
  const maint = vc.maintenance ?? {}
  return [
    {
      key: 'insp',
      title: t('dashboard_analytics.compliance_inspection'),
      overdue: ins.overdue ?? 0,
      soon: ins.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'insu',
      title: t('dashboard_analytics.compliance_insurance'),
      overdue: insu.overdue ?? 0,
      soon: insu.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'road',
      title: t('dashboard_analytics.compliance_road_fee'),
      overdue: road.overdue ?? 0,
      soon: road.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'maint',
      title: t('dashboard_analytics.compliance_maintenance'),
      overdue: 0,
      soon: null,
      stale: maint.no_recent_service_180d ?? 0,
      staleLabel: t('dashboard_analytics.compliance_maint_stale'),
    },
  ]
})

const chartT = (key) => t(`dashboard_analytics.${key}`)

const optDonut = computed(() =>
  tripsStatusDonutOption({
    tripsByStatus: summary.value?.trips_by_status,
    labelMap: tripLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optFleet = computed(() =>
  fleetModeDonutOption({
    tripsByFleetMode: summary.value?.trips_by_fleet_mode,
    labelMap: fleetLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optTripType = computed(() =>
  tripsByTripTypeBarOption({
    tripsByTripType: summary.value?.trips_by_trip_type,
    labelMap: tripTypeLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optDispatchDonut = computed(() =>
  statusDonutOption({
    countsByStatus: summary.value?.dispatch_requests_by_status,
    labelMap: dispatchStatusLabelMap.value,
    emptyText: emptyChartLabel.value,
  }),
)

const optHourLine = computed(() => {
  const counts = normalizeTripsByHour(summary.value?.trips_by_hour)
  const base = tripsByHourLineOption({
    counts24: counts,
    t: chartT,
    emptyText: emptyChartLabel.value,
  })
  if (base.graphic) return base
  return {
    ...base,
    backgroundColor: 'transparent',
    xAxis: {
      ...base.xAxis,
      axisLabel: { ...base.xAxis.axisLabel, color: '#94a3b8' },
      axisLine: { lineStyle: { color: 'rgba(148,163,184,0.35)' } },
    },
    yAxis: {
      ...base.yAxis,
      nameTextStyle: { color: '#94a3b8', fontSize: 10 },
      axisLabel: { color: '#94a3b8', fontSize: 10 },
      splitLine: { lineStyle: { color: 'rgba(148,163,184,0.2)' } },
    },
    series: base.series.map((s) => ({
      ...s,
      lineStyle: { ...s.lineStyle, color: '#60a5fa' },
      itemStyle: { color: '#60a5fa' },
      label: s.label ? { ...s.label, color: '#94a3b8' } : s.label,
    })),
  }
})

const optCostBar = computed(() =>
  costsByTypeBarOption({
    costsByType: summary.value?.confirmed_costs_by_type,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optCostPipeline = computed(() =>
  costsPipelineBarOption({
    costsByPipelineStatus: summary.value?.costs_by_pipeline_status,
    labelMap: costPipelineLabelMap.value,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optProviders = computed(() =>
  providersHorizontalBarOption({
    rows: summary.value?.confirmed_costs_by_provider,
    formatMoney,
    emptyText: emptyChartLabel.value,
  }),
)

const optRequesters = computed(() =>
  topRequestersBarOption({
    rows: summary.value?.top_requesters,
    tripsSuffix: t('dashboard_analytics.tooltip_trips_unit'),
    emptyText: emptyChartLabel.value,
  }),
)

const optPlates = computed(() =>
  licensePlateTripsBarOption({
    tripsByPlate: summary.value?.trips_by_license_plate,
    plateSuffix: t('dashboard_analytics.tooltip_trips_unit'),
    emptyText: emptyChartLabel.value,
  }),
)

async function reloadSummary() {
  if (!rangeValid.value) return
  loading.value = true
  loadError.value = ''
  try {
    summary.value = await getSummary(summaryFilters.value)
    await loadRecentLists()
  } catch {
    loadError.value = t('dashboard_analytics.load_error')
  } finally {
    loading.value = false
  }
}

watch(
  dimensionFilterBarVisible,
  (vis) => {
    try {
      sessionStorage.setItem(DIMENSION_BAR_VISIBLE_KEY, JSON.stringify(vis))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function onWindowResize() {
  updateChartHeights()
  updateQuickScrollState()
}

onMounted(() => {
  loadDimensionBarVisibility()
  updateChartHeights()
  window.addEventListener('resize', onWindowResize)
  reloadSummary()
  nextTick(() => updateQuickScrollState())
})

onUnmounted(() => {
  window.removeEventListener('resize', onWindowResize)
})
</script>

<style scoped>
.dash-date-input {
  color-scheme: light dark;
}
.dash-date-input::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.55;
}
.dash-date-input:hover::-webkit-calendar-picker-indicator {
  opacity: 1;
}
.dash-quick-scroll {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.dash-quick-scroll::-webkit-scrollbar {
  display: none;
}
</style>
