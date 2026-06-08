<template>
  <div>
    <section class="mx-auto max-w-7xl px-3 py-8 xs:px-4 sm:px-6 lg:py-10">
      <!-- Heading + primary CTA -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ greetingTitle }}
          </h1>
          <p class="mt-1 text-sm font-medium text-va-800/90">{{ todayLine }}</p>
          <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ t('portal.dashboard_lead') }}</p>
        </div>
        <RouterLink
          :to="{ name: 'portalCreate' }"
          class="inline-flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-2xl bg-va-800 px-6 text-sm font-bold text-white shadow-md transition hover:bg-va-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800 max-[379px]:px-3"
        >
          <PlusCircleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          <span class="max-[379px]:sr-only">{{ t('portal.cta_primary') }}</span>
        </RouterLink>
      </div>

      <PortalKpiCards class="mt-8" :loading="summaryLoading" :summary="summary" />

      <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
          <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ t('portal.recent_requests_heading') }}</h2>
            <RouterLink
              :to="{ name: 'portalRequestList' }"
              class="text-sm font-semibold text-va-800 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
            >
              {{ t('portal.view_all_requests') }}
            </RouterLink>
          </div>

          <div
            class="flex flex-wrap gap-2"
            role="group"
            :aria-label="t('portal.recent_filter_aria')"
          >
            <button
              v-for="chip in recentFilterChips"
              :key="chip.key"
              type="button"
              class="rounded-full px-3 py-1.5 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
              :class="
                recentFilter === chip.key
                  ? 'bg-va-800 text-white shadow-sm'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              "
              @click="setRecentFilter(chip.key)"
            >
              {{ chip.label }}
            </button>
          </div>

          <PortalRequestSkeleton v-if="loading && !silentListRefresh" class="mt-2" :aria-label="t('portal.loading_requests')" />

          <div v-else-if="fetchError" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ fetchError }}
          </div>

          <PortalEmptyState
            v-else-if="!displayItems.length"
            class="mt-2"
            :title="emptyTitle"
            :description="emptyDescription"
          >
            <template #action>
              <RouterLink
                :to="{ name: 'portalCreate' }"
                class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-va-800 px-8 text-sm font-semibold text-white shadow-md hover:bg-va-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
              >
                {{ t('portal.cta_primary') }}
              </RouterLink>
            </template>
          </PortalEmptyState>

          <PortalRequestsTable
            v-else
            :requests="displayItems"
            :highlight-request-id="highlightRequestId"
          />
        </div>

        <aside class="space-y-6 lg:sticky lg:top-[5.5rem] lg:self-start">
          <PortalQuickActions :pending-count="pendingCount" />
          <PortalNotificationsPanel :refresh-tick="pollTick" />
        </aside>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PlusCircleIcon } from '@heroicons/vue/24/outline'
import { getPortalRequestsSummary, getPortalDispatchRequest, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import { useAuthStore } from '../../store'
import { formatPortalTodayDate } from '../../util/portalDatetime.js'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalKpiCards from '../../components/portal/PortalKpiCards.vue'
import PortalQuickActions from '../../components/portal/PortalQuickActions.vue'
import PortalNotificationsPanel from '../../components/portal/PortalNotificationsPanel.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'

const RECENT_LIMIT = 8
const APPROVED_FETCH_LIMIT = 40
const PORTAL_RECENT_HIGHLIGHT_KEY = 'portal_recent_highlight_id'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const silentListRefresh = ref(false)
const fetchError = ref('')
const items = ref([])
const recentFilter = ref('all')
const highlightRequestId = ref(null)

const summary = ref(null)
const summaryLoading = ref(true)

const pollTick = ref(0)
let pollTimer = null

const localeKey = computed(() => (locale.value === 'en' ? 'en' : 'vi'))

const greetingTitle = computed(() => {
  const raw = auth.user?.name?.trim() || auth.user?.email?.trim() || ''
  const first = raw.split(/\s+/).filter(Boolean)[0] || t('portal.dashboard_guest_name')
  return t('portal.dashboard_greeting', { name: first })
})

const todayLine = computed(() => t('portal.dashboard_today', { date: formatPortalTodayDate(localeKey.value) }))

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

const emptyTitle = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_title') : t('portal.filter_empty'),
)
const emptyDescription = computed(() =>
  recentFilter.value === 'all' ? t('portal.empty_desc') : t('portal.recent_filter_empty_hint'),
)

