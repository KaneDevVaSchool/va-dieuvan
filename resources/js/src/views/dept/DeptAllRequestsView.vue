<template>
  <div class="mx-auto w-full max-w-5xl">
    <div class="relative z-40 mt-6">
      <AppFilterBar>
        <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
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
                  {{ activeFilterCount > 9 ? '9+' : activeFilterCount }}
                </span>
              </span>
              <span class="hidden text-sm font-medium text-slate-700 sm:inline dark:text-slate-200">{{
                t('dept.filter_toolbar_label')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
            >
              <p
                class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
              >
                {{ t('filter_bar.active_title') }}
              </p>
              <div class="p-3 pt-2">
                <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                  <li v-if="filters.status" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('dept.filter_label_status') }}</span>
                    <span class="font-medium">{{ statusLabel(filters.status) }}</span>
                  </li>
                  <li v-if="filters.trip_type" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('dept.filter_label_trip_type') }}</span>
                    <span class="font-medium">{{ tripTypeLabel(filters.trip_type) }}</span>
                  </li>
                  <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('dept.filter_label_date_range') }}</span>
                    <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
                  </li>
                  <li v-if="searchQ.trim()" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.search') }}</span>
                    <span class="max-w-[10rem] truncate font-medium" :title="searchQ">{{ searchQ }}</span>
                  </li>
                  <li v-if="filters.per_page !== DEFAULT_PER_PAGE" class="flex justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
                    <span class="font-medium">{{ filters.per_page }}</span>
                  </li>
                  <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
                </ul>
                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                    {{ t('trips_page.filter_show_controls_title') }}
                  </p>
                  <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                    {{ t('trips_page.filter_show_controls_hint') }}
                  </p>
                  <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                    <li v-for="fd in filterControlDefs" :key="'dept-all-vis-' + fd.id" class="flex items-start gap-2">
                      <input
                        :id="'dept-all-filter-vis-' + fd.id"
                        v-model="filterControlVisible[fd.id]"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                      />
                      <label
                        :for="'dept-all-filter-vis-' + fd.id"
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
                  @click="resetFilters()"
                >
                  {{ t('filter_bar.clear_all') }}
                </button>
              </div>
            </div>
          </details>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <AppFilterDropdown
              v-if="filterControlVisible.status"
              root-class="shrink-0"
              :label="t('dept.filter_label_status')"
              :summary-text="filters.status ? statusLabel(filters.status) : t('dept.filter_status_all')"
              summary-text-class="max-w-[10rem]"
              panel-class="min-w-[220px] py-1"
            >
              <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
                <li v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.status === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                    "
                    @click="applyFilterPatch($event, { status: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="filterControlVisible.trip_type"
              root-class="shrink-0"
              :label="t('dept.filter_label_trip_type')"
              :summary-text="filters.trip_type ? tripTypeLabel(filters.trip_type) : t('dept.filter_trip_type_all')"
              summary-text-class="max-w-[10rem]"
              panel-class="min-w-[220px] py-1"
            >
              <ul class="space-y-0.5 px-1 py-1">
                <li v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                  <button
                    type="button"
                    class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                    :class="
                      filters.trip_type === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                    "
                    @click="applyFilterPatch($event, { trip_type: opt.value })"
                  >
                    {{ opt.label }}
                  </button>
                </li>
              </ul>
            </AppFilterDropdown>

            <AppFilterDropdown
              v-if="filterControlVisible.date"
              root-class="shrink-0"
              :label="t('dept.filter_label_date_range')"
              :summary-text="dateRangeSummary"
              full-width-summary
              panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="sr-only" for="dept-all-date-from">{{ t('dept.filter_date_from') }}</label>
                <input
                  id="dept-all-date-from"
                  v-model="filters.from"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                  @change="onDateFilterChange"
                />
                <span class="hidden text-slate-300 dark:text-slate-600 sm:inline">—</span>
                <label class="sr-only" for="dept-all-date-to">{{ t('dept.filter_date_to') }}</label>
                <input
                  id="dept-all-date-to"
                  v-model="filters.to"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                  @change="onDateFilterChange"
                />
              </div>
            </AppFilterDropdown>

            <input
              v-if="filterControlVisible.search"
              v-model="searchQ"
              type="search"
              :aria-label="t('dept.filter_search_placeholder')"
              :placeholder="t('dept.filter_search_placeholder')"
              :title="t('dept.filter_search_placeholder')"
              class="h-9 w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-52 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            />

            <label v-if="filterControlVisible.per_page" class="inline-flex shrink-0 items-center gap-1.5">
              <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
              <select
                v-model.number="filters.per_page"
                class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                :aria-label="t('filter_bar.per_page')"
                @change="onPerPageChange"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline dark:text-slate-400" aria-hidden="true">{{
                t('dept.per_page_unit')
              }}</span>
            </label>
          </div>

          <div
            v-if="activeFilterCount > 0"
            class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 dark:border-violet-900/40"
          >
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
              :title="t('filter_bar.clear_icon')"
              :aria-label="t('filter_bar.clear_icon')"
              @click="resetFilters()"
            >
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                <XMarkIcon
                  class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                />
              </span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <div v-if="error" class="mt-6 max-w-5xl rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100">
      {{ error }}
    </div>
    <div v-else class="mt-8 max-w-5xl space-y-4">
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('dept.empty_all') }}</p>
      <DeptRequestCard v-for="r in items" :key="r.id" :req="r" :show-rejection="r.status === 'rejected'" @detail="goDetail" />
      <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
      <div v-else-if="page < lastPage" class="flex justify-center">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
          :disabled="loadingMore"
          @click="loadMore"
        >
          {{ loadingMore ? t('dept.loading') : t('dept.load_more') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { labelTripType } from '../../util/labels'

const FILTER_VISIBILITY_KEY = 'va.dept.all.filter_vis_v1'
const DEFAULT_PER_PAGE = 20

const STATUS_VALUES = ['pending', 'price_filled', 'approved', 'rejected']
const TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

const { t } = useI18n()
const router = useRouter()

const items = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const funnelDetailsRef = ref(null)

const filters = reactive({
  status: '',
  trip_type: '',
  from: '',
  to: '',
  per_page: DEFAULT_PER_PAGE,
})
const searchQ = ref('')

function defaultFilterControlVisibility() {
  return {
    status: true,
    trip_type: true,
    date: true,
    search: true,
    per_page: true,
  }
}

const filterControlVisible = reactive(defaultFilterControlVisibility())

const filterControlDefs = computed(() => [
  { id: 'status', label: t('dept.filter_label_status') },
  { id: 'trip_type', label: t('dept.filter_label_trip_type') },
  { id: 'date', label: t('dept.filter_label_date_range') },
  { id: 'search', label: t('filter_bar.search') },
  { id: 'per_page', label: t('filter_bar.per_page') },
])

let searchDebounceId = null

const statusFilterOptions = computed(() => [
  { value: '', label: t('dept.filter_status_all') },
  ...STATUS_VALUES.map((value) => ({
    value,
    label: t(`request_detail.request_status.${value}`),
  })),
])

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('dept.filter_trip_type_all') },
  ...TRIP_TYPES.map((value) => ({
    value,
    label: labelTripType(value),
  })),
])

