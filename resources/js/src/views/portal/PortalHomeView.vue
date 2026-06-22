<template>
  <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6 sm:py-6">
    <PortalHomeHero />

    <PortalDispatchSummaryBar
      variant="dashboard"
      :loading="summaryLoading"
      :summary="summary"
      :active-key="kpiActiveKey"
      @quick-filter="onKpiNavigate"
    />

    <div class="grid gap-4 lg:grid-cols-3 lg:items-start">
      <aside
        class="order-1 space-y-4 lg:order-2 lg:col-span-1"
        :aria-label="t('portal.home_sidebar_title')"
      >
        <PortalQuickActions :pending-count="pendingCount" />
        <PortalNotificationsPanel />
      </aside>

      <div class="order-2 min-w-0 lg:order-1 lg:col-span-2">
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
        <div class="min-w-0">
          <h2 class="text-base font-semibold text-slate-900">{{ t('portal.home_heading') }}</h2>
          <p class="mt-0.5 text-sm text-slate-600">{{ t('portal.home_lead') }}</p>
        </div>
        <RouterLink
          :to="{ name: 'portalRequestList' }"
          class="shrink-0 text-sm font-semibold text-va-800 hover:underline"
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
            class="min-h-[44px] rounded-lg px-3 py-2 text-xs font-semibold transition sm:min-h-[36px] sm:py-1.5 sm:text-sm"
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
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getPortalRequestsSummary, getPortalDispatchRequest, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalDispatchSummaryBar from '../../components/portal/PortalDispatchSummaryBar.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'
import PortalHomeHero from '../../components/portal/PortalHomeHero.vue'
import PortalQuickActions from '../../components/portal/PortalQuickActions.vue'
import PortalNotificationsPanel from '../../components/portal/PortalNotificationsPanel.vue'

const RECENT_LIMIT = 10
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

const pendingCount = computed(() => {
  const n = summary.value?.pending
  return typeof n === 'number' && Number.isFinite(n) ? n : 0
})

const recentFilterChips = computed(() => [
  { key: 'all', label: t('portal.recent_filter_all') },
  { key: 'pending', label: t('portal.recent_filter_pending') },
  { key: 'processing', label: t('portal.recent_filter_processing') },
  { key: 'completed', label: t('portal.recent_filter_completed') },
])

const displayItems = computed(() => items.value ?? [])

const recentFilterApi = computed(() => {
  const key = recentFilter.value
  if (key === 'pending' || key === 'processing' || key === 'completed') return key
  return undefined
})

const emptyTitle = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_title') : t('portal.empty_filtered'),
)

const emptyDescription = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_desc') : t('portal.recent_filter_empty_hint'),
)

function setRecentFilter(key) {
  if (recentFilter.value === key) return
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

async function loadRecent(opts = { silent: false }) {
  const silent = !!opts.silent
  if (!silent) loading.value = true
  fetchError.value = ''
  try {
    const params = {
      per_page: RECENT_LIMIT,
      page: 1,
      sort: 'created_desc',
    }
    if (recentFilterApi.value) {
      params.filter = recentFilterApi.value
    }
    const data = await listPortalRequests(params)
    items.value = data.items ?? []
  } catch (e) {
    fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
    items.value = []
  } finally {
    if (!silent) loading.value = false
  }
}

watch(recentFilter, () => {
  loadRecent()
})

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
