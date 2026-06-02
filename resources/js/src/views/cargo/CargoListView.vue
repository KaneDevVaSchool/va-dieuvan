<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('cargo_page.hero_title') }}
        </h1>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          to="/dispatch-requests/new"
          class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.cta_new_request') }}
        </RouterLink>
        <RouterLink
          :to="{ path: '/requests', query: { trip_type: 'cargo' } }"
          class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
        >
          {{ t('cargo_page.link_cargo_requests') }}
        </RouterLink>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
      <div
        v-for="box in kpiBoxes"
        :key="box.key"
        class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:p-4"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="box.iconWrap">
            <component :is="box.icon" class="h-5 w-5" :class="box.iconClass" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white sm:text-2xl">
              {{ kpiLoading ? '…' : fmtInt(box.value) }}
            </div>
            <div class="mt-0.5 text-xs font-medium leading-snug text-slate-600 dark:text-slate-400">
              {{ box.label }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="relative z-40">
      <AppFilterBar>
        <div ref="cargoFilterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
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
              <p
                class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
              >
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
                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                    {{ t('trips_page.filter_show_controls_title') }}
                  </p>
                  <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                    <li v-for="fd in dimensionFilters" :key="'vis-' + fd.id" class="flex items-start gap-2">
                      <input
                        :id="'cargo-filter-vis-' + fd.id"
                        v-model="filterDropdownVisible[fd.id]"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                      />
                      <label
                        :for="'cargo-filter-vis-' + fd.id"
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
                <span class="max-w-[10rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ currentPresetLabel }}
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-[100] min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
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
                      @click="onApplyPreset(p.id, $event)"
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
                class="fixed inset-x-3 top-20 z-[200] max-h-[min(75vh,28rem)] w-auto overflow-y-auto overflow-x-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-b from-white via-white to-slate-50/95 shadow-2xl shadow-violet-500/20 ring-1 ring-slate-900/5 dark:border-violet-800/45 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/50 dark:ring-slate-950/50 sm:absolute sm:inset-x-auto sm:left-auto sm:right-0 sm:top-[calc(100%+8px)] sm:z-[100] sm:max-h-[min(70vh,32rem)] sm:w-[20.5rem] sm:shadow-xl"
              >
                <div
                  class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-indigo-50/40 px-3 py-2.5 dark:border-violet-900/40 dark:from-violet-950/40 dark:to-indigo-950/20"
                >
                  <div class="flex items-start gap-2">
                    <CalendarDaysIcon class="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                        {{ t('dashboard_analytics.date_range_title') }}
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
                      @click="onApplyQuickDateRange(chip.kind, $event)"
                    >
                      {{ chip.label }}
                    </button>
                  </div>
                  <div class="mt-3 space-y-3">
                    <div
                      class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                    >
                      <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="cargo-range-from">
                        {{ t('dashboard_analytics.range_from') }}
                      </label>
                      <input
                        id="cargo-range-from"
                        v-model="rangeFrom"
                        type="date"
                        :max="rangeTo || undefined"
                        class="mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        @change="onRangeFromChange"
                      />
                    </div>
                    <div class="flex items-center justify-center gap-2 px-1">
                      <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                      <span
                        class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-800 dark:bg-violet-950/80 dark:text-violet-200"
                      >
                        {{ rangeValid ? t('dashboard_analytics.date_range_span', { n: rangeDaySpan }) : '—' }}
                      </span>
                      <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                    </div>
                    <div
                      class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                    >
                      <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="cargo-range-to">
                        {{ t('dashboard_analytics.range_to') }}
                      </label>
                      <input
                        id="cargo-range-to"
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
                    :disabled="loading || !rangeValid"
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

            <template v-for="fd in visibleDimensionFilters" :key="fd.id">
              <details class="group relative min-w-0">
                <summary
                  class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
                >
                  <span class="max-w-[9rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100 sm:max-w-[10rem]">
                    {{ fd.summary }}
                  </span>
                  <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                </summary>
                <div
                  class="absolute left-0 top-[calc(100%+6px)] z-[100] max-h-[min(70vh,24rem)] min-w-[220px] overflow-y-auto rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950 max-sm:fixed max-sm:inset-x-3 max-sm:top-24 max-sm:z-[200] max-sm:max-h-[min(75vh,28rem)] max-sm:w-auto"
                >
                  <template v-if="fd.id === 'search'">
                    <div class="p-3">
                      <label class="text-xs font-medium text-slate-600 dark:text-slate-400" for="cargo-filter-q">{{ t('filter_bar.search') }}</label>
                      <input
                        id="cargo-filter-q"
                        v-model="searchInput"
                        type="search"
                        :placeholder="t('cargo_page.search_placeholder')"
                        class="mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        @keydown.enter.prevent="flushSearch"
                      />
                    </div>
                  </template>
                  <ul v-else class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                    <li v-for="opt in fd.options" :key="String(opt.value) + opt.label">
                      <button
                        type="button"
                        :class="[
                          'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                          fd.isSelected(opt.value)
                            ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                            : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                        ]"
                        @click="onDimensionPick(fd, opt.value, $event)"
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


    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
          <div class="flex flex-col gap-2 border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ t('cargo_page.list_title') }}</h2>
        
            </div>
          </div>
          <div v-if="loading" class="p-6 text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.loading') }}</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-[800px] w-full border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200/90 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400">
                  <th class="px-4 py-3">{{ t('cargo_page.col_code') }}</th>
                  <th class="px-4 py-3">{{ t('cargo_page.col_route') }}</th>
                  <th class="px-4 py-3">{{ t('cargo_page.col_status') }}</th>
                  <th class="px-4 py-3">{{ t('cargo_page.col_time') }}</th>
                  <th
                    class="min-w-[14rem] border-l border-teal-100/90 bg-gradient-to-br from-teal-50/90 via-white to-slate-50/50 px-3 py-3 text-teal-900 dark:border-teal-900/40 dark:from-teal-950/40 dark:via-slate-900/80 dark:to-slate-900/60 dark:text-teal-200"
                  >
                    <span class="flex items-center gap-1.5">
                      <span class="inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500 dark:bg-teal-400" aria-hidden="true" />
                      {{ t('cargo_page.col_links') }}
                    </span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="s in items"
                  :key="s.id"
                  class="border-b border-slate-100 transition hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/30"
                >
                  <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-200">
                    <RouterLink
                      :to="'/cargo/' + s.id"
                      class="font-semibold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
                    >
                      {{ s.tracking_code || '#' + s.id }}
                    </RouterLink>
                  </td>
                  <td class="max-w-[240px] px-4 py-3 text-slate-700 dark:text-slate-300">
                    <RouterLink :to="'/cargo/' + s.id" class="block hover:opacity-90">
                      <div class="truncate font-medium">{{ s.pickup_address || '—' }}</div>
                      <div class="truncate text-xs text-slate-500 dark:text-slate-400">→ {{ s.delivery_address || '—' }}</div>
                    </RouterLink>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="cargoStatusPillClass(s.status)">
                      {{ labelCargoStatus(s.status) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 tabular-nums text-xs text-slate-600 dark:text-slate-400">
                    {{ fmt(s.created_at) }}
                    <div v-if="s.sla_due_at" class="mt-0.5 text-[11px] text-slate-500">
                      {{ t('cargo_page.sla_prefix') }} {{ fmt(s.sla_due_at) }}
                    </div>
                  </td>
                  <td class="border-l border-slate-100/90 bg-slate-50/30 align-top px-3 py-3 dark:border-slate-800 dark:bg-slate-900/20">
                    <div class="flex min-w-[12.5rem] flex-col gap-1.5">
                      <RouterLink
                        :to="'/cargo/' + s.id"
                        class="group flex items-center gap-2 rounded-xl border border-teal-200/80 bg-white px-2.5 py-2 text-xs font-semibold text-teal-800 shadow-sm transition hover:border-teal-400 hover:bg-teal-50/90 hover:shadow dark:border-teal-800/60 dark:bg-slate-900/80 dark:text-teal-100 dark:hover:border-teal-600 dark:hover:bg-teal-950/50"
                      >
                        <CubeIcon class="h-4 w-4 shrink-0 text-teal-600 opacity-90 group-hover:opacity-100 dark:text-teal-400" aria-hidden="true" />
                        <span class="min-w-0 flex-1 leading-snug">{{ t('cargo_page.link_detail') }}</span>
                        <ArrowTopRightOnSquareIcon
                          class="h-3.5 w-3.5 shrink-0 text-teal-500/80 opacity-0 transition group-hover:opacity-100 dark:text-teal-400/90"
                          aria-hidden="true"
                        />
                      </RouterLink>
                      <RouterLink
                        v-if="dispatchRequestId(s)"
                        :to="'/requests/' + dispatchRequestId(s)"
                        class="group flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-2.5 py-2 text-xs font-medium text-slate-800 shadow-sm transition hover:border-violet-300 hover:bg-violet-50/80 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-violet-600 dark:hover:bg-violet-950/40"
                      >
                        <DocumentTextIcon class="h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                        <span class="min-w-0 flex-1 leading-snug">{{ t('cargo_page.link_request') }}</span>
                        <span
                          class="rounded-md bg-violet-100/90 px-1.5 py-px text-[10px] font-bold tabular-nums text-violet-800 dark:bg-violet-950/80 dark:text-violet-200"
                          >#{{ dispatchRequestId(s) }}</span
                        >
                      </RouterLink>
                      <RouterLink
                        v-if="s.trip_id"
                        :to="'/trips/' + s.trip_id"
                        class="group flex items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-2.5 py-2 text-xs font-medium text-slate-800 shadow-sm transition hover:border-sky-300 hover:bg-sky-50/80 dark:border-slate-600 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-sky-600 dark:hover:bg-sky-950/40"
                      >
                        <TruckIcon class="h-4 w-4 shrink-0 text-sky-600 dark:text-sky-400" aria-hidden="true" />
                        <span class="min-w-0 flex-1 leading-snug">{{ t('cargo_page.link_trip') }}</span>
                        <span
                          class="rounded-md bg-sky-100/90 px-1.5 py-px text-[10px] font-bold tabular-nums text-sky-900 dark:bg-sky-950/80 dark:text-sky-200"
                          >#{{ s.trip_id }}</span
                        >
                      </RouterLink>
                      <div
                        v-if="!dispatchRequestId(s) && !s.trip_id"
                        class="rounded-xl border border-dashed border-slate-200/90 bg-slate-50/50 px-2.5 py-2 text-center text-[11px] text-slate-500 dark:border-slate-600 dark:bg-slate-800/30 dark:text-slate-400"
                      >
                        {{ t('cargo_page.links_only_detail') }}
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="!items.length" class="p-8 text-center text-slate-500 dark:text-slate-400">{{ t('cargo_page.empty') }}</div>
          </div>
          <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/90 px-4 py-3 text-sm dark:border-slate-700">
            <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_page.total', { n: meta.total ?? 0 }) }}</span>
            <div class="flex gap-2">
              <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">
                {{ t('cargo_page.prev') }}
              </Button>
              <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
                {{ t('cargo_page.next') }}
              </Button>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ t('cargo_page.chart_fleet_title') }}</h3>
          <DashboardEChart class="mt-2" height="220px" :option="fleetChartOption" :aria-label="t('cargo_page.chart_fleet_title')" />
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ClockIcon,
  CubeIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  PlusIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import DashboardEChart from '../../components/dashboard/DashboardEChart.vue'
