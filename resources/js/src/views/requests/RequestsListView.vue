<template>
  <div class="space-y-6">
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
      :request-status="filters.request_status"
      :only-trashed="filters.only_trashed"
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

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.from"
            :placeholder="t('requests_page.filter_depart_from')"
            :max-date="filters.to || null"
            input-id="requests-filter-from"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.to"
            :placeholder="t('requests_page.filter_depart_to')"
            :min-date="filters.from || null"
            input-id="requests-filter-to"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.trash">
          <select
            :value="filters.only_trashed ? '1' : ''"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_vis_trash')"
            data-testid="requests-filter-trash"
            @change="onTrashFilterSelect($event.target.value)"
          >
            <option value="">{{ t('requests_page.filter_vis_trash') }}</option>
            <option value="1">{{ t('requests_page.tab_trash') }}</option>
          </select>
        </DatagridFilterField>

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

      <div class="border-t border-slate-100 dark:border-slate-700">
      <div
        v-if="canBulkTrash && selectedIds.length"
        class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/50 px-3 py-2 dark:border-slate-700"
      >
        <span class="text-sm text-slate-600 dark:text-slate-400">
          {{ t('requests_page.selected_count', { n: selectedIds.length }) }}
        </span>
        <button
          v-if="!isTrashTab"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-medium text-rose-800 shadow-sm transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="bulkSubmitting"
          data-testid="requests-bulk-move-trash"
          @click="openBulkConfirm('delete')"
        >
          <TrashIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('requests_page.bulk_move_trash') }}
        </button>
        <template v-else>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-white px-3 py-1.5 text-sm font-medium text-teal-900 shadow-sm transition hover:bg-teal-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="bulkSubmitting"
            data-testid="requests-bulk-restore"
            @click="openBulkConfirm('restore')"
          >
            <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ t('requests_page.bulk_restore') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-900 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="bulkSubmitting"
            data-testid="requests-bulk-force-delete"
            @click="openBulkConfirm('force_delete')"
          >
            <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ t('requests_page.bulk_force_delete') }}
          </button>
        </template>
      </div>

      <div v-if="loading" class="px-4 py-5">
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
        <div class="space-y-3 p-3 sm:p-4 md:space-y-4">
          <div
            v-if="canBulkTrash && selectableIdsOnPage.length"
            class="flex items-center gap-2 px-1"
          >
            <input
              id="requests-cards-select-all"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
              :checked="allSelectableOnPageChecked"
              :aria-label="t('requests_page.col_select')"
              @change="onToggleHeaderCheckbox"
            />
            <label for="requests-cards-select-all" class="cursor-pointer text-sm text-slate-600 dark:text-slate-400">
              {{ t('requests_page.col_select') }}
            </label>
          </div>

          <article
            v-for="r in items"
            :key="r.id"
            class="overflow-hidden rounded-2xl border bg-white shadow-sm transition dark:bg-slate-900/50"
            :class="requestCardClass(r)"
            :data-testid="`requests-row-${r.id}`"
          >
            <!-- Header -->
            <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 gap-3 sm:gap-4">
                  <div
                    v-if="canBulkTrash && (isTrashTab || canDeleteRow(r))"
                    class="pt-1.5"
                    @click.stop
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                      :checked="selectedIds.includes(r.id)"
                      :aria-label="t('requests_page.col_select')"
                      @change="toggleRowSelected(r.id, $event.target.checked)"
                    />
                  </div>
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
                    :class="requestTypeIconWrap(r.trip_type)"
                  >
                    <component
                      :is="requestTypeIcon(r.trip_type)"
                      class="h-6 w-6"
                      :class="requestTypeIconColor(r.trip_type)"
                      aria-hidden="true"
                    />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <RouterLink
                        :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                        class="font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                        :data-testid="`requests-card-link-${r.id}`"
                      >
                        {{ displayRequestCode(r) }}
                      </RouterLink>
                      <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="requestTypeBadgeClass(r.trip_type)"
                      >
                        {{ labelTripType(r.trip_type) || t('requests_page.empty_trip_type') }}
                      </span>
                      <span
                        v-if="r.is_urgent"
                        class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-200"
                      >
                        {{ t('requests_page.filter_priority_urgent') }}
                      </span>
                      <span
                        v-if="r.dispatch_request_template_id"
                        class="inline-flex items-center gap-0.5 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-100"
                      >
                        <ArrowPathIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        {{ t('requests_page.badge_recurring') }}
                      </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 xl:grid-cols-3">
                      <div class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                          {{ t('requests_page.meta_created') }}
                        </dt>
                        <dd class="mt-0.5 font-medium tabular-nums text-slate-800 dark:text-slate-200">
                          {{ formatRequestDateTime(r.created_at) }}
                        </dd>
                      </div>
                      <div class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                          {{ t('requests_page.mobile_meta_channel') }}
                        </dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                          {{ r.source_channel ? labelSourceChannel(r.source_channel) : t('requests_page.empty_channel') }}
                        </dd>
                      </div>
                      <div class="min-w-0">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                          {{ t('requests_page.col_paper') }}
                        </dt>
                        <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                          {{ r.paper_status ? labelPaperStatus(r.paper_status) : t('requests_page.empty_paper') }}
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
                  <StatusBadge :status="r.status" />
                  <span
                    v-if="r.trip?.status"
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                  >
                    {{ labelTripStatus(r.trip.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Body grid: origin · destination · requester · sla -->
            <div class="grid grid-cols-1 gap-4 px-3 py-4 sm:px-4 md:grid-cols-2 md:gap-5 xl:grid-cols-4">
              <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                  <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('trips_page.col_origin') }}
                </div>
                <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
                  {{ displayPlaceLabel(r.origin, 'empty_origin') }}
                </p>
                <div class="mt-2">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('requests_page.col_depart_at') }}
                  </p>
                  <p class="mt-0.5 text-sm font-semibold tabular-nums text-teal-800 dark:text-teal-300">
                    {{ formatDepartDate(r.depart_at) }}
                  </p>
                </div>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
                  <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('trips_page.col_destination') }}
                </div>
                <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
                  {{ displayPlaceLabel(r.destination, 'empty_destination') }}
                </p>
                <div class="mt-2">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ t('requests_page.col_arrive_by') }}
                  </p>
                  <p class="mt-0.5 text-sm font-semibold tabular-nums text-rose-800 dark:text-rose-300">
                    {{ formatArriveByDate(r.arrive_by) }}
                  </p>
                </div>
              </div>
              <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('requests_page.col_requester') }}
                </p>
                <div class="mt-2 flex items-start gap-2">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200">
                    {{ requesterInitials(r.requester?.name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                      {{ r.requester?.name || t('requests_page.empty_requester') }}
                    </p>
                    <p v-if="r.paper_reference" class="mt-1 truncate text-xs text-slate-600 dark:text-slate-400" :title="r.paper_reference">
                      {{ r.paper_reference }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('requests_page.col_sla') }}
                </p>
                <div class="mt-2 text-sm">
                  <span v-if="slaCell(r).kind === 'ok'" class="inline-flex items-center gap-1.5 font-medium text-emerald-700 dark:text-emerald-400">
                    <CheckCircleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ t('requests_page.sla_on_track') }}
                  </span>
                  <span v-else-if="slaCell(r).kind === 'warn'" class="inline-flex items-center gap-1.5 font-medium text-amber-800 dark:text-amber-300">
                    <ExclamationTriangleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ slaCell(r).text }}
                  </span>
                  <span v-else class="text-slate-500 dark:text-slate-400">{{ tripTimelineHint(r) }}</span>
                </div>
                <p
                  v-if="requestNotesListCell(r) !== t('requests_page.empty_notes')"
                  class="mt-3 line-clamp-3 border-t border-slate-200/80 pt-2 text-xs leading-relaxed text-slate-600 dark:border-slate-700 dark:text-slate-400"
                  :title="requestNotesListCell(r)"
                >
                  <span class="font-semibold text-slate-700 dark:text-slate-300">{{ t('requests_page.col_notes') }}:</span>
                  {{ requestNotesListCell(r) }}
                </p>
              </div>
            </div>

            <!-- Footer: meta + actions -->
            <div class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-4 sm:py-3.5 md:flex-row md:items-center md:justify-between">
              <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
                <span class="inline-flex items-center gap-1.5">
                  <UserPlusIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                  {{ passengerSummary(r) }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <ArrowsRightLeftIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                  {{ labelTripType(r.trip_type) || t('requests_page.empty_trip_type') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <ClockIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                  {{ tripTimelineHint(r) }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                <template v-if="isTrashTab">
                  <button
                    v-if="canBulkTrash"
                    type="button"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100 sm:min-h-0 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-100"
                    @click="openBulkConfirm('restore', [r.id])"
                  >
                    <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('requests_page.bulk_restore') }}
                  </button>
                  <button
                    v-if="canBulkTrash"
                    type="button"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm font-medium text-red-800 shadow-sm hover:bg-red-50 sm:min-h-0 dark:border-red-900 dark:bg-slate-900 dark:text-red-300"
                    @click="openBulkConfirm('force_delete', [r.id])"
                  >
                    <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('requests_page.bulk_force_delete') }}
                  </button>
                </template>
                <template v-else>
                  <RouterLink
                    :to="{ name: 'requestDetail', params: { id: String(r.id) } }"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                  >
                    <EyeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('requests_page.view') }}
                  </RouterLink>
                  <a
                    v-if="requestMapsHref(r)"
                    :href="requestMapsHref(r)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100 sm:min-h-0 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-100"
                  >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('trips_page.action_track') }}
                  </a>
                  <button
                    v-if="r.status === 'draft'"
                    type="button"
                    class="col-span-2 inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-va-800 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-va-900 sm:col-span-1 sm:min-h-0"
                    @click="openDraftEditor(r.id)"
                  >
                    <PencilSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ t('requests_page.edit') }}
                  </button>
                </template>
              </div>
            </div>
          </article>
        </div>

        </template>
      </div>
      </div>
    </div>

    <nav
      v-if="!loading && items.length"
      class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between"
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
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  ArrowTopRightOnSquareIcon,
  TrashIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  TruckIcon,
  UserPlusIcon,
  XMarkIcon,
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
import { formatListDateTime } from '../../util/datetime'
import { formatDispatchRequestRefCode } from '../../util/portalRequestFormat'

