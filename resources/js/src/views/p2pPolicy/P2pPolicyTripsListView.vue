<template>
  <div class="w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="p2pStepTo('p2pPolicyHub', workflowTermId)"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.trips_title') }}
        </h1>
        <p class="max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.trips_subtitle') }}
        </p>
      </div>
    </header>

    <div class="relative z-40">
      <AppFilterBar>
        <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <details class="group relative">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
            </summary>
            <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] rounded-2xl border bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900">
              <p class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-300">{{ t('dashboard_analytics.filter_applied_title') }}</p>
              <ul class="mt-2 space-y-1 text-sm">
                <li v-if="!activeFilterCount">{{ t('p2p_policy_page.no_filters') }}</li>
              </ul>
              <button type="button" class="mt-3 w-full rounded-xl border px-3 py-2 text-sm" @click="onClearFilters">
                {{ t('p2p_policy_page.clear_filters') }}
              </button>
            </div>
          </details>

          <template v-for="fd in filterDefs" :key="fd.id">
            <label
              v-if="fd.id === 'per_page' && visibility.per_page"
              class="inline-flex shrink-0 items-center gap-1.5"
            >
              <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
              <select
                v-model.number="filters.per_page"
                class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                :aria-label="t('filter_bar.per_page')"
                @change="onPerPageChange"
              >
                <option v-for="n in P2P_TRIPS_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
              </select>
              <span class="hidden text-xs text-slate-500 sm:inline dark:text-slate-400">{{ t('p2p_policy_page.rows') }}</span>
            </label>
            <details v-else-if="visibility[fd.id]" class="group relative min-w-0 shrink-0">
              <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-white/90 bg-white/95 px-2 py-1.5 text-sm [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95">
                <span class="whitespace-nowrap text-slate-600 dark:text-slate-400">{{ t(fd.labelKey) }}</span>
                <span class="max-w-[10rem] truncate font-medium text-slate-900 dark:text-white">{{ filterLabel(fd.id) }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
              </summary>
              <div class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <select
                  v-if="fd.id === 'p2p_policy_term_id'"
                  v-model="filters.p2p_policy_term_id"
                  class="w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onTermFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="pt in p2pTerms" :key="pt.id" :value="pt.id">{{ p2pTermLabel(pt) }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'policy_route_id'"
                  v-model="filters.policy_route_id"
                  class="w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <input
                  v-else-if="fd.id === 'run_date_from' || fd.id === 'run_date_to'"
                  v-model="filters[fd.id]"
                  type="date"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                />
                <select
                  v-else-if="fd.id === 'leg'"
                  v-model="filters.leg"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option value="morning">{{ t('p2p_policy_page.leg_morning') }}</option>
                  <option value="afternoon">{{ t('p2p_policy_page.leg_afternoon') }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'trip_status'"
                  v-model="filters.trip_status"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="st in tripStatusOptions" :key="st" :value="st">{{ labelTripStatus(st) }}</option>
                </select>
              </div>
            </details>
          </template>

          <input
            v-model="filters.q"
            type="search"
            class="min-w-[10rem] flex-1 rounded-lg border-0 bg-white/90 px-3 py-2 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-900/90 dark:ring-slate-700"
            :placeholder="t('p2p_policy_page.trips_search_placeholder')"
            :aria-label="t('p2p_policy_page.trips_search_placeholder')"
            @keydown.enter="onSearch"
          />
        </div>
      </AppFilterBar>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700 dark:bg-slate-900/80">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
            <tr>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_run_date') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_leg') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_route') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_depart') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_driver') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_vehicle') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_passengers') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_trip_status') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_reminder') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_trip_link') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in items"
              :key="row.id"
              class="border-t border-slate-100 transition-colors hover:bg-violet-50/40 dark:border-slate-800 dark:hover:bg-violet-950/20"
              :class="idx % 2 === 1 ? 'bg-slate-50/40 dark:bg-slate-900/20' : ''"
            >
              <td class="whitespace-nowrap px-3 py-2.5 text-slate-800 dark:text-slate-200">
                {{ formatIsoDate(row.run_date, locale) }}
              </td>
              <td class="px-3 py-2.5">
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                  {{ legLabel(row.leg) }}
                </span>
              </td>
              <td class="px-3 py-2.5">
                <div class="font-medium text-slate-900 dark:text-white">{{ row.route_name || '—' }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ campusLine(row) }}
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-2.5 text-slate-700 dark:text-slate-300">
                {{ formatIsoDateTime(row.depart_at, locale) }}
              </td>
              <td class="px-3 py-2.5 text-slate-700 dark:text-slate-300">{{ row.driver?.full_name || '—' }}</td>
              <td class="px-3 py-2.5 font-mono text-xs text-slate-700 dark:text-slate-300">
                {{ row.vehicle?.license_plate || '—' }}
              </td>
              <td class="px-3 py-2.5 text-center text-slate-700 dark:text-slate-300">{{ row.passenger_count ?? '—' }}</td>
              <td class="px-3 py-2.5">
                <span v-if="row.trip_status" class="text-xs font-medium text-slate-800 dark:text-slate-200">
                  {{ labelTripStatus(row.trip_status) }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-3 py-2.5 text-xs">
                <span
                  v-if="row.trip_id && row.depart_reminder_sent_at"
                  class="text-emerald-700 dark:text-emerald-400"
                >
                  {{ t('p2p_policy_page.trips_reminder_sent') }}
                </span>
                <span v-else-if="row.trip_id" class="text-slate-500">{{ t('p2p_policy_page.trips_reminder_pending') }}</span>
                <span v-else>—</span>
              </td>
              <td class="px-3 py-2.5">
                <RouterLink
                  v-if="row.trip_id"
                  :to="staffPath(`/trips/${row.trip_id}`)"
                  class="text-sm font-semibold text-teal-800 underline hover:text-teal-950 dark:text-teal-300"
                >
                  #{{ row.trip_id }}
                </RouterLink>
                <span v-else class="text-xs text-slate-400">{{ t('p2p_policy_page.trips_no_trip') }}</span>
              </td>
            </tr>
            <tr v-if="!loading && !items.length">
              <td colspan="10" class="px-3 py-10 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading" class="flex items-center justify-center gap-2 border-t border-slate-100 py-8 text-sm text-slate-500 dark:border-slate-800">
          <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
          {{ t('common.processing') }}
        </div>
      </div>

      <div
        v-if="meta.total > 0"
        class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50"
      >
        <p class="text-sm text-slate-600 dark:text-slate-400">
          {{
            t('p2p_policy_page.trips_pagination_summary', {
              from: pageFrom,
              to: pageTo,
              total: meta.total,
            })
          }}
        </p>
        <div v-if="(meta.last_page ?? 1) > 1" class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            {{ t('p2p_policy_page.routes_page_prev') }}
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            type="button"
            class="min-w-[2rem] rounded-lg px-2 py-1.5 text-sm"
            :class="
              p === meta.current_page
                ? 'bg-teal-600 font-semibold text-white'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :disabled="loading"
            @click="goPage(p)"
          >
            {{ p }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('p2p_policy_page.routes_page_next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'
import { listP2pPolicyTerms, listPolicyRoutes, listPolicyTripSlots } from '../../api/p2pPolicy'
import {
  P2P_TRIPS_DEFAULT_PER_PAGE,
  P2P_TRIPS_PER_PAGE_OPTIONS,
  useP2pPolicyTripSlotFilters,
} from '../../composables/useP2pPolicyTripSlotFilters'
import { p2pStepTo, p2pWorkflowQuery, resolveP2pTermIdFromRoute } from '../../composables/useP2pPolicyWorkflow'
import { p2pTermStatusLabel } from '../../utils/p2pPolicyStudentLabels'
import { formatIsoDate, formatIsoDateTime } from '../../util/datetime'
import { labelTripStatus } from '../../util/labels'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const { filters, visibility, apiParams, activeFilterCount, clearFilters, resetPage, filterDefs } =
  useP2pPolicyTripSlotFilters()

const workflowTermId = computed(() => {
  const fromFilter = filters.p2p_policy_term_id
  if (fromFilter !== '' && fromFilter != null) return Number(fromFilter)
  return resolveP2pTermIdFromRoute(route)
})

const items = ref([])
const meta = ref({ total: 0, current_page: 1, per_page: P2P_TRIPS_DEFAULT_PER_PAGE, last_page: 1 })
const loading = ref(false)
const routes = ref([])
const p2pTerms = ref([])
const listReady = ref(false)

const tripStatusOptions = ['approved', 'assigned', 'in_progress', 'completed', 'cancelled']

const pageFrom = computed(() => {
  if (!meta.value.total) return 0
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const span = 5
  let start = Math.max(1, cur - Math.floor(span / 2))
  let end = Math.min(last, start + span - 1)
  start = Math.max(1, end - span + 1)
  const nums = []
  for (let p = start; p <= end; p++) nums.push(p)
  return nums
})

function p2pTermLabel(pt) {
  const year = pt.academic_term?.academic_year ?? pt.academic_year ?? ''
  const status = p2pTermStatusLabel(t, pt.status ?? '')
  return year ? `${year} — ${status}` : `#${pt.id} — ${status}`
}

function legLabel(leg) {
  if (leg === 'afternoon') return t('p2p_policy_page.leg_afternoon')
  return t('p2p_policy_page.leg_morning')
}

function campusLine(row) {
  const o = row.origin_campus?.name || row.origin_campus?.code
  const d = row.dest_campus?.name || row.dest_campus?.code
  if (o && d) return `${o} → ${d}`
  return o || d || '—'
}

function filterLabel(id) {
  if (id === 'per_page') return String(filters.per_page)
  if (id === 'p2p_policy_term_id' && filters.p2p_policy_term_id) {
    const pt = p2pTerms.value.find((x) => String(x.id) === String(filters.p2p_policy_term_id))
    return pt ? p2pTermLabel(pt) : '—'
  }
  if (id === 'policy_route_id' && filters.policy_route_id) {
    return routes.value.find((r) => String(r.id) === String(filters.policy_route_id))?.name ?? '—'
  }
  if (id === 'leg' && filters.leg) return legLabel(filters.leg)
  if (id === 'trip_status' && filters.trip_status) return labelTripStatus(filters.trip_status)
  if (id === 'run_date_from' || id === 'run_date_to') return filters[id] || t('p2p_policy_page.filter_any')
  return t('p2p_policy_page.filter_any')
}

async function reload() {
  if (!listReady.value) return
  loading.value = true
  try {
    const res = await listPolicyTripSlots(apiParams.value)
    items.value = res.items ?? []
    meta.value = res.meta ?? {
      total: 0,
      current_page: 1,
      per_page: filters.per_page,
      last_page: 1,
    }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  const last = meta.value.last_page ?? 1
  filters.page = Math.min(Math.max(1, p), last)
}

function onPerPageChange() {
  resetPage()
}

function onFilterChange() {
  resetPage()
  syncWorkflowQuery()
}

function onTermFilterChange() {
  resetPage()
  loadRouteOptions().then(() => {
    syncWorkflowQuery()
  })
}

function onSearch() {
  resetPage()
}

function onClearFilters() {
  clearFilters()
  syncWorkflowQuery()
}

function syncWorkflowQuery() {
  router.replace({
    query: p2pWorkflowQuery(filters.p2p_policy_term_id || resolveP2pTermIdFromRoute(route)),
  })
}

async function loadRouteOptions() {
  const params = { per_page: 100 }
  if (filters.p2p_policy_term_id) {
    params.p2p_policy_term_id = filters.p2p_policy_term_id
  }
  const r = await listPolicyRoutes(params)
  routes.value = r.items ?? []
}

onMounted(async () => {
  const pt = await listP2pPolicyTerms({ per_page: 50 })
  p2pTerms.value = pt.items ?? []

  const qTerm = resolveP2pTermIdFromRoute(route)
  if (qTerm) {
    filters.p2p_policy_term_id = qTerm
  } else if (p2pTerms.value[0] && !filters.p2p_policy_term_id) {
    filters.p2p_policy_term_id = p2pTerms.value[0].id
  }

  await loadRouteOptions()
  syncWorkflowQuery()
  listReady.value = true
  await reload()
})

watch(
  () => filters.p2p_policy_term_id,
  async () => {
    if (!listReady.value) return
    await loadRouteOptions()
    syncWorkflowQuery()
  },
)

watch(
  apiParams,
  () => {
    if (!listReady.value) return
    reload()
  },
  { deep: true },
)
</script>