function isProcessingRequest(req) {
  if (req?.status !== 'approved') return false
  const trip = req.trip
  if (!trip) return true
  return !['completed', 'cancelled'].includes(trip.status)
}

function isCompletedRequest(req) {
  return req?.status === 'approved' && req?.trip?.status === 'completed'
}

function applyRecentFilter(list) {
  const rows = list ?? []
  switch (recentFilter.value) {
    case 'pending':
      return rows.filter((r) => r.status === 'pending' || r.status === 'price_filled')
    case 'processing':
      return rows.filter(isProcessingRequest)
    case 'completed':
      return rows.filter(isCompletedRequest)
    default:
      return rows
  }
}

const displayItems = computed(() => applyRecentFilter(items.value).slice(0, RECENT_LIMIT))

function consumeHighlightFromNavigation() {
  let id = null
  const q = route.query.highlight
  if (q != null && String(q).trim() !== '') {
    const n = Number(q)
    if (Number.isFinite(n) && n > 0) id = n
  }
  if (id == null) {
    try {
      const raw = sessionStorage.getItem(PORTAL_RECENT_HIGHLIGHT_KEY)
      if (raw) {
        const n = Number(raw)
        if (Number.isFinite(n) && n > 0) id = n
        sessionStorage.removeItem(PORTAL_RECENT_HIGHLIGHT_KEY)
      }
    } catch {
      /* ignore */
    }
  } else if (route.query.highlight != null) {
    const next = { ...route.query }
    delete next.highlight
    router.replace({ query: next })
  }
  highlightRequestId.value = id
}

async function ensureHighlightVisible() {
  const id = highlightRequestId.value
  if (!id) return
  if (applyRecentFilter(items.value).some((r) => Number(r.id) === Number(id))) return
  try {
    const req = await getPortalDispatchRequest(id)
    if (req && applyRecentFilter([req]).length) {
      items.value = [req, ...items.value.filter((r) => Number(r.id) !== Number(id))]
    } else {
      highlightRequestId.value = null
    }
  } catch {
    highlightRequestId.value = null
  }
}

async function loadSummary(silent = false) {
  if (!silent) summaryLoading.value = true
  try {
    summary.value = await getPortalRequestsSummary()
  } catch {
    if (!silent) {
      summary.value = {
        processing: 0,
        pending: 0,
        completed_this_month: 0,
        rejected: 0,
        trends: { completed_this_month: 0 },
      }
    }
  } finally {
    if (!silent) summaryLoading.value = false
  }
}

async function loadRecent(silent = false) {
  silentListRefresh.value = silent
  if (!silent) {
    loading.value = true
    fetchError.value = ''
  }
  try {
    const filter = recentFilter.value
    const params = { page: 1, sort: 'created_desc' }
    if (filter === 'pending') {
      params.filter = 'pending'
      params.per_page = RECENT_LIMIT
    } else if (filter === 'processing' || filter === 'completed') {
      params.filter = 'approved'
      params.per_page = APPROVED_FETCH_LIMIT
    } else {
      params.per_page = RECENT_LIMIT
    }
    const data = await listPortalRequests(params)
    items.value = data.items ?? []
    await ensureHighlightVisible()
  } catch (e) {
    if (!silent) {
      fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
      items.value = []
    }
  } finally {
    silentListRefresh.value = false
    if (!silent) loading.value = false
  }
}

function setRecentFilter(key) {
  if (recentFilter.value === key) return
  recentFilter.value = key
  loadRecent(false)
}

async function refreshAllQuiet() {
  pollTick.value += 1
  await Promise.all([loadSummary(true), loadRecent(true)])
}

function onVisibilityChange() {
  if (document.visibilityState === 'visible') refreshAllQuiet()
}

onMounted(async () => {
  consumeHighlightFromNavigation()
  await Promise.all([loadSummary(false), loadRecent(false)])
  pollTimer = window.setInterval(refreshAllQuiet, 30_000)
  document.addEventListener('visibilitychange', onVisibilityChange)
})

onBeforeUnmount(() => {
  if (pollTimer) window.clearInterval(pollTimer)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>
