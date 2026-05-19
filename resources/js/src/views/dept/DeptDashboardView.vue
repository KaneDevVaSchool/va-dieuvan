<template>
  <div class="mx-auto w-full max-w-5xl">
    <header class="md:hidden">
      <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
        {{ t('dept.pending_heading', { dept: deptName }) }}
      </h1>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
        {{ t('dept.pending_subtitle', { count: summary.pending_count }) }}
      </p>
    </header>
    <p class="mt-0 hidden text-sm text-slate-600 dark:text-slate-400 md:block">
      {{ t('dept.pending_subtitle', { count: summary.pending_count }) }}
    </p>

    <div class="mt-8 grid max-w-5xl gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('dept.kpi_pending') }}</p>
        <p class="mt-2 text-3xl font-bold text-[#800020]">{{ summary.pending_today }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ t('dept.kpi_pending_sub') }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ t('dept.kpi_approved_month') }}
        </p>
        <p class="mt-2 text-3xl font-bold text-slate-900">{{ summary.approved_this_month }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ t('dept.kpi_approved_sub') }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('dept.kpi_rate') }}</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ summary.approval_rate_30d != null ? `${summary.approval_rate_30d}%` : '—' }}
        </p>
        <p class="mt-1 text-xs text-slate-500">{{ t('dept.kpi_rate_sub') }}</p>
      </div>
    </div>

    <div class="mt-10 max-w-5xl">
      <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">{{ t('dept.list_pending_title') }}</h2>

      <div class="relative z-40 mt-4">
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
                <span class="hidden text-sm font-medium text-slate-700 sm:inline dark:text-slate-200">{{ t('dept.filter_toolbar_label') }}</span>
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
                    <li v-if="filterTripType" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('dept.filter_label_trip_type') }}</span>
                      <span class="font-medium">{{ tripTypeLabel(filterTripType) }}</span>
                    </li>
                    <li v-if="filterQ.trim()" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.search') }}</span>
                      <span class="max-w-[10rem] truncate font-medium" :title="filterQ">{{ filterQ }}</span>
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
                      <li v-for="fd in filterControlDefs" :key="'dept-dash-vis-' + fd.id" class="flex items-start gap-2">
                        <input
                          :id="'dept-dash-filter-vis-' + fd.id"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label
                          :for="'dept-dash-filter-vis-' + fd.id"
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
                v-if="filterControlVisible.trip_type"
                root-class="shrink-0"
                :label="t('dept.filter_label_trip_type')"
                :summary-text="filterTripType ? tripTypeLabel(filterTripType) : t('dept.filter_trip_type_all')"
                summary-text-class="max-w-[10rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_all' : opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filterTripType === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="applyTripType($event, opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <input
                v-if="filterControlVisible.search"
                v-model.trim="filterQ"
                type="search"
                :aria-label="t('dept.filter_search_placeholder')"
                :placeholder="t('dept.filter_search_placeholder')"
                :title="t('dept.filter_search_placeholder')"
                class="h-9 w-[min(100%,11rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-52 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                autocomplete="off"
              />
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

      <div v-if="loadError" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ loadError }}
      </div>

      <div v-else class="mt-6 space-y-4">
        <p v-if="!loading && !mergedItems.length" class="text-sm text-slate-500">{{ t('dept.empty_pending') }}</p>
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
        <div v-if="loading" class="py-8 text-center text-sm text-slate-500">{{ t('dept.loading') }}</div>
        <div v-else-if="canLoadMore" class="flex justify-center pt-2">
          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50"
            :disabled="loadingMore"
            @click="loadMore"
          >
            <ArrowDownIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
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
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowDownIcon, ChevronDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { deptDecideDispatchRequest, getDeptSummary, listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'
import Modal from '../../components/ui/Modal.vue'
import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { labelTripType } from '../../util/labels'

const FILTER_VISIBILITY_KEY = 'va.dept.dashboard.filter_vis_v1'
const TRIP_TYPES = ['door_to_door', 'point_to_point', 'business', 'cargo']

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const deptLoadSummary = inject('deptLoadSummary', async () => {})

const summaryLocal = ref({
  pending_count: 0,
  pending_today: 0,
  approved_this_month: 0,
  approval_rate_30d: null,
})

const loading = ref(true)
const loadingMore = ref(false)
const loadError = ref('')
const filterTripType = ref('')
const filterQ = ref('')
const funnelDetailsRef = ref(null)

function defaultFilterControlVisibility() {
  return {
    trip_type: true,
    search: true,
  }
}

const filterControlVisible = reactive(defaultFilterControlVisibility())

const filterControlDefs = computed(() => [
  { id: 'trip_type', label: t('dept.filter_label_trip_type') },
  { id: 'search', label: t('filter_bar.search') },
])

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('dept.filter_trip_type_all') },
  ...TRIP_TYPES.map((value) => ({
    value,
    label: labelTripType(value),
  })),
])

const activeFilterCount = computed(() => {
  let n = 0
  if (filterTripType.value) n++
  if (filterQ.value.trim()) n++
  return n
})

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

function applyTripType(ev, value) {
  filterTripType.value = value
  closeParentDetails(ev)
}

function resetFilters() {
  filterTripType.value = ''
  filterQ.value = ''
  closeFunnelMenu()
}

watch(filterControlVisible, saveFilterVisibility, { deep: true })

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

const summary = computed(() => summaryLocal.value)

function mergeAndFilter() {
  let list = [...itemsPriceFilled.value, ...itemsPending.value]
  const q = filterQ.value.toLowerCase()
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

async function refreshSummaryLocal() {
  try {
    const data = await getDeptSummary()
    summaryLocal.value = {
      pending_count: data.pending_count ?? 0,
      pending_today: data.pending_today ?? 0,
      approved_this_month: data.approved_this_month ?? 0,
      approval_rate_30d: data.approval_rate_30d ?? null,
    }
  } catch {
    /* sidebar still loads */
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
      refreshSummaryLocal(),
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

function goDetail(id) {
  router.push({ name: 'deptRequestDetail', params: { id: String(id) } })
}

async function onApprove(id) {
  actingId.value = id
  try {
    await deptDecideDispatchRequest(id, { decision: 'approve' })
    itemsPriceFilled.value = itemsPriceFilled.value.filter((r) => r.id !== id)
    await refreshSummaryLocal()
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
    await refreshSummaryLocal()
    await deptLoadSummary()
  } catch (e) {
    loadError.value = formatApiError(e, t('dept.action_error'))
  } finally {
    rejectSubmitting.value = false
  }
}

onMounted(() => {
  loadFilterVisibility()
  initialLoad()
})
</script>