const { t, locale } = useI18n()
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

const searchInput = ref('')
let searchDebounce = null
const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const REQUEST_FILTER_CONTROLS = [
  { key: 'trip_type', label: '', default: false },
  { key: 'date_range', label: '', default: false },
  { key: 'trash', label: '', default: false },
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
} = useVisibleFilterControls(REQUEST_FILTER_CONTROLS, 'va-dieuvan.requests.visible-filters.v2')

const FILTER_CONTROL_LABEL_KEYS = {
  trip_type: 'requests_page.filter_vis_trip_type',
  date_range: 'requests_page.filter_vis_depart',
  trash: 'requests_page.filter_vis_trash',
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

const showExportMenu = ref(false)
const requestsDatagridRef = ref(null)
useDetailsAutoCloseWithin(requestsDatagridRef)

function closeToolbarMenusExceptFilter() {
  showExportMenu.value = false
}

function toggleExportMenu() {
  closeFilterPanel()
  showExportMenu.value = !showExportMenu.value
}

function onDatagridDocMouseDown(ev) {
  const t = ev.target
  if (!t || typeof t.closest !== 'function') return
  if (t.closest('[data-requests-export-panel]')) return
  showExportMenu.value = false
}

function onKpiQuickFilter({ tab, sla }) {
  filters.sla_risk_only = !!sla
  filters.only_trashed = false
  filters.trip_status_filter = ''
  if (tab === 'pending') {
    filters.request_status = 'pending'
  } else if (tab === 'approved') {
    filters.request_status = 'approved'
  } else if (!sla) {
    filters.request_status = ''
  }
  filters.page = 1
  syncListScopeToRoute()
  reload()
}

function onTrashFilterSelect(raw) {
  filters.only_trashed = raw === '1'
  if (filters.only_trashed) {
    filters.request_status = ''
    filters.trip_status_filter = ''
  }
  filters.page = 1
  syncListScopeToRoute()
  syncRoutePageAfterReset()
  reload()
}

function syncListScopeToRoute() {
  const q = { ...route.query }
  delete q.page
  delete q.status
  delete q.trip_status
  delete q.trash
  if (filters.only_trashed) {
    q.trash = '1'
  } else if (filters.trip_status_filter) {
    q.trip_status = filters.trip_status_filter
  } else if (filters.request_status) {
    q.status = filters.request_status
  }
  router.replace({ query: q })
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

/** Chỉ hiển thị ghi chú do người dùng nhập; bản cũ lưu BM.03 trong `notes` thì để trống (xem chi tiết). */
function requestNotesListCell(r) {
  const n = String(r?.notes ?? '').trim()
  if (!n) return t('requests_page.empty_notes')
  if (isLegacyBm03NotesBlock(n)) return t('requests_page.empty_notes')
  return n
}

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

watch(items, () => {
  selectedIds.value = selectedIds.value.filter((id) => items.value.some((r) => r.id === id))
})

const REQUEST_SORT_VALUES = ['created_desc', 'created_asc', 'depart_desc', 'depart_asc', 'id_desc']

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
  only_trashed: false,
  sla_risk_only: false,
  recurring_only: false,
  extracurricular_only: false,
  student_count_submitted: undefined,
  per_page: 10,
  page: 1,
  sort: 'created_desc',
})