import { listCargoShipments } from '../../api/cargo'
import { useNotificationStore } from '../../store/notificationCenter'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { labelCargoStatus } from '../../util/labels'

const { t, locale } = useI18n()
const notifStore = useNotificationStore()

const loading = ref(false)
const kpiLoading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', from: '', to: '', q: '', page: 1, per_page: 20 })
const funnelDetailsRef = ref(null)
const cargoFilterBarRef = ref(null)
useDetailsAutoCloseWithin(cargoFilterBarRef)

const searchInput = ref('')
const searchDebounce = ref(null)

const CARGO_FILTER_ROW_IDS = ['status', 'search', 'per_page']
const CARGO_FILTER_VISIBILITY_KEY = 'cargo-list-filter-dropdowns'
const filterDropdownVisible = reactive(Object.fromEntries(CARGO_FILTER_ROW_IDS.map((id) => [id, true])))

function loadFilterDropdownVisibility() {
  try {
    const raw = localStorage.getItem(CARGO_FILTER_VISIBILITY_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    for (const id of CARGO_FILTER_ROW_IDS) {
      if (typeof o[id] === 'boolean') {
        filterDropdownVisible[id] = o[id]
      }
    }
  } catch {
    /* ignore */
  }
}

watch(
  () => CARGO_FILTER_ROW_IDS.map((id) => filterDropdownVisible[id]),
  () => {
    try {
      localStorage.setItem(CARGO_FILTER_VISIBILITY_KEY, JSON.stringify({ ...filterDropdownVisible }))
    } catch {
      /* ignore */
    }
  },
)

const CARGO_STATUS_VALUES = ['pending', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled']

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

function closeParentDetails(ev) {
  const el = ev?.currentTarget
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function onApplyPreset(id, ev) {
  applyPreset(id)
  closeParentDetails(ev)
}

function onApplyQuickDateRange(kind, ev) {
  applyQuickDateRange(kind)
  closeParentDetails(ev)
}

function onDimensionPick(fd, value, ev) {
  fd.pick(value)
  closeParentDetails(ev)
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

const statusOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...CARGO_STATUS_VALUES.map((s) => ({ value: s, label: labelCargoStatus(s) })),
])

const perPageOptions = computed(() => [
  { value: 10, label: '10' },
  { value: 20, label: '20' },
  { value: 50, label: '50' },
  { value: 100, label: '100' },
])

const dimensionFilters = computed(() => {
  const fa = t('filter_bar.all')
  return [
    {
      id: 'status',
      label: t('filter_bar.status'),
      summary: filters.status ? labelCargoStatus(filters.status) : fa,
      options: statusOptions.value,
      isSelected: (v) => (v === '' ? !filters.status : filters.status === v),
      pick: (v) => {
        filters.status = v || ''
        onFilterChange()
      },
    },
    {
      id: 'search',
      label: t('filter_bar.search'),
      summary: filters.q?.trim() ? filters.q.trim().slice(0, 18) + (filters.q.trim().length > 18 ? '…' : '') : fa,
      options: [],
      isSelected: () => false,
      pick: () => {},
    },
    {
      id: 'per_page',
      label: t('filter_bar.per_page'),
      summary: String(filters.per_page),
      options: perPageOptions.value.map((o) => ({ value: o.value, label: o.label })),
      isSelected: (v) => filters.per_page === v,
      pick: (v) => {
        filters.per_page = v
        onFilterChange()
      },
    },
  ]
})

const visibleDimensionFilters = computed(() => dimensionFilters.value.filter((fd) => filterDropdownVisible[fd.id] !== false))

const activeFilterLines = computed(() => {
  const rows = []
  if (filters.status) {
    rows.push({ label: t('filter_bar.status'), value: labelCargoStatus(filters.status) })
  }
  return rows
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.q?.trim()) n++
  if (filters.per_page !== 20) n++
  return n
})

const kpi = ref({ inTransit: 0, completed: 0, pending: 0, issues: 0 })

const kpiBoxes = computed(() => [
  {
    key: 'inTransit',
    label: t('cargo_page.kpi_in_transit'),
    value: kpi.value.inTransit,
    icon: TruckIcon,
    iconWrap: 'bg-sky-100 dark:bg-sky-950/50',
    iconClass: 'text-sky-600 dark:text-sky-400',
  },
  {
    key: 'completed',
    label: t('cargo_page.kpi_completed'),
    value: kpi.value.completed,
    icon: CheckCircleIcon,
    iconWrap: 'bg-emerald-100 dark:bg-emerald-950/50',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
  },
  {
    key: 'pending',
    label: t('cargo_page.kpi_pending'),
    value: kpi.value.pending,
    icon: ClockIcon,
    iconWrap: 'bg-amber-100 dark:bg-amber-950/50',
    iconClass: 'text-amber-600 dark:text-amber-400',
  },
  {
    key: 'issues',
    label: t('cargo_page.kpi_issues'),
    value: kpi.value.issues,
    icon: ExclamationTriangleIcon,
    iconWrap: 'bg-rose-100 dark:bg-rose-950/50',
    iconClass: 'text-rose-600 dark:text-rose-400',
  },
])

const chartLinePts = ref([0, 0, 0, 0, 0, 0, 0])

const chartLineLabels = computed(() => {
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const labels = []
  for (let i = 6; i >= 0; i--) {
    const d = subDays(new Date(), i)
    labels.push(d.toLocaleDateString(loc, { weekday: 'short' }))
  }
  return labels
})

const fleetChartOption = computed(() => ({
  grid: { left: 40, right: 12, top: 16, bottom: 22 },
  tooltip: { trigger: 'axis' },
  xAxis: {
    type: 'category',
    data: chartLineLabels.value,
    axisLabel: { fontSize: 10, color: '#64748b' },
  },
  yAxis: {
    type: 'value',
    splitLine: { lineStyle: { opacity: 0.2 } },
    axisLabel: { fontSize: 10, color: '#64748b' },
  },
  series: [
    {
      type: 'line',
      smooth: true,
      data: chartLinePts.value,
      areaStyle: { color: 'rgba(13,148,136,0.12)' },
      lineStyle: { color: '#0d9488', width: 2 },
      itemStyle: { color: '#0f766e' },
    },
  ],
}))

function fmtInt(n) {
  return new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n ?? 0)
}

