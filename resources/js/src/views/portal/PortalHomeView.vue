<template>
  <div class="w-full min-w-0 space-y-4 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
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
    <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
        <div class="flex flex-col gap-3">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex shrink-0 items-center rounded-md bg-va-800 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white"
                >
                  {{ t('portal.home_top5_badge') }}
                </span>
                <h2 class="text-base font-semibold text-slate-900">{{ t('portal.home_heading') }}</h2>
              </div>
              <p class="mt-1 text-sm text-slate-600">{{ t('portal.home_lead') }}</p>
            </div>
            <RouterLink
              :to="{ name: 'portalRequestList', query: recentListQuery }"
              class="inline-flex h-10 shrink-0 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-va-800 shadow-sm transition hover:border-va-200 hover:bg-va-50"
              data-testid="portal-home-view-all"
            >
              {{ t('portal.view_all_requests') }}
            </RouterLink>
          </div>
          <div class="flex w-full min-w-0 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <DatagridSegmentedControl
              v-model="recentFilter"
              :options="recentQuickOptions"
              :aria-label="t('portal.recent_filter_aria')"
            />
          </div>
        </div>
      </div>

      <PortalRequestSkeleton
        v-if="loading && !silentListRefresh"
        class="px-4 py-8"
        :count="5"
        :aria-label="t('portal.loading_requests')"
      />

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

      <div v-else class="space-y-3 px-4 pb-4 pt-3 sm:space-y-4">
        <p
          class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2 text-xs font-medium text-slate-600 sm:text-sm"
          role="status"
        >
          {{ recentSummaryText }}
        </p>
        <div class="space-y-3 sm:space-y-4">
          <PortalHomeRequestCard
            v-for="(req, index) in displayItems"
            :key="req.id"
            :req="req"
            :rank="index + 1"
            :highlighted="isHighlighted(req)"
          />
        </div>
      </div>
    </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getPortalRequestsSummary, getPortalDispatchRequest, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalDispatchSummaryBar from '../../components/portal/PortalDispatchSummaryBar.vue'
import PortalHomeRequestCard from '../../components/portal/PortalHomeRequestCard.vue'
import DatagridSegmentedControl from '../../components/shared/ui/DatagridSegmentedControl.vue'
import PortalHomeHero from '../../components/portal/PortalHomeHero.vue'
import PortalQuickActions from '../../components/portal/PortalQuickActions.vue'
import PortalNotificationsPanel from '../../components/portal/PortalNotificationsPanel.vue'

const RECENT_LIMIT = 5
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

const recentQuickOptions = computed(() => [
  { value: 'all', label: t('portal.shell.quick_all') },
  { value: 'pending', label: t('portal.filter_pending') },
  { value: 'processing', label: t('portal.shell.quick_processing') },
  { value: 'done', label: t('portal.shell.quick_done') },
])

const displayItems = computed(() => items.value ?? [])

const recentTotal = computed(() => {
  const s = summary.value
  const key = recentFilter.value
  if (key === 'pending' && typeof s?.pending === 'number') return s.pending
  if (key === 'processing' && typeof s?.processing === 'number') return s.processing
  if (key === 'done' && typeof s?.completed_this_month === 'number') return s.completed_this_month
  if (typeof s?.total === 'number') return s.total
  return displayItems.value.length
})

const recentSummaryText = computed(() =>
  t('portal.home_recent_summary', {
    shown: displayItems.value.length,
    total: recentTotal.value,
  }),
)

const recentListQuery = computed(() => {
  const key = recentFilter.value
  if (key === 'pending' || key === 'processing') return { filter: key }
  if (key === 'done') return { filter: 'completed' }
  return {}
})

const recentFilterApi = computed(() => {
  const key = recentFilter.value
  if (key === 'pending' || key === 'processing') return key
  if (key === 'done') return 'completed'
  return undefined
})

const emptyTitle = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_title') : t('portal.empty_filtered'),
)

const emptyDescription = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_desc') : t('portal.recent_filter_empty_hint'),
)

function isHighlighted(req) {
  const hid = highlightRequestId.value
  if (hid == null || hid === '') return false
  return Number(req?.id) === Number(hid)
}

function syncKpiActiveFromRecentFilter() {
  const v = recentFilter.value
  if (v === 'pending') kpiActiveKey.value = 'pending'
  else if (v === 'processing') kpiActiveKey.value = 'processing'
  else if (v === 'done') kpiActiveKey.value = 'completed'
  else kpiActiveKey.value = ''
}

function onKpiNavigate(payload) {
  const key = payload?.key ?? ''
  kpiActiveKey.value = key
  if (key === 'sla_risk') {
    router.push({ name: 'portalRequestList', query: { sla: '1' } })
    return
  }
  if (key === 'processing') {
    recentFilter.value = 'processing'
    return
  }
  if (key === 'completed') {
    recentFilter.value = 'done'
    return
  }
  if (key === 'pending') {
    recentFilter.value = 'pending'
    return
  }
  recentFilter.value = 'all'
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
  syncKpiActiveFromRecentFilter()
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
  await scrollToHighlightedCard()
})

async function scrollToHighlightedCard() {
  const id = highlightRequestId.value
  if (id == null || id === '') return
  await nextTick()
  const el = document.querySelector(`[data-testid="portal-home-request-card-${id}"]`)
  el?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
}

onBeforeUnmount(() => {})
</script>
