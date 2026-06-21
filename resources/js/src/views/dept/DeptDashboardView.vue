<template>
  <div class="w-full space-y-5">
    <header class="md:hidden">
      <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
        {{ t('dept.pending_heading', { dept: deptName }) }}
      </h1>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
        {{ t('dept.pending_subtitle', { count: kpiStats.pending_count }) }}
      </p>
    </header>
    <p class="mt-0 hidden text-base text-slate-600 dark:text-slate-400 md:block">
      {{ t('dept.pending_subtitle', { count: kpiStats.pending_count }) }}
    </p>

    <DeptPendingSummaryBar
      :stats="kpiStats"
      :loading="kpiLoading"
      :active-trip-type="filterTripType"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="pendingDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="dept-pending-search"
              :placeholder="t('dept.filter_search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              data-testid="dept-pending-toolbar-search"
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
                  test-id="dept-pending-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('dept.filter_btn') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'dept-pending-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`dept-pending-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`dept-pending-filter-vis-${fd.key}`"
                />
                <label
                  :for="`dept-pending-filter-vis-${fd.key}`"
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
              data-testid="dept-pending-reset-filters"
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
            v-model="filterTripType"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dept.filter_label_trip_type')"
            data-testid="dept-pending-filter-trip-type"
          >
            <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>
      </div>

      <div class="border-t border-slate-100 px-4 py-4 dark:border-slate-700 sm:px-5">
        <div
          v-if="loadError"
          class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100"
        >
          {{ loadError }}
        </div>
        <template v-else>
          <h2 class="sr-only">{{ t('dept.list_pending_title') }}</h2>
          <p v-if="!loading && !mergedItems.length" class="text-sm text-slate-500">{{ t('dept.empty_pending') }}</p>
          <div class="space-y-4">
            <DeptRequestCard
              v-for="r in mergedItems"
              :key="r.id"
              :req="r"
              :emphasize="r.status === 'price_filled'"
              :show-actions="r.status === 'price_filled'"
              :acting="actingId === r.id"
              @detail="goDetail"
              @approve="onApprove"
              @reject="openReject"
            />
          </div>
          <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
          <div v-else-if="canLoadMore" class="mt-4 flex justify-center">
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
              :disabled="loadingMore"
              data-testid="dept-pending-load-more"
              @click="loadMore"
            >
              {{ loadingMore ? t('dept.loading') : t('dept.load_more') }}
            </button>
          </div>
        </template>
      </div>
    </div>

    <Modal
      :open="rejectOpen"
      :title="t('dept.reject_modal_title')"
      :description="t('dept.reject_modal_desc')"
      @close="rejectOpen = false"
    >
      <div class="space-y-3">
        <textarea
          v-model="rejectReason"
          rows="3"
          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none ring-[#800020]/20 focus:ring-2"
          :placeholder="t('dept.reject_placeholder')"
        />
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            @click="rejectOpen = false"
          >
            {{ t('dept.cancel') }}
          </button>
          <Button variant="danger" :loading="rejectSubmitting" class="min-h-[44px] px-4" @click="confirmReject">
            {{ t('dept.reject_btn') }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { deptDecideDispatchRequest, getDeptSummary, listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'
import DeptPendingSummaryBar from '../../components/dept/DeptPendingSummaryBar.vue'
import Modal from '../../components/ui/Modal.vue'
import Button from '../../components/ui/Button.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { labelTripType } from '../../util/labels'
import { DEPT_FILTER_CONTROL_CLASS, DEPT_FILTER_LABEL_KEYS, DEPT_TRIP_TYPES } from '../../util/deptListPage.js'

const FILTER_CONTROL_CLASS = DEPT_FILTER_CONTROL_CLASS

const PENDING_FILTER_CONTROLS = [{ key: 'trip_type', label: '', default: false }]

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const deptLoadSummary = inject('deptLoadSummary', async () => {})

const kpiLoading = ref(false)
const kpiStats = ref({
  pending_count: 0,
  pending_today: 0,
  approved_this_month: 0,
  approval_rate_30d: null,
  cargo: 0,
})

const loading = ref(true)
const loadingMore = ref(false)
const loadError = ref('')
const filterTripType = ref('')
const searchInput = ref('')
const pendingDatagridRef = ref(null)
useDetailsAutoCloseWithin(pendingDatagridRef)

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs: filterControlDefsRaw,
} = useVisibleFilterControls(PENDING_FILTER_CONTROLS, 'va.dept.dashboard.filter_vis_v2')

const filterControlDefs = computed(() =>
  filterControlDefsRaw.map((fd) => ({
    key: fd.key,
    label: t(DEPT_FILTER_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('dept.filter_label_trip_type') },
  ...DEPT_TRIP_TYPES.map((value) => ({
    value,
    label: labelTripType(value),
  })),
])

const pagePending = ref(1)
const pagePriceFilled = ref(1)
const lastPagePending = ref(1)
const lastPagePrice = ref(1)
const itemsPending = ref([])
const itemsPriceFilled = ref([])

const actingId = ref(null)
const rejectOpen = ref(false)
const rejectReason = ref('')
const rejectTargetId = ref(null)
const rejectSubmitting = ref(false)

const deptName = computed(() => {
  const d = auth.user?.department?.name
  if (d) return d
  const cms = auth.user?.cms_user_info
  if (cms && typeof cms.department_name === 'string' && cms.department_name) return cms.department_name
  return t('dept.dept_fallback')
})

function mergeAndFilter() {
  let list = [...itemsPriceFilled.value, ...itemsPending.value]
  const q = searchInput.value.trim().toLowerCase()
  const tt = filterTripType.value
  list = list.filter((r) => {
    if (tt && r.trip_type !== tt) return false
    if (!q) return true
    const hay = `${r.origin ?? ''} ${r.destination ?? ''} ${r.notes ?? ''} ${r.id}`.toLowerCase()
    return hay.includes(q)
  })
  list.sort((a, b) => {
    const da = a.depart_at ? new Date(a.depart_at).getTime() : 0
    const db = b.depart_at ? new Date(b.depart_at).getTime() : 0
    return db - da
  })
  const seen = new Set()
  return list.filter((r) => {
    if (seen.has(r.id)) return false
    seen.add(r.id)
    return true
  })
}

const mergedItems = computed(() => mergeAndFilter())

const canLoadMore = computed(
  () => pagePending.value < lastPagePending.value || pagePriceFilled.value < lastPagePrice.value,
)

async function reloadKpis() {
  kpiLoading.value = true
  try {
    const [deptSummary, cargoRes] = await Promise.all([
      getDeptSummary(),
      listRequests({ status: 'price_filled', per_page: 1, page: 1, trip_type: 'cargo', sort: 'depart_desc' }),
    ])
    kpiStats.value = {
      pending_count: deptSummary.pending_count ?? 0,
      pending_today: deptSummary.pending_today ?? 0,
      approved_this_month: deptSummary.approved_this_month ?? 0,
      approval_rate_30d: deptSummary.approval_rate_30d ?? null,
      cargo: cargoRes.meta?.total ?? 0,
    }
  } catch {
    /* supplementary */
  } finally {
    kpiLoading.value = false
  }
}

async function fetchPage(status, page, append) {
  const res = await listRequests({
    status,
    per_page: 15,
    page,
    sort: 'depart_desc',
  })
  if (status === 'pending') {
    lastPagePending.value = res.meta?.last_page ?? 1
    if (append) itemsPending.value = itemsPending.value.concat(res.items ?? [])
    else itemsPending.value = res.items ?? []
  } else {
    lastPagePrice.value = res.meta?.last_page ?? 1
    if (append) itemsPriceFilled.value = itemsPriceFilled.value.concat(res.items ?? [])
    else itemsPriceFilled.value = res.items ?? []
  }
}

async function initialLoad() {
  loading.value = true
  loadError.value = ''
  pagePending.value = 1
  pagePriceFilled.value = 1
  try {
    await Promise.all([
      fetchPage('price_filled', 1, false),
      fetchPage('pending', 1, false),
      reloadKpis(),
    ])
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  loadingMore.value = true
  try {
    if (pagePriceFilled.value < lastPagePrice.value) {
      pagePriceFilled.value += 1
      await fetchPage('price_filled', pagePriceFilled.value, true)
    }
    if (pagePending.value < lastPagePending.value) {
      pagePending.value += 1
      await fetchPage('pending', pagePending.value, true)
    }
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loadingMore.value = false
  }
}

function flushSearch() {
  /* client-side filter via mergedItems */
}

function resetFilters() {
  filterTripType.value = ''
  searchInput.value = ''
  closeFilterPanel()
}

function onKpiQuickFilter(payload) {
  if (payload.kind === 'navigate' && payload.to === 'approved') {
    router.push({ name: 'deptApproved' })
    return
  }
  if (payload.kind === 'trip_type') {
    filterTripType.value = payload.value ?? ''
  }
}

function goDetail(id) {
  router.push({ name: 'deptRequestDetail', params: { id: String(id) } })
}

async function onApprove(id) {
  actingId.value = id
  try {
    await deptDecideDispatchRequest(id, { decision: 'approve' })
    itemsPriceFilled.value = itemsPriceFilled.value.filter((r) => r.id !== id)
    await reloadKpis()
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.action_error'))
  } finally {
    actingId.value = null
  }
}

function openReject(id) {
  rejectTargetId.value = id
  rejectReason.value = ''
  rejectOpen.value = true
}

async function confirmReject() {
  const id = rejectTargetId.value
  if (id == null) return
  const reason = rejectReason.value.trim()
  if (!reason) {
    loadError.value = t('dept.reject_reason_required')
    return
  }
  rejectSubmitting.value = true
  try {
    await deptDecideDispatchRequest(id, { decision: 'reject', rejection_reason: reason })
    itemsPriceFilled.value = itemsPriceFilled.value.filter((r) => r.id !== id)
    rejectOpen.value = false
    await reloadKpis()
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.action_error'))
  } finally {
    rejectSubmitting.value = false
  }
}

onMounted(() => {
  initialLoad()
})
</script>
