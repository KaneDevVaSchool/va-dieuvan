<template>
  <div class="cv-page min-h-0 min-w-0 pb-12 text-slate-900 dark:text-slate-100">

    <!-- Header -->
    <div class="mb-5 flex items-start justify-between gap-3">
      <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
        {{ t('costs_page.hero_title') }}
      </h1>
    </div>

    <!-- ── Tab bar (pill) ──────────────────────────────────────── -->
    <div class="cv-tab-bar" role="tablist" :aria-label="t('costs_page.hero_title')">
      <button
        role="tab"
        :aria-selected="activeTab === 'all_trips'"
        class="cv-tab"
        :class="activeTab === 'all_trips' ? 'cv-tab--active' : ''"
        data-testid="tab-all-trips"
        @click="activeTab = 'all_trips'"
      >
        {{ t('costs_page.tab_all_trips') }}
        <span v-if="activeTab === 'all_trips' && meta.total != null" class="cv-tab-badge">{{ meta.total }}</span>
      </button>
      <button
        role="tab"
        :aria-selected="activeTab === 'standalone'"
        class="cv-tab"
        :class="activeTab === 'standalone' ? 'cv-tab--active' : ''"
        data-testid="tab-standalone"
        @click="activeTab = 'standalone'"
      >
        {{ t('costs_page.tab_standalone') }}
        <span v-if="activeTab === 'standalone' && meta.total != null" class="cv-tab-badge">{{ meta.total }}</span>
      </button>
      <button
        role="tab"
        :aria-selected="activeTab === 'business_personnel'"
        class="cv-tab"
        :class="activeTab === 'business_personnel' ? 'cv-tab--active' : ''"
        data-testid="tab-bp"
        @click="activeTab = 'business_personnel'"
      >
        {{ t('costs_page.tab_business_personnel') }}
        <span v-if="activeTab === 'business_personnel' && bpMeta.total > 0" class="cv-tab-badge">{{ bpMeta.total }}</span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB: all_trips / standalone
    ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'all_trips' || activeTab === 'standalone'">

      <!-- ── Filter strip row 1 ──────────────────────────────── -->
      <div class="cv-filter-strip mt-4">
        <!-- Status chips -->
        <div class="flex flex-wrap items-center gap-1.5" role="group" :aria-label="t('filter_bar.status')">
          <button
            v-for="opt in statusChipOptions"
            :key="opt.value === '' ? '_all' : opt.value"
            type="button"
            class="cv-status-chip"
            :class="filters.status === opt.value ? 'cv-status-chip--active' : ''"
            @click="setStatusFilter(opt.value)"
          >
            {{ opt.label }}
          </button>
        </div>

        <div class="hidden h-6 w-px bg-slate-200 dark:bg-slate-700 sm:block" aria-hidden="true" />

        <!-- Date range -->
        <div class="flex items-center gap-1">
          <input
            v-model="filters.from"
            type="date"
            class="cv-date-input"
            :aria-label="t('costs_page.filter_recorded_date')"
            @change="onFilterDateChange"
          />
          <span class="text-xs text-slate-400">→</span>
          <input
            v-model="filters.to"
            type="date"
            class="cv-date-input"
            :aria-label="t('costs_page.filter_recorded_date')"
            @change="onFilterDateChange"
          />
        </div>

        <!-- Search -->
        <input
          v-model="searchQ"
          type="search"
          class="cv-search-input"
          :placeholder="t('costs_page.filter_search_page')"
          :aria-label="t('costs_page.filter_search_page')"
        />

        <!-- More filters toggle -->
        <button
          type="button"
          class="cv-more-btn"
          :class="showSecondaryFilters ? 'cv-more-btn--active' : ''"
          @click="showSecondaryFilters = !showSecondaryFilters"
        >
          {{ t('costs_page.filter_more') }}
          <span v-if="secondaryActiveFilterCount > 0" class="cv-more-badge">{{ secondaryActiveFilterCount }}</span>
          <ChevronDownIcon
            class="h-3.5 w-3.5 shrink-0 transition-transform"
            :class="showSecondaryFilters ? 'rotate-180' : ''"
            aria-hidden="true"
          />
        </button>

        <!-- Right: clear + add -->
        <div class="ml-auto flex shrink-0 items-center gap-2">
          <button
            v-if="activeFilterCount > 0"
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            @click="resetFilters"
          >
            <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
            {{ t('costs_page.filter_clear') }}
          </button>
          <button
            v-if="showAddCostButton"
            type="button"
            class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg bg-va-800 px-3.5 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35 dark:ring-white/10"
            data-testid="costs-add-btn"
            @click="openAddCostModal"
          >
            <PlusCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ activeTab === 'standalone' ? t('costs_page.add_standalone_cost') : t('costs_page.add_cost') }}
          </button>
        </div>
      </div>

      <!-- ── Filter strip row 2: secondary (collapsible) ───────── -->
      <div v-show="showSecondaryFilters" class="cv-secondary-filters mt-2">
        <!-- Type -->
        <select
          v-model="filters.type"
          class="cv-select"
          :aria-label="t('costs_page.filter_cost_type')"
          @change="onSimpleFilterSelectChange"
        >
          <option v-for="opt in typeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <!-- Trip type (not standalone) -->
        <select
          v-if="activeTab !== 'standalone'"
          v-model="filters.trip_type"
          class="cv-select"
          :aria-label="t('costs_page.filter_trip_type')"
          @change="onSimpleFilterSelectChange"
        >
          <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <!-- Trip picker (not standalone) -->
        <details
          v-if="activeTab !== 'standalone'"
          ref="filterTripDropdownRef"
          class="cv-dropdown-details group relative shrink-0"
        >
          <summary
            class="cv-dropdown-trigger"
            :class="filters.trip_id ? 'cv-dropdown-trigger--active' : ''"
          >
            <span class="max-w-[11rem] truncate">{{ filters.trip_id ? tripFilterSummaryShort : t('costs_page.filter_trip') }}</span>
            <ChevronDownIcon class="ml-1 h-3.5 w-3.5 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" />
          </summary>
          <div class="cv-dropdown-panel">
            <input
              v-model="filterTripSearch"
              type="search"
              class="costs-input mb-2 h-8 w-full text-sm"
              :placeholder="t('costs_page.trip_search_ph')"
              autocomplete="off"
              @click.stop
            />
            <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-0.5">
              <li>
                <button
                  type="button"
                  class="cv-dropdown-item"
                  :class="!filters.trip_id ? 'cv-dropdown-item--active' : ''"
                  @click="applyFilterPatch($event, { trip_id: '' })"
                >
                  {{ t('costs_page.trip_all') }}
                </button>
              </li>
              <li v-for="tripRow in filteredTripsForFilter" :key="tripRow.id">
                <button
                  type="button"
                  class="cv-dropdown-item"
                  :class="String(filters.trip_id) === String(tripRow.id) ? 'cv-dropdown-item--active' : ''"
                  @click="applyFilterPatch($event, { trip_id: String(tripRow.id) })"
                >
                  {{ formatTripPickerLabel(tripRow) }}
                </button>
              </li>
            </ul>
            <p v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForFilter.length" class="mt-2 text-[11px] text-amber-800 dark:text-amber-200">{{ t('costs_page.trip_no_match') }}</p>
            <p v-else-if="tripsForModalLoading" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ t('costs_page.trip_loading') }}</p>
            <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ t('costs_page.trip_empty_scope') }}</p>
          </div>
        </details>

        <!-- Provider (not standalone) -->
        <select
          v-if="activeTab !== 'standalone'"
          v-model="filters.provider"
          class="cv-select"
          :aria-label="t('costs_page.filter_provider')"
          @change="onSimpleFilterSelectChange"
        >
          <option v-for="opt in providerFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <!-- Amount range -->
        <div class="flex items-center gap-1">
          <input
            :value="formatVndDigitsInput(String(filters.amount_min).replace(/\D/g, ''))"
            type="text"
            inputmode="numeric"
            class="cv-amount-input"
            :placeholder="t('costs_page.filter_amount_min')"
            autocomplete="off"
            @input="onAmountFilterInput('amount_min', $event)"
          />
          <span class="text-xs text-slate-400">–</span>
          <input
            :value="formatVndDigitsInput(String(filters.amount_max).replace(/\D/g, ''))"
            type="text"
            inputmode="numeric"
            class="cv-amount-input"
            :placeholder="t('costs_page.filter_amount_max')"
            autocomplete="off"
            @input="onAmountFilterInput('amount_max', $event)"
          />
        </div>

        <!-- Fleet mode (not standalone) -->
        <select
          v-if="activeTab !== 'standalone'"
          v-model="filters.fleet_mode"
          class="cv-select"
          :aria-label="t('dashboard_analytics.filter_fleet')"
          @change="onSimpleFilterSelectChange"
        >
          <option v-for="opt in fleetModeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <button
          type="button"
          class="ml-auto inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
          @click="resetSecondaryFilters"
        >
          <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
          {{ t('costs_page.filter_clear_secondary') }}
        </button>
      </div>

      <!-- ── KPI summary bar ─────────────────────────────────── -->
      <div v-if="!tableBusy || items.length > 0" class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div class="cv-kpi-card">
          <span class="cv-kpi-num">{{ meta.total ?? 0 }}</span>
          <span class="cv-kpi-label">{{ t('costs_page.kpi_total') }}</span>
          <span class="cv-kpi-hint">{{ t('costs_page.kpi_total_hint') }}</span>
        </div>
        <div class="cv-kpi-card cv-kpi-card--confirmed">
          <span class="cv-kpi-num">{{ kpiSummary.confirmed.count }}</span>
          <span class="cv-kpi-label">{{ t('costs_page.kpi_confirmed') }}</span>
          <span class="cv-kpi-amount">{{ formatVnd(kpiSummary.confirmed.amount) }}</span>
        </div>
        <div class="cv-kpi-card cv-kpi-card--pending">
          <span class="cv-kpi-num">{{ kpiSummary.pending.count }}</span>
          <span class="cv-kpi-label">{{ t('costs_page.kpi_submitted') }}</span>
          <span class="cv-kpi-amount">{{ formatVnd(kpiSummary.pending.amount) }}</span>
        </div>
        <div class="cv-kpi-card cv-kpi-card--rejected">
          <span class="cv-kpi-num">{{ kpiSummary.rejected.count }}</span>
          <span class="cv-kpi-label">{{ t('costs_page.kpi_rejected') }}</span>
          <span class="cv-kpi-amount">{{ formatVnd(kpiSummary.rejected.amount) }}</span>
        </div>
      </div>

      <!-- ── Table card ──────────────────────────────────────── -->
      <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <!-- Table header -->
        <div class="flex items-center justify-between border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
          <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ activeTab === 'standalone' ? t('costs_page.table_title_standalone') : t('costs_page.table_title') }}
            </h2>
            <p v-if="activeTab === 'standalone'" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
              {{ t('costs_page.standalone_intro') }}
            </p>
          </div>
          <span
            v-if="tableBusy"
            class="inline-block size-4 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"
            aria-hidden="true"
          />
        </div>

        <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
          <table class="cv-table min-w-[760px] w-full">
            <thead>
              <tr>
                <th class="cv-th w-10 text-center">{{ t('costs_page.col_no') }}</th>
                <th class="cv-th min-w-[16rem]">{{ t('costs_page.col_description') }}</th>
                <th class="cv-th min-w-[9rem]">{{ t('costs_page.col_trip') }}</th>
                <th class="cv-th min-w-[8rem]">{{ t('costs_page.col_provider') }}</th>
                <th class="cv-th min-w-[8rem] text-right">{{ t('costs_page.col_payment') }}</th>
                <th class="cv-th min-w-[9.5rem]">{{ t('costs_page.col_status_info') }}</th>
                <th v-if="canReconcileCosts" class="cv-th w-12 text-right">{{ t('costs_page.col_actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(c, idx) in displayedItems"
                :key="costRowKey(c)"
                class="cv-row"
                :class="[
                  idx % 2 === 1 ? 'cv-row--alt' : '',
                  isWizardEstimateLine(c) ? 'cv-row--estimate' : 'cv-row--clickable',
                ]"
                :role="isWizardEstimateLine(c) ? undefined : 'button'"
                :tabindex="isWizardEstimateLine(c) ? undefined : 0"
                :data-testid="isWizardEstimateLine(c) ? undefined : `cost-row-${c.id}`"
                :aria-label="isWizardEstimateLine(c) ? undefined : t('costs_page.open_cost_row', { id: c.id })"
                @click="onCostRowActivate(c)"
                @keydown.enter.prevent="onCostRowActivate(c)"
                @keydown.space.prevent="onCostRowActivate(c)"
              >
                <!-- # STT -->
                <td class="cv-td text-center tabular-nums text-slate-400 dark:text-slate-500">{{ rowIndex(idx) }}</td>

                <!-- # Nội dung -->
                <td class="cv-td">
                  <p
                    class="line-clamp-2 text-sm font-medium text-slate-900 dark:text-slate-100"
                    :title="c.description || ''"
                  >
                    {{ c.description || '—' }}
                  </p>
                  <p
                    v-if="isWizardEstimateLine(c)"
                    class="mt-0.5 text-xs font-medium text-violet-700 dark:text-violet-400"
                  >
                    {{ estimateKindLabel(c) }}
                  </p>
                  <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span
                      class="cv-type-badge"
                      :class="isWizardEstimateLine(c) ? 'cv-type-badge--estimate' : ''"
                    >
                      {{ isWizardEstimateLine(c) ? estimateKindLabel(c) : typeLabel(c.type) }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ costSubmitterLabel(c) }}</span>
                    <span
                      v-if="costEvidenceCount(c) > 0"
                      class="inline-flex items-center rounded-full bg-teal-50 px-1.5 py-0.5 text-[11px] font-medium text-teal-700 ring-1 ring-teal-200/70 dark:bg-teal-950/40 dark:text-teal-300 dark:ring-teal-800/50"
                    >
                      {{ t('costs_page.detail_evidence_count', { count: costEvidenceCount(c) }) }}
                    </span>
                  </div>
                </td>

                <!-- # Chuyến -->
                <td class="cv-td" @click.stop>
                  <RouterLink
                    v-if="c.trip_id"
                    class="font-mono text-sm font-bold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
                    :to="`/trips/${c.trip_id}`"
                  >{{ costTripCode(c) }}</RouterLink>
                  <span
                    v-else-if="!isWizardEstimateLine(c)"
                    class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200/60 dark:bg-amber-950/30 dark:text-amber-400 dark:ring-amber-800/40"
                  >
                    {{ t('costs_page.badge_standalone') }}
                  </span>
                  <span v-else class="text-slate-400">—</span>
                  <div v-if="tripTypeFromCost(c) || tripFleetModeFromCost(c) !== 'unspecified'" class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                    <span
                      v-if="tripTypeFromCost(c)"
                      class="text-xs font-medium"
                      :class="tripTypeTextClass(tripTypeFromCost(c))"
                    >{{ labelTripType(tripTypeFromCost(c)) }}</span>
                    <span
                      v-if="tripFleetModeFromCost(c) !== 'unspecified'"
                      class="text-xs text-slate-400 dark:text-slate-500"
                    >{{ fleetModeLabel(tripFleetModeFromCost(c)) }}</span>
                  </div>
                </td>

                <!-- # NCC / Đơn vị -->
                <td class="cv-td">
                  <span
                    v-if="costProviderName(c)"
                    class="text-sm text-slate-700 dark:text-slate-300"
                  >{{ costProviderName(c) }}</span>
                  <span v-else class="text-sm text-slate-400 dark:text-slate-500">{{ unitLabel }}</span>
                </td>

                <!-- # Số tiền -->
                <td class="cv-td text-right">
                  <span class="text-sm font-bold tabular-nums text-slate-900 dark:text-slate-100">
                    {{ formatVnd(c.amount) }}
                  </span>
                  <div
                    v-if="isWizardEstimateLine(c) && (Number(c.unit_price) > 0 || Number(c.extra_fee) > 0)"
                    class="mt-0.5 space-y-0.5 text-right"
                  >
                    <span v-if="Number(c.unit_price) > 0" class="block text-xs text-slate-400 tabular-nums">
                      {{ t('costs_page.note_unit_price_short') }} {{ formatVnd(c.unit_price) }}
                    </span>
                    <span v-if="Number(c.extra_fee) > 0" class="block text-xs text-slate-400 tabular-nums">
                      {{ t('costs_page.note_extra_fee_short') }} {{ formatVnd(c.extra_fee) }}
                    </span>
                  </div>
                </td>

                <!-- # Trạng thái -->
                <td class="cv-td">
                  <span
                    class="cv-status-badge"
                    :class="costStatusBadgeClass(c.status)"
                  >
                    {{ isWizardEstimateLine(c) ? estimateKindLabel(c) : statusLabel(c.status) }}
                  </span>
                  <p class="mt-1 text-xs tabular-nums text-slate-500 dark:text-slate-400">{{ formatDateDMY(c.created_at) }}</p>
                  <p v-if="c.confirmer?.name" class="text-xs text-slate-500 dark:text-slate-400">{{ c.confirmer.name }}</p>
                  <p
                    v-if="c.rejection_reason"
                    class="mt-0.5 line-clamp-1 text-xs text-rose-700 dark:text-rose-400"
                    :title="c.rejection_reason"
                  >{{ c.rejection_reason }}</p>
                </td>

                <!-- # Thao tác -->
                <td v-if="canReconcileCosts" class="cv-td text-right" @click.stop>
                  <AppRowActionsMenu
                    v-if="!isWizardEstimateLine(c)"
                    align="end"
                    root-class="text-right"
                    :aria-label="t('costs_page.col_actions')"
                    :trigger-sr-only="t('costs_page.col_actions')"
                    :disabled="decidingId != null || deletingCostId != null"
                  >
                    <button
                      type="button"
                      role="menuitem"
                      class="costs-menu-item text-teal-800 hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
                      data-testid="cost-row-detail-menu"
                      @click="openCostDetail(c)"
                    >
                      {{ t('costs_page.action_view_detail') }}
                    </button>
                    <div class="my-1 border-t border-slate-100 dark:border-slate-700" role="separator" />
                    <button
                      v-if="isCostPendingDecision(c)"
                      type="button"
                      role="menuitem"
                      class="costs-menu-item text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                      @click="quickApproveCost(c)"
                    >
                      {{ t('costs_page.action_approve') }}
                    </button>
                    <button
                      v-if="isCostPendingDecision(c)"
                      type="button"
                      role="menuitem"
                      class="costs-menu-item text-rose-800 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                      @click="quickRejectCost(c)"
                    >
                      {{ t('costs_page.action_reject') }}
                    </button>
                    <div
                      v-if="isCostPendingDecision(c)"
                      class="my-1 border-t border-slate-100 dark:border-slate-700"
                      role="separator"
                    />
                    <button
                      type="button"
                      role="menuitem"
                      class="costs-menu-item text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                      @click="openDeleteModal(c)"
                    >
                      {{ t('costs_page.action_delete') }}
                    </button>
                  </AppRowActionsMenu>
                  <span v-else class="text-[11px] text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!tableBusy && !hasTableRows" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ t('costs_page.empty_table') }}
          </div>
          <div v-if="tableBusy && !hasTableRows" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
            <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
            {{ t('costs_page.loading_table') }}
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col gap-3 border-t border-slate-200/90 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50">
          <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-600 dark:text-slate-400">
            <span>
              {{ t('costs_page.pagination_of', { current: meta.current_page ?? 1, last: meta.last_page ?? 1 }) }}
              <span class="text-slate-400"> · </span>
              {{ meta.total ?? 0 }} {{ t('costs_page.pagination_records_suffix') }}
            </span>
            <label class="inline-flex items-center gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.pagination_per_page_label') }}</span>
              <select
                v-model.number="filters.per_page"
                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-800 outline-none ring-teal-500/30 focus:ring-2 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                :aria-label="t('costs_page.pagination_per_page_aria')"
                data-testid="costs-pagination-per-page"
                @change="onPerPageChange"
              >
                <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <span class="text-slate-500 dark:text-slate-400">{{ t('costs_page.per_page_unit') }}</span>
            </label>
          </div>
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

    <!-- ══════════════════════════════════════════════════════════
         TAB: business_personnel
    ═══════════════════════════════════════════════════════════════ -->
    <section v-else aria-labelledby="costs-section-business-personnel">

      <!-- Filter bar (always visible) -->
      <div class="cv-filter-strip mt-4">
        <div class="flex items-center gap-1">
          <input
            v-model="bpFilters.from"
            type="date"
            class="cv-date-input"
            :aria-label="t('costs_page.filter_recorded_date')"
            @change="onBpFilterDropdownChange"
          />
          <span class="text-xs text-slate-400">→</span>
          <input
            v-model="bpFilters.to"
            type="date"
            class="cv-date-input"
            :aria-label="t('costs_page.filter_recorded_date')"
            @change="onBpFilterDropdownChange"
          />
        </div>

        <!-- BP trip picker -->
        <details ref="bpFilterTripDropdownRef" class="cv-dropdown-details group relative shrink-0">
          <summary
            class="cv-dropdown-trigger"
            :class="bpFilters.trip_id ? 'cv-dropdown-trigger--active' : ''"
          >
            <span class="max-w-[11rem] truncate">{{ bpFilters.trip_id ? bpTripFilterSummaryShort : t('costs_page.filter_trip') }}</span>
            <ChevronDownIcon class="ml-1 h-3.5 w-3.5 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" />
          </summary>
          <div class="cv-dropdown-panel">
            <input
              v-model="bpFilterTripSearch"
              type="search"
              class="costs-input mb-2 h-8 w-full text-sm"
              :placeholder="t('costs_page.trip_search_ph')"
              autocomplete="off"
              @click.stop
            />
            <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-0.5">
              <li>
                <button
                  type="button"
                  class="cv-dropdown-item"
                  :class="!bpFilters.trip_id ? 'cv-dropdown-item--active' : ''"
                  @click="applyBpTripFilter($event, '')"
                >
                  {{ t('costs_page.trip_all') }}
                </button>
              </li>
              <li v-for="tripRow in filteredTripsForBpFilter" :key="tripRow.id">
                <button
                  type="button"
                  class="cv-dropdown-item"
                  :class="String(bpFilters.trip_id) === String(tripRow.id) ? 'cv-dropdown-item--active' : ''"
                  @click="applyBpTripFilter($event, String(tripRow.id))"
                >
                  {{ formatTripPickerLabel(tripRow) }}
                </button>
              </li>
            </ul>
            <p v-if="!tripsForModalLoading && tripOptionsRaw.length && !filteredTripsForBpFilter.length" class="mt-2 text-[11px] text-amber-800 dark:text-amber-200">{{ t('costs_page.trip_no_match') }}</p>
            <p v-else-if="tripsForModalLoading" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ t('costs_page.trip_loading') }}</p>
            <p v-else-if="!tripsForModalLoading && !tripOptionsRaw.length" class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ t('costs_page.trip_empty_scope') }}</p>
          </div>
        </details>

        <div class="ml-auto">
          <button
            v-if="bpActiveFilterCount > 0"
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
            @click="resetBpFilters"
          >
            <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
            {{ t('costs_page.filter_clear') }}
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
          <h2
            id="costs-section-business-personnel"
            class="text-sm font-semibold text-slate-900 dark:text-slate-100"
          >
            {{ t('costs_page.section_business_personnel') }}
          </h2>
        </div>
        <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
          <table class="cv-table min-w-[700px] w-full">
            <thead>
              <tr>
                <th class="cv-th w-10 text-center">{{ t('costs_page.col_no') }}</th>
                <th class="cv-th min-w-[5rem]">{{ t('costs_page.col_trip') }}</th>
                <th class="cv-th min-w-[8rem]">{{ t('costs_page.col_personnel') }}</th>
                <th class="cv-th min-w-[12rem]">{{ t('costs_page.col_route') }}</th>
                <th class="cv-th min-w-[6.5rem] text-right">{{ t('costs_page.col_unit_price') }}</th>
                <th class="cv-th min-w-[6rem] text-right">{{ t('costs_page.col_extra_fee') }}</th>
                <th class="cv-th min-w-[7rem] text-right">{{ t('costs_page.col_payment') }}</th>
                <th class="cv-th min-w-[7rem]">{{ t('costs_page.col_submitter') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in bpLines"
                :key="`${row.trip_id}-${row.line_no}`"
                class="cv-row cv-row--clickable"
                :class="idx % 2 === 1 ? 'cv-row--alt' : ''"
                role="button"
                :tabindex="0"
                :data-testid="`bp-row-${row.trip_id}`"
                @click="router.push({ name: 'tripDetail', params: { id: row.trip_id } })"
                @keydown.enter.prevent="router.push({ name: 'tripDetail', params: { id: row.trip_id } })"
              >
                <td class="cv-td text-center tabular-nums text-slate-400 dark:text-slate-500">{{ idx + 1 }}</td>
                <td class="cv-td" @click.stop>
                  <RouterLink
                    :to="{ name: 'tripDetail', params: { id: row.trip_id } }"
                    class="font-mono text-sm font-bold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
                  >
                    {{ formatTripCode(row.trip_id) }}
                  </RouterLink>
                </td>
                <td class="cv-td">
                  <span class="line-clamp-2">{{ row.personnel_label || '—' }}</span>
                  <span v-if="row.guests" class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">{{ row.guests }}</span>
                </td>
                <td class="cv-td text-slate-700 dark:text-slate-300">
                  <span v-if="row.pickup || row.dropoff">{{ row.pickup || '…' }} → {{ row.dropoff || '…' }}</span>
                  <span v-else-if="row.request_origin || row.request_destination">
                    {{ row.request_origin || '…' }} → {{ row.request_destination || '…' }}
                  </span>
                  <span v-else>—</span>
                </td>
                <td class="cv-td text-right tabular-nums">{{ formatVnd(row.unit_price) }}</td>
                <td class="cv-td text-right tabular-nums">{{ formatVnd(row.extra_fee) }}</td>
                <td class="cv-td text-right font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatVnd(row.amount_total) }}</td>
                <td class="cv-td">{{ row.requester_name || '—' }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="bpLoading && !bpLines.length" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
            <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
            {{ t('costs_page.business_personnel_loading') }}
          </div>
          <div v-else-if="!bpLoading && !bpLines.length" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ t('costs_page.business_personnel_empty') }}
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         MODALS (unchanged)
    ═══════════════════════════════════════════════════════════════ -->

    <!-- Add cost modal -->
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
                  <option v-for="tr in filteredTripsForPicker" :key="tr.id" :value="String(tr.id)">
                    {{ formatTripPickerLabel(tr) }}
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

    <!-- Reject modal -->
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h2 id="costs-reject-title" class="text-base font-semibold text-rose-950 dark:text-rose-100">
                {{ t('costs_page.reject_modal_title') }}
              </h2>
              <p
                class="mt-2 rounded-lg border border-rose-200/80 bg-white/80 px-3 py-2 text-xs font-medium text-rose-900 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-100"
              >
                {{ t('costs_page.reject_modal_summary', { id: rejectTarget.id, trip: costTripCode(rejectTarget) }) }}
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

    <!-- Delete modal -->
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
                {{ t('costs_page.delete_modal_summary', { id: deleteTarget.id, trip: costTripCode(deleteTarget) }) }}
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

    <!-- Quick add cost type modal -->
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

    <!-- Cost detail modal -->
    <StaffCostDetailModal
      :open="detailModalOpen"
      :cost-id="detailCostId"
      :type-label-fn="typeLabel"
      @close="closeCostDetail"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, PlusCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'
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
import { formatTripCode, formatVnd, formatVndDigitsInput, labelTripType } from '../../util/labels'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import StaffCostDetailModal from '../../components/costs/StaffCostDetailModal.vue'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'

const { t, te, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

/** @type {import('vue').Ref<'all_trips' | 'standalone' | 'business_personnel'>} */
const activeTab = ref('all_trips')

const COSTS_PER_PAGE_OPTIONS = [5, 10, 15, 20]
const DEFAULT_PER_PAGE = 10

const unitLabel = computed(() => t('costs_page.unit_label_const'))

const BUILTIN_COST_TYPES = ['fuel', 'toll', 'parking', 'other']
const EXTRA_TYPES_STORAGE_KEY = 'va.costs.extra_types_v1'

const extraCostTypes = ref([])

function typeLabel(slug) {
  if (!slug) return '—'
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  const hit = extraCostTypes.value.find((x) => x.slug === slug)
  return hit?.label ?? slug
}

// ── Detail modal ─────────────────────────────────────────────────
const detailModalOpen = ref(false)
/** @type {import('vue').Ref<number | null>} */
const detailCostId = ref(null)

function openCostDetail(c) {
  if (!c?.id || isWizardEstimateLine(c)) return
  detailCostId.value = Number(c.id)
  detailModalOpen.value = true
}

function closeCostDetail() {
  detailModalOpen.value = false
  detailCostId.value = null
}

function onCostRowActivate(c) {
  openCostDetail(c)
}

// ── Evidence helpers ─────────────────────────────────────────────
function costEvidenceCount(c) {
  if (isWizardEstimateLine(c)) return 0
  let n = Number(c.attachments_count) || 0
  if (c.receipt_url) n += 1
  return n
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

const TRIP_TYPE_TEXT_CLASSES = {
  point_to_point: 'text-sky-700 dark:text-sky-400',
  cargo: 'text-amber-700 dark:text-amber-400',
  business: 'text-violet-700 dark:text-violet-400',
  door_to_door: 'text-teal-700 dark:text-teal-400',
}

function tripTypeTextClass(slug) {
  return TRIP_TYPE_TEXT_CLASSES[slug] ?? 'text-slate-700 dark:text-slate-300'
}

function costStatusBadgeClass(status) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed') return 'cv-status-badge--confirmed'
  if (s === 'rejected') return 'cv-status-badge--rejected'
  if (s === 'submitted' || s === 'draft') return 'cv-status-badge--pending'
  if (s === 'estimate') return 'cv-status-badge--estimate'
  return 'cv-status-badge--default'
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

// ── Data refs ────────────────────────────────────────────────────
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
const filterTripSearch = ref('')

// Dropdown refs for auto-close
const filterTripDropdownRef = ref(null)
const bpFilterTripDropdownRef = ref(null)
useDetailsAutoClose(filterTripDropdownRef)
useDetailsAutoClose(bpFilterTripDropdownRef)

// Secondary filters collapse state
const showSecondaryFilters = ref(false)

// ── Decide / reject / delete ─────────────────────────────────────
const decidingId = ref(null)
const rejectModalOpen = ref(false)
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const rejectTarget = ref(null)
const rejectReasonInput = ref('')

const deleteModalOpen = ref(false)
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const deleteTarget = ref(null)
const deletingCostId = ref(null)

// ── Main filter state ─────────────────────────────────────────────
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
    filters.status = ''
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

// ── Approve / reject / delete actions ────────────────────────────
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

// ── Add cost form ────────────────────────────────────────────────
const addCostModalOpen = ref(false)
const addCostTripOptional = ref(false)
const tripPickerSearch = ref('')
const tripOptionsRaw = ref([])
const tripsForModalLoading = ref(false)
const costMsgIsError = ref(false)
const costForm = ref({ trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' })
const costAmountDigits = ref('')
const quickAddTypeOpen = ref(false)
const quickAddTypeLabel = ref('')
const quickAddTypeError = ref('')
const submitting = ref(false)
const costMsg = ref('')

// ── Permissions ──────────────────────────────────────────────────
const canReconcileCosts = computed(() => auth.hasPermission('trip.cost.reconcile'))
const canSubmitTripCost = computed(() => auth.hasPermission('trip.record.create'))
const canSubmitStandaloneCost = computed(() =>
  auth.hasAnyPermission(['trip.record.create', 'trip.cost.reconcile']),
)
const showAddCostButton = computed(() => {
  if (activeTab.value === 'standalone') return canSubmitStandaloneCost.value
  return canSubmitTripCost.value
})

const costAmountDisplay = computed(() => formatVndDigitsInput(costAmountDigits.value))

watch(extraCostTypes, (v) => {
  try {
    localStorage.setItem(EXTRA_TYPES_STORAGE_KEY, JSON.stringify(v))
  } catch {
    /* ignore */
  }
}, { deep: true })

// ── Filter option lists ───────────────────────────────────────────
const statusChipOptions = computed(() => [
  { value: '', label: t('costs_page.filter_status_all') },
  { value: 'submitted', label: statusLabel('submitted') },
  { value: 'confirmed', label: statusLabel('confirmed') },
  { value: 'rejected', label: statusLabel('rejected') },
])

const statusFilterOptions = computed(() => {
  const keys = ['draft', 'submitted', 'confirmed', 'rejected']
  return [
    { value: '', label: t('filter_bar.status') },
    ...keys.map((value) => ({
      value,
      label: te(`dashboard_analytics.cost_status_${value}`) ? t(`dashboard_analytics.cost_status_${value}`) : value,
    })),
  ]
})

const perPageFilterOptions = computed(() =>
  COSTS_PER_PAGE_OPTIONS.map((value) => ({
    value,
    label: String(value),
  })),
)

const typeFilterOptions = computed(() => {
  const rows = [{ value: '', label: t('costs_page.filter_cost_type') }]
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
  { value: '', label: t('costs_page.filter_trip_type') },
  ...TRIP_TYPE_SLUGS.map((value) => ({ value, label: labelTripType(value) })),
])

const fleetModeFilterOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_fleet') },
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

const secondaryActiveFilterCount = computed(() => {
  let n = 0
  if (filters.type) n++
  if (filters.trip_type) n++
  if (filters.trip_id) n++
  if (filters.provider) n++
  if (filters.fleet_mode) n++
  if (filters.amount_min || filters.amount_max) n++
  return n
})

const amountRangeSummary = computed(() => {
  if (!filters.amount_min && !filters.amount_max) return t('costs_page.filter_amount_range')
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
  const rows = [{ value: '', label: t('costs_page.filter_provider') }]
  if (hasEmpty) rows.push({ value: '__none__', label: t('costs_page.filter_provider_none') })
  for (const name of [...names].sort((a, b) => a.localeCompare(b, 'vi'))) {
    rows.push({ value: name, label: name })
  }
  return rows
})

function passesClientRowFiltersForCost(c) {
  if (!providerFilterMatchesRecordedCost(c)) return false
  if (!rowAmountMatchesFilter(c.amount)) return false
  return true
}

// ── Trip picker helpers ───────────────────────────────────────────
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

const selectedBpFilterTrip = computed(() => {
  if (!bpFilters.trip_id) return null
  const id = Number(bpFilters.trip_id)
  if (!Number.isFinite(id)) return null
  return tripOptionsRaw.value.find((tripRow) => Number(tripRow.id) === id) ?? null
})

const bpTripFilterSummaryFull = computed(() => {
  if (!bpFilters.trip_id) return t('costs_page.trip_all')
  const tr = selectedBpFilterTrip.value
  return tr ? formatTripPickerLabel(tr) : t('costs_page.trip_filter_chip', { id: formatTripCode(bpFilters.trip_id) })
})

const bpTripFilterSummaryShort = computed(() => {
  if (!bpFilters.trip_id) return t('costs_page.filter_trip')
  const tr = selectedBpFilterTrip.value
  if (tr) {
    const dr = tr.dispatch_request ?? tr.dispatchRequest
    const o = (dr?.origin ?? '—').trim().slice(0, 22)
    const d = (dr?.destination ?? '—').trim().slice(0, 22)
    return `${formatTripCode(tr.id)} · ${o} → ${d}`
  }
  return formatTripCode(bpFilters.trip_id)
})

const bpActiveFilterCount = computed(() => {
  let n = 0
  if (bpFilters.from || bpFilters.to) n++
  if (bpFilters.trip_id) n++
  return n
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
  return tr ? formatTripPickerLabel(tr) : t('costs_page.trip_filter_chip', { id: formatTripCode(filters.trip_id) })
})

const tripFilterSummaryShort = computed(() => {
  if (!filters.trip_id) return t('costs_page.filter_trip')
  const tr = selectedFilterTrip.value
  if (tr) {
    const dr = tr.dispatch_request ?? tr.dispatchRequest
    const o = (dr?.origin ?? '—').trim().slice(0, 22)
    const d = (dr?.destination ?? '—').trim().slice(0, 22)
    return `${formatTripCode(tr.id)} · ${o} → ${d}`
  }
  return formatTripCode(filters.trip_id)
})

// ── Displayed items & KPI ─────────────────────────────────────────
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

const kpiSummary = computed(() => {
  const all = displayedItems.value.filter((c) => !isWizardEstimateLine(c))
  const sumAmt = (arr) => arr.reduce((s, c) => s + Number(c.amount || 0), 0)
  const confirmed = all.filter((c) => c.status === 'confirmed')
  const pending = all.filter((c) => ['submitted', 'draft'].includes(c.status))
  const rejected = all.filter((c) => c.status === 'rejected')
  return {
    confirmed: { count: confirmed.length, amount: sumAmt(confirmed) },
    pending: { count: pending.length, amount: sumAmt(pending) },
    rejected: { count: rejected.length, amount: sumAmt(rejected) },
  }
})

const hasTableRows = computed(() => displayedItems.value.length > 0)
const tableBusy = computed(() => loading.value || estimatesLoading.value)

function rowIndex(idx) {
  const page = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? DEFAULT_PER_PAGE
  return (page - 1) * per + idx + 1
}

// ── Extra cost types ─────────────────────────────────────────────
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
  costAmountDigits.value = raw.length > 15 ? raw.slice(0, 15) : raw
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

function costTripCode(c) {
  return formatTripCode(c?.trip_id ?? c?.trip?.id)
}

function formatTripPickerLabel(tripRow) {
  const dr = tripRow.dispatch_request ?? tripRow.dispatchRequest
  const o = (dr?.origin ?? '—').trim().slice(0, 40)
  const d = (dr?.destination ?? '—').trim().slice(0, 40)
  const dep = formatDateDMY(tripRow.depart_at)
  const typSlug = dr?.trip_type ?? null
  const typStr = typSlug ? ` [${labelTripType(typSlug)}]` : ''
  return `${formatTripCode(tripRow.id)}${typStr} · ${o} → ${d} · ${dep}`
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

// ── Filter actions ───────────────────────────────────────────────
function setStatusFilter(val) {
  filters.status = val
  filters.page = 1
  reload()
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  filters.page = 1
  closeParentDetails(ev)
  reload()
}

function onAmountFilterInput(field, ev) {
  const raw = String(ev?.target?.value ?? '').replace(/\D/g, '')
  filters[field] = raw
}

function onFilterDateChange() {
  filters.page = 1
  reload()
}

function onBpFilterDropdownChange(ev) {
  if (ev) closeParentDetails(ev)
  reloadBp()
}

function onPerPageChange() {
  if (!COSTS_PER_PAGE_OPTIONS.includes(Number(filters.per_page))) {
    filters.per_page = DEFAULT_PER_PAGE
  }
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
  reload()
}

function resetSecondaryFilters() {
  filters.type = ''
  filters.trip_type = ''
  filters.trip_id = ''
  filters.provider = ''
  filters.fleet_mode = ''
  filters.amount_min = ''
  filters.amount_max = ''
  filters.page = 1
  filterTripSearch.value = ''
  reload()
}

// ── API reload ───────────────────────────────────────────────────
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
    } else {
      const [res, estRes] = await Promise.all([
        listTripCosts(p),
        listWizardEstimateLines(estimateParams),
      ])
      items.value = res.items ?? []
      meta.value = res.meta ?? {}
      estimateItems.value = (estRes.items ?? []).map(mapWizardLineToCostRow)
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

// ── Watchers ─────────────────────────────────────────────────────
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

// ── Lifecycle ────────────────────────────────────────────────────
onMounted(async () => {
  loadExtraCostTypesFromStorage()
  hydrateCostStatusFromRoute()
  await loadTripPickerOptions()
  await reload()
})
</script>

<style scoped>
/* ── Tab bar ──────────────────────────────────────────────────── */
.cv-tab-bar {
  @apply flex gap-1.5 rounded-2xl bg-slate-100/80 p-1.5 dark:bg-slate-800/60;
}

.cv-tab {
  @apply flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 hover:text-slate-800 sm:flex-none dark:text-slate-400 dark:hover:text-slate-200;
}

.cv-tab--active {
  @apply bg-white text-teal-800 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:text-teal-300 dark:ring-slate-700;
}

.cv-tab-badge {
  @apply rounded-full bg-teal-100 px-1.5 py-0.5 text-xs font-bold text-teal-800 dark:bg-teal-950/60 dark:text-teal-300;
}

/* ── Filter strip ─────────────────────────────────────────────── */
.cv-filter-strip {
  @apply flex flex-wrap items-center gap-x-2 gap-y-2;
}

.cv-status-chip {
  @apply rounded-full border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-300;
}

.cv-status-chip--active {
  @apply border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-700 dark:bg-teal-950/50 dark:text-teal-300;
}

.cv-date-input {
  @apply h-8 rounded-lg border-0 bg-white px-2 text-xs text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600;
}

.cv-search-input {
  @apply h-8 w-40 rounded-lg border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 sm:w-48;
}

.cv-more-btn {
  @apply flex h-8 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-300;
}

.cv-more-btn--active {
  @apply border-teal-300 text-teal-800 dark:border-teal-700 dark:text-teal-300;
}

.cv-more-badge {
  @apply rounded-full bg-teal-600 px-1.5 py-0.5 text-[10px] font-bold text-white;
}

/* ── Secondary filters ────────────────────────────────────────── */
.cv-secondary-filters {
  @apply flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/70 bg-slate-50/80 px-3 py-2.5 dark:border-slate-700/60 dark:bg-slate-800/40;
}

.cv-select {
  @apply h-8 max-w-[min(100%,11rem)] shrink-0 rounded-lg border-0 bg-white px-2 text-xs font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600;
}

.cv-amount-input {
  @apply h-8 w-28 rounded-lg border-0 bg-white px-2 text-xs shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600;
}

/* ── Dropdown details ─────────────────────────────────────────── */
.cv-dropdown-details {
  @apply relative shrink-0;
}

.cv-dropdown-trigger {
  @apply flex h-8 cursor-pointer list-none items-center gap-1 rounded-lg border-0 bg-white px-2.5 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-teal-50 hover:text-teal-800 focus:outline-none dark:bg-slate-950 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-teal-950/40 dark:hover:text-teal-300 [&::-webkit-details-marker]:hidden;
}

.cv-dropdown-trigger--active {
  @apply text-teal-800 ring-teal-300 dark:text-teal-300 dark:ring-teal-700;
}

.cv-dropdown-panel {
  @apply absolute left-0 top-[calc(100%+6px)] z-[110] min-w-[260px] rounded-xl border border-slate-200/80 bg-white p-3 shadow-xl ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50;
}

.cv-dropdown-item {
  @apply flex w-full rounded-lg px-3 py-1.5 text-left text-xs text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800;
}

.cv-dropdown-item--active {
  @apply bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100;
}

/* ── KPI cards ────────────────────────────────────────────────── */
.cv-kpi-card {
  @apply flex flex-col gap-0.5 rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/60;
}

.cv-kpi-card--confirmed {
  @apply border-emerald-200/70 bg-emerald-50/60 dark:border-emerald-800/40 dark:bg-emerald-950/20;
}

.cv-kpi-card--pending {
  @apply border-amber-200/70 bg-amber-50/60 dark:border-amber-800/40 dark:bg-amber-950/20;
}

.cv-kpi-card--rejected {
  @apply border-rose-200/70 bg-rose-50/60 dark:border-rose-800/40 dark:bg-rose-950/20;
}

.cv-kpi-num {
  @apply text-xl font-bold tabular-nums text-slate-900 dark:text-white;
}

.cv-kpi-label {
  @apply text-xs font-semibold text-slate-600 dark:text-slate-300;
}

.cv-kpi-hint {
  @apply text-[11px] text-slate-400 dark:text-slate-500;
}

.cv-kpi-amount {
  @apply text-xs tabular-nums text-slate-500 dark:text-slate-400;
}

/* ── Table ────────────────────────────────────────────────────── */
.cv-table {
  @apply border-collapse text-left text-sm;
}

.cv-table thead {
  @apply bg-slate-50/80 text-xs font-semibold text-slate-600 dark:bg-slate-800/80 dark:text-slate-300;
}

.cv-th {
  @apply border-b border-slate-200/80 px-3 py-3 align-top font-semibold tracking-tight dark:border-slate-700;
}

.cv-td {
  @apply border-b border-slate-100 px-3 py-3 align-top dark:border-slate-800;
}

.cv-row {
  @apply transition-colors hover:bg-teal-50/40 dark:hover:bg-teal-950/20;
}

.cv-row--clickable {
  @apply cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-teal-500;
}

.cv-row--alt {
  @apply bg-slate-50/30 dark:bg-slate-900/20;
}

.cv-row--estimate {
  @apply bg-violet-50/30 hover:bg-violet-50/50 dark:bg-violet-950/15 dark:hover:bg-violet-950/25;
}

/* ── Type badge ───────────────────────────────────────────────── */
.cv-type-badge {
  @apply rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400;
}

.cv-type-badge--estimate {
  @apply bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300;
}

/* ── Status badge ─────────────────────────────────────────────── */
.cv-status-badge {
  @apply inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold;
}

.cv-status-badge--confirmed {
  @apply bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300;
}

.cv-status-badge--pending {
  @apply bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300;
}

.cv-status-badge--rejected {
  @apply bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300;
}

.cv-status-badge--estimate {
  @apply bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300;
}

.cv-status-badge--default {
  @apply bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300;
}

/* ── Modal shared ─────────────────────────────────────────────── */
.costs-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500;
}

.costs-btn-primary {
  @apply inline-flex items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-btn-ghost {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800;
}

.costs-menu-item {
  @apply flex w-full items-center px-3 py-2 text-left text-sm font-medium transition;
}
</style>
