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

      <!-- Truy cập nhanh: mũi tên hai bên, ẩn thanh cuộn -->
      <div>
        <h2 class="px-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('dashboard_analytics.quick_title') }}
        </h2>
        <div class="relative -mx-0.5 mt-2 flex items-stretch gap-1 sm:-mx-1 sm:gap-2">
          <button
            type="button"
            class="flex h-auto min-h-[4.75rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-va-200/70 hover:bg-white hover:text-va-800 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-va-800 dark:hover:text-va-300 sm:w-9"
            :disabled="!quickCanScrollLeft"
            :aria-label="t('dashboard_analytics.quick_scroll_prev')"
            @click="scrollQuickLinks(-1)"
          >
            <ChevronLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
          <div
            ref="quickScrollRef"
            class="dash-quick-scroll min-h-[4.75rem] min-w-0 flex-1 overflow-x-auto overflow-y-hidden scroll-smooth"
            @scroll.passive="updateQuickScrollState"
          >
            <div class="flex h-full flex-nowrap items-stretch gap-2 px-0.5 py-0.5 sm:gap-3">
              <RouterLink
                v-for="item in quickLinks"
                :key="item.to"
                :to="item.to"
                :title="item.hint"
                :class="[
                  'flex w-[6.25rem] shrink-0 flex-col items-center justify-center gap-1.5 rounded-2xl border bg-gradient-to-b px-2.5 py-3 text-center shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md sm:w-28 md:w-32',
                  item.cardClass,
                ]"
              >
                <component :is="item.icon" :class="['h-6 w-6 shrink-0 sm:h-7 sm:w-7', item.iconClass]" aria-hidden="true" />
                <span class="w-full text-center line-clamp-2 text-[11px] font-medium leading-tight sm:text-xs" :class="item.labelClass">
                  {{ item.title }}
                </span>
              </RouterLink>
            </div>
          </div>
          <button
            type="button"
            class="flex h-auto min-h-[4.75rem] w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-va-200/70 hover:bg-white hover:text-va-800 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-va-800 dark:hover:text-va-300 sm:w-9"
            :disabled="!quickCanScrollRight"
            :aria-label="t('dashboard_analytics.quick_scroll_next')"
            @click="scrollQuickLinks(1)"
          >
            <ChevronRightIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          </button>
        </div>
      </div>

      <TransportReportFilters />
    </div>

    <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
      <span class="inline-block size-4 animate-pulse rounded-full bg-slate-300 dark:bg-slate-600" />
      {{ t('dashboard_analytics.loading') }}
    </div>

    <!-- Dải thống kê KPI -->
    <DashboardSummaryBar :metrics="dashboardMetrics" />

    <!-- Tuân thủ & bảo trì xe -->
    <section aria-labelledby="dash-section-compliance">
      <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50/40 to-va-50/25 p-3 shadow-sm ring-1 ring-slate-900/[0.03] dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-va-950/20 dark:ring-white/[0.04]">
        <h2 id="dash-section-compliance" class="text-sm font-semibold text-slate-900 dark:text-white">
          {{ t('dashboard_analytics.section_compliance') }}
        </h2>
        <div class="mt-2.5 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="box in complianceBoxes"
            :key="box.key"
            class="rounded-lg border border-slate-100/90 bg-white/90 px-3 py-2.5 shadow-sm ring-1 ring-slate-900/[0.02] dark:border-slate-700 dark:bg-slate-950/50 dark:ring-white/[0.04]"
            :class="box.cardTone"
          >
            <div class="text-xs font-semibold" :class="box.titleClass">{{ box.title }}</div>
            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] tabular-nums">
              <span class="text-rose-600 dark:text-rose-400">{{ t('dashboard_analytics.compliance_overdue') }}: {{ box.overdue }}</span>
              <span v-if="box.soon != null" class="text-amber-700 dark:text-amber-400">{{ t('dashboard_analytics.compliance_due_30d') }}: {{ box.soon }}</span>
              <span v-if="box.stale != null" class="text-slate-600 dark:text-slate-400">{{ box.staleLabel }}: {{ box.stale }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

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
                      <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-400 dark:text-slate-500">
                        #{{ rq.id }}
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
import { computed, markRaw, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  BanknotesIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  DocumentMagnifyingGlassIcon,
  PlusCircleIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import TransportReportFilters from '../components/reports/TransportReportFilters.vue'
import DashboardSummaryBar from '../components/dashboard/DashboardSummaryBar.vue'
import DatagridToolbarSearch from '../components/shared/ui/DatagridToolbarSearch.vue'
import { useTransportReportSummary } from '../composables/useTransportReportSummary'
import { listTrips } from '../api/trips'
import { listRequests, normalizeRequestListParams } from '../api/requests'
import { labelTripStatus, labelRequestStatus } from '../util/labels'
import { buildStaffPrefixedPath as staffPath } from '../config/dispatchWebBase'

const { t } = useI18n()

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

const quickScrollRef = ref(null)
const quickCanScrollLeft = ref(false)
const quickCanScrollRight = ref(false)

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

function updateQuickScrollState() {
  const el = quickScrollRef.value
  if (!el) {
    quickCanScrollLeft.value = false
    quickCanScrollRight.value = false
    return
  }
  const { scrollLeft, scrollWidth, clientWidth } = el
  quickCanScrollLeft.value = scrollLeft > 2
  quickCanScrollRight.value = scrollLeft + clientWidth < scrollWidth - 2
}

function scrollQuickLinks(direction) {
  const el = quickScrollRef.value
  if (!el) return
  const step = Math.max(160, Math.floor(el.clientWidth * 0.82))
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

const quickLinks = computed(() => [
  {
    to: staffPath('/trips'),
    title: t('dashboard_analytics.quick_trips'),
    hint: t('dashboard_analytics.quick_trips_tooltip'),
    icon: markRaw(TruckIcon),
    cardClass:
      'border-sky-200/90 from-sky-50/95 to-white ring-sky-900/[0.06] hover:border-sky-300 dark:border-sky-800/55 dark:from-sky-950/35 dark:to-slate-900/85 dark:ring-sky-900/25 dark:hover:border-sky-700',
    iconClass: 'text-sky-600 dark:text-sky-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/dispatch-requests/new'),
    title: t('dashboard_analytics.quick_new_request'),
    hint: t('dashboard_analytics.quick_new_request_tooltip'),
    icon: markRaw(PlusCircleIcon),
    cardClass:
      'border-emerald-200/90 from-emerald-50/95 to-white ring-emerald-900/[0.06] hover:border-emerald-300 dark:border-emerald-800/55 dark:from-emerald-950/35 dark:to-slate-900/85 dark:ring-emerald-900/25 dark:hover:border-emerald-700',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/requests'),
    title: t('dashboard_analytics.quick_requests'),
    hint: t('dashboard_analytics.quick_requests_tooltip'),
    icon: markRaw(ClipboardDocumentListIcon),
    cardClass:
      'border-violet-200/90 from-violet-50/95 to-white ring-violet-900/[0.06] hover:border-violet-300 dark:border-violet-800/55 dark:from-violet-950/35 dark:to-slate-900/85 dark:ring-violet-900/25 dark:hover:border-violet-700',
    iconClass: 'text-violet-600 dark:text-violet-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/resources/list'),
    title: t('dashboard_analytics.quick_resources'),
    hint: t('dashboard_analytics.quick_resources_tooltip'),
    icon: markRaw(UserGroupIcon),
    cardClass:
      'border-indigo-200/90 from-indigo-50/95 to-white ring-indigo-900/[0.06] hover:border-indigo-300 dark:border-indigo-800/55 dark:from-indigo-950/35 dark:to-slate-900/85 dark:ring-indigo-900/25 dark:hover:border-indigo-700',
    iconClass: 'text-indigo-600 dark:text-indigo-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/cargo'),
    title: t('dashboard_analytics.quick_cargo'),
    hint: t('dashboard_analytics.quick_cargo_tooltip'),
    icon: markRaw(CubeIcon),
    cardClass:
      'border-amber-200/90 from-amber-50/95 to-white ring-amber-900/[0.06] hover:border-amber-300 dark:border-amber-800/55 dark:from-amber-950/35 dark:to-slate-900/85 dark:ring-amber-900/25 dark:hover:border-amber-700',
    iconClass: 'text-amber-600 dark:text-amber-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/costs'),
    title: t('dashboard_analytics.quick_costs'),
    hint: t('dashboard_analytics.quick_costs_tooltip'),
    icon: markRaw(BanknotesIcon),
    cardClass:
      'border-rose-200/90 from-rose-50/95 to-white ring-rose-900/[0.06] hover:border-rose-300 dark:border-rose-800/55 dark:from-rose-950/35 dark:to-slate-900/85 dark:ring-rose-900/25 dark:hover:border-rose-700',
    iconClass: 'text-rose-600 dark:text-rose-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/pricing'),
    title: t('dashboard_analytics.quick_pricing'),
    hint: t('dashboard_analytics.quick_pricing_tooltip'),
    icon: markRaw(TableCellsIcon),
    cardClass:
      'border-cyan-200/90 from-cyan-50/95 to-white ring-cyan-900/[0.06] hover:border-cyan-300 dark:border-cyan-800/55 dark:from-cyan-950/35 dark:to-slate-900/85 dark:ring-cyan-900/25 dark:hover:border-cyan-700',
    iconClass: 'text-cyan-600 dark:text-cyan-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
  {
    to: staffPath('/audit-logs'),
    title: t('dashboard_analytics.quick_audit'),
    hint: t('dashboard_analytics.quick_audit_tooltip'),
    icon: markRaw(DocumentMagnifyingGlassIcon),
    cardClass:
      'border-slate-300/90 from-slate-100/90 to-white ring-slate-900/[0.05] hover:border-slate-400 dark:border-slate-600 dark:from-slate-800/50 dark:to-slate-900/85 dark:ring-slate-900/30 dark:hover:border-slate-500',
    iconClass: 'text-slate-600 dark:text-slate-400',
    labelClass: 'text-slate-800 dark:text-slate-100',
  },
])

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
    const hay = `#${rq.id} ${rq.origin ?? ''} ${rq.destination ?? ''} ${labelRequestStatus(rq.status)}`.toLowerCase()
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
      cardTone: 'border-l-4 border-l-teal-500 bg-gradient-to-r from-teal-50/80 to-white dark:from-teal-950/35 dark:to-slate-950/50',
      titleClass: 'text-teal-900 dark:text-teal-200',
      overdue: ins.overdue ?? 0,
      soon: ins.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'insu',
      title: t('dashboard_analytics.compliance_insurance'),
      cardTone: 'border-l-4 border-l-sky-500 bg-gradient-to-r from-sky-50/80 to-white dark:from-sky-950/35 dark:to-slate-950/50',
      titleClass: 'text-sky-900 dark:text-sky-200',
      overdue: insu.overdue ?? 0,
      soon: insu.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'road',
      title: t('dashboard_analytics.compliance_road_fee'),
      cardTone: 'border-l-4 border-l-amber-500 bg-gradient-to-r from-amber-50/80 to-white dark:from-amber-950/30 dark:to-slate-950/50',
      titleClass: 'text-amber-900 dark:text-amber-200',
      overdue: road.overdue ?? 0,
      soon: road.due_within_30_days ?? 0,
      stale: null,
      staleLabel: null,
    },
    {
      key: 'maint',
      title: t('dashboard_analytics.compliance_maintenance'),
      cardTone: 'border-l-4 border-l-violet-500 bg-gradient-to-r from-violet-50/80 to-white dark:from-violet-950/30 dark:to-slate-950/50',
      titleClass: 'text-violet-900 dark:text-violet-200',
      overdue: 0,
      soon: null,
      stale: maint.no_recent_service_180d ?? 0,
      staleLabel: t('dashboard_analytics.compliance_maint_stale'),
    },
  ]
})

function formatDepartShort(s) {
  if (s == null || s === '') return '—'
  return String(s).replace('T', ' ').slice(0, 16)
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

function onDashboardResize() {
  updateQuickScrollState()
}

onMounted(() => {
  reloadSummary()
  window.addEventListener('resize', onDashboardResize)
  nextTick(() => updateQuickScrollState())
})

onUnmounted(() => {
  window.removeEventListener('resize', onDashboardResize)
})
</script>

<style scoped>
.dash-quick-scroll {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.dash-quick-scroll::-webkit-scrollbar {
  display: none;
}
</style>
