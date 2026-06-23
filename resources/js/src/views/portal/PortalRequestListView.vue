<template>
  <div class="w-full min-w-0 space-y-4 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <PortalDispatchSummaryBar
      :variant="summaryVariant"
      :loading="summaryLoading"
      :summary="summary"
      :active-key="kpiActiveKey"
      @quick-filter="onKpiQuickFilter"
    />

    <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <PortalRequestsListToolbar
        v-model:search-input="searchInput"
        v-model:quick-filter="quickFilter"
        v-model:export-open="exportOpen"
        v-model:show-filter-panel-dd="showFilterPanelDd"
        :filter-control-defs="filterControlDefsLabeled"
        :quick-filter-options="isExtracurricularModule ? ecQuickFilterOptions : null"
        :visible-filters="visibleFilters"
        :active-filter-count="activeFilterCount"
        :show-suggest="showSearchSuggest"
        :search-suggest-loading="searchSuggestLoading"
        :search-suggestions="searchSuggestions"
        :search-suggest-focus="searchSuggestFocus"
        @search-input="onSearchInput"
        @search-focus="onSearchFocus"
        @search-blur="onSearchBlur"
        @search-enter="commitSearchSuggest"
        @search-suggest-pick="pickSearchSuggestion"
        @toggle-filter-panel="toggleFilterPanel"
        @close-filter-panel="closeFilterPanel"
        @reset-filters="resetFilters"
        @export-csv="exportCurrentCsv"
        @export-excel="exportCurrentCsv"
      >
        <template #suggest-row="{ req }">
          <span class="font-mono text-sm font-semibold text-slate-900">{{ formatDispatchRequestRefCode(req) }}</span>
          <span class="truncate text-xs text-slate-500">{{ suggestRouteLine(req) }}</span>
        </template>
      </PortalRequestsListToolbar>

      <PortalRequestsFilterRow
        v-if="hasFilterRow"
        :visible-filters="visibleFilters"
        v-model:filter-status="filterStatus"
        v-model:filter-trip-type="filterTripType"
        v-model:filter-urgent="filterUrgent"
        v-model:filter-extracurricular="filterExtracurricular"
        v-model:sort="sort"
        v-model:date-from="dateFrom"
        v-model:date-to="dateTo"
        :show-extracurricular-filter="!isExtracurricularModule"
        :show-trip-type-filter="!isExtracurricularModule"
        :show-urgent-filter="!isExtracurricularModule"
        :filter-options="filterOptions"
        :sort-options="sortOptions"
        :trip-type-options="tripTypeOptions"
        :urgent-options="urgentOptions"
        :extracurricular-options="extracurricularOptions"
        @filter-change="onAdvancedFilterChange"
      />

      <PortalRequestSkeleton
        v-if="loading && !silentListRefresh"
        class="px-4 py-8"
        :aria-label="t('portal.loading_requests')"
      />

      <div v-else-if="fetchError" class="mx-4 mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ fetchError }}
      </div>

      <template v-else>
        <PortalEmptyState
          v-if="!items.length"
          class="px-4 py-10"
          :title="t('portal.empty_title')"
          :description="t('portal.empty_desc')"
        >
          <template #action>
            <RouterLink
              :to="{ name: portalRoutes.create }"
              class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-va-800 px-6 text-sm font-semibold text-white hover:bg-va-900"
            >
              {{ t('portal.cta_primary') }}
            </RouterLink>
          </template>
        </PortalEmptyState>

        <div v-else class="space-y-4 px-4 pb-4 pt-2">
          <div
            v-if="isExtracurricularMode && showPlanCreatedHint"
            class="flex gap-3 rounded-xl border border-teal-200 bg-teal-50/90 px-4 py-3 text-sm text-teal-950"
            role="status"
          >
            <p class="min-w-0 flex-1 leading-relaxed">{{ t('portal.recurring_plan.created_list_hint') }}</p>
            <button
              type="button"
              class="shrink-0 text-xs font-semibold text-teal-800 underline hover:text-teal-950"
              @click="dismissPlanCreatedHint"
            >
              {{ t('portal.welcome_banner_dismiss') }}
            </button>
          </div>
          <div
            v-if="isExtracurricularMode"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-va-200/70 bg-gradient-to-r from-slate-50 via-va-50/40 to-va-50/30 px-3 py-2.5 shadow-sm ring-1 ring-va-100/50"
          >
            <div class="flex flex-wrap gap-1 rounded-xl bg-white/80 p-1 shadow-inner ring-1 ring-slate-200/60">
              <button
                v-for="mode in listViewModes"
                :key="mode.id"
                type="button"
                class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                :class="
                  extracurricularListView === mode.id
                    ? 'bg-va-800 text-white shadow-sm shadow-va-500/25'
                    : 'text-slate-600 hover:bg-va-50 hover:text-va-900'
                "
                @click="extracurricularListView = mode.id"
              >
                {{ mode.label }}
              </button>
            </div>
            <label
              v-if="extracurricularListView === 'schedule'"
              class="flex w-full min-w-0 flex-col gap-1 text-sm text-slate-700 sm:w-auto sm:flex-row sm:items-center sm:gap-2"
            >
              <span class="text-xs font-medium text-slate-600 sm:text-sm">{{ t('portal.recurring_plan.schedule_group_label') }}</span>
              <select
                v-model="scheduleGroupBy"
                class="h-9 w-full min-w-0 rounded-lg border border-va-200/80 bg-white px-2.5 text-sm font-medium text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-va-500/30 sm:w-auto"
              >
                <option value="day">{{ t('portal.recurring_plan.group_by_day') }}</option>
                <option value="plan">{{ t('portal.recurring_plan.group_by_plan') }}</option>
                <option value="route">{{ t('portal.recurring_plan.group_by_route') }}</option>
                <option value="status">{{ t('portal.recurring_plan.group_by_status') }}</option>
              </select>
            </label>
          </div>
          <div
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50/50 px-3 py-2 sm:justify-end"
          >
            <p
              v-if="pagination && (pagination.total ?? 0) > 0"
              class="text-xs font-medium text-slate-600 sm:text-sm"
            >
              {{
                t('portal.pagination_summary', {
                  from: pageFrom,
                  to: pageTo,
                  total: pagination.total ?? 0,
                })
              }}
            </p>
            <label class="flex items-center gap-2">
              <span class="sr-only">{{ t('portal.filter_per_page') }}</span>
              <span class="text-xs text-slate-500 sm:text-sm">{{ t('portal.filter_per_page') }}</span>
              <select
                v-model.number="perPage"
                class="h-9 min-w-[3.25rem] rounded-lg border border-slate-200 bg-white px-2 text-sm font-medium text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                :aria-label="t('portal.filter_per_page')"
                @change="onPerPageChange"
              >
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
              </select>
            </label>
          </div>
          <ExtracurricularRequestsCalendar
            v-if="isExtracurricularMode && extracurricularListView === 'calendar'"
            :requests="items"
            :detail-route-name="portalRoutes.detail"
          />
          <ExtracurricularScheduleTable
            v-else-if="isExtracurricularMode && extracurricularListView === 'schedule'"
            :requests="items"
            :group-by="scheduleGroupBy"
            :detail-route-name="portalRoutes.detail"
            @refresh="reloadSilent"
            @plan-label-saved="onPlanLabelSaved"
          />
          <ExtracurricularRequestsDataTable
            v-else-if="isExtracurricularMode"
            ref="extracurricularTableRef"
            :requests="items"
            variant="portal"
            :detail-route-name="portalRoutes.detail"
            @refresh="reloadFromStart"
            @clone="onCloneFromList"
          />
          <PortalRequestsTable v-else :requests="items" />
          <nav
            v-if="pagination && (pagination.last_page ?? 1) > 1"
            class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            :aria-label="t('portal.filter_per_page')"
          >
            <p class="text-sm text-slate-500">
              {{
                t('portal.pagination_summary', {
                  from: pageFrom,
                  to: pageTo,
                  total: pagination.total ?? 0,
                })
              }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50"
                :disabled="(pagination.current_page ?? 1) <= 1 || loading"
                @click="goPage((pagination.current_page ?? 1) - 1)"
              >
                {{ t('portal.page_prev') }}
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-for="p in pageNumbers"
                  :key="p"
                  type="button"
                  class="min-w-[2.25rem] rounded-lg px-2 py-1.5 text-sm"
                  :class="
                    p === pagination.current_page
                      ? 'bg-va-800 font-semibold text-white'
                      : 'text-slate-600 hover:bg-slate-100'
                  "
                  @click="goPage(p)"
                >
                  {{ p }}
                </button>
              </div>
              <button
                type="button"
                class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50"
                :disabled="(pagination.current_page ?? 1) >= (pagination.last_page ?? 1) || loading"
                @click="goPage((pagination.current_page ?? 1) + 1)"
              >
                {{ t('portal.page_next') }}
              </button>
            </div>
          </nav>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { confirmAndCloneDispatchRequest } from '../../composables/useDispatchRequestClone'
import { getPortalRequestsSummary, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'
import PortalDispatchSummaryBar from '../../components/portal/PortalDispatchSummaryBar.vue'
import PortalRequestsListToolbar from '../../components/portal/PortalRequestsListToolbar.vue'
import PortalRequestsFilterRow from '../../components/portal/PortalRequestsFilterRow.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls'
import ExtracurricularRequestsDataTable from '../../components/requests/ExtracurricularRequestsDataTable.vue'
import ExtracurricularRequestsCalendar from '../../components/portal/extracurricular/ExtracurricularRequestsCalendar.vue'
import ExtracurricularScheduleTable from '../../components/portal/extracurricular/ExtracurricularScheduleTable.vue'
import { usePortalExtracurricularModule } from '../../composables/usePortalExtracurricularModule'
import { formatDispatchRequestRefCode } from '../../util/portalRequestFormat.js'
import { usePortalRequestEmptyText } from '../../composables/usePortalRequestEmptyText.js'

const PER_PAGE_OPTIONS = [5, 10, 15, 20]
const PER_PAGE_KEY = 'portal-list-per-page'
const SUGGEST_PER_PAGE = 8

function readStoredPerPage() {
  try {
    const n = parseInt(localStorage.getItem(PER_PAGE_KEY) ?? '10', 10)
    return PER_PAGE_OPTIONS.includes(n) ? n : 10
  } catch {
    return 10
  }
}

const { t } = useI18n()
const emptyText = usePortalRequestEmptyText()
const route = useRoute()
const router = useRouter()
const { isExtracurricularModule, routes: portalRoutes } = usePortalExtracurricularModule()

const isExtracurricularMode = computed(
  () => isExtracurricularModule.value || filterExtracurricular.value === 'extracurricular',
)

const extracurricularTableRef = ref(null)
const extracurricularListView = ref('schedule')
const scheduleGroupBy = ref('plan')

const summary = ref(null)
const summaryLoading = ref(true)
const kpiActiveKey = ref('')
const quickFilter = ref('all')
const exportOpen = ref(false)
const slaRiskOnly = ref(false)
const suppressQuickFilterReload = ref(false)

const ecQuickFilterOptions = computed(() => [
  { value: 'all', label: t('portal.shell.ec_tab_all') },
  { value: 'draft', label: t('portal.shell.ec_tab_draft') },
  { value: 'pending', label: t('portal.shell.ec_tab_pending') },
  { value: 'processing', label: t('portal.shell.ec_tab_active') },
  { value: 'done', label: t('portal.shell.ec_tab_done') },
])

const summaryVariant = computed(() =>
  isExtracurricularModule.value ? 'extracurricular' : 'list',
)

const listViewModes = computed(() => [
  { id: 'calendar', label: t('portal.recurring_plan.list_view_calendar') },
  { id: 'schedule', label: t('portal.recurring_plan.list_view_schedule') },
  { id: 'detail', label: t('portal.recurring_plan.list_view_detail') },
])

// â”€â”€ List state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const loading = ref(true)
const silentListRefresh = ref(false)
const fetchError = ref('')
const items = ref([])
const pagination = ref(null)
const perPage = ref(readStoredPerPage())
const perPageOptions = PER_PAGE_OPTIONS

// â”€â”€ Filter state â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const filterStatus = ref('all')
const filterTripType = ref('all')
const filterUrgent = ref('all')
const filterExtracurricular = ref('all')
const sort = ref('depart_desc')
const searchInput = ref('')
const debouncedQ = ref('')
const searchDropdownOpen = ref(false)
const searchSuggestions = ref([])
const searchSuggestLoading = ref(false)
const searchSuggestFocus = ref(-1)
const dateFrom = ref('')
const dateTo = ref('')

const showSearchSuggest = computed(
  () => searchDropdownOpen.value && String(searchInput.value ?? '').trim().length >= 1,
)

const portalFilterControls = isExtracurricularModule.value
  ? [
      { key: 'status', label: t('portal.filter_label_status'), default: true },
      { key: 'sort', label: t('portal.filter_label_sort'), default: false },
      { key: 'date_range', label: t('portal.filter_label_date_range'), default: false },
    ]
  : [
      { key: 'status', label: t('portal.filter_label_status'), default: false },
      { key: 'sort', label: t('portal.filter_label_sort'), default: false },
      { key: 'trip_type', label: t('portal.filter_label_trip_type'), default: false },
      { key: 'urgent', label: t('portal.filter_label_urgent'), default: false },
      { key: 'extracurricular', label: t('portal.filter_label_extracurricular'), default: false },
      { key: 'date_range', label: t('portal.filter_label_date_range'), default: false },
    ]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(
  portalFilterControls,
  isExtracurricularModule.value ? 'portal-ec-requests-filter-vis.v1' : 'portal-requests-filter-vis.v1',
)

const filterControlDefsLabeled = computed(() =>
  filterControlDefs.map((fd) => ({
    ...fd,
    label:
      {
        status: t('portal.filter_label_status'),
        sort: t('portal.filter_label_sort'),
        trip_type: t('portal.filter_label_trip_type'),
        urgent: t('portal.filter_label_urgent'),
        extracurricular: t('portal.filter_label_extracurricular'),
        date_range: t('portal.filter_label_date_range'),
      }[fd.key] ?? fd.label,
  })),
)

function toggleFilterPanel() {
  exportOpen.value = false
  openFilterPanel(() => {
    exportOpen.value = false
  })
}

function syncQuickFilterFromStatus() {
  suppressQuickFilterReload.value = true
  const s = filterStatus.value
  if (s === 'all') quickFilter.value = 'all'
  else if (s === 'draft') quickFilter.value = 'draft'
  else if (s === 'pending') quickFilter.value = 'pending'
  else if (s === 'processing') quickFilter.value = 'processing'
  else if (s === 'completed') quickFilter.value = 'done'
  else quickFilter.value = 'all'
  suppressQuickFilterReload.value = false
}

function onAdvancedFilterChange() {
  syncQuickFilterFromStatus()
  reloadFromStart()
}

const filterOptions = computed(() => [
  { key: 'all', label: t('portal.filter_all') },
  { key: 'pending', label: t('portal.filter_pending') },
  { key: 'processing', label: t('portal.shell.quick_processing') },
  { key: 'completed', label: t('portal.shell.quick_done') },
  { key: 'draft', label: t('portal.shell.ec_tab_draft') },
  { key: 'approved', label: t('portal.filter_approved') },
  { key: 'rejected', label: t('portal.filter_rejected') },
  { key: 'returned', label: t('portal.filter_returned') },
])

const sortOptions = computed(() => [
  { value: 'depart_desc', label: t('portal.sort_depart_desc') },
  { value: 'depart_asc', label: t('portal.sort_depart_asc') },
  { value: 'created_desc', label: t('portal.sort_created_desc') },
  { value: 'created_asc', label: t('portal.sort_created_asc') },
])

const tripTypeOptions = computed(() => [
  { key: 'all', label: t('portal.filter_trip_type_all') },
  { key: 'business', label: t('portal.filter_trip_type_business') },
  { key: 'cargo', label: t('portal.filter_trip_type_cargo') },
  { key: 'door_to_door', label: t('portal.filter_trip_type_door_to_door') },
  { key: 'point_to_point', label: t('portal.filter_trip_type_point_to_point') },
])

const urgentOptions = computed(() => [
  { key: 'all', label: t('portal.filter_urgent_all') },
  { key: 'urgent', label: t('portal.filter_urgent_yes') },
  { key: 'normal', label: t('portal.filter_urgent_no') },
])

const extracurricularOptions = computed(() => [
  { key: 'all', label: t('portal.filter_extracurricular_all') },
  { key: 'extracurricular', label: t('portal.filter_extracurricular_only') },
])

const currentStatusLabel = computed(
  () => filterOptions.value.find((o) => o.key === filterStatus.value)?.label ?? filterStatus.value,
)

const currentSortLabel = computed(
  () => sortOptions.value.find((o) => o.value === sort.value)?.label ?? sort.value,
)

const currentTripTypeLabel = computed(
  () => tripTypeOptions.value.find((o) => o.key === filterTripType.value)?.label ?? filterTripType.value,
)

const currentUrgentLabel = computed(
  () => urgentOptions.value.find((o) => o.key === filterUrgent.value)?.label ?? filterUrgent.value,
)

const currentExtracurricularLabel = computed(
  () =>
    extracurricularOptions.value.find((o) => o.key === filterExtracurricular.value)?.label
    ?? filterExtracurricular.value,
)

const currentDateRangeLabel = computed(() => {
  if (dateFrom.value && dateTo.value) return `${dateFrom.value} â€” ${dateTo.value}`
  if (dateFrom.value) return `${t('portal.filter_date_from')}: ${dateFrom.value}`
  if (dateTo.value) return `${t('portal.filter_date_to')}: ${dateTo.value}`
  return t('portal.filter_all')
})

const statusFilterActive = computed(() => filterStatus.value !== 'all')
const sortFilterActive = computed(() => sort.value !== 'depart_desc')
const tripTypeFilterActive = computed(() => filterTripType.value !== 'all')
const urgentFilterActive = computed(() => filterUrgent.value !== 'all')
const extracurricularFilterActive = computed(() => filterExtracurricular.value !== 'all')
const dateRangeFilterActive = computed(() => Boolean(dateFrom.value || dateTo.value))

function filterChipSummary(fieldLabel, valueLabel, isActive) {
  return isActive ? valueLabel : fieldLabel
}

const statusChipSummary = computed(() =>
  filterChipSummary(
    t('portal.filter_label_status'),
    currentStatusLabel.value,
    statusFilterActive.value,
  ),
)
const sortChipSummary = computed(() =>
  filterChipSummary(t('portal.filter_label_sort'), currentSortLabel.value, sortFilterActive.value),
)
const tripTypeChipSummary = computed(() =>
  filterChipSummary(
    t('portal.filter_label_trip_type'),
    currentTripTypeLabel.value,
    tripTypeFilterActive.value,
  ),
)
const urgentChipSummary = computed(() =>
  filterChipSummary(
    t('portal.filter_label_urgent'),
    currentUrgentLabel.value,
    urgentFilterActive.value,
  ),
)
const extracurricularChipSummary = computed(() =>
  filterChipSummary(
    t('portal.filter_label_extracurricular'),
    currentExtracurricularLabel.value,
    extracurricularFilterActive.value,
  ),
)
const dateRangeChipSummary = computed(() =>
  filterChipSummary(
    t('portal.filter_label_date_range'),
    currentDateRangeLabel.value,
    dateRangeFilterActive.value,
  ),
)

// â”€â”€ Active filter count + summary lines â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const activeFilterCount = computed(() => {
  let n = 0
  if (filterStatus.value !== 'all') n++
  if (filterTripType.value !== 'all') n++
  if (filterUrgent.value !== 'all') n++
  if (filterExtracurricular.value !== 'all') n++
  if (sort.value !== 'depart_desc') n++
  if (debouncedQ.value) n++
  if (dateFrom.value || dateTo.value) n++
  if (slaRiskOnly.value) n++
  return n
})

