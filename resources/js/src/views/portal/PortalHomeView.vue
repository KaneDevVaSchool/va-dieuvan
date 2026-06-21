<template>
  <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6 sm:py-6">
    <PortalDispatchSummaryBar
      variant="dashboard"
      :loading="summaryLoading"
      :summary="summary"
      :active-key="kpiActiveKey"
      @quick-filter="onKpiNavigate"
    />

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
        <h2 class="text-base font-semibold text-slate-900">{{ t('portal.recent_requests_heading') }}</h2>
        <RouterLink
          :to="{ name: 'portalRequestList' }"
          class="text-sm font-semibold text-va-800 hover:underline"
          data-testid="portal-home-view-all"
        >
          {{ t('portal.view_all_requests') }}
        </RouterLink>
      </div>

      <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
        <div class="flex flex-wrap gap-2" role="group" :aria-label="t('portal.recent_filter_aria')">
          <button
            v-for="chip in recentFilterChips"
            :key="chip.key"
            type="button"
            class="min-h-[36px] rounded-lg px-3 py-1.5 text-xs font-semibold transition sm:text-sm"
            :class="
              recentFilter === chip.key
                ? 'bg-va-800 text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            "
            :data-testid="`portal-recent-filter-${chip.key}`"
            @click="setRecentFilter(chip.key)"
          >
            {{ chip.label }}
          </button>
        </div>
      </div>

      <PortalRequestSkeleton v-if="loading && !silentListRefresh" class="px-4 py-8" :aria-label="t('portal.loading_requests')" />

      <div v-else-if="fetchError" class="mx-4 my-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ fetchError }}
      </div>

      <PortalEmptyState
        v-else-if="!displayItems.length"
        class="px-4 py-10"
        :title="emptyTitle"
        :description="emptyDescription"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'portalCreate' }"
            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-va-800 px-6 text-sm font-semibold text-white hover:bg-va-900"
            data-testid="portal-home-create-empty"
          >
            {{ t('portal.cta_primary') }}
          </RouterLink>
        </template>
      </PortalEmptyState>

      <div v-else class="px-2 pb-2 pt-1 sm:px-3">
        <PortalRequestsTable :requests="displayItems" :highlight-request-id="highlightRequestId" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getPortalRequestsSummary, getPortalDispatchRequest, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalDispatchSummaryBar from '../../components/portal/PortalDispatchSummaryBar.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'

const RECENT_LIMIT = 8
const APPROVED_FETCH_LIMIT = 40
const PORTAL_RECENT_HIGHLIGHT_KEY = 'portal_recent_highlight_id'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const silentListRefresh = ref(false)
const fetchError = ref('')
const summary = ref(null)
const summaryLoading = ref(true)
const kpiActiveKey = ref('')
const recentFilter = ref('all')
const items = ref([])
const highlightRequestId = ref(null)

const recentFilterChips = computed(() => [
  { key: 'all', label: t('portal.filter_all') },
  { key: 'pending', label: t('portal.filter_pending') },
  { key: 'approved', label: t('portal.filter_approved') },
])

const displayItems = computed(() => {
  const list = items.value ?? []
  if (recentFilter.value === 'pending') {
    return list.filter((r) => r.status === 'pending' || r.status === 'price_filled')
  }
  if (recentFilter.value === 'approved') {
    return list.filter((r) => r.status === 'approved')
  }
  return list
})

const emptyTitle = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_title') : t('portal.empty_filtered'),
)

const emptyDescription = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_desc') : t('portal.empty_filtered'),
)

function setRecentFilter(key) {
  recentFilter.value = key
}

function onKpiNavigate(payload) {
  const key = payload?.key ?? ''
  kpiActiveKey.value = key
  const query = {}
  if (key === 'pending') query.filter = 'pending'
  else if (key === 'processing') query.filter = 'processing'
  else if (key === 'completed') query.filter = 'completed'
  else if (key === 'sla_risk') query.sla = '1'
  router.push({ name: 'portalRequestList', query })
}

async function loadSummary() {
  summaryLoading.value = true
  try {
    summary.value = await getPortalRequestsSummary()
  } catch {
    summary.value = null
  } finally {
    summaryLoading.value = false
  }
}

async function loadRecent() {
  loading.value = true
  fetchError.value = ''
  try {
    const data = await listPortalRequests({
      per_page: Math.max(RECENT_LIMIT, APPROVED_FETCH_LIMIT),
      page: 1,
      sort: 'created_desc',
    })
    items.value = (data.items ?? []).slice(0, RECENT_LIMIT)
  } catch (e) {
    fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loadSummary()
  await loadRecent()
  try {
    const hid = sessionStorage.getItem(PORTAL_RECENT_HIGHLIGHT_KEY)
    if (hid) {
      highlightRequestId.value = Number(hid)
      sessionStorage.removeItem(PORTAL_RECENT_HIGHLIGHT_KEY)
    }
  } catch {
    // ignore
  }
  const createdId = route.query.created
  if (createdId) {
    try {
      await getPortalDispatchRequest(Number(createdId))
      highlightRequestId.value = Number(createdId)
    } catch {
      // ignore
    }
  }
})

onBeforeUnmount(() => {})
</script>
