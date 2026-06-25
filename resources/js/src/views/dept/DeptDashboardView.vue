<template>
  <div class="flex min-h-0 w-full flex-col gap-4 xl:gap-5">
    <header class="md:hidden">
      <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
        {{ t('dept.pending_heading', { dept: deptName }) }}
      </h1>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
        {{ t('dept.inbox_subtitle', { count: kpiStats.pending_count }) }}
      </p>
    </header>
    <p class="mt-0 hidden text-base text-slate-600 dark:text-slate-400 md:block">
      {{ t('dept.inbox_subtitle', { count: kpiStats.pending_count }) }}
    </p>

    <DeptPendingSummaryBar
      :stats="kpiStats"
      :loading="kpiLoading"
      :active-queue-filter="kpiQueueFilter"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="pendingDatagridRef"
      class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40 xl:max-h-[calc(100dvh-14rem)]"
    >
      <div class="shrink-0 border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
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

      <div
        class="flex min-h-0 min-w-0 flex-1 flex-col border-t border-slate-100 dark:border-slate-700 xl:flex-row"
      >
        <div
          class="flex min-h-0 min-w-0 flex-col xl:w-[min(26rem,38%)] xl:shrink-0 xl:border-r xl:border-slate-100 xl:dark:border-slate-700"
        >
          <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4">
            <div
              v-if="loadError"
              class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-100"
            >
              {{ loadError }}
            </div>
            <template v-else>
              <h2 class="sr-only">{{ t('dept.list_pending_title') }}</h2>
              <p v-if="!loading && !filteredItems.length" class="py-6 text-center text-sm text-slate-500">
                {{
                  items.length && inboxFilterActive
                    ? t('dept.inbox_empty_filter')
                    : t('dept.empty_pending')
                }}
              </p>
              <button
                v-if="!loading && !filteredItems.length && inboxFilterActive"
                type="button"
                class="mx-auto mt-2 block text-sm font-semibold text-[color:var(--va-brand)] hover:underline"
                data-testid="dept-inbox-clear-filters"
                @click="clearInboxFilters"
              >
                {{ t('dept.inbox_clear_filters') }}
              </button>
              <div v-else class="space-y-2">
                <DeptApprovalInboxRow
                  v-for="r in filteredItems"
                  :key="r.id"
                  :req="r"
                  :selected="selectedId === r.id"
                  :acting="actingId === r.id"
                  :sla-hours="slaHours"
                  @select="onSelectRow"
                  @detail="goDetail"
                  @approve="openApprove"
                  @reject="openReject"
                />
              </div>
              <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
              <div v-else-if="canLoadMore" class="mt-3 flex justify-center pb-2">
                <button
                  type="button"
                  class="rounded-xl border border-slate-200 bg-white px-6 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
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

        <div class="hidden min-h-0 min-w-0 flex-1 p-3 xl:flex xl:p-4">
          <DeptApprovalDetailPanel
            :request-id="selectedId"
            :sla-hours="slaHours"
            :acting="actingId != null && actingId === selectedId"
            @approve="openApprove"
            @reject="openReject"
            @open-full="goDetail"
          />
        </div>
      </div>
    </div>

    <Modal
      :open="approveOpen"
      :title="t('dept.approve_modal_title')"
      :description="t('dept.approve_modal_desc')"
      @close="approveOpen = false"
    >
      <div class="space-y-3">
        <textarea
          v-model="approveNote"
          rows="3"
          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none ring-[#800020]/20 focus:ring-2 dark:border-slate-600 dark:bg-slate-900"
          :placeholder="t('dept.approve_note_placeholder')"
        />
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            @click="approveOpen = false"
          >
            {{ t('dept.cancel') }}
          </button>
          <Button variant="primary" :loading="approveSubmitting" class="min-h-[44px] px-4" @click="confirmApprove">
            {{ t('dept.approve_confirm_btn') }}
          </Button>
        </div>
      </div>
    </Modal>

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
import { computed, inject, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'
import { deptDecideDispatchRequest, getDeptSummary, listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptApprovalInboxRow from '../../components/dept/DeptApprovalInboxRow.vue'
import DeptApprovalDetailPanel from '../../components/dept/DeptApprovalDetailPanel.vue'
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
import { matchesDeptInboxKpiFilter } from '../../composables/useDeptApprovalSla.js'

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
  overdue_count: 0,
  urgent_pending_count: 0,
  approved_this_month: 0,
  approval_rate_30d: null,
  approved_total: 0,
  rejected_total: 0,
  dept_approval_sla_hours: 24,
})

const slaHours = computed(() => kpiStats.value.dept_approval_sla_hours ?? 24)

const loading = ref(true)
const loadingMore = ref(false)
const loadError = ref('')
const filterTripType = ref('')
const kpiQueueFilter = ref('')
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
} = useVisibleFilterControls(PENDING_FILTER_CONTROLS, 'va.dept.dashboard.filter_vis_v3')

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