const isTrashTab = computed(() => filters.only_trashed)

watch(
  () => filters.only_trashed,
  () => {
    selectedIds.value = []
  },
)

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.only_trashed) n++
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
        csvEscapeCell(displayRequestCode(r)),
        csvEscapeCell(displayRoute(r)),
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

function buildRouteQueryFromState() {
  const out = {}
  if (filters.only_trashed) out.trash = '1'
  else if (filters.trip_status_filter) out.trip_status = filters.trip_status_filter
  else if (filters.request_status) out.status = filters.request_status

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

function displayRequestCode(r) {
  return formatDispatchRequestRefCode(r) || `REQ-${r?.id ?? ''}`
}

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function dateLocaleKey() {
  return locale.value === 'en' ? 'en' : 'vi'
}

function formatRequestDateTime(v) {
  if (!v) return t('requests_page.empty_datetime')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('requests_page.empty_datetime')
}

function formatDepartDate(v) {
  if (!v) return t('requests_page.empty_depart_at')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('requests_page.empty_depart_at')
}

function formatArriveByDate(v) {
  if (!v) return t('requests_page.empty_arrive_by')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('requests_page.empty_arrive_by')
}

function displayPlaceLabel(value, emptyKey = 'empty_route') {
  const s = value != null ? String(value).trim() : ''
  return s || t(`requests_page.${emptyKey}`)
}

function displayRoute(r) {
  const o = String(r?.origin ?? '').trim()
  const d = String(r?.destination ?? '').trim()
  if (!o && !d) return t('requests_page.empty_route')
  if (!o) return `${t('requests_page.empty_origin')} → ${d}`
  if (!d) return `${o} → ${t('requests_page.empty_destination')}`
  return `${o} → ${d}`
}

function formatShortDate(v) {
  return formatRequestDateTime(v)
}

// ── Card visuals (mirror trips list cards) ────────────────────────────
function requestTypeIcon(tt) {
  if (tt === 'door_to_door') return TruckIcon
  if (tt === 'point_to_point') return MapPinIcon
  if (tt === 'business') return BriefcaseIcon
  if (tt === 'cargo') return CubeIcon
  return MapPinIcon
}

function requestTypeIconColor(tt) {
  if (tt === 'door_to_door') return 'text-sky-600 dark:text-sky-400'
  if (tt === 'point_to_point') return 'text-emerald-600 dark:text-emerald-400'
  if (tt === 'business') return 'text-amber-600 dark:text-amber-400'
  if (tt === 'cargo') return 'text-orange-600 dark:text-orange-400'
  return 'text-slate-600 dark:text-slate-400'
}

function requestTypeIconWrap(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 dark:bg-sky-950/40'
  if (tt === 'point_to_point') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (tt === 'business') return 'bg-amber-100 dark:bg-amber-950/40'
  if (tt === 'cargo') return 'bg-orange-100 dark:bg-orange-950/40'
  return 'bg-slate-100 dark:bg-slate-800/60'
}

function requestTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (tt === 'point_to_point') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (tt === 'business') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  if (tt === 'cargo') return 'bg-orange-100 text-orange-900 dark:bg-orange-950/50 dark:text-orange-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function requesterInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function passengerSummary(r) {
  const n = dispatchRequestDisplayPassengerCount(r)
  if (n > 0) return t('requests_page.passengers', { n })
  if (r.trip_type === 'cargo') return t('requests_page.cargo')
  return t('requests_page.no_passenger_info')
}