const dateRangeSummary = computed(() => {
  if (!filters.from && !filters.to) return '—'
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.trip_type) n++
  if (filters.from || filters.to) n++
  if (searchQ.value.trim()) n++
  if (filters.per_page !== DEFAULT_PER_PAGE) n++
  return n
})

function statusLabel(value) {
  if (!value) return t('dept.filter_status_all')
  const key = `request_detail.request_status.${value}`
  return t(key)
}

function tripTypeLabel(value) {
  if (!value) return t('dept.filter_trip_type_all')
  return labelTripType(value)
}

function loadFilterVisibility() {
  try {
    const raw = localStorage.getItem(FILTER_VISIBILITY_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    for (const k of Object.keys(base)) {
      if (typeof o[k] === 'boolean') base[k] = o[k]
    }
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
}

function saveFilterVisibility() {
  try {
    localStorage.setItem(FILTER_VISIBILITY_KEY, JSON.stringify({ ...filterControlVisible }))
  } catch {
    /* ignore */
  }
}

function closeParentDetails(ev) {
  const el = ev?.currentTarget
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function closeFunnelMenu() {
  const el = funnelDetailsRef.value
  if (el && 'open' in el) el.open = false
}

function buildListParams(p) {
  /** @type {Record<string, unknown>} */
  const params = {
    per_page: filters.per_page,
    page: p,
    sort: 'depart_desc',
  }
  if (filters.status) params.status = filters.status
  if (filters.trip_type) params.trip_type = filters.trip_type
  if (filters.from) params.from = filters.from
  if (filters.to) params.to = filters.to
  const q = searchQ.value.trim()
  if (q) params.q = q
  return params
}

async function fetchList(p, append) {
  const res = await listRequests(buildListParams(p))
  lastPage.value = res.meta?.last_page ?? 1
  if (append) items.value = items.value.concat(res.items ?? [])
  else items.value = res.items ?? []
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  closeParentDetails(ev)
  page.value = 1
  reload()
}

function onDateFilterChange(ev) {
  closeParentDetails(ev)
  page.value = 1
  reload()
}

function onPerPageChange() {
  page.value = 1
  reload()
}

function resetFilters() {
  clearTimeout(searchDebounceId)
  filters.status = ''
  filters.trip_type = ''
  filters.from = ''
  filters.to = ''
  filters.per_page = DEFAULT_PER_PAGE
  searchQ.value = ''
  closeFunnelMenu()
  page.value = 1
  reload()
}

async function reload() {
  loading.value = true
  error.value = ''
  page.value = 1
  try {
    await fetchList(1, false)
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

watch(searchQ, () => {
  clearTimeout(searchDebounceId)
  searchDebounceId = setTimeout(() => {
    page.value = 1
    reload()
  }, 400)
})

watch(filterControlVisible, saveFilterVisibility, { deep: true })

onMounted(() => {
  loadFilterVisibility()
  reload()
})
</script>