function kpiBaseParams() {
  const p = {
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q?.trim() || undefined,
    per_page: 1,
    page: 1,
  }
  Object.keys(p).forEach((k) => {
    if (p[k] === '' || p[k] === undefined || p[k] === null) delete p[k]
  })
  return p
}

function listParams() {
  const p = {
    status: filters.status || undefined,
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q?.trim() || undefined,
    page: filters.page,
    per_page: filters.per_page,
  }
  Object.keys(p).forEach((k) => {
    if (p[k] === '' || p[k] === undefined || p[k] === null) delete p[k]
  })
  return p
}

async function reloadKpis() {
  if (!rangeValid.value) return
  kpiLoading.value = true
  try {
    const base = kpiBaseParams()
    const [r1, r2, rDone, rPend, rFail, rCanc] = await Promise.all([
      listCargoShipments({ ...base, status: 'in_transit' }),
      listCargoShipments({ ...base, status: 'picked_up' }),
      listCargoShipments({ ...base, status: 'delivered' }),
      listCargoShipments({ ...base, status: 'pending' }),
      listCargoShipments({ ...base, status: 'failed' }),
      listCargoShipments({ ...base, status: 'cancelled' }),
    ])
    kpi.value.inTransit = (r1.meta?.total ?? 0) + (r2.meta?.total ?? 0)
    kpi.value.completed = rDone.meta?.total ?? 0
    kpi.value.pending = rPend.meta?.total ?? 0
    kpi.value.issues = (rFail.meta?.total ?? 0) + (rCanc.meta?.total ?? 0)
  } catch {
    kpi.value = { inTransit: 0, completed: 0, pending: 0, issues: 0 }
  } finally {
    kpiLoading.value = false
  }
}

