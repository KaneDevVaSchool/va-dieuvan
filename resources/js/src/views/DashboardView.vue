<template>
  <div class="space-y-5">
    <div
      v-if="loadError"
      class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100"
    >
      {{ loadError }}
    </div>

    <div class="space-y-4">
      <!-- Tiêu đề trang -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
            {{ t('dashboard_analytics.title') }}
          </h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ t('dashboard_analytics.subtitle') }}
          </p>
        </div>
        <RouterLink
          class="shrink-0 text-sm font-medium text-va-800 hover:text-va-900 dark:text-va-300 dark:hover:text-va-200"
          :to="staffPath('/reports')"
        >
          {{ t('dashboard_analytics.reports_link') }} →
        </RouterLink>
      </div>

      <TransportReportFilters />
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <!-- Dải thống kê KPI -->
    <DashboardSummaryBar :metrics="dashboardMetrics" />

    <DashboardQuickAccessStrip />

    <!-- Tuân thủ & bảo trì xe -->
    <DashboardComplianceSummaryBar :boxes="complianceBoxes" />

    <!-- Hoạt động gần đây -->
    <section class="border-t border-slate-200/80 pt-5 dark:border-slate-800" aria-labelledby="dash-section-recent">
      <h2 id="dash-section-recent" class="mb-3 px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.section_recent_activity') }}
      </h2>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Chuyến gần đây -->
        <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
              {{ t('dashboard_analytics.section_recent_trips') }}
            </h3>
            <RouterLink
              class="text-xs font-medium text-va-800 hover:text-va-900 dark:text-va-300"
              :to="staffPath('/trips')"
            >
              {{ t('dashboard_analytics.recent_see_all') }} →
            </RouterLink>
          </div>

          <div v-if="recentTrips.length" class="mb-3">
            <DatagridToolbarSearch
              v-model="tripSearch"
              input-id="dash-recent-trips-search"
              :placeholder="t('dashboard_analytics.recent_search_trips')"
              :aria-label="t('dashboard_analytics.recent_search_trips')"
              stretch
              inline-actions
              hide-label
              input-height="h-9"
            />
          </div>

          <div v-if="recentTripsBusy && !recentTrips.length" class="space-y-2" aria-busy="true">
            <div v-for="s in 5" :key="'tskel-' + s" class="h-[4.25rem] animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/80" />
          </div>
          <div v-else-if="filteredRecentTrips.length" class="space-y-2">
            <RouterLink
              v-for="tr in filteredRecentTrips"
              :key="tr.id"
              :to="staffPath(`/trips/${tr.id}`)"
              :class="[
                'group flex gap-3 rounded-xl border p-3 transition',
                'border-slate-100 bg-gradient-to-br from-white to-slate-50/90 shadow-sm ring-1 ring-slate-900/[0.03]',
                'hover:border-va-200/90 hover:shadow-md hover:ring-va-700/10',
                'dark:border-slate-700/90 dark:from-slate-900 dark:to-slate-900/80 dark:ring-white/[0.04]',
                'dark:hover:border-va-800/60',
                recentTripsBusy ? 'pointer-events-none opacity-55' : '',
              ]"
            >
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-va-50 text-va-800 shadow-inner dark:bg-va-950/55 dark:text-va-300" aria-hidden="true">
                <TruckIcon class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                      <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-400 dark:text-slate-500">
                        TRP-{{ String(tr.id).padStart(4, '0') }}
                      </span>
                      <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <ArrowsRightLeftIcon class="h-3.5 w-3.5 opacity-70" aria-hidden="true" />
                        {{ t('dashboard_analytics.recent_col_route') }}
                      </span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-va-900 dark:text-slate-50 dark:group-hover:text-va-200">
                      <span class="text-slate-800 dark:text-slate-100">{{ tr.dispatch_request?.origin ?? '—' }}</span>
                      <span class="mx-1 text-va-700 dark:text-va-400">→</span>
                      <span class="text-slate-800 dark:text-slate-100">{{ tr.dispatch_request?.destination ?? '—' }}</span>
                    </p>
                    <p class="mt-1 flex items-center gap-1.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
                      <ClockIcon class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                      <span>{{ t('dashboard_analytics.recent_col_when') }}: {{ formatDepartShort(tr.depart_at) }}</span>
                    </p>
                  </div>
                  <span :class="['shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold leading-none', tripStatusPillClass(tr.status)]">
                    {{ labelTripStatus(tr.status) }}
                  </span>
                </div>
              </div>
            </RouterLink>
          </div>
          <p v-else-if="recentTrips.length" class="text-sm text-slate-500 dark:text-slate-400">
            {{ t('dashboard_analytics.recent_filtered_empty') }}
          </p>
          <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
        </div>

        <!-- Yêu cầu gần đây -->
        <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
              {{ t('dashboard_analytics.section_recent_requests') }}
            </h3>
            <RouterLink
              class="text-xs font-medium text-va-800 hover:text-va-900 dark:text-va-300"
              :to="staffPath('/requests')"
            >
              {{ t('dashboard_analytics.recent_see_all') }} →
            </RouterLink>
          </div>

          <div v-if="recentRequests.length" class="mb-3">
            <DatagridToolbarSearch
              v-model="requestSearch"
              input-id="dash-recent-requests-search"
              :placeholder="t('dashboard_analytics.recent_search_requests')"
              :aria-label="t('dashboard_analytics.recent_search_requests')"
              stretch
              inline-actions
              hide-label
              input-height="h-9"
            />
          </div>

          <div v-if="recentRequestsBusy && !recentRequests.length" class="space-y-2" aria-busy="true">
            <div v-for="s in 5" :key="'rskel-' + s" class="h-[4.25rem] animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/80" />
          </div>
          <div v-else-if="filteredRecentRequests.length" class="space-y-2">
            <div
              v-for="rq in filteredRecentRequests"
              :key="rq.id"
              :class="[
                'flex gap-3 rounded-xl border p-3',
                'border-slate-100 bg-gradient-to-br from-white to-violet-50/40 shadow-sm ring-1 ring-slate-900/[0.03]',
                'dark:border-slate-700/90 dark:from-slate-900 dark:to-violet-950/25 dark:ring-white/[0.04]',
                recentRequestsBusy ? 'opacity-55' : '',
              ]"
            >
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100/95 text-violet-700 shadow-inner dark:bg-violet-950/50 dark:text-violet-300" aria-hidden="true">
                <ClipboardDocumentListIcon class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                      <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-600 dark:text-slate-300">
                        {{ requestRefCode(rq) }}
                      </span>
                      <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <ArrowsRightLeftIcon class="h-3.5 w-3.5 opacity-70" aria-hidden="true" />
                        {{ t('dashboard_analytics.recent_col_route') }}
                      </span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-50">
                      <span class="text-slate-800 dark:text-slate-100">{{ rq.origin ?? '—' }}</span>
                      <span class="mx-1 text-violet-500 dark:text-violet-400">→</span>
                      <span class="text-slate-800 dark:text-slate-100">{{ rq.destination ?? '—' }}</span>
                    </p>
                    <p class="mt-1 flex items-center gap-1.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
                      <ClockIcon class="h-3.5 w-3.5 shrink-0 opacity-80" aria-hidden="true" />
                      <span>{{ t('dashboard_analytics.recent_col_when') }}: {{ formatDepartShort(rq.depart_at) }}</span>
                    </p>
                  </div>
                  <span :class="['shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold leading-none', requestStatusPillClass(rq.status)]">
                    {{ labelRequestStatus(rq.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <p v-else-if="recentRequests.length" class="text-sm text-slate-500 dark:text-slate-400">
            {{ t('dashboard_analytics.recent_filtered_empty') }}
          </p>
          <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.recent_empty') }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import TransportReportFilters from '../components/reports/TransportReportFilters.vue'
import DashboardSummaryBar from '../components/dashboard/DashboardSummaryBar.vue'
import DashboardQuickAccessStrip from '../components/dashboard/DashboardQuickAccessStrip.vue'
import DashboardComplianceSummaryBar from '../components/dashboard/DashboardComplianceSummaryBar.vue'
import DatagridToolbarSearch from '../components/shared/ui/DatagridToolbarSearch.vue'
import { useTransportReportSummary } from '../composables/useTransportReportSummary'
import { listTrips } from '../api/trips'
import { listRequests, normalizeRequestListParams } from '../api/requests'
import { labelTripStatus, labelRequestStatus } from '../util/labels'
import { formatDispatchRequestRefCode } from '../util/portalRequestFormat'
import { formatListDateTime } from '../util/datetime'
import { buildStaffPrefixedPath as staffPath } from '../config/dispatchWebBase'

const { t, locale } = useI18n()

const {
  loading,
  loadError,
  summary,
  summaryFilters,
  totalTrips,
  totalConfirmedCost,
  topProviderName,
  topProviderAmount,
  completionRate,
  completedTrips,
  totalTripsInRange,
  rangeValid,
  reloadSummary,
} = useTransportReportSummary()

const RECENT_PAGE_SIZE = 5

const recentTrips = ref([])
const recentRequests = ref([])
const recentTripsBusy = ref(false)
const recentRequestsBusy = ref(false)
const tripSearch = ref('')
const requestSearch = ref('')

const dashboardMetrics = computed(() => ({
  totalTrips: totalTrips.value,
  completionRate: completionRate.value,
  completedTrips: completedTrips.value,
  totalTripsInRange: totalTripsInRange.value,
  confirmedCost: totalConfirmedCost.value,
  distanceKm: summary.value?.trip_records_distance_km,
  topProviderName: topProviderName.value,
  topProviderAmount: topProviderAmount.value,
  slaBreaches: summary.value?.cargo_sla_breaches ?? 0,
}))

const tripListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  if (o.trip_status) {
    o.status = o.trip_status
    delete o.trip_status
  }
  return o
})

const requestListQuery = computed(() => {
  const o = { ...summaryFilters.value }
  delete o.fleet_mode
  return o
})

const filteredRecentTrips = computed(() => {
  const q = tripSearch.value.trim().toLowerCase()
  if (!q) return recentTrips.value
  return recentTrips.value.filter((tr) => {
    const hay = `TRP-${String(tr.id).padStart(4, '0')} ${tr.dispatch_request?.origin ?? ''} ${
      tr.dispatch_request?.destination ?? ''
    } ${labelTripStatus(tr.status)}`.toLowerCase()
    return hay.includes(q)
  })
})

const filteredRecentRequests = computed(() => {
  const q = requestSearch.value.trim().toLowerCase()
  if (!q) return recentRequests.value
  return recentRequests.value.filter((rq) => {
    const hay = `${requestRefCode(rq)} ${rq.origin ?? ''} ${rq.destination ?? ''} ${labelRequestStatus(rq.status)}`.toLowerCase()
    return hay.includes(q)
  })
})

const complianceBoxes = computed(() => {
  const vc = summary.value?.vehicle_compliance ?? {}
  const ins = vc.inspection ?? {}
  const insu = vc.insurance ?? {}
  const road = vc.road_fee ?? {}
  const maint = vc.maintenance ?? {}
  return [
    {
      key: 'insp',
      title: t('dashboard_analytics.compliance_inspection'),
      overdue: ins.overdue ?? 0,
      soon: ins.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'insu',
      title: t('dashboard_analytics.compliance_insurance'),
      overdue: insu.overdue ?? 0,
      soon: insu.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'road',
      title: t('dashboard_analytics.compliance_road_fee'),
      overdue: road.overdue ?? 0,
      soon: road.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'maint',
      title: t('dashboard_analytics.compliance_maintenance'),
      overdue: 0,
      soon: null,
      stale: maint.no_recent_service_180d ?? 0,
      staleLabel: t('dashboard_analytics.compliance_maint_stale'),
    },
  ]
})

function dateLocaleKey() {
  return locale.value === 'en' ? 'en' : 'vi'
}

function requestRefCode(rq) {
  return formatDispatchRequestRefCode(rq) || `#${rq?.id ?? ''}`
}

function formatDepartShort(s) {
  if (s == null || s === '') return '—'
  const out = formatListDateTime(s, dateLocaleKey())
  return out || '—'
}

function tripStatusPillClass(s) {
  const map = {
    pending: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
    approved: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
    assigned: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
    driver_confirmed: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    in_progress: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
    completed: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    incident: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

function requestStatusPillClass(s) {
  const map = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    approved: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
    cancelled: 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

async function fetchRecentTrips() {
  if (!rangeValid.value) return
  recentTripsBusy.value = true
  try {
    const res = await listTrips({
      ...tripListQuery.value,
      per_page: RECENT_PAGE_SIZE,
      page: 1,
    })
    recentTrips.value = res.items ?? []
  } catch {
    recentTrips.value = []
  } finally {
    recentTripsBusy.value = false
  }
}

async function fetchRecentRequests() {
  if (!rangeValid.value) return
  recentRequestsBusy.value = true
  try {
    const res = await listRequests(
      normalizeRequestListParams({
        ...requestListQuery.value,
        per_page: RECENT_PAGE_SIZE,
        page: 1,
      }),
    )
    recentRequests.value = res.items ?? []
  } catch {
    recentRequests.value = []
  } finally {
    recentRequestsBusy.value = false
  }
}

async function loadRecentLists() {
  if (!rangeValid.value) return
  await Promise.all([fetchRecentTrips(), fetchRecentRequests()])
}

watch(
  summary,
  () => {
    loadRecentLists()
  },
  { deep: true },
)

onMounted(() => {
  reloadSummary()
})
</script>