async function loadSummary() {
  summaryLoading.value = true
  try {
    summary.value = await getPortalRequestsSummary({
      module: isExtracurricularModule.value ? 'extracurricular' : 'all',
    })
  } catch {
    summary.value = null
  } finally {
    summaryLoading.value = false
  }
}

function onKpiQuickFilter(payload) {
  const key = payload?.key ?? ''
  kpiActiveKey.value = key
  slaRiskOnly.value = key === 'sla_risk'

  if (key === 'pending') {
    filterStatus.value = 'pending'
    quickFilter.value = 'pending'
  } else if (key === 'processing' || key === 'in_progress') {
    filterStatus.value = 'processing'
    quickFilter.value = 'processing'
  } else if (key === 'completed') {
    filterStatus.value = 'completed'
    quickFilter.value = 'done'
  } else if (key === 'overdue') {
    filterStatus.value = 'pending'
    quickFilter.value = 'pending'
  } else {
    filterStatus.value = 'all'
    quickFilter.value = 'all'
  }

  reloadFromStart()
}

function exportCurrentCsv() {
  const rows = items.value || []
  if (!rows.length) return
  const header = ['id', 'status', 'origin', 'destination', 'depart_at']
  const lines = [header.join(',')]
  for (const r of rows) {
    const cols = [r.id, r.status, r.origin, r.destination, r.depart_at].map((c) =>
      `"${String(c ?? '').replace(/"/g, '""')}"`,
    )
    lines.push(cols.join(','))
  }
  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'portal-requests.csv'
  a.click()
  URL.revokeObjectURL(url)
}

