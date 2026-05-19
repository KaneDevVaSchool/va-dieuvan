<template>
  <div class="px-6 py-8 lg:px-10">
    <header class="max-w-5xl">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">
        {{ t('dept.pending_heading', { dept: deptName }) }}
      </h1>
      <p class="mt-2 text-sm text-slate-600">
        {{ t('dept.pending_subtitle', { count: summary.pending_count }) }}
      </p>
    </header>

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
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">{{ t('dept.list_pending_title') }}</h2>
        <button
          type="button"
          class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
          @click="filterOpen = !filterOpen"
        >
          <FunnelIcon class="h-4 w-4" aria-hidden="true" />
          {{ t('dept.filter_btn') }}
        </button>
      </div>

      <div
        v-if="filterOpen"
        class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end"
      >
        <label class="block min-w-0 flex-1 text-sm">
          <span class="mb-1 block font-medium text-slate-700">{{ t('requests_page.filter_trip_type') }}</span>
          <select
            v-model="filterTripType"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium outline-none ring-[#800020]/20 focus:ring-2"
          >
            <option value="">{{ t('requests_page.all') }}</option>
            <option value="door_to_door">{{ t('request_detail.trip_type.door_to_door') }}</option>
            <option value="point_to_point">{{ t('request_detail.trip_type.point_to_point') }}</option>
            <option value="business">{{ t('request_detail.trip_type.business') }}</option>
            <option value="cargo">{{ t('request_detail.trip_type.cargo') }}</option>
          </select>
        </label>
        <label class="block min-w-0 flex-1 text-sm">
          <span class="mb-1 block font-medium text-slate-700">{{ t('requests_page.search_placeholder') }}</span>
          <input
            v-model.trim="filterQ"
            type="search"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-[#800020]/20 focus:ring-2"
            autocomplete="off"
          />
        </label>
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
import { computed, inject, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { deptDecideDispatchRequest, getDeptSummary, listRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import DeptRequestCard from '../../components/dept/DeptRequestCard.vue'
import Modal from '../../components/ui/Modal.vue'
import Button from '../../components/ui/Button.vue'

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
const filterOpen = ref(false)
const filterTripType = ref('')
const filterQ = ref('')

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

onMounted(initialLoad)
</script>