function requestMapsHref(r) {
  const o = String(r?.origin ?? '').trim()
  const d = String(r?.destination ?? '').trim()
  if (!o || !d) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
}

function requestCardClass(r) {
  if (isTrashTab.value) {
    if (r.is_urgent) return 'border-amber-300 bg-amber-50/40 dark:border-amber-900/60'
    return 'border-slate-200/90 bg-slate-50/70 dark:border-slate-700'
  }
  if (r.is_urgent) {
    return 'border-l-4 border-l-amber-500 border-y-slate-200/90 border-r-slate-200/90 dark:border-y-slate-700 dark:border-r-slate-700'
  }
  return 'border-slate-200/90 dark:border-slate-700'
}

function tripTimelineHint(r) {
  if (r.trip?.status) {
    return labelTripStatus(r.trip.status)
  }
  if (r.status === 'pending') return t('requests_page.hint_await_assign')
  if (r.status === 'approved' && !r.trip) return t('requests_page.hint_no_trip')
  if (r.paper_status) return labelPaperStatus(r.paper_status)
  return t('requests_page.empty_timeline_hint')
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

function buildListParams() {
  const params = { ...filters }
  delete params.priority
  params.q = searchInput.value.trim() || undefined
  if (filters.priority === 'urgent') {
    params.is_urgent = true
  }

  delete params.request_status
  delete params.trip_status_filter
  delete params.only_trashed

  if (filters.only_trashed) {
    params.only_trashed = 1
    params.status = undefined
    params.trip_status = undefined
  } else if (filters.request_status) {
    params.status = filters.request_status
    params.trip_status = filters.trip_status_filter || undefined
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
    filters.only_trashed = false
    filters.trip_status_filter = ''
  }
  onFilterChange()
  syncListScopeToRoute()
}

function onTripStatusFilterChange() {
  if (filters.trip_status_filter) {
    filters.only_trashed = false
    filters.request_status = ''
  }
  onFilterChange()
  syncListScopeToRoute()
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
  filters.trip_type = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.from = ''
  filters.to = ''
  filters.priority = ''
  filters.request_status = ''
  filters.trip_status_filter = ''
  filters.only_trashed = false
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
  filters.only_trashed = q.trash === '1' || q.trash === 'true'
  filters.trip_status_filter = ''
  filters.request_status = ''
  if (filters.only_trashed) {
    /* scope from trash */
  } else if (typeof q.trip_status === 'string') {
    filters.trip_status_filter = q.trip_status
  } else if (
    typeof q.status === 'string' &&
    ['draft', 'pending', 'price_filled', 'approved', 'rejected', 'cancelled'].includes(q.status)
  ) {
    filters.request_status = q.status
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