watch(quickFilter, (v) => {
  if (suppressQuickFilterReload.value) return
  slaRiskOnly.value = false
  kpiActiveKey.value = ''
  if (v === 'all') filterStatus.value = 'all'
  else if (v === 'draft') filterStatus.value = 'draft'
  else if (v === 'pending') filterStatus.value = 'pending'
  else if (v === 'processing') filterStatus.value = 'processing'
  else if (v === 'done') filterStatus.value = 'completed'
  reloadFromStart()
})

const pageFrom = computed(() => {
  const cur = pagination.value?.current_page ?? 1
  const per = pagination.value?.per_page ?? perPage.value
  const total = pagination.value?.total ?? 0
  if (total === 0) return 0
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = pagination.value?.current_page ?? 1
  const per = pagination.value?.per_page ?? perPage.value
  const total = pagination.value?.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = pagination.value?.last_page ?? 1
  const cur = pagination.value?.current_page ?? 1
  const window = 3
  const start = Math.max(1, cur - 1)
  const end = Math.min(last, start + window - 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

// â”€â”€ Search autocomplete â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let suggestTimer = null

function suggestRouteLine(req) {
  const o = emptyText.portalFieldHasValue(req.origin)
  const d = emptyText.portalFieldHasValue(req.destination)
  if (o || d) {
    return `${emptyText.origin(req.origin)} → ${emptyText.destination(req.destination)}`
  }
  return t('portal.card_no_route')
}

async function fetchSearchSuggestions(term) {
  const q = String(term ?? '').trim()
  if (!q) {
    searchSuggestions.value = []
    return
  }
  searchSuggestLoading.value = true
  try {
    const data = await listPortalRequests({
      per_page: SUGGEST_PER_PAGE,
      page: 1,
      sort: 'depart_desc',
      q,
    })
    searchSuggestions.value = data.items ?? []
    searchSuggestFocus.value = searchSuggestions.value.length ? 0 : -1
  } catch {
    searchSuggestions.value = []
    searchSuggestFocus.value = -1
  } finally {
    searchSuggestLoading.value = false
  }
}

function scheduleSearchSuggestions() {
  clearTimeout(suggestTimer)
  const term = String(searchInput.value ?? '').trim()
  if (!term) {
    searchSuggestions.value = []
    searchSuggestFocus.value = -1
    return
  }
  suggestTimer = window.setTimeout(() => fetchSearchSuggestions(term), 280)
}

function onSearchInput() {
  searchDropdownOpen.value = true
  scheduleSearchSuggestions()
}

function onSearchFocus() {
  searchDropdownOpen.value = true
  if (String(searchInput.value ?? '').trim()) {
    scheduleSearchSuggestions()
  }
}

function onSearchBlur() {
  window.setTimeout(() => {
    searchDropdownOpen.value = false
    searchSuggestFocus.value = -1
  }, 150)
}

function closeSearchSuggest() {
  searchDropdownOpen.value = false
  searchSuggestFocus.value = -1
}

function moveSearchSuggest(delta) {
  if (!searchSuggestions.value.length) return
  const n = searchSuggestions.value.length
  if (searchSuggestFocus.value < 0) {
    searchSuggestFocus.value = delta > 0 ? 0 : n - 1
    return
  }
  searchSuggestFocus.value = (searchSuggestFocus.value + delta + n) % n
}

function flushSearch(term = searchInput.value) {
  const q = String(term ?? '').trim()
  clearTimeout(debounceTimer)
  searchInput.value = q
  closeSearchSuggest()
  searchSuggestions.value = []
  if (q !== debouncedQ.value) {
    debouncedQ.value = q
  } else {
    reloadFromStart()
  }
}

function pickSearchSuggestion(req) {
  flushSearch(formatDispatchRequestRefCode(req) || String(req.id))
}

function commitSearchSuggest() {
  if (showSearchSuggest.value && searchSuggestFocus.value >= 0) {
    const req = searchSuggestions.value[searchSuggestFocus.value]
    if (req) {
      pickSearchSuggestion(req)
      return
    }
  }
  flushSearch()
}

// â”€â”€ Debounced search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let debounceTimer = null

watch(searchInput, (v) => {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    debouncedQ.value = String(v ?? '').trim()
  }, 350)
})

watch(debouncedQ, () => {
  reloadFromStart()
})

let dateDebounceTimer = null
watch([dateFrom, dateTo], () => {
  clearTimeout(dateDebounceTimer)
  dateDebounceTimer = window.setTimeout(() => {
    reloadFromStart()
  }, 400)
})

// â”€â”€ Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function closeParentDetails(ev) {
  const el = ev?.currentTarget?.closest?.('details')
  if (el && 'open' in el) el.open = false
}

