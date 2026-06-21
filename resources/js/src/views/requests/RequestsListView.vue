<template>
  <div
    class="space-y-6"
    :class="!loading && items.length ? 'pb-[4.75rem] sm:pb-[4.25rem]' : ''"
  >
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">
          {{ t('requests_page.title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('requests_page.subtitle') }}</p>
      </div>
      <RouterLink
        to="/dispatch-requests/new"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-va-900"
        data-testid="requests-create-btn"
      >
        <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
        {{ t('requests_page.create') }}
      </RouterLink>
    </div>

    <RequestsSummaryBar
      :stats="stats"
      :active-tab="activeTab"
      :sla-risk-only="filters.sla_risk_only"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="requestsDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="requests-list-search"
              :placeholder="t('requests_page.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              @enter="applySearchNow"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('requests_page.filter_show_controls_title')"
              :hint="t('requests_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="requests-toolbar-filter"
                  @click="openFilterPanel(closeToolbarMenusExceptFilter)"
                >
                  {{ t('requests_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'req-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`requests-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30"
                  :data-testid="`requests-filter-vis-${fd.key}`"
                />
                <label
                  :for="`requests-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <div class="relative" data-requests-columns-panel>
              <DatagridToolbarActionButton
                icon="columns"
                :active="showColumnsMenu"
                test-id="requests-toolbar-columns"
                @click="toggleColumnsMenu"
              >
                {{ t('requests_page.toolbar_columns') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showColumnsMenu"
                class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[240px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
                @click.stop
              >
                <ul class="max-h-[min(50vh,320px)] space-y-2 overflow-y-auto text-slate-700 dark:text-slate-300">
                  <li v-for="opt in requestColumnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                    <input
                      :id="`req-col-${opt.id}`"
                      type="checkbox"
                      class="rounded border-slate-300 text-va-800 focus:ring-va-700/30"
                      :checked="requestColumnVisible[opt.id] !== false"
                      @change="setRequestColumn(opt.id, $event.target.checked)"
                    />
                    <label :for="`req-col-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
                  </li>
                </ul>
              </div>
            </div>

            <div class="relative" data-requests-export-panel>
              <DatagridToolbarActionButton
                icon="export"
                :disabled="exportingCsv || !(meta.total ?? 0)"
                :active="showExportMenu"
                test-id="requests-toolbar-export"
                @click="toggleExportMenu"
              >
                {{ exportingCsv ? t('requests_page.export_csv_busy') : t('requests_page.toolbar_export') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showExportMenu"
                class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[200px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  type="button"
                  class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                  data-testid="requests-export-csv"
                  @click="exportRequestsCsv(); showExportMenu = false"
                >
                  CSV
                </button>
              </div>
            </div>
          </div>

          <div class="ml-auto flex shrink-0 items-center">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
              :title="t('requests_page.filter_clear_all')"
              data-testid="requests-reset-filters"
              @click="resetFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.trip_type">
          <select
            v-model="filters.trip_type"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_trip_type')"
            data-testid="requests-filter-trip-type"
            @change="onFilterChange"
          >
            <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <div v-if="visibleFilters.date_range" class="min-w-0 w-full sm:col-span-2 xl:col-span-2">
          <div class="mb-2 flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100"
              @click="applyDepartRangePreset('week')"
            >
              {{ t('requests_page.filter_depart_this_week') }}
            </button>
            <button
              type="button"
              class="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100"
              @click="applyDepartRangePreset('month')"
            >
              {{ t('requests_page.filter_depart_this_month') }}
            </button>
          </div>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3">
            <FilterDatePicker
              v-model="filters.from"
              :placeholder="t('requests_page.filter_depart_range')"
              :max-date="filters.to || null"
              input-id="requests-filter-from"
              @update:model-value="onFilterChange"
            />
            <FilterDatePicker
              v-model="filters.to"
              :placeholder="t('requests_page.filter_depart_range')"
              :min-date="filters.from || null"
              input-id="requests-filter-to"
              @update:model-value="onFilterChange"
            />
          </div>
        </div>

        <DatagridFilterField v-if="visibleFilters.channel">
          <select
            v-model="filters.source_channel"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_channel')"
            @change="onFilterChange"
          >
            <option v-for="opt in channelFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.paper">
          <select
            v-model="filters.paper_status"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_paper')"
            @change="onFilterChange"
          >
            <option v-for="opt in paperFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.priority">
          <select
            v-model="filters.priority"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_priority')"
            @change="onFilterChange"
          >
            <option v-for="opt in priorityFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.request_status">
          <select
            v-model="filters.request_status"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_request_status')"
            @change="onRequestStatusFilterChange"
          >
            <option v-for="opt in requestStatusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.trip_status">
          <select
            v-model="filters.trip_status_filter"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_trip_status')"
            @change="onTripStatusFilterChange"
          >
            <option v-for="opt in tripStatusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.sla_risk">
          <select
            :value="filters.sla_risk_only ? '1' : ''"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_vis_sla_risk')"
            @change="onSlaRiskSelect($event.target.value)"
          >
            <option value="">{{ t('requests_page.filter_vis_sla_risk') }}</option>
            <option value="1">{{ t('requests_page.sla_filter_chip') }}</option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.sort">
          <select
            :value="filters.sort"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.sort_label')"
            @change="onSortChange($event.target.value)"
          >
            <option v-for="opt in sortSelectOptionsWithLabel" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model.number="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_per_page')"
            @change="onFilterChange"
          >
            <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.recurring">
          <select
            :value="filters.recurring_only ? '1' : ''"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_vis_recurring')"
            @change="onRecurringFilterSelect($event.target.value)"
          >
            <option v-for="opt in recurringFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.extracurricular">
          <select
            :value="extracurricularFilterSelectValue"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_vis_extracurricular')"
            @change="onExtracurricularFilterSelect($event.target.value)"
          >
            <option v-for="opt in extracurricularFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.student_count && filters.extracurricular_only">
          <select
            :value="studentCountFilterSelectValue"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_vis_student_count')"
            @change="onStudentCountFilterSelect($event.target.value)"
          >
            <option v-for="opt in studentCountFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <div v-if="activeFilterCount > 0" class="col-span-full flex justify-end">
          <button type="button" class="text-xs font-medium text-va-800 hover:underline" @click="resetFilters">
            {{ t('requests_page.filter_clear_all') }}
          </button>
        </div>
      </div>
    </div>


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
      </div>

      <div v-if="loading" class="border-t border-slate-100 px-4 py-5">
        <div class="mb-3 h-4 w-40 animate-pulse rounded bg-slate-100" />
        <div class="space-y-2">
          <div v-for="n in 7" :key="n" class="h-14 animate-pulse rounded-lg bg-slate-100" />
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
        <table class="min-w-full divide-y divide-slate-200 text-left text-base">
          <thead class="bg-slate-50/80">
            <tr>
              <th v-if="canBulkTrash" class="w-11 px-4 py-3.5">
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                  :checked="allSelectableOnPageChecked"
                  :disabled="!selectableIdsOnPage.length"
                  :aria-label="t('requests_page.col_select')"
                  @change="onToggleHeaderCheckbox"
                />
              </th>
              <th class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">{{ t('requests_page.col_id') }}</th>
              <th class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">{{ t('requests_page.col_trip') }}</th>
              <th v-if="requestColOn('type_channel')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_type_channel') }}
              </th>
              <th v-if="requestColOn('timeline')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_timeline') }}
              </th>
              <th v-if="requestColOn('sla')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_sla') }}
              </th>
              <th v-if="requestColOn('depart_at')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_depart_at') }}
              </th>
              <th v-if="requestColOn('arrive_by')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_arrive_by') }}
              </th>
              <th v-if="requestColOn('paper')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_paper') }}
              </th>
              <th v-if="requestColOn('requester')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_requester') }}
              </th>
              <th v-if="requestColOn('urgent')" class="px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_urgent') }}
              </th>
              <th v-if="requestColOn('notes')" class="min-w-[8rem] px-4 py-3.5 text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_notes') }}
              </th>
              <th class="min-w-[7.5rem] px-4 py-3.5 text-right text-sm font-semibold uppercase tracking-wide text-slate-600">
                {{ t('requests_page.col_actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="r in items"
              :key="r.id"
              class="transition"
              :class="[requestRowClass(r), !isTrashTab ? 'cursor-pointer' : '']"
              :tabindex="!isTrashTab ? 0 : undefined"
              :data-testid="`requests-row-${r.id}`"
              :aria-label="!isTrashTab ? t('requests_page.open_request_row', { id: r.id }) : undefined"
              @click="!isTrashTab && openRequestDetail(r.id)"
              @keydown.enter="!isTrashTab && openRequestDetail(r.id)"
            >
              <td v-if="canBulkTrash" class="px-4 py-3.5 align-top" :class="isTrashTab ? 'text-slate-700' : ''" @click.stop>
                <input
                  v-if="isTrashTab || canDeleteRow(r)"
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                  :checked="selectedIds.includes(r.id)"
                  @change="toggleRowSelected(r.id, $event.target.checked)"
                />
              </td>
              <td class="px-4 py-3.5 align-top">
                <div class="max-w-fit">
                  <div
                    class="flex items-center gap-1.5 text-base font-semibold text-slate-900"
                  >
                    <span
                      v-if="r.dispatch_request_template_id"
                      class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-indigo-50 px-2 py-0.5 text-sm font-bold uppercase text-indigo-900 ring-1 ring-indigo-600/20 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-500/30"
                    >
                      <ArrowPathIcon class="h-4 w-4 shrink-0 text-indigo-700 dark:text-indigo-300" aria-hidden="true" />
                      {{ t('requests_page.badge_recurring') }}
                    </span>
                    <ExclamationTriangleIcon
                      v-if="r.is_urgent"
                      class="h-5 w-5 shrink-0 text-amber-600"
                      aria-hidden="true"
                    />
                    <RouterLink
                      :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                      class="font-mono text-base text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400"
                      @click.stop
                    >
                      REQ-{{ r.id }}
                    </RouterLink>
                  </div>
                  <div class="mt-0.5 text-sm text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                </div>
              </td>
              <td class="max-w-xs px-4 py-3.5 align-top">
                <div class="flex gap-2.5">
                  <component :is="tripTypeIcon(r.trip_type)" class="mt-0.5 h-6 w-6 shrink-0 text-teal-600" />
                  <div class="min-w-0">
                    <div class="truncate text-base font-semibold leading-snug text-slate-900">
                      {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                    </div>
                    <div class="mt-0.5 text-sm text-slate-500">
                      <span v-if="dispatchRequestDisplayPassengerCount(r)">{{ t('requests_page.passengers', { n: dispatchRequestDisplayPassengerCount(r) }) }}</span>
                      <span v-else-if="r.trip_type === 'cargo'">{{ t('requests_page.cargo') }}</span>
                      <span v-else>{{ t('requests_page.no_passenger_info') }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td v-if="requestColOn('type_channel')" class="px-4 py-3.5 align-top">
                <div class="font-medium text-slate-900">{{ labelTripType(r.trip_type) }}</div>
                <div class="mt-0.5 text-sm text-slate-500">{{ labelSourceChannel(r.source_channel) }}</div>
              </td>
              <td v-if="requestColOn('timeline')" class="px-4 py-3.5 align-top">
                <StatusBadge :status="r.status" />
                <div class="mt-1.5 text-sm text-slate-500">{{ tripTimelineHint(r) }}</div>
              </td>
              <td v-if="requestColOn('sla')" class="px-4 py-3.5 align-top text-sm">
                <span v-if="slaCell(r).kind === 'ok'" class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                  <CheckCircleIcon class="h-5 w-5" />
                  {{ t('requests_page.sla_on_track') }}
                </span>
                <span v-else-if="slaCell(r).kind === 'warn'" class="inline-flex items-center gap-1.5 font-medium text-amber-800">
                  <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                  {{ slaCell(r).text }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td v-if="requestColOn('depart_at')" class="whitespace-nowrap px-4 py-3.5 align-top text-sm font-medium tabular-nums text-slate-700">
                {{ formatDepartDate(r.depart_at) }}
              </td>
              <td v-if="requestColOn('arrive_by')" class="whitespace-nowrap px-4 py-3.5 align-top text-sm font-medium tabular-nums text-slate-700">
                {{ formatDepartDate(r.arrive_by) }}
              </td>
              <td v-if="requestColOn('paper')" class="max-w-[10rem] px-4 py-3.5 align-top text-sm">
                <div class="font-medium text-slate-800">{{ labelPaperStatus(r.paper_status) }}</div>
                <div v-if="r.paper_reference" class="mt-0.5 truncate text-slate-500" :title="r.paper_reference">
                  {{ r.paper_reference }}
                </div>
              </td>
              <td v-if="requestColOn('requester')" class="max-w-[10rem] px-4 py-3.5 align-top text-sm font-medium text-slate-700">
                <span class="truncate">{{ r.requester?.name ?? '—' }}</span>
              </td>
              <td v-if="requestColOn('urgent')" class="px-4 py-3.5 align-top">
                <span
                  v-if="r.is_urgent"
                  class="inline-flex rounded-md bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800"
                >
                  {{ t('requests_page.filter_priority_urgent') }}
                </span>
                <span v-else class="text-sm text-slate-400">—</span>
              </td>
              <td v-if="requestColOn('notes')" class="max-w-xs px-4 py-3.5 align-top text-sm leading-relaxed text-slate-600">
                <p class="line-clamp-2">{{ requestNotesListCell(r) }}</p>
              </td>
              <td class="px-4 py-3.5 align-top text-right" :class="isTrashTab ? 'text-slate-800' : ''" @click.stop>
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
          <li
            v-for="r in items"
            :key="`m-${r.id}`"
            class="px-4 py-4"
            :class="[requestRowClass(r), !isTrashTab ? 'cursor-pointer' : '']"
            :tabindex="!isTrashTab ? 0 : undefined"
            :data-testid="`requests-row-mobile-${r.id}`"
            :aria-label="!isTrashTab ? t('requests_page.open_request_row', { id: r.id }) : undefined"
            @click="!isTrashTab && openRequestDetail(r.id)"
            @keydown.enter="!isTrashTab && openRequestDetail(r.id)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 text-base font-semibold text-slate-900">
                  <span
                    v-if="r.dispatch_request_template_id"
                    class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-indigo-50 px-2 py-0.5 text-sm font-bold uppercase text-indigo-900 ring-1 ring-indigo-600/20 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-500/30"
                  >
                    <ArrowPathIcon class="h-4 w-4 shrink-0 text-indigo-700 dark:text-indigo-300" aria-hidden="true" />
                    {{ t('requests_page.badge_recurring') }}
                  </span>
                  <ExclamationTriangleIcon
                    v-if="r.is_urgent"
                    class="h-5 w-5 shrink-0 text-amber-600"
                    aria-hidden="true"
                  />
                  <RouterLink
                    :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                    class="font-mono underline decoration-slate-300 underline-offset-2 hover:text-va-800"
                    @click.stop
                  >
                    REQ-{{ r.id }}
                  </RouterLink>
                </div>
                <div class="mt-0.5 text-sm text-slate-500">{{ formatShortDate(r.created_at) }}</div>
                <div class="mt-1.5 truncate text-base font-semibold text-slate-800">
                  {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                </div>
              </div>
              <StatusBadge class="shrink-0" :status="r.status" />
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
  ExclamationTriangleIcon,
  FunnelIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  ViewColumnsIcon,
  XMarkIcon,
  AcademicCapIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import RequestsSummaryBar from '../../components/requests/RequestsSummaryBar.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import {
  bulkForceDeleteRequests,
  bulkRestoreRequests,
  bulkSoftDeleteRequests,
  cloneDispatchRequest,
  listRequests,
} from '../../api/requests'
import ExtracurricularRequestsDataTable from '../../components/requests/ExtracurricularRequestsDataTable.vue'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useAuthStore } from '../../store'
import {
  labelPaperStatus,
  labelRequestStatus,
  labelSourceChannel,
  labelTripStatus,
  labelTripType,
} from '../../util/labels'
import { isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'
import { dispatchRequestDisplayPassengerCount } from '../../util/dispatchRequestPassengers'

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
const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const REQUEST_FILTER_CONTROLS = [
  { key: 'trip_type', label: '', default: false },
  { key: 'date_range', label: '', default: false },
  { key: 'channel', label: '', default: false },
  { key: 'paper', label: '', default: false },
  { key: 'priority', label: '', default: false },
  { key: 'request_status', label: '', default: false },
  { key: 'trip_status', label: '', default: false },
  { key: 'sla_risk', label: '', default: false },
  { key: 'sort', label: '', default: false },
  { key: 'per_page', label: '', default: false },
  { key: 'recurring', label: '', default: false },
  { key: 'extracurricular', label: '', default: false },
  { key: 'student_count', label: '', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useVisibleFilterControls(REQUEST_FILTER_CONTROLS, 'va-dieuvan.requests.visible-filters.v1')

const FILTER_CONTROL_LABEL_KEYS = {
  trip_type: 'requests_page.filter_vis_trip_type',
  date_range: 'requests_page.filter_vis_depart',
  channel: 'requests_page.filter_vis_channel',
  paper: 'requests_page.filter_vis_paper',
  priority: 'requests_page.filter_vis_priority',
  request_status: 'requests_page.filter_vis_request_status',
  trip_status: 'requests_page.filter_vis_trip_status',
  sla_risk: 'requests_page.filter_vis_sla_risk',
  sort: 'requests_page.filter_vis_sort',
  per_page: 'requests_page.filter_vis_per_page',
  recurring: 'requests_page.filter_vis_recurring',
  extracurricular: 'requests_page.filter_vis_extracurricular',
  student_count: 'requests_page.filter_vis_student_count',
}

const filterControlDefs = computed(() =>
  REQUEST_FILTER_CONTROLS.map((fd) => ({
    ...fd,
    label: t(FILTER_CONTROL_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const showColumnsMenu = ref(false)
const showExportMenu = ref(false)
const requestsDatagridRef = ref(null)
useDetailsAutoCloseWithin(requestsDatagridRef)

function closeToolbarMenusExceptFilter() {
  showColumnsMenu.value = false
  showExportMenu.value = false
}

function toggleColumnsMenu() {
  showExportMenu.value = false
  closeFilterPanel()
  showColumnsMenu.value = !showColumnsMenu.value
}

function toggleExportMenu() {
  showColumnsMenu.value = false
  closeFilterPanel()
  showExportMenu.value = !showExportMenu.value
}

function onDatagridDocMouseDown(ev) {
  const t = ev.target
  if (!t || typeof t.closest !== 'function') return
  if (t.closest('[data-requests-columns-panel]')) return
  if (t.closest('[data-requests-export-panel]')) return
  showColumnsMenu.value = false
  showExportMenu.value = false
}

function onKpiQuickFilter({ tab, sla }) {
  filters.sla_risk_only = !!sla
  filters.request_status = ''
  filters.trip_status_filter = ''
  if (tab && tab !== activeTab.value) {
    setTab(tab)
  } else if (sla) {
    filters.page = 1
    syncRoutePageAfterReset()
    reload()
  } else if (tab === 'all' && activeTab.value !== 'all') {
    setTab('all')
  }
}

function onSlaRiskSelect(raw) {
  filters.sla_risk_only = raw === '1'
  onFilterChange()
}
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
function openRequestDetail(id) {
  router.push({ name: 'requestDetail', params: { id: String(id) } })
}

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

function isoDateLocal(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function applyDepartRangePreset(kind) {
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
  onFilterChange()
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
  filters.student_count_submitted = undefined
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
  }, 350)
}

watch(
  searchInput,
  () => {
    onSearchInput()
  },
)

watch(
  () => route.query,
  () => {
    applyRouteQuery()
    reload()
  },
  { deep: true },
)

onMounted(() => {
  document.addEventListener('mousedown', onDatagridDocMouseDown)
  applyRouteQuery()
  reload()
})

onActivated(() => {
  applyRouteQuery()
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onDatagridDocMouseDown)
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
