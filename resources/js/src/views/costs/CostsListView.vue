<template>
  <div class="cv-page space-y-6 min-h-0 min-w-0 pb-12 text-slate-900 dark:text-slate-100">

    <!-- Header -->
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ t('costs_page.hero_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('costs_page.hero_subtitle') }}</p>
      </div>
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
      <button
        role="tab"
        :aria-selected="activeTab === 'notes'"
        class="cv-tab"
        :class="activeTab === 'notes' ? 'cv-tab--active' : ''"
        data-testid="tab-notes"
        @click="activeTab = 'notes'"
      >
        {{ t('costs_page.tab_notes') }}
        <span v-if="activeTab === 'notes' && notesMeta.total > 0" class="cv-tab-badge">{{ notesMeta.total }}</span>
      </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         TAB: all_trips / standalone
    ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'all_trips' || activeTab === 'standalone'">

      <!-- ── KPI summary strip (kpi-summary-strip.mdc) ─────────── -->
      <CostsSummaryBar
        :total="meta.total ?? 0"
        :confirmed="kpiSummary.confirmed"
        :pending="kpiSummary.pending"
        :rejected="kpiSummary.rejected"
        :loading="tableBusy"
        :active-status="filters.status"
        @quick-filter="onKpiQuickFilter"
      />

      <!-- ── Datagrid toolbar (datagrid-toolbar.mdc) ───────────── -->
      <div
        ref="costsDatagridRef"
        class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <DatagridToolbarSearch
                v-model="searchQ"
                input-id="costs-list-search"
                :placeholder="t('costs_page.search_placeholder')"
                stretch
                inline-actions
                hide-label
                input-height="h-10"
              />
            </div>

            <div class="flex shrink-0 items-center gap-2">
              <FilterVisibilityDropdown
                :open="showFilterPanelDd"
                :title="t('costs_page.filter_show_controls_title')"
                :hint="t('costs_page.filter_show_controls_hint')"
                @close="closeFilterPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="filter"
                    :active="showFilterPanelDd"
                    test-id="costs-toolbar-filter"
                    @click="toggleFilterPanel"
                  >
                    {{ t('costs_page.toolbar_filter') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="fd in filterControlDefs" :key="'costs-vis-' + fd.key" class="flex items-start gap-2">
                  <input
                    :id="`costs-filter-vis-${fd.key}`"
                    v-model="visibleFilters[fd.key]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                    :data-testid="`costs-filter-vis-${fd.key}`"
                  />
                  <label
                    :for="`costs-filter-vis-${fd.key}`"
                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                  >
                    {{ fd.label }}
                  </label>
                </li>
              </FilterVisibilityDropdown>

              <FilterVisibilityDropdown
                :open="showColPanelDd"
                :title="t('costs_page.column_visibility_title')"
                @close="closeColPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="columns"
                    :active="showColPanelDd"
                    test-id="costs-toolbar-columns"
                    @click="toggleColPanel"
                  >
                    {{ t('costs_page.toolbar_columns') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="cd in colControlDefs" :key="'costs-col-vis-' + cd.id" class="flex items-start gap-2">
                  <input
                    :id="`costs-col-vis-${cd.id}`"
                    v-model="colVisible[cd.id]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                    :data-testid="`costs-col-vis-${cd.id}`"
                  />
                  <label
                    :for="`costs-col-vis-${cd.id}`"
                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                  >
                    {{ cd.label }}
                  </label>
                </li>
              </FilterVisibilityDropdown>
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2">
              <button
                type="button"
                class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
                :title="t('costs_page.filter_clear_all')"
                data-testid="costs-reset-filters"
                @click="resetFilters"
              >
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
              </button>
              <button
                v-if="showAddCostButton"
                type="button"
                class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg bg-va-800 px-3.5 text-sm font-semibold text-white shadow-sm ring-1 ring-black/5 transition hover:bg-va-900 focus:outline-none focus:ring-2 focus:ring-va-800/35 dark:ring-white/10"
                data-testid="costs-add-btn"
                @click="openAddCostModal"
              >
                <PlusCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ activeTab === 'standalone' ? t('costs_page.add_standalone_cost') : t('costs_page.add_cost') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Filter value row -->
        <div
          v-if="hasFilterRow"
          class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
        >
          <DatagridFilterField v-if="visibleFilters.status">
            <select
              v-model="filters.status"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('filter_bar.status')"
              data-testid="costs-filter-status"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.date_range">
            <FilterDatePicker
              v-model="filters.from"
              :placeholder="t('dashboard_analytics.range_from')"
              :max-date="filters.to || null"
              input-id="costs-filter-from"
              @update:model-value="onFilterDateChange"
            />
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.date_range">
            <FilterDatePicker
              v-model="filters.to"
              :placeholder="t('dashboard_analytics.range_to')"
              :min-date="filters.from || null"
              input-id="costs-filter-to"
              @update:model-value="onFilterDateChange"
            />
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.type">
            <select
              v-model="filters.type"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.filter_cost_type')"
              data-testid="costs-filter-type"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in typeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.trip_type && activeTab !== 'standalone'">
            <select
              v-model="filters.trip_type"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.filter_trip_type')"
              data-testid="costs-filter-trip-type"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.trip && activeTab !== 'standalone'">
            <select
              v-model="filters.trip_id"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.filter_trip')"
              data-testid="costs-filter-trip"
              @change="onSimpleFilterSelectChange"
            >
              <option value="">{{ t('costs_page.trip_all') }}</option>
              <option v-for="tripRow in tripOptionsRaw" :key="tripRow.id" :value="String(tripRow.id)">
                {{ formatTripPickerLabel(tripRow) }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.provider && activeTab !== 'standalone'">
            <select
              v-model="filters.provider"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.filter_provider')"
              data-testid="costs-filter-provider"
            >
              <option v-for="opt in providerFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.fleet_mode && activeTab !== 'standalone'">
            <select
              v-model="filters.fleet_mode"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('dashboard_analytics.filter_fleet')"
              data-testid="costs-filter-fleet"
              @change="onSimpleFilterSelectChange"
            >
              <option v-for="opt in fleetModeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.amount_range" class="sm:col-span-2 xl:col-span-2">
            <div class="flex items-center gap-1.5">
              <input
                :value="formatVndDigitsInput(String(filters.amount_min).replace(/\D/g, ''))"
                type="text"
                inputmode="numeric"
                :class="FILTER_CONTROL_CLASS"
                class="tabular-nums"
                :placeholder="t('costs_page.filter_amount_min')"
                autocomplete="off"
                @input="onAmountFilterInput('amount_min', $event)"
              />
              <span class="text-xs font-medium text-slate-400">–</span>
              <input
                :value="formatVndDigitsInput(String(filters.amount_max).replace(/\D/g, ''))"
                type="text"
                inputmode="numeric"
                :class="FILTER_CONTROL_CLASS"
                class="tabular-nums"
                :placeholder="t('costs_page.filter_amount_max')"
                autocomplete="off"
                @input="onAmountFilterInput('amount_max', $event)"
              />
            </div>
          </DatagridFilterField>

          <DatagridFilterField v-if="visibleFilters.per_page">
            <select
              v-model.number="filters.per_page"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.pagination_per_page_aria')"
              data-testid="costs-filter-per-page"
              @change="onPerPageChange"
            >
              <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </DatagridFilterField>
        </div>
      </div>

      <!-- ── Cost record cards ─────────────────────────────────── -->
      <p v-if="activeTab === 'standalone'" class="-mt-2 text-xs text-slate-500 dark:text-slate-400">
        {{ t('costs_page.standalone_intro') }}
      </p>

      <div v-if="tableBusy && !hasTableRows" class="flex items-center justify-center gap-2 py-12 text-sm text-slate-500">
        <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
        {{ t('costs_page.loading_table') }}
      </div>
      <div v-else class="space-y-3 md:space-y-4">
        <article
          v-for="c in displayedItems"
          :key="costRowKey(c)"
          class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/50"
          :data-testid="`cost-card-${c.id}`"
        >
          <!-- Header -->
          <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="flex min-w-0 gap-3 sm:gap-4">
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
                  :class="costStatusIconWrap(c.status)"
                >
                  <BanknotesIcon class="h-6 w-6" :class="costStatusIconColor(c.status)" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <div v-if="c.trip_id" class="min-w-0">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.card_trip_code') }}
                      </p>
                      <RouterLink
                        :to="`/trips/${c.trip_id}`"
                        class="mt-0.5 inline-block font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                        :data-testid="`cost-card-link-${c.id}`"
                        @click.stop
                      >
                        {{ costTripCode(c) }}
                      </RouterLink>
                    </div>
                    <div v-else class="min-w-0">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.detail_cost_code') }}
                      </p>
                      <p class="mt-0.5 font-mono text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        {{ formatCostNoteCode(c.id) }}
                      </p>
                    </div>
                    <span
                      v-if="!c.trip_id"
                      class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-200"
                    >
                      {{ t('costs_page.badge_standalone') }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                      {{ typeLabel(c.type) }}
                    </span>
                    <span
                      v-if="tripTypeFromCost(c)"
                      class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      :class="tripTypeBadgeClass(tripTypeFromCost(c))"
                    >
                      {{ labelTripType(tripTypeFromCost(c)) }}
                    </span>
                    <span
                      v-if="costEvidenceCount(c) > 0"
                      class="inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700 ring-1 ring-teal-200/70 dark:bg-teal-950/40 dark:text-teal-300 dark:ring-teal-800/50"
                    >
                      {{ t('costs_page.detail_evidence_count', { count: costEvidenceCount(c) }) }}
                    </span>
                  </div>
                  <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 xl:grid-cols-3">
                    <div class="min-w-0">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.card_recorded_label') }}
                      </dt>
                      <dd class="mt-0.5 font-medium tabular-nums text-slate-800 dark:text-slate-200">
                        {{ formatDateDMYDisplay(c.created_at) }}
                      </dd>
                    </div>
                    <div v-if="colVisible.submitter" class="min-w-0">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.col_submitter') }}
                      </dt>
                      <dd class="mt-0.5 flex items-center gap-2">
                        <UserAvatar
                          :name="costSubmitterRawName(c) || ''"
                          :email="costSubmitterEmail(c)"
                          :avatar-url="costSubmitterAvatarUrl(c)"
                          :title="costSubmitterDisplayName(c) || ''"
                          size="sm"
                          data-testid="cost-card-submitter-avatar"
                        />
                        <span
                          class="min-w-0 truncate font-medium"
                          :class="costSubmitterDisplayName(c) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
                        >
                          {{ costSubmitterDisplayName(c) || t('costs_page.empty_not_available') }}
                        </span>
                      </dd>
                    </div>
                    <div v-if="colVisible.provider" class="min-w-0">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.col_provider') }}
                      </dt>
                      <dd
                        class="mt-0.5 font-medium"
                        :class="costProviderName(c) ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'"
                      >
                        {{ costProviderName(c) || unitLabel }}
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>
              <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                <span class="text-xl font-bold tabular-nums text-slate-900 dark:text-slate-100 sm:text-2xl">
                  {{ formatVnd(c.amount) }}
                </span>
                <span class="cv-status-badge" :class="costStatusBadgeClass(c.status)">
                  {{ statusLabel(c.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Body: description -->
          <div class="px-3 py-4 sm:px-5">
            <p
              v-if="displayTextOrNull(c.description)"
              class="text-sm leading-relaxed text-slate-700 dark:text-slate-300"
              :title="c.description"
            >
              {{ c.description }}
            </p>
            <p v-else class="text-sm italic text-slate-400 dark:text-slate-500">
              {{ t('costs_page.empty_description') }}
            </p>
            <p
              v-if="c.rejection_reason"
              class="mt-2 rounded-lg border border-rose-200/70 bg-rose-50/70 px-3 py-1.5 text-xs text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300"
              :title="c.rejection_reason"
            >
              {{ c.rejection_reason }}
            </p>
          </div>

          <!-- Footer -->
          <div class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-5 sm:py-3.5 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
              <span v-if="c.confirmer?.name" class="inline-flex items-center gap-1.5">
                <CheckCircleIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                {{ t('costs_page.card_confirmer_label') }}: {{ c.confirmer.name }}
              </span>
              <span
                v-if="colVisible.fleet && tripFleetModeFromCost(c) !== 'unspecified'"
                class="inline-flex items-center gap-1.5"
              >
                <TruckIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                {{ fleetModeLabel(tripFleetModeFromCost(c)) }}
              </span>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
              <button
                type="button"
                class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                :data-testid="`cost-card-detail-${c.id}`"
                @click="openCostDetail(c)"
              >
                <EyeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ t('costs_page.action_view_detail') }}
              </button>
              <template v-if="canReconcileCosts && isCostPendingDecision(c)">
                <button
                  type="button"
                  class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 sm:min-h-0"
                  :disabled="decidingId != null"
                  :data-testid="`cost-card-approve-${c.id}`"
                  @click="quickApproveCost(c)"
                >
                  <CheckCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('costs_page.action_approve') }}
                </button>
                <button
                  type="button"
                  class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 disabled:opacity-50 sm:min-h-0 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200"
                  :disabled="decidingId != null"
                  :data-testid="`cost-card-reject-${c.id}`"
                  @click="quickRejectCost(c)"
                >
                  <XCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('costs_page.action_reject') }}
                </button>
              </template>
              <AppRowActionsMenu
                v-if="canReconcileCosts"
                align="end"
                :aria-label="t('costs_page.row_actions_aria', { code: costRowReferenceLabel(c) })"
                :trigger-sr-only="t('costs_page.col_actions')"
                :disabled="decidingId != null || deletingCostId != null"
                root-class="text-right"
                data-testid="cost-card-actions-menu"
              >
                <button
                  type="button"
                  role="menuitem"
                  class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-800 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                  data-testid="cost-card-delete-btn"
                  :disabled="deletingCostId != null"
                  @click="openDeleteModal(c)"
                >
                  {{ t('costs_page.action_delete') }}
                </button>
              </AppRowActionsMenu>
            </div>
          </div>
        </article>

        <div
          v-if="!hasTableRows"
          class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
        >
          {{ t('costs_page.empty_table') }}
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="(meta.total ?? 0) > 0"
        class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-slate-600 dark:text-slate-400">
          <span>
            {{ t('costs_page.pagination_of', { current: meta.current_page ?? 1, last: meta.last_page ?? 1 }) }}
            <span class="text-slate-400"> · </span>
            {{ meta.total ?? 0 }} {{ t('costs_page.pagination_records_suffix') }}
          </span>
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
    </template>

    <!-- ══════════════════════════════════════════════════════════
         TAB: business_personnel
    ═══════════════════════════════════════════════════════════════ -->
    <section v-else-if="activeTab === 'business_personnel'" aria-labelledby="costs-section-business-personnel">

      <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h2
            id="costs-section-business-personnel"
            class="sr-only"
          >
            {{ t('costs_page.section_business_personnel') }}
          </h2>
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <DatagridToolbarSearch
                v-model="bpSearchQ"
                input-id="costs-bp-search"
                :placeholder="t('costs_page.bp_search_placeholder')"
                stretch
                inline-actions
                hide-label
                input-height="h-10"
              />
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <FilterVisibilityDropdown
                :open="showBpFilterPanelDd"
                :title="t('costs_page.filter_show_controls_title')"
                :hint="t('costs_page.filter_show_controls_hint')"
                @close="closeBpFilterPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="filter"
                    :active="showBpFilterPanelDd"
                    test-id="costs-bp-toolbar-filter"
                    @click="toggleBpFilterPanel"
                  >
                    {{ t('costs_page.toolbar_filter') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="fd in bpFilterControlDefs" :key="'costs-bp-vis-' + fd.key" class="flex items-start gap-2">
                  <input
                    :id="`costs-bp-filter-vis-${fd.key}`"
                    v-model="bpVisibleFilters[fd.key]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                    :data-testid="`costs-bp-filter-vis-${fd.key}`"
                  />
                  <label
                    :for="`costs-bp-filter-vis-${fd.key}`"
                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                  >
                    {{ fd.label }}
                  </label>
                </li>
              </FilterVisibilityDropdown>
            </div>
            <div class="ml-auto flex shrink-0 items-center gap-2">
              <button
                v-if="bpActiveFilterCount > 0"
                type="button"
                class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
                :title="t('costs_page.filter_clear')"
                data-testid="costs-bp-reset-filters"
                @click="resetBpFilters"
              >
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="hasBpFilterRow"
          class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
        >
            <template v-if="bpVisibleFilters.date_range">
              <DatagridFilterField>
                <FilterDatePicker
                v-model="bpFilters.from"
                :placeholder="t('dashboard_analytics.range_from')"
                :max-date="bpFilters.to || null"
                input-id="costs-bp-filter-from"
                @update:model-value="onBpFilterDateChange"
              />
            </DatagridFilterField>
            <DatagridFilterField>
              <FilterDatePicker
                v-model="bpFilters.to"
                :placeholder="t('dashboard_analytics.range_to')"
                :min-date="bpFilters.from || null"
                input-id="costs-bp-filter-to"
                @update:model-value="onBpFilterDateChange"
              />
            </DatagridFilterField>
          </template>

          <DatagridFilterField v-if="bpVisibleFilters.trip">
            <select
              v-model="bpFilters.trip_id"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('costs_page.filter_trip')"
              data-testid="costs-bp-filter-trip"
              :disabled="tripsForModalLoading"
              @change="onBpTripFilterChange"
            >
              <option value="">{{ t('costs_page.filter_trip') }}</option>
              <option v-for="tripRow in tripOptionsRaw" :key="tripRow.id" :value="String(tripRow.id)">
                {{ formatTripPickerLabel(tripRow) }}
              </option>
            </select>
          </DatagridFilterField>
        </div>
      </div>

      <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ t('costs_page.section_business_personnel') }}
        <span v-if="bpMeta.total > 0" class="ml-1 font-normal text-slate-500 dark:text-slate-400">({{ bpDisplayedLines.length }})</span>
      </p>

      <div v-if="bpLoading && !bpLines.length" class="mt-3 flex items-center justify-center gap-2 py-12 text-sm text-slate-500">
        <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
        {{ t('costs_page.business_personnel_loading') }}
      </div>

      <div v-else class="mt-3 space-y-3 md:space-y-4">
        <article
          v-for="row in bpDisplayedLines"
          :key="bpRowKey(row)"
          class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/50"
          :data-testid="`bp-card-${row.trip_id}-${row.line_no}`"
        >
          <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="flex min-w-0 gap-3 sm:gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 sm:h-12 sm:w-12 dark:bg-violet-950/40">
                  <BriefcaseIcon class="h-6 w-6 text-violet-700 dark:text-violet-300" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <div class="min-w-0">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.card_trip_code') }}
                      </p>
                      <RouterLink
                        :to="{ name: 'tripDetail', params: { id: row.trip_id } }"
                        class="mt-0.5 inline-block font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                        :data-testid="`bp-card-link-${row.trip_id}-${row.line_no}`"
                        @click.stop
                      >
                        {{ formatTripCode(row.trip_id) }}
                      </RouterLink>
                    </div>
                    <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-900 dark:bg-violet-950/50 dark:text-violet-100">
                      {{ labelTripType('business') }}
                    </span>
                    <span
                      v-if="row.line_no > 1"
                      class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                    >
                      {{ t('costs_page.bp_line_badge', { n: row.line_no }) }}
                    </span>
                  </div>
                  <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 xl:grid-cols-3">
                    <div class="min-w-0">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.bp_card_depart_label') }}
                      </dt>
                      <dd
                        class="mt-0.5 font-medium tabular-nums"
                        :class="bpDepartDisplay(row) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
                      >
                        {{ bpDepartDisplay(row) || t('costs_page.empty_date') }}
                      </dd>
                    </div>
                    <div class="min-w-0">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.col_submitter') }}
                      </dt>
                      <dd class="mt-0.5 flex items-center gap-2">
                        <UserAvatar
                          :name="row.requester_name || ''"
                          :email="row.requester_email || ''"
                          :avatar-url="row.requester_avatar_url"
                          :title="row.requester_name || ''"
                          size="sm"
                          data-testid="bp-card-requester-avatar"
                        />
                        <span
                          class="min-w-0 truncate font-medium"
                          :class="displayTextOrNull(row.requester_name) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
                        >
                          {{ displayTextOrNull(row.requester_name) || t('costs_page.empty_not_available') }}
                        </span>
                      </dd>
                    </div>
                    <div class="min-w-0 sm:col-span-2 xl:col-span-1">
                      <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('costs_page.col_route') }}
                      </dt>
                      <dd
                        class="mt-0.5 font-medium"
                        :class="bpRouteDisplay(row) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
                      >
                        <span :class="bpRoutePrimary(row) ? '' : 'line-clamp-2'">{{ bpRouteDisplay(row) || t('costs_page.empty_route') }}</span>
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>
              <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                <span class="text-xl font-bold tabular-nums text-slate-900 dark:text-slate-100 sm:text-2xl">
                  {{ formatVnd(row.amount_total) }}
                </span>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-100 bg-slate-50/50 px-3 py-4 sm:px-5 dark:border-slate-800 dark:bg-slate-950/20">
            <template v-if="displayTextOrNull(row.personnel_label)">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_personnel') }}
                </h3>
                <span
                  v-if="bpShowGuestsBadge(row)"
                  class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-medium tabular-nums text-slate-700 ring-1 ring-slate-200/90 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700"
                >
                  {{ t('costs_page.bp_guests_label') }}: {{ row.guests }}
                </span>
              </div>
              <p
                v-if="bpNarrativeContent(row).headline"
                class="mt-2 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100"
              >
                {{ bpNarrativeContent(row).headline }}
              </p>
              <p
                class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300"
                :class="bpNarrativeNeedsCollapse(row) && !isBpNarrativeExpanded(row) ? 'line-clamp-6' : ''"
              >
                {{ bpNarrativeContent(row).body }}
              </p>
              <button
                v-if="bpNarrativeNeedsCollapse(row)"
                type="button"
                class="mt-2 text-xs font-medium text-va-800 underline decoration-va-300 underline-offset-2 hover:decoration-va-600 dark:text-va-300"
                :data-testid="`bp-card-toggle-${row.trip_id}-${row.line_no}`"
                @click="toggleBpNarrativeExpanded(row)"
              >
                {{ isBpNarrativeExpanded(row) ? t('costs_page.bp_show_less') : t('costs_page.bp_show_more') }}
              </button>
              <div
                v-if="bpWaypointShort(row)"
                class="mt-3 flex gap-2.5 rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/60"
              >
                <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                <div class="min-w-0">
                  <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('costs_page.bp_waypoint_label') }}
                  </p>
                  <p class="mt-0.5 text-sm leading-snug text-slate-700 dark:text-slate-300">
                    {{ bpWaypointShort(row) }}
                  </p>
                </div>
              </div>
            </template>
            <p v-else class="text-sm italic text-slate-400 dark:text-slate-500">
              {{ t('costs_page.empty_description') }}
            </p>
          </div>

          <div class="border-t border-slate-100 bg-white px-3 py-4 dark:border-slate-800 dark:bg-slate-900/30 sm:px-5">
            <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">
              {{ t('costs_page.bp_footer_pricing_heading') }}
            </p>
            <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-stretch lg:justify-between">
              <dl class="grid min-w-0 flex-1 grid-cols-1 gap-2 sm:grid-cols-3">
                <div
                  class="flex gap-3 rounded-xl border border-slate-200/90 bg-slate-50/90 px-3 py-3 dark:border-slate-700 dark:bg-slate-900/50"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-slate-200/90 dark:bg-slate-800 dark:ring-slate-600"
                    aria-hidden="true"
                  >
                    <BanknotesIcon class="h-5 w-5 text-va-800 dark:text-va-300" />
                  </div>
                  <div class="min-w-0">
                    <dt class="text-xs font-medium text-slate-600 dark:text-slate-400">
                      {{ t('costs_page.col_unit_price') }}
                    </dt>
                    <dd class="mt-0.5 text-base font-bold tabular-nums text-slate-900 dark:text-slate-100">
                      {{ formatVnd(row.unit_price) }}
                    </dd>
                  </div>
                </div>
                <div
                  class="flex gap-3 rounded-xl border border-slate-200/90 bg-slate-50/90 px-3 py-3 dark:border-slate-700 dark:bg-slate-900/50"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-slate-200/90 dark:bg-slate-800 dark:ring-slate-600"
                    aria-hidden="true"
                  >
                    <BanknotesIcon class="h-5 w-5 text-sky-700 dark:text-sky-300" />
                  </div>
                  <div class="min-w-0">
                    <dt class="text-xs font-medium text-slate-600 dark:text-slate-400">
                      {{ t('costs_page.col_extra_fee') }}
                    </dt>
                    <dd class="mt-0.5 text-base font-bold tabular-nums text-slate-900 dark:text-slate-100">
                      {{ formatVnd(row.extra_fee) }}
                    </dd>
                  </div>
                </div>
                <div
                  class="flex gap-3 rounded-xl border border-violet-200/90 bg-violet-50/50 px-3 py-3 dark:border-violet-900/50 dark:bg-violet-950/25"
                >
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-violet-200/90 dark:bg-slate-800 dark:ring-violet-800/60"
                    aria-hidden="true"
                  >
                    <BanknotesIcon class="h-5 w-5 text-violet-800 dark:text-violet-300" />
                  </div>
                  <div class="min-w-0">
                    <dt class="text-xs font-medium text-violet-900/90 dark:text-violet-200/90">
                      {{ t('costs_page.bp_label_line_total') }}
                    </dt>
                    <dd class="mt-0.5 text-base font-bold tabular-nums text-violet-950 dark:text-violet-100">
                      {{ formatVnd(row.amount_total) }}
                    </dd>
                  </div>
                </div>
              </dl>
              <div class="flex shrink-0 items-end sm:justify-end">
                <RouterLink
                  :to="{ name: 'tripDetail', params: { id: row.trip_id } }"
                  class="inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 sm:w-auto dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                  :data-testid="`bp-card-detail-${row.trip_id}-${row.line_no}`"
                >
                  <EyeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('costs_page.action_view_detail') }}
                </RouterLink>
              </div>
            </div>
          </div>
        </article>

        <div
          v-if="!bpLoading && !bpDisplayedLines.length"
          class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
        >
          {{ bpSearchQ.trim() ? t('costs_page.bp_empty_search') : t('costs_page.business_personnel_empty') }}
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         TAB: notes (Ghi chú vận hành)
    ═══════════════════════════════════════════════════════════════ -->
    <section v-if="activeTab === 'notes'" aria-labelledby="costs-section-notes-title">

      <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">

        <!-- ── Toolbar ─────────────────────────────────────────── -->
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <!-- Search -->
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <label :for="'notes-search-input'" class="sr-only">{{ t('costs_page.notes_search_placeholder') }}</label>
              <div class="relative flex items-center">
                <svg class="pointer-events-none absolute left-3 h-4 w-4 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                  id="notes-search-input"
                  v-model="notesSearchQ"
                  type="search"
                  autocomplete="off"
                  class="h-10 w-full rounded-lg border border-slate-200 bg-white py-0 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                  :placeholder="t('costs_page.notes_search_placeholder')"
                  data-testid="notes-search"
                />
              </div>
            </div>

            <!-- Right actions -->
            <div class="ml-auto flex shrink-0 items-center gap-2">
              <button
                type="button"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                :disabled="notesLoading"
                data-testid="notes-refresh"
                @click="reloadNotes"
              >
                <svg class="h-4 w-4" :class="notesLoading ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                {{ t('costs_page.notes_refresh') }}
              </button>
            </div>
          </div>
        </div>

        <!-- ── Notes list ─────────────────────────────────────── -->
        <div class="divide-y divide-slate-100 dark:divide-slate-700/80">

          <!-- Loading skeleton -->
          <div v-if="notesLoading && !notesList.length" class="flex items-center justify-center gap-2 px-5 py-12 text-sm text-slate-500 dark:text-slate-400">
            <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600 dark:border-slate-700 dark:border-t-teal-400" aria-hidden="true" />
            {{ t('costs_page.notes_loading') }}
          </div>

          <!-- Empty state -->
          <div
            v-else-if="!notesLoading && !notesFiltered.length"
            class="flex flex-col items-center justify-center gap-3 px-5 py-14 text-center"
          >
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
              <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
              </svg>
            </span>
            <div>
              <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ notesSearchQ ? t('costs_page.notes_empty_search') : t('costs_page.notes_empty') }}
              </p>
              <p v-if="!notesSearchQ" class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ t('costs_page.notes_empty_hint') }}</p>
            </div>
          </div>

          <!-- Note rows -->
          <template v-else>
            <div
              v-for="note in notesFiltered"
              :key="note.id"
              class="group flex items-start gap-3 px-5 py-4 transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
              :data-testid="`note-row-${note.id}`"
            >
              <!-- Avatar -->
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold uppercase text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 select-none" aria-hidden="true">
                {{ (note.creator?.name ?? note.user?.name ?? '?').slice(0, 1) }}
              </div>

              <!-- Content -->
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                  <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    {{ note.creator?.name ?? note.user?.name ?? t('costs_page.notes_unknown_author') }}
                  </span>
                  <span class="text-xs tabular-nums text-slate-400 dark:text-slate-500">
                    {{ formatNoteTime(note.created_at) }}
                  </span>
                </div>
                <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ note.body ?? note.message }}</p>
              </div>

              <!-- Delete action -->
              <button
                v-if="canAddCostNote"
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-300 opacity-0 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100 focus-visible:opacity-100 dark:text-slate-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                :class="deletingNoteId === note.id ? 'opacity-100 cursor-not-allowed' : ''"
                :disabled="deletingNoteId != null"
                :aria-label="t('costs_page.notes_delete_aria')"
                :data-testid="`note-delete-${note.id}`"
                @click="deleteCostNoteItem(note.id)"
              >
                <span v-if="deletingNoteId === note.id" class="inline-block size-4 animate-spin rounded-full border-2 border-rose-300 border-t-rose-600" aria-hidden="true" />
                <XMarkIcon v-else class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </template>
        </div>

        <!-- ── Pagination ──────────────────────────────────────── -->
        <div
          v-if="(notesMeta.last_page ?? 1) > 1"
          class="flex items-center justify-between border-t border-slate-100 px-5 py-3 dark:border-slate-700"
        >
          <span class="text-xs text-slate-500 dark:text-slate-400">
            {{ t('costs_page.pagination_page') }} {{ notesMeta.current_page }} / {{ notesMeta.last_page }}
            · {{ notesMeta.total }} {{ t('costs_page.notes_count_suffix') }}
          </span>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              :disabled="notesLoading || (notesMeta.current_page ?? 1) <= 1"
              data-testid="notes-prev"
              @click="notePageChange(-1)"
            >
              {{ t('costs_page.prev') }}
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              :disabled="notesLoading || (notesMeta.current_page ?? 1) >= (notesMeta.last_page ?? 1)"
              data-testid="notes-next"
              @click="notePageChange(1)"
            >
              {{ t('costs_page.next') }}
            </button>
          </div>
        </div>

        <!-- ── Add note form ──────────────────────────────────── -->
        <div
          v-if="canAddCostNote"
          class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/30"
        >
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('costs_page.notes_add_section_label') }}
          </p>
          <textarea
            v-model="newCostNote"
            rows="3"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
            :placeholder="t('costs_page.notes_add_placeholder')"
            data-testid="notes-new-input"
            @keydown.ctrl.enter.prevent="submitCostNote"
            @keydown.meta.enter.prevent="submitCostNote"
          />
          <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
            <p
              v-if="costNoteMsg"
              class="text-sm"
              :class="costNoteMsgIsError ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'"
            >
              {{ costNoteMsg }}
            </p>
            <p v-else class="text-xs text-slate-400 dark:text-slate-500">{{ t('costs_page.notes_add_hint') }}</p>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-teal-600/40"
              :disabled="addingCostNote || !newCostNote.trim()"
              data-testid="notes-add-btn"
              @click="submitCostNote"
            >
              <span v-if="addingCostNote" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
              <PlusCircleIcon v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('costs_page.notes_add_action') }}
            </button>
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
                {{ t('costs_page.reject_modal_summary', { code: costRowReferenceLabel(rejectTarget) }) }}
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
                {{ t('costs_page.delete_modal_summary', { code: costRowReferenceLabel(deleteTarget) }) }}
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
import {
  BanknotesIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  EyeIcon,
  FunnelIcon,
  MapPinIcon,
  PlusCircleIcon,
  TruckIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import CostsSummaryBar from '../../components/costs/CostsSummaryBar.vue'
import UserAvatar from '../../components/branding/UserAvatar.vue'
import {
  listTripCosts,
  listBusinessPersonnelCostLines,
  submitTripCost,
  submitStandaloneTripCost,
  decideTripCost,
  deleteTripCost,
  listCostNotes,
  addCostNote,
  deleteCostNote,
} from '../../api/costs'
import { listTrips } from '../../api/trips'
import { newIdempotencyKey } from '../../util/idempotency'
import { formatCostNoteCode, formatTripCode, formatVnd, formatVndDigitsInput, labelTripType } from '../../util/labels'
import StaffCostDetailModal from '../../components/costs/StaffCostDetailModal.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { displayTextOrNull, isEmptyDisplay } from '../../util/displayValue'

const { t, te, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

/** @type {import('vue').Ref<'all_trips' | 'standalone' | 'business_personnel' | 'notes'>} */
const activeTab = ref('all_trips')

const COSTS_PER_PAGE_OPTIONS = [5, 10, 15, 20]
const DEFAULT_PER_PAGE = 10
const COSTS_COL_VISIBILITY_KEY = 'va.costs.col_visibility_v3'
const COL_IDS = ['provider', 'submitter', 'fleet']

const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const COSTS_FILTER_CONTROLS = [
  { key: 'status', default: false },
  { key: 'date_range', default: false },
  { key: 'type', default: false },
  { key: 'trip_type', default: false },
  { key: 'trip', default: false },
  { key: 'provider', default: false },
  { key: 'fleet_mode', default: false },
  { key: 'amount_range', default: false },
  { key: 'per_page', default: false },
]

const COSTS_FILTER_VIS_LABEL_KEYS = {
  status: 'costs_page.filter_vis_status',
  date_range: 'costs_page.filter_vis_date',
  type: 'costs_page.filter_vis_type',
  trip_type: 'costs_page.filter_vis_trip_type',
  trip: 'costs_page.filter_vis_trip',
  provider: 'costs_page.filter_vis_provider',
  fleet_mode: 'costs_page.filter_vis_fleet_mode',
  amount_range: 'costs_page.filter_vis_amount_range',
  per_page: 'filter_bar.per_page',
}

const BP_FILTER_CONTROLS = [
  { key: 'date_range', default: false },
  { key: 'trip', default: false },
]

const BP_FILTER_VIS_LABEL_KEYS = {
  date_range: 'costs_page.filter_vis_date',
  trip: 'costs_page.filter_vis_trip',
}

function defaultColVisibility() {
  return Object.fromEntries(COL_IDS.map((id) => [id, true]))
}

const unitLabel = computed(() => t('costs_page.unit_label_const'))

const BUILTIN_COST_TYPES = ['fuel', 'toll', 'parking', 'other']
const EXTRA_TYPES_STORAGE_KEY = 'va.costs.extra_types_v1'

const extraCostTypes = ref([])

function typeLabel(slug) {
  if (!slug) return t('costs_page.empty_not_available')
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
  if (!c?.id) return
  detailCostId.value = Number(c.id)
  detailModalOpen.value = true
}

function closeCostDetail() {
  detailModalOpen.value = false
  detailCostId.value = null
}

// ── Evidence helpers ─────────────────────────────────────────────
function costEvidenceCount(c) {
  let n = Number(c.attachments_count) || 0
  if (c.receipt_url) n += 1
  return n
}

function costRowKey(c) {
  return c.id
}

function costRowReferenceLabel(c) {
  if (!c?.trip_id) return t('costs_page.badge_standalone')
  return formatTripCode(c?.trip_id ?? c?.trip?.id)
}

const TRIP_TYPE_SLUGS = ['point_to_point', 'cargo', 'business', 'door_to_door']

function tripTypeFromCost(c) {
  return c?.trip?.dispatch_request?.trip_type ?? c?.trip?.dispatchRequest?.trip_type ?? null
}

function dispatchRequestFromCost(c) {
  return c?.trip?.dispatch_request ?? c?.trip?.dispatchRequest ?? null
}

function costSubmitterRawName(c) {
  const dr = dispatchRequestFromCost(c)
  const requester = dr?.requester?.name
  if (tripTypeFromCost(c) === 'business' && requester) return requester
  return c.creator?.name ?? ''
}

function costSubmitterDisplayName(c) {
  return displayTextOrNull(costSubmitterRawName(c))
}

function costSubmitterEmail(c) {
  const dr = dispatchRequestFromCost(c)
  if (tripTypeFromCost(c) === 'business' && dr?.requester?.email) {
    return dr.requester.email
  }
  return c.creator?.email ?? ''
}

function costSubmitterAvatarUrl(c) {
  const dr = dispatchRequestFromCost(c)
  if (tripTypeFromCost(c) === 'business' && dr?.requester?.avatar_url) {
    return dr.requester.avatar_url
  }
  return c.creator?.avatar_url ?? ''
}

function costSubmitterLabel(c) {
  return costSubmitterDisplayName(c) || ''
}

function bpRoutePart(value) {
  return displayTextOrNull(value)
}

function bpRoutePrimary(row) {
  const pickup = bpRoutePart(row.pickup)
  const dropoff = bpRoutePart(row.dropoff)
  if (pickup || dropoff) {
    const from = pickup || t('costs_page.empty_not_available')
    const to = dropoff || t('costs_page.empty_not_available')
    return `${from} → ${to}`
  }
  const origin = bpRoutePart(row.request_origin)
  const dest = bpRoutePart(row.request_destination)
  if (origin || dest) {
    const from = origin || t('costs_page.empty_not_available')
    const to = dest || t('costs_page.empty_not_available')
    return `${from} → ${to}`
  }
  return null
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

function costStatusBadgeClass(status) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed') return 'cv-status-badge--confirmed'
  if (s === 'rejected') return 'cv-status-badge--rejected'
  if (s === 'submitted' || s === 'draft') return 'cv-status-badge--pending'
  return 'cv-status-badge--default'
}

function costStatusIconWrap(status) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (s === 'rejected') return 'bg-rose-100 dark:bg-rose-950/40'
  if (s === 'submitted' || s === 'draft') return 'bg-amber-100 dark:bg-amber-950/40'
  return 'bg-slate-100 dark:bg-slate-800/60'
}

function costStatusIconColor(status) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed') return 'text-emerald-600 dark:text-emerald-400'
  if (s === 'rejected') return 'text-rose-600 dark:text-rose-400'
  if (s === 'submitted' || s === 'draft') return 'text-amber-600 dark:text-amber-400'
  return 'text-slate-600 dark:text-slate-400'
}

function tripTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-200'
  if (tt === 'point_to_point') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (tt === 'business') return 'bg-violet-100 text-violet-900 dark:bg-violet-950/50 dark:text-violet-100'
  if (tt === 'cargo') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function onKpiQuickFilter(payload) {
  const next = payload?.status ?? ''
  // Toggle off if the active status card is clicked again.
  setStatusFilter(filters.status === next ? '' : next)
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
const items = ref([])
const meta = ref({})
const costsDatagridRef = ref(null)
const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useVisibleFilterControls(COSTS_FILTER_CONTROLS, 'va-dieuvan.costs.visible-filters.v1')

const {
  visibleFilters: bpVisibleFilters,
  hasFilterRow: hasBpFilterRow,
  showFilterPanelDd: showBpFilterPanelDd,
  openFilterPanel: openBpFilterPanel,
  closeFilterPanel: closeBpFilterPanel,
} = useVisibleFilterControls(BP_FILTER_CONTROLS, 'va-dieuvan.costs.bp.visible-filters.v1')

const bpFilterControlDefs = computed(() =>
  BP_FILTER_CONTROLS.map((fd) => ({
    key: fd.key,
    label: t(BP_FILTER_VIS_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

function toggleBpFilterPanel() {
  openBpFilterPanel()
}

const filterControlDefs = computed(() =>
  COSTS_FILTER_CONTROLS.map((fd) => ({
    key: fd.key,
    label: t(COSTS_FILTER_VIS_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

// Column visibility dropdown (separate panel)
const showColPanelDd = ref(false)
function closeColPanel() {
  showColPanelDd.value = false
}
function toggleColPanel() {
  closeFilterPanel()
  showColPanelDd.value = !showColPanelDd.value
}
function toggleFilterPanel() {
  closeColPanel()
  openFilterPanel()
}

const colVisible = reactive(defaultColVisibility())

const bpLoading = ref(false)
const bpLines = ref([])
const bpMeta = ref({ total: 0 })
const bpSearchQ = ref('')
const bpFilters = reactive({
  trip_id: '',
  from: '',
  to: '',
})

const searchQ = ref('')

// ── Notes tab state ───────────────────────────────────────────────
const notesLoading = ref(false)
const notesList = ref([])
const notesMeta = ref({ total: 0, current_page: 1, last_page: 1 })
const notesPage = ref(1)
const notesSearchQ = ref('')
const newCostNote = ref('')
const addingCostNote = ref(false)
const costNoteMsg = ref('')
const costNoteMsgIsError = ref(false)
/** @type {import('vue').Ref<number | null>} */
const deletingNoteId = ref(null)

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
  if (!c?.id) return
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

const canAddCostNote = computed(() =>
  auth.hasAnyPermission(['trip.record.create', 'trip.cost.reconcile', 'trip.cost.notes.create']),
)

const costAmountDisplay = computed(() => formatVndDigitsInput(costAmountDigits.value))

watch(extraCostTypes, (v) => {
  try {
    localStorage.setItem(EXTRA_TYPES_STORAGE_KEY, JSON.stringify(v))
  } catch {
    /* ignore */
  }
}, { deep: true })

// ── Filter option lists ───────────────────────────────────────────
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

function tableColLabel(colId) {
  const keys = {
    provider: 'col_provider',
    submitter: 'col_submitter',
    fleet: 'col_fleet_source',
  }
  const k = keys[colId]
  return k ? t(`costs_page.${k}`) : colId
}

const colControlDefs = computed(() => COL_IDS.map((id) => ({ id, label: tableColLabel(id) })))

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

function bpRowKey(row) {
  return `${row.trip_id}-${row.line_no}`
}

function bpDepartDisplay(row) {
  return formatDateDMYDisplay(row.depart_at || row.trip_depart_at)
}

const bpNarrativeExpandedKeys = ref(new Set())

function isBpNarrativeExpanded(row) {
  return bpNarrativeExpandedKeys.value.has(bpRowKey(row))
}

function toggleBpNarrativeExpanded(row) {
  const key = bpRowKey(row)
  const next = new Set(bpNarrativeExpandedKeys.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  bpNarrativeExpandedKeys.value = next
}

function bpNormalizeSpaces(raw) {
  return String(raw ?? '').replace(/\s+/g, ' ').trim()
}

function formatBpNarrative(raw) {
  const s = bpNormalizeSpaces(raw)
  if (!s) return ''
  return s
    .replace(/\s+(?=Ngày\s+\d)/gi, '\n\n')
    .replace(/\s+(\d{1,2}g\d{2}:)/gi, '\n$1')
}

function bpNarrativeHeadline(raw) {
  const s = bpNormalizeSpaces(raw)
  if (!s) return null
  const daySplit = s.match(/^(.{8,120}?)(?=\s+Ngày\s+\d)/i)
  if (daySplit) return daySplit[1].trim()
  if (s.length <= 72) return s
  return `${s.slice(0, 69)}…`
}

function bpNarrativeContent(row) {
  const formatted = formatBpNarrative(row.personnel_label)
  const parts = formatted.split('\n\n').map((p) => p.trim()).filter(Boolean)
  if (parts.length > 1) {
    return { headline: parts[0], body: parts.slice(1).join('\n\n') }
  }
  const headline = bpNarrativeHeadline(row.personnel_label)
  const norm = bpNormalizeSpaces(row.personnel_label)
  if (headline && norm.length > headline.length + 24) {
    const bodyFromNorm = norm.slice(headline.length).trim()
    return {
      headline,
      body: bodyFromNorm ? formatBpNarrative(bodyFromNorm) : formatted,
    }
  }
  return { headline: null, body: formatted }
}

function bpNarrativeNeedsCollapse(row) {
  const { body } = bpNarrativeContent(row)
  return body.length > 280 || body.split('\n').length > 6
}

function bpTextsEqual(a, b) {
  return bpNormalizeSpaces(a).toLowerCase() === bpNormalizeSpaces(b).toLowerCase()
}

function bpWaypointShort(row) {
  const wp = displayTextOrNull(row.waypoint)
  if (!wp || bpIsPlaceholderWaypoint(wp)) return null
  const personnel = displayTextOrNull(row.personnel_label)
  if (personnel && bpTextsEqual(wp, personnel)) return null
  if (bpNormalizeSpaces(wp).length > 100) return null
  return wp
}

function bpIsPlaceholderWaypoint(value) {
  const s = bpNormalizeSpaces(value).toLowerCase()
  return (
    s === 'không có' ||
    s === 'khong co' ||
    s === 'n/a' ||
    s === 'na' ||
    s === 'none' ||
    s === 'null'
  )
}

function bpShowGuestsBadge(row) {
  const g = String(row.guests ?? '').trim()
  return Boolean(g && g !== '1')
}

function bpRouteDisplay(row) {
  return bpRoutePrimary(row) || bpNarrativeHeadline(row.personnel_label)
}

const filteredTripsForPicker = computed(() => {
  const q = tripPickerSearch.value
  const list = tripOptionsRaw.value
  if (!q.trim()) return list
  return list.filter((tripRow) => tripMatchesSearch(tripRow, q))
})

const bpDisplayedLines = computed(() => {
  const q = bpSearchQ.value.trim().toLowerCase()
  const list = bpLines.value
  if (!q) return list
  return list.filter((row) => {
    const code = formatTripCode(row.trip_id).toLowerCase()
    const personnel = String(row.personnel_label ?? '').toLowerCase()
    const route = String(bpRoutePrimary(row) ?? '').toLowerCase()
    const req = String(row.requester_name ?? '').toLowerCase()
    const guests = String(row.guests ?? '').toLowerCase()
    return (
      code.includes(q) ||
      personnel.includes(q) ||
      route.includes(q) ||
      req.includes(q) ||
      guests.includes(q)
    )
  })
})

const bpActiveFilterCount = computed(() => {
  let n = 0
  if (bpFilters.from || bpFilters.to) n++
  if (bpFilters.trip_id) n++
  return n
})

// ── Displayed items & KPI ─────────────────────────────────────────
const displayedItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  const list = items.value.filter((c) => passesClientRowFiltersForCost(c))
  if (!q) return list
  return list.filter((c) => {
    const d = String(c.description ?? '').toLowerCase()
    const creator = String(c.creator?.name ?? '').toLowerCase()
    const submitter = String(costSubmitterLabel(c) ?? '').toLowerCase()
    const ty = String(c.type ?? '').toLowerCase()
    const trip = String(c.trip_id ?? '').toLowerCase()
    const tripCode = costRowReferenceLabel(c).toLowerCase()
    const prov = costProviderName(c).toLowerCase()
    const fleet = fleetModeLabel(tripFleetModeFromCost(c)).toLowerCase()
    return (
      d.includes(q) ||
      creator.includes(q) ||
      submitter.includes(q) ||
      ty.includes(q) ||
      trip.includes(q) ||
      tripCode.includes(q) ||
      prov.includes(q) ||
      fleet.includes(q)
    )
  })
})

const kpiSummary = computed(() => {
  const all = displayedItems.value
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
const tableBusy = computed(() => loading.value)

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

function formatDateDMYDisplay(iso) {
  if (isEmptyDisplay(iso)) return t('costs_page.empty_date')
  const formatted = formatDateDMY(iso)
  return isEmptyDisplay(formatted) ? t('costs_page.empty_date') : formatted
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

// ── Filter actions ───────────────────────────────────────────────
function setStatusFilter(val) {
  filters.status = val
  filters.page = 1
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

function onBpFilterDateChange() {
  reloadBp()
}

function onBpTripFilterChange() {
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
  closeFilterPanel()
  closeColPanel()
  reload()
}

// ── API reload ───────────────────────────────────────────────────
async function reload() {
  loading.value = true
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
    const res = await listTripCosts(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
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

function resetBpFilters() {
  bpFilters.trip_id = ''
  bpFilters.from = ''
  bpFilters.to = ''
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

// ── Notes tab helpers ─────────────────────────────────────────────
const notesFiltered = computed(() => {
  const q = notesSearchQ.value.trim().toLowerCase()
  if (!q) return notesList.value
  return notesList.value.filter((n) => {
    const body = String(n.body ?? n.message ?? '').toLowerCase()
    const author = String(n.creator?.name ?? n.user?.name ?? '').toLowerCase()
    return body.includes(q) || author.includes(q)
  })
})

async function reloadNotes() {
  notesLoading.value = true
  try {
    const res = await listCostNotes({ page: notesPage.value, per_page: 20 })
    notesList.value = res.items ?? (Array.isArray(res) ? res : [])
    notesMeta.value = res.meta ?? { total: notesList.value.length, current_page: 1, last_page: 1 }
  } catch {
    notesList.value = []
  } finally {
    notesLoading.value = false
  }
}

async function submitCostNote() {
  costNoteMsg.value = ''
  costNoteMsgIsError.value = false
  const body = newCostNote.value.trim()
  if (!body) return
  addingCostNote.value = true
  try {
    await addCostNote({ body }, { idempotencyKey: newIdempotencyKey() })
    newCostNote.value = ''
    costNoteMsg.value = t('costs_page.notes_add_ok')
    await reloadNotes()
  } catch (e) {
    costNoteMsgIsError.value = true
    costNoteMsg.value = e?.response?.data?.message ?? t('costs_page.notes_add_err')
  } finally {
    addingCostNote.value = false
  }
}

async function deleteCostNoteItem(id) {
  if (!id || deletingNoteId.value != null) return
  deletingNoteId.value = id
  try {
    await deleteCostNote(id)
    await reloadNotes()
  } catch (e) {
    showAppErrorFromApi(e, t('costs_page.notes_delete_err'))
  } finally {
    deletingNoteId.value = null
  }
}

function notePageChange(delta) {
  const target = (notesMeta.value.current_page ?? 1) + delta
  notesPage.value = target
  reloadNotes()
}

function formatNoteTime(iso) {
  if (!iso) return ''
  try {
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-GB', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(iso))
  } catch {
    return iso
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
    loadTripPickerOptions()
    reloadBp()
    return
  }
  if (tab === 'notes') {
    notesPage.value = 1
    reloadNotes()
    return
  }
  if (tab === 'standalone' || tab === 'all_trips') {
    filters.page = 1
    reload()
  }
})

// ── Lifecycle ────────────────────────────────────────────────────
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

onMounted(async () => {
  loadExtraCostTypesFromStorage()
  loadColVisibility()
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

/* ── Filter strip (business-personnel tab) ────────────────────── */
.cv-filter-strip {
  @apply flex flex-wrap items-center gap-x-2 gap-y-2;
}

.cv-date-input {
  @apply h-8 rounded-lg border-0 bg-white px-2 text-xs text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600;
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
</style>