function onFilter(key, ev) {
  filterStatus.value = key
  closeParentDetails(ev)
  reloadFromStart()
}

function onTripType(key, ev) {
  filterTripType.value = key
  closeParentDetails(ev)
  reloadFromStart()
}

function onUrgent(key, ev) {
  filterUrgent.value = key
  closeParentDetails(ev)
  reloadFromStart()
}

function onExtracurricular(key, ev) {
  filterExtracurricular.value = key
  closeParentDetails(ev)
  reloadFromStart()
}

function onSort(value, ev) {
  sort.value = value
  closeParentDetails(ev)
  reloadFromStart()
}

function resetFilters() {
  filterStatus.value = 'all'
  filterTripType.value = 'all'
  filterUrgent.value = 'all'
  filterExtracurricular.value = 'all'
  sort.value = 'depart_desc'
  clearTimeout(debounceTimer)
  searchInput.value = ''
  debouncedQ.value = ''
  closeSearchSuggest()
  searchSuggestions.value = []
  dateFrom.value = ''
  dateTo.value = ''
  slaRiskOnly.value = false
  quickFilter.value = 'all'
  kpiActiveKey.value = ''
  reloadFromStart()
}

// â”€â”€ API â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function listParams(page) {
  return {
    per_page: perPage.value,
    page,
    sort: sort.value,
    filter: filterStatus.value === 'all' ? undefined : filterStatus.value,
    sla_risk_only: slaRiskOnly.value ? true : undefined,
    q: debouncedQ.value || undefined,
    trip_type: filterTripType.value !== 'all' ? filterTripType.value : undefined,
    is_urgent:
      filterUrgent.value !== 'all' ? (filterUrgent.value === 'urgent' ? 1 : 0) : undefined,
    extracurricular_only:
      isExtracurricularModule.value || filterExtracurricular.value === 'extracurricular'
        ? true
        : undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
  }
}