async function loadChartSeries() {
  const days = Array.from({ length: 7 }, (_, i) => ymd(subDays(new Date(), 6 - i)))
  try {
    const results = await Promise.all(
      days.map((day) => listCargoShipments({ from: day, to: day, status: 'delivered', per_page: 1 })),
    )
    chartLinePts.value = results.map((r) => r.meta?.total ?? 0)
  } catch {
    chartLinePts.value = [0, 0, 0, 0, 0, 0, 0]
  }
}

function fmt(v) {
  return v ? new Date(v).toLocaleString(locale.value === 'en' ? 'en-GB' : 'vi-VN') : ''
}

function cargoStatusPillClass(st) {
  const map = {
    pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    picked_up: 'bg-sky-100 text-sky-900 dark:bg-sky-950/40 dark:text-sky-100',
    in_transit: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    delivered: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    failed: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
    cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
  }
  return map[st] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
}

function dispatchRequestId(s) {
  return s.dispatch_request_id ?? s.dispatch_request?.id ?? null
}

function onFilterChange() {
  if (!rangeValid.value) return
  filters.page = 1
  reloadKpis()
  loadChartSeries()
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.q = ''
  searchInput.value = ''
  filters.per_page = 20
  filters.page = 1
  preset.value = 'month'
  syncRangeForPreset('month')
  syncFiltersFromRange()
  if (funnelDetailsRef.value) funnelDetailsRef.value.open = false
  reloadKpis()
  loadChartSeries()
  reload()
}

async function reload() {
  if (!rangeValid.value) return
  loading.value = true
  try {
    const res = await listCargoShipments(listParams())
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    void notifStore.refreshBadges()
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
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

onMounted(() => {
  loadFilterDropdownVisibility()
  syncRangeForPreset('month')
  syncFiltersFromRange()
  searchInput.value = filters.q
  reloadKpis()
  loadChartSeries()
  reload()
})
</script>
