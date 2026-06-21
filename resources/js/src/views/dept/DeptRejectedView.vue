<template>
  <div class="w-full space-y-5">
    <DeptRejectedSummaryBar
      :stats="kpiStats"
      :loading="kpiLoading"
      :active-trip-type="filters.trip_type"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="rejectedDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="dept-rejected-search"
              :placeholder="t('dept.filter_search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              data-testid="dept-rejected-toolbar-search"
              @enter="flushSearch"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('trips_page.filter_show_controls_title')"
              :hint="t('trips_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="dept-rejected-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('dept.filter_btn') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'dept-rejected-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`dept-rejected-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`dept-rejected-filter-vis-${fd.key}`"
                />
                <label
                  :for="`dept-rejected-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
              :title="t('filter_bar.clear_all')"
              data-testid="dept-rejected-reset-filters"
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
            :aria-label="t('dept.filter_label_trip_type')"
            data-testid="dept-rejected-filter-trip-type"
            @change="onFilterChange"
          >
            <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <div v-if="visibleFilters.date_range" class="min-w-0 w-full sm:col-span-2 xl:col-span-2">
          <DatagridFilterField>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <FilterDatePicker
                v-model="filters.from"
                :placeholder="t('dept.filter_date_from')"
                :max-date="filters.to || null"
                input-id="dept-rejected-filter-from"
                data-testid="dept-rejected-filter-from"
                @update:model-value="onFilterChange"
              />
              <FilterDatePicker
                v-model="filters.to"
                :placeholder="t('dept.filter_date_to')"
                :min-date="filters.from || null"
                input-id="dept-rejected-filter-to"
                data-testid="dept-rejected-filter-to"
                @update:model-value="onFilterChange"
              />
            </div>
          </DatagridFilterField>
        </div>

        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model.number="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('filter_bar.per_page')"
            data-testid="dept-rejected-filter-per-page"
            @change="onFilterChange"
          >
            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n === DEFAULT_PER_PAGE ? t('filter_bar.per_page') : n }}</option>
          </select>
        </DatagridFilterField>
      </div>

      <div class="border-t border-slate-100 px-4 py-4 dark:border-slate-700 sm:px-5">
        <div
          v-if="error"
          class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100"
        >
          {{ error }}
        </div>
        <template v-else>
          <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('dept.empty_rejected') }}</p>
          <div class="space-y-4">
            <DeptRequestCard v-for="r in items" :key="r.id" :req="r" show-rejection @detail="goDetail" />
          </div>
          <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
          <div v-else-if="page < lastPage" class="mt-4 flex justify-center">
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
              :disabled="loadingMore"
              data-testid="dept-rejected-load-more"
              @click="loadMore"
            >
              {{ loadingMore ? t('dept.loading') : t('dept.load_more') }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { getDeptSummary, listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'
import DeptRejectedSummaryBar from '../../components/dept/DeptRejectedSummaryBar.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { labelTripType } from '../../util/labels'
import {
  DEPT_DEFAULT_PER_PAGE,
  DEPT_FILTER_CONTROL_CLASS,
  DEPT_FILTER_LABEL_KEYS,
  DEPT_PER_PAGE_OPTIONS,
  DEPT_TRIP_TYPES,
  deptMonthRangeIso,
} from '../../util/deptListPage.js'

const DEFAULT_PER_PAGE = DEPT_DEFAULT_PER_PAGE
const perPageOptions = DEPT_PER_PAGE_OPTIONS
const FILTER_CONTROL_CLASS = DEPT_FILTER_CONTROL_CLASS

const REJECTED_FILTER_CONTROLS = [
  { key: 'trip_type', label: '', default: false },
  { key: 'date_range', label: '', default: false },
  { key: 'per_page', label: '', default: false },
]

const { t } = useI18n()
const router = useRouter()

const items = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const kpiLoading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const searchInput = ref('')
const rejectedDatagridRef = ref(null)
useDetailsAutoCloseWithin(rejectedDatagridRef)

const kpiStats = ref({
  total: 0,
  rejected_this_month: 0,
  pending_count: 0,
  approval_rate_30d: null,
  cargo: 0,
})

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs: filterControlDefsRaw,
} = useVisibleFilterControls(REJECTED_FILTER_CONTROLS, 'va.dept.rejected.filter_vis_v2')

const filterControlDefs = computed(() =>
  filterControlDefsRaw.map((fd) => ({
    key: fd.key,
    label: t(DEPT_FILTER_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const filters = reactive({
  trip_type: '',
  from: '',
  to: '',
  per_page: DEFAULT_PER_PAGE,
})

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('dept.filter_label_trip_type') },
  ...DEPT_TRIP_TYPES.map((value) => ({
    value,
    label: labelTripType(value),
  })),
])

let searchDebounceId = null

function kpiListBaseParams() {
  const params = {
    status: 'rejected',
    per_page: 1,
    page: 1,
    sort: 'depart_desc',
  }
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to
  const q = searchInput.value.trim()
  if (q) params.q = q
  return params
}

async function reloadKpis() {
  kpiLoading.value = true
  try {
    const base = kpiListBaseParams()
    const month = deptMonthRangeIso()
    const [deptSummary, totalRes, cargoRes, monthRes] = await Promise.all([
      getDeptSummary(),
      listRequests(base),
      listRequests({ ...base, trip_type: 'cargo' }),
      listRequests({ ...base, from: month.from, to: month.to }),
    ])
    kpiStats.value = {
      total: totalRes.meta?.total ?? 0,
      rejected_this_month: monthRes.meta?.total ?? 0,
      pending_count: deptSummary.pending_count ?? 0,
      approval_rate_30d: deptSummary.approval_rate_30d ?? null,
      cargo: cargoRes.meta?.total ?? 0,
    }
  } catch {
    /* supplementary */
  } finally {
    kpiLoading.value = false
  }
}

function buildListParams(p) {
  const params = {
    status: 'rejected',
    per_page: filters.per_page,
    page: p,
    sort: 'depart_desc',
  }
  if (filters.trip_type) params.trip_type = filters.trip_type
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to
  const q = searchInput.value.trim()
  if (q) params.q = q
  return params
}

async function fetchList(p, append) {
  const res = await listRequests(buildListParams(p))
  lastPage.value = res.meta?.last_page ?? 1
  if (append) items.value = items.value.concat(res.items ?? [])
  else items.value = res.items ?? []
}

function onFilterChange() {
  page.value = 1
  reload()
}

function flushSearch() {
  clearTimeout(searchDebounceId)
  page.value = 1
  reload()
}

function resetFilters() {
  clearTimeout(searchDebounceId)
  filters.trip_type = ''
  filters.from = ''
  filters.to = ''
  filters.per_page = DEFAULT_PER_PAGE
  searchInput.value = ''
  closeFilterPanel()
  page.value = 1
  reload()
}

function onKpiQuickFilter(payload) {
  if (payload.kind === 'navigate' && payload.to === 'pending') {
    router.push({ name: 'deptDashboard' })
    return
  }
  if (payload.kind === 'month') {
    const { from, to } = deptMonthRangeIso()
    filters.from = from
    filters.to = to
    page.value = 1
    reload()
    return
  }
  if (payload.kind === 'trip_type') {
    filters.trip_type = payload.value ?? ''
    page.value = 1
    reload()
  }
}

async function reload() {
  loading.value = true
  error.value = ''
  page.value = 1
  try {
    await Promise.all([fetchList(1, false), reloadKpis()])
  } catch (e) {
    error.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  loadingMore.value = true
  try {
    page.value += 1
    await fetchList(page.value, true)
  } catch (e) {
    error.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loadingMore.value = false
  }
}

function goDetail(id) {
  router.push({ name: 'deptRequestDetail', params: { id: String(id) } })
}

watch(searchInput, () => {
  clearTimeout(searchDebounceId)
  searchDebounceId = setTimeout(() => {
    page.value = 1
    reload()
  }, 350)
})

onMounted(() => {
  reload()
})
</script>