async function loadPage(page = 1, silent = false) {
  if (silent) {
    silentListRefresh.value = true
  } else {
    loading.value = true
    fetchError.value = ''
  }
  try {
    const data = await listPortalRequests(listParams(page))
    items.value = data.items ?? []
    pagination.value = data.meta ?? null
  } catch (e) {
    if (!silent) {
      fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
      items.value = []
      pagination.value = null
    }
  } finally {
    silentListRefresh.value = false
    if (!silent) loading.value = false
  }
}

function reloadFromStart() {
  loadPage(1)
}

function reloadSilent() {
  loadPage(1, true)
}

function templateIdFromRequest(req) {
  const tid = req?.dispatch_request_template_id ?? req?.dispatch_request_template?.id
  if (tid == null || tid === '') return null
  const n = Number(tid)
  return Number.isFinite(n) && n > 0 ? n : null
}

function onPlanLabelSaved({ templateId, label }) {
  const tid = Number(templateId)
  const trimmed = String(label || '').trim()
  if (!Number.isFinite(tid) || tid <= 0 || !trimmed) return
  for (const req of items.value) {
    if (templateIdFromRequest(req) !== tid) continue
    req.recurring_plan_label = trimmed
    if (!req.dispatch_request_template) {
      req.dispatch_request_template = { id: tid }
    }
    if (!req.dispatch_request_template.dispatch_package) {
      req.dispatch_request_template.dispatch_package = { label: trimmed }
    } else {
      req.dispatch_request_template.dispatch_package.label = trimmed
    }
  }
}

