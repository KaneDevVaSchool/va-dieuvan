<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-xl font-semibold tracking-tight text-slate-900">
            {{ t('requests_page.title') }}
          </h1>
          <span
            class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-600/20"
          >
            {{ t('requests_page.workspace_badge') }}
          </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ t('requests_page.subtitle') }}</p>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
          <input
            v-model="searchInput"
            type="search"
            :placeholder="t('requests_page.search_placeholder')"
            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            @input="onSearchInput"
            @keydown.enter="applySearchNow"
          />
        </div>
        <RouterLink
          to="/notifications"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          :title="t('requests_page.notifications')"
        >
          <BellIcon class="h-5 w-5" aria-hidden="true" />
        </RouterLink>
        <RouterLink
          to="/dispatch-requests/new"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('requests_page.create') }}
        </RouterLink>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_total') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.total) }}
            </p>
            <p v-if="stats.month_trend_pct != null" class="mt-1 text-xs text-teal-700">
              {{ trendLabel(stats.month_trend_pct) }}
            </p>
            <p v-else class="mt-1 text-xs text-slate-400">{{ t('requests_page.kpi_no_trend') }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 p-2 text-slate-600">
            <RectangleStackIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_in_progress') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.trips_in_progress) }}
            </p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-teal-500 transition-all"
                :style="{ width: progressBarPct + '%' }"
              />
            </div>
          </div>
          <div class="rounded-lg bg-teal-50 p-2 text-teal-700">
            <TruckIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_sla_risk') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.sla_risk) }}
            </p>
            <span
              v-if="stats.sla_risk > 0"
              class="mt-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 ring-1 ring-amber-200"
            >
              {{ t('requests_page.kpi_action_required') }}
            </span>
            <span v-else class="mt-2 inline-flex text-xs text-emerald-700">{{ t('requests_page.kpi_sla_ok') }}</span>
          </div>
          <div class="rounded-lg bg-amber-50 p-2 text-amber-700">
            <ExclamationTriangleIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
          {{ t('requests_page.kpi_volume') }}
        </p>
        <div class="mt-3 h-16 w-full">
          <svg
            class="h-full w-full text-teal-600"
            viewBox="0 0 120 48"
            preserveAspectRatio="none"
            aria-hidden="true"
          >
            <polyline
              :points="sparklinePoints"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <p class="mt-1 text-[11px] text-slate-400">{{ t('requests_page.kpi_volume_hint') }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-4 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <Select v-model="filters.trip_type" :label="t('requests_page.filter_trip_type')" placeholder="—">
          <option value="">{{ t('requests_page.all') }}</option>
          <option value="door_to_door">{{ labelTripType('door_to_door') }}</option>
          <option value="point_to_point">{{ labelTripType('point_to_point') }}</option>
          <option value="business">{{ labelTripType('business') }}</option>
          <option value="cargo">{{ labelTripType('cargo') }}</option>
        </Select>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">{{ t('requests_page.filter_date_range') }}</label>
          <div class="flex flex-wrap items-center gap-2">
            <input
              v-model="filters.from"
              type="date"
              class="min-w-0 flex-1 rounded-md border border-slate-200 bg-white px-2 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
            <span class="text-slate-400">—</span>
            <input
              v-model="filters.to"
              type="date"
              class="min-w-0 flex-1 rounded-md border border-slate-200 bg-white px-2 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
          </div>
        </div>
        <Select v-model="filters.source_channel" :label="t('requests_page.filter_channel')" placeholder="—">
          <option value="">{{ t('requests_page.all') }}</option>
          <option value="portal">{{ labelSourceChannel('portal') }}</option>
          <option value="zalo">{{ labelSourceChannel('zalo') }}</option>
          <option value="paper">{{ labelSourceChannel('paper') }}</option>
        </Select>
        <Select v-model="filters.paper_status" :label="t('requests_page.filter_paper')" placeholder="—">
          <option value="">{{ t('requests_page.all') }}</option>
          <option value="pending">{{ labelPaperStatus('pending') }}</option>
          <option value="received">{{ labelPaperStatus('received') }}</option>
          <option value="digitally_signed">{{ labelPaperStatus('digitally_signed') }}</option>
        </Select>
        <label class="flex cursor-pointer items-center gap-3 pt-6 xl:col-span-1 xl:pt-0">
          <button
            type="button"
            role="switch"
            :aria-checked="filters.sla_risk_only"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border border-slate-200 bg-white transition focus:outline-none focus:ring-2 focus:ring-teal-500/30"
            :class="filters.sla_risk_only ? 'bg-teal-600' : 'bg-slate-200'"
            @click="toggleSla"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 translate-x-0.5 translate-y-0.5 rounded-full bg-white shadow transition"
              :class="filters.sla_risk_only ? 'translate-x-5' : ''"
            />
          </button>
          <span class="text-sm text-slate-700">{{ t('requests_page.sla_toggle') }}</span>
        </label>
      </div>
      <div class="flex shrink-0 gap-2">
        <Button variant="secondary" :loading="loading" @click="reload">{{ t('requests_page.apply_filters') }}</Button>
        <Button variant="secondary" :disabled="loading" @click="resetFilters">{{ t('requests_page.reset') }}</Button>
      </div>
    </div>

    <!-- Status tabs -->
    <div class="-mx-1 overflow-x-auto pb-1">
      <nav class="flex min-w-max gap-1 border-b border-slate-200 px-1" aria-label="Tabs">
        <button
          v-for="tab in tabDefs"
          :key="tab.id"
          type="button"
          class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'border-teal-600 text-teal-800'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
          "
          @click="setTab(tab.id)"
        >
          {{ tab.label }}
          <span class="ml-1.5 tabular-nums text-slate-400">({{ formatInt(tabCount(tab.id)) }})</span>
        </button>
      </nav>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div v-if="loading" class="p-8 text-center text-sm text-slate-500">{{ t('requests_page.loading') }}</div>
      <div v-else-if="!items.length" class="p-8 text-center text-sm text-slate-500">
        {{ t('requests_page.empty') }}
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="w-10 px-3 py-3">
                <span class="sr-only">{{ t('requests_page.col_select') }}</span>
              </th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_id') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_trip') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_type_channel') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_timeline') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_sla') }}</th>
              <th class="w-28 px-3 py-3 text-right font-semibold text-slate-700">{{ t('requests_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="r in items" :key="r.id" class="transition hover:bg-slate-50/80">
              <td class="px-3 py-3 align-top">
                <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" disabled />
              </td>
              <td class="px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">REQ-{{ r.id }}</div>
                <div class="text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
              </td>
              <td class="max-w-xs px-3 py-3 align-top">
                <div class="flex gap-2">
                  <component :is="tripTypeIcon(r.trip_type)" class="mt-0.5 h-5 w-5 shrink-0 text-teal-600" />
                  <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">
                      {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                    </div>
                    <div class="text-xs text-slate-500">
                      <span v-if="r.passenger_count">{{ t('requests_page.passengers', { n: r.passenger_count }) }}</span>
                      <span v-else-if="r.trip_type === 'cargo'">{{ t('requests_page.cargo') }}</span>
                      <span v-else>{{ t('requests_page.no_passenger_info') }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 align-top">
                <div>{{ labelTripType(r.trip_type) }}</div>
                <div class="text-xs text-slate-500">{{ labelSourceChannel(r.source_channel) }}</div>
              </td>
              <td class="px-3 py-3 align-top">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                  :class="badgeClass(r.status)"
                >
                  {{ labelRequestStatus(r.status) }}
                </span>
                <div class="mt-1 text-xs text-slate-500">{{ tripTimelineHint(r) }}</div>
              </td>
              <td class="px-3 py-3 align-top text-xs">
                <span v-if="slaCell(r).kind === 'ok'" class="inline-flex items-center gap-1 text-emerald-700">
                  <CheckCircleIcon class="h-4 w-4" />
                  {{ t('requests_page.sla_on_track') }}
                </span>
                <span v-else-if="slaCell(r).kind === 'warn'" class="inline-flex items-center gap-1 text-amber-800">
                  <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                  {{ slaCell(r).text }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="flex items-center justify-end gap-1">
                  <RouterLink
                    :to="`/requests/${r.id}`"
                    class="inline-flex rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    :title="t('requests_page.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </RouterLink>
                  <RouterLink
                    v-if="r.status === 'draft'"
                    :to="`/requests/${r.id}`"
                    class="inline-flex rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    :title="t('requests_page.edit')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
          {{
            t('requests_page.pagination_summary', {
              from: pageFrom,
              to: pageTo,
              total: meta.total ?? 0,
            })
          }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1 || loading" @click="goPage((meta.current_page ?? 1) - 1)">
            {{ t('requests_page.prev') }}
          </Button>
          <div class="flex items-center gap-1">
            <button
              v-for="p in pageNumbers"
              :key="p"
              type="button"
              class="min-w-[2.25rem] rounded-md px-2 py-1.5 text-sm"
              :class="
                p === meta.current_page
                  ? 'bg-teal-600 font-medium text-white'
                  : 'text-slate-600 hover:bg-slate-100'
              "
              @click="goPage(p)"
            >
              {{ p }}
            </button>
          </div>
          <Button
            variant="secondary"
            :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1) || loading"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('requests_page.next') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BellIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  RectangleStackIcon,
  TruckIcon,
  AcademicCapIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import { listRequests } from '../../api/requests'
import {
  labelPaperStatus,
  labelRequestStatus,
  labelSourceChannel,
  labelTripStatus,
  labelTripType,
} from '../../util/labels'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const loading = ref(false)
const items = ref([])
const meta = ref({})
const stats = ref({
  total: 0,
  by_status: {},
  trips_in_progress: 0,
  trips_completed: 0,
  sla_risk: 0,
  month_trend_pct: null,
  volume_trend: [],
})

const activeTab = ref('all')
const searchInput = ref('')
let searchDebounce = null

const filters = reactive({
  q: '',
  trip_type: '',
  source_channel: '',
  paper_status: '',
  from: '',
  to: '',
  sla_risk_only: false,
  per_page: 10,
  page: 1,
})

const tabDefs = computed(() => [
  { id: 'all', label: t('requests_page.tab_all') },
  { id: 'draft', label: t('requests_page.tab_draft') },
  { id: 'pending', label: t('requests_page.tab_pending') },
  { id: 'approved', label: t('requests_page.tab_approved') },
  { id: 'rejected', label: t('requests_page.tab_rejected') },
  { id: 'cancelled', label: t('requests_page.tab_cancelled') },
  { id: 'trip_in_progress', label: t('requests_page.tab_trip_running') },
  { id: 'trip_completed', label: t('requests_page.tab_trip_done') },
])

const progressBarPct = computed(() => {
  const t = stats.value.total || 0
  const x = stats.value.trips_in_progress || 0
  if (t <= 0) return 0
  return Math.min(100, Math.round((x / t) * 100))
})

const sparklinePoints = computed(() => {
  const pts = stats.value.volume_trend
  if (!pts?.length) return '0,40 120,40'
  const max = Math.max(...pts, 1)
  const w = 120
  const h = 40
  return pts
    .map((v, i) => {
      const x = (i / Math.max(pts.length - 1, 1)) * w
      const y = h - (v / max) * (h - 4) - 2
      return `${x.toFixed(1)},${y.toFixed(1)}`
    })
    .join(' ')
})

const pageFrom = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  if (total === 0) return 0
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const window = 3
  const start = Math.max(1, cur - 1)
  const end = Math.min(last, start + window - 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function trendLabel(pct) {
  if (pct > 0) return t('requests_page.trend_up', { pct })
  if (pct < 0) return t('requests_page.trend_down', { pct: Math.abs(pct) })
  return t('requests_page.trend_flat')
}

function formatShortDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
  } catch {
    return String(v)
  }
}

function badgeClass(status) {
  if (status === 'approved') return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'
  if (status === 'pending') return 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
  if (status === 'draft') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800 ring-1 ring-rose-200'
  if (status === 'cancelled') return 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'
  return 'bg-slate-100 text-slate-700'
}

function tripTypeIcon(type) {
  if (type === 'cargo') return CubeIcon
  if (type === 'business') return AcademicCapIcon
  return MapPinIcon
}

function tripTimelineHint(r) {
  if (r.trip?.status) {
    return labelTripStatus(r.trip.status)
  }
  if (r.status === 'pending') return t('requests_page.hint_await_assign')
  if (r.status === 'approved' && !r.trip) return t('requests_page.hint_no_trip')
  return labelPaperStatus(r.paper_status) || '—'
}

/** @param {Record<string, unknown>} r */
function slaCell(r) {
  if (r.status !== 'pending') {
    return { kind: 'neutral' }
  }
  const depart = r.depart_at ? new Date(r.depart_at) : null
  if (r.is_urgent) {
    return { kind: 'warn', text: t('requests_page.sla_urgent') }
  }
  if (depart) {
    const hours = (depart.getTime() - Date.now()) / 36e5
    if (hours > 0 && hours <= 48) {
      return {
        kind: 'warn',
        text: t('requests_page.sla_depart_hours', { h: Math.max(1, Math.round(hours)) }),
      }
    }
  }
  return { kind: 'ok' }
}

function tabCount(tabId) {
  const b = stats.value.by_status || {}
  switch (tabId) {
    case 'all':
      return stats.value.total ?? 0
    case 'draft':
      return b.draft ?? 0
    case 'pending':
      return b.pending ?? 0
    case 'approved':
      return b.approved ?? 0
    case 'rejected':
      return b.rejected ?? 0
    case 'cancelled':
      return b.cancelled ?? 0
    case 'trip_in_progress':
      return stats.value.trips_in_progress ?? 0
    case 'trip_completed':
      return stats.value.trips_completed ?? 0
    default:
      return 0
  }
}

function buildListParams() {
  const params = { ...filters }
  params.q = searchInput.value.trim() || undefined

  if (activeTab.value === 'trip_in_progress') {
    params.trip_status = 'in_progress'
    params.status = undefined
  } else if (activeTab.value === 'trip_completed') {
    params.trip_status = 'completed'
    params.status = undefined
  } else if (activeTab.value !== 'all') {
    params.status = activeTab.value
    params.trip_status = undefined
  } else {
    params.status = undefined
    params.trip_status = undefined
  }

  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  if (params.sla_risk_only === false) delete params.sla_risk_only

  return params
}

async function reload() {
  loading.value = true
  try {
    const params = buildListParams()
    const res = await listRequests(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    if (res.stats) {
      stats.value = { ...stats.value, ...res.stats }
    }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload()
}

function setTab(id) {
  activeTab.value = id
  filters.page = 1
  const q = { ...route.query }
  delete q.status
  delete q.trip_status
  if (id === 'trip_in_progress') q.trip_status = 'in_progress'
  else if (id === 'trip_completed') q.trip_status = 'completed'
  else if (id !== 'all') q.status = id
  router.replace({ query: q })
}

function toggleSla() {
  filters.sla_risk_only = !filters.sla_risk_only
  filters.page = 1
  reload()
}

function resetFilters() {
  activeTab.value = 'all'
  filters.trip_type = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.from = ''
  filters.to = ''
  filters.sla_risk_only = false
  filters.per_page = 10
  filters.page = 1
  searchInput.value = ''
  const hadQuery = Object.keys(route.query).length > 0
  router.replace({ query: {} })
  if (!hadQuery) {
    reload()
  }
}

function applySearchNow() {
  if (searchDebounce) clearTimeout(searchDebounce)
  filters.page = 1
  reload()
}

function applyRouteQuery() {
  const q = route.query
  if (typeof q.trip_status === 'string') {
    if (q.trip_status === 'in_progress') activeTab.value = 'trip_in_progress'
    else if (q.trip_status === 'completed') activeTab.value = 'trip_completed'
  } else if (typeof q.status === 'string' && ['draft', 'pending', 'approved', 'rejected', 'cancelled'].includes(q.status)) {
    activeTab.value = q.status
  } else {
    activeTab.value = 'all'
  }
  if (typeof q.trip_type === 'string') filters.trip_type = q.trip_type
  if (typeof q.source_channel === 'string') filters.source_channel = q.source_channel
  if (typeof q.paper_status === 'string') filters.paper_status = q.paper_status
  if (typeof q.q === 'string') {
    searchInput.value = q.q
  }
}

function onSearchInput() {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    filters.page = 1
    reload()
  }, 400)
}

watch(
  () => route.query,
  () => {
    applyRouteQuery()
    reload()
  },
  { deep: true },
)

onMounted(() => {
  applyRouteQuery()
  reload()
})

onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