const page = ref(1)
const lastPage = ref(1)
const items = ref([])
const selectedId = ref(null)

const actingId = ref(null)
const approveOpen = ref(false)
const approveNote = ref('')
const approveTargetId = ref(null)
const approveSubmitting = ref(false)
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

const filteredItems = computed(() => {
  let list = [...items.value]
  const q = searchInput.value.trim().toLowerCase()
  const tt = filterTripType.value
  const kf = kpiQueueFilter.value
  const sla = slaHours.value
  list = list.filter((r) => {
    if (tt && r.trip_type !== tt) return false
    if (!matchesDeptInboxKpiFilter(r, kf, sla)) return false
    if (!q) return true
    const hay = `${r.origin ?? ''} ${r.destination ?? ''} ${r.notes ?? ''} ${r.id}`.toLowerCase()
    return hay.includes(q)
  })
  return list
})

const inboxFilterActive = computed(() => Boolean(kpiQueueFilter.value || filterTripType.value || searchInput.value.trim()))

const canLoadMore = computed(() => page.value < lastPage.value)

async function reloadKpis() {
  kpiLoading.value = true
  try {
    const deptSummary = await getDeptSummary()
    kpiStats.value = {
      pending_count: deptSummary.pending_count ?? 0,
      pending_today: deptSummary.pending_today ?? 0,
      overdue_count: deptSummary.overdue_count ?? 0,
      urgent_pending_count: deptSummary.urgent_pending_count ?? 0,
      approved_this_month: deptSummary.approved_this_month ?? 0,
      approval_rate_30d: deptSummary.approval_rate_30d ?? null,
      approved_total: deptSummary.approved_total ?? 0,
      rejected_total: deptSummary.rejected_total ?? 0,
      dept_approval_sla_hours: deptSummary.dept_approval_sla_hours ?? 24,
    }
  } catch {
    /* supplementary */
  } finally {
    kpiLoading.value = false
  }
}

async function fetchPage(pageNum, append) {
  const res = await listRequests({
    status: 'price_filled',
    per_page: 25,
    page: pageNum,
    sort: 'approval_inbox',
  })
  lastPage.value = res.meta?.last_page ?? 1
  const batch = res.items ?? []
  if (append) items.value = items.value.concat(batch)
  else items.value = batch
}

function removeItem(id) {
  items.value = items.value.filter((r) => r.id !== id)
  if (selectedId.value === id) {
    const next = items.value[0]
    selectedId.value = next?.id ?? null
  }
  kpiStats.value = {
    ...kpiStats.value,
    pending_count: Math.max(0, (kpiStats.value.pending_count ?? 1) - 1),
  }
}

async function initialLoad() {
  loading.value = true
  loadError.value = ''
  page.value = 1
  try {
    await Promise.all([fetchPage(1, false), reloadKpis()])
    await deptLoadSummary()
    if (!selectedId.value && items.value.length) {
      selectedId.value = items.value[0].id
    }
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  loadingMore.value = true
  try {
    page.value += 1
    await fetchPage(page.value, true)
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.load_error'))
  } finally {
    loadingMore.value = false
  }
}

function flushSearch() {
  /* client-side */
}

function onKpiQuickFilter(payload) {
  if (payload.kind === 'navigate' && payload.to === 'approved') {
    router.push({ name: 'deptApproved' })
    return
  }
  if (payload.kind === 'kpi') {
    const v = payload.value ?? ''
    if (v === '') {
      kpiQueueFilter.value = ''
      return
    }
    kpiQueueFilter.value = kpiQueueFilter.value === v ? '' : v
  }
}

function clearInboxFilters() {
  kpiQueueFilter.value = ''
  filterTripType.value = ''
  searchInput.value = ''
  closeFilterPanel()
}

function onSelectRow(id) {
  selectedId.value = id
}

function goDetail(id) {
  router.push({ name: 'deptRequestDetail', params: { id: String(id) } })
}

function openApprove(id) {
  approveTargetId.value = id
  approveNote.value = ''
  approveOpen.value = true
}

async function confirmApprove() {
  const id = approveTargetId.value
  if (id == null) return
  approveSubmitting.value = true
  actingId.value = id
  try {
    await deptDecideDispatchRequest(id, { decision: 'approve' })
    removeItem(id)
    approveOpen.value = false
    await reloadKpis()
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.action_error'))
  } finally {
    approveSubmitting.value = false
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
  actingId.value = id
  try {
    await deptDecideDispatchRequest(id, { decision: 'reject', rejection_reason: reason })
    removeItem(id)
    rejectOpen.value = false
    await reloadKpis()
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.action_error'))
  } finally {
    rejectSubmitting.value = false
    actingId.value = null
  }
}

watch(filteredItems, (list) => {
  if (!list.length) {
    selectedId.value = null
    return
  }
  if (!list.some((r) => r.id === selectedId.value)) {
    selectedId.value = list[0].id
  }
})

onMounted(() => {
  initialLoad()
})
</script>