function goPage(page) {
  if (loading.value) return
  const last = pagination.value?.last_page ?? 1
  const p = Math.max(1, Math.min(page, last))
  loadPage(p)
}

async function onCloneFromList(req) {
  if (!req?.id) return
  extracurricularTableRef.value?.setCloneBusy?.(req.id, true)
  try {
    const dr = await confirmAndCloneDispatchRequest(req, t)
    if (!dr?.id) return
    await router.push({ name: portalRoutes.value.create, query: { replace: String(dr.id) } })
  } catch (e) {
    fetchError.value = formatApiError(e, t('request_detail.reset_clone_fail'))
  } finally {
    extracurricularTableRef.value?.setCloneBusy?.(req.id, false)
  }
}

function onPerPageChange() {
  try {
    localStorage.setItem(PER_PAGE_KEY, String(perPage.value))
  } catch {
    // ignore
  }
  reloadFromStart()
}

const showPlanCreatedHint = ref(false)

function dismissPlanCreatedHint() {
  showPlanCreatedHint.value = false
  const q = { ...route.query }
  delete q.plan_created
  router.replace({ query: q })
}

watch(
  () => route.query.plan_created,
  (v) => {
    showPlanCreatedHint.value = String(v) === '1'
  },
  { immediate: true },
)

watch(extracurricularListView, () => {
  if (isExtracurricularMode.value) {
    reloadFromStart()
  }
})

onMounted(() => {
  if (route.query.q) {
    const q = String(route.query.q)
    searchInput.value = q
    debouncedQ.value = q
  }
  const qFilter = route.query.filter
  if (qFilter && typeof qFilter === 'string') {
    suppressQuickFilterReload.value = true
    filterStatus.value = qFilter
    if (qFilter === 'processing') quickFilter.value = 'processing'
    else if (qFilter === 'pending') quickFilter.value = 'pending'
    else if (qFilter === 'draft') quickFilter.value = 'draft'
    else if (qFilter === 'completed') quickFilter.value = 'done'
    suppressQuickFilterReload.value = false
  }
  if (String(route.query.sla) === '1') {
    slaRiskOnly.value = true
    kpiActiveKey.value = 'sla_risk'
  }
  if (isExtracurricularModule.value) {
    filterExtracurricular.value = 'extracurricular'
  }
  loadSummary()
  loadPage(1)
})

onBeforeUnmount(() => {
  clearTimeout(debounceTimer)
  clearTimeout(suggestTimer)
  clearTimeout(dateDebounceTimer)
})
</script>
