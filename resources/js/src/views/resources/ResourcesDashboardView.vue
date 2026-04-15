<template>
  <div class="mx-auto max-w-[1920px] space-y-4 pb-6 text-slate-900">
    <!-- Header -->
    <header
      class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-start lg:justify-between"
    >
      <div>
        <h1 class="text-lg font-semibold tracking-tight md:text-xl">{{ t('resources_dashboard.title') }}</h1>
        <p class="mt-0.5 text-sm text-slate-600">{{ t('resources_dashboard.subtitle') }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-0.5 text-xs shadow-sm">
          <button
            v-for="mode in viewModes"
            :key="mode.id"
            type="button"
            class="rounded-md px-2.5 py-1.5 font-medium transition"
            :class="
              viewMode === mode.id
                ? 'bg-teal-600 text-white shadow-sm'
                : mode.id === 'day'
                  ? 'text-slate-600 hover:bg-slate-50'
                  : 'cursor-not-allowed text-slate-400'
            "
            :disabled="mode.id !== 'day'"
            :title="mode.id !== 'day' ? t('resources_dashboard.view_coming') : ''"
            @click="viewMode = mode.id"
          >
            {{ mode.label }}
          </button>
        </div>
        <div class="flex flex-wrap items-center gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 shadow-sm hover:bg-slate-50"
            @click="shiftDay(-1)"
          >
            ‹
          </button>
          <span class="min-w-[10rem] text-center text-sm font-medium tabular-nums text-slate-900">{{ dayTitle }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 shadow-sm hover:bg-slate-50"
            @click="shiftDay(1)"
          >
            ›
          </button>
          <button
            type="button"
            class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-800 hover:bg-teal-100"
            @click="goToday"
          >
            {{ t('dispatcher_board.today') }}
          </button>
        </div>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-sm hover:bg-slate-50"
          to="/dispatcher"
        >
          {{ t('resources_dashboard.open_dispatcher') }}
        </RouterLink>
        <RouterLink
          class="inline-flex items-center rounded-lg border border-teal-600 bg-teal-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-teal-700"
          to="/resources/list"
        >
          {{ t('resources_dashboard.open_list') }}
        </RouterLink>
      </div>
    </header>

    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
      {{ loadError }}
    </div>

    <!-- KPI row -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-medium text-slate-500">{{ t('resources_dashboard.kpi_fleet') }}</div>
        <div v-if="statsLoading" class="mt-2 h-8 w-24 animate-pulse rounded bg-slate-200" />
        <template v-else>
          <div class="mt-1 text-2xl font-bold tabular-nums">{{ vehiclesOperational }} / {{ vehiclesTotal }}</div>
          <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-teal-500" :style="{ width: pct(vehiclesOperational, vehiclesTotal) }" />
          </div>
        </template>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-medium text-slate-500">{{ t('resources_dashboard.kpi_drivers') }}</div>
        <div v-if="statsLoading" class="mt-2 h-8 w-24 animate-pulse rounded bg-slate-200" />
        <template v-else>
          <div class="mt-1 text-2xl font-bold tabular-nums">{{ driversAvailable }} / {{ driversEmployed }}</div>
          <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-amber-400" :style="{ width: pct(driversAvailable, driversEmployed) }" />
          </div>
        </template>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-medium text-slate-500">{{ t('resources_dashboard.kpi_gaps') }}</div>
        <div v-if="statsLoading" class="mt-2 h-8 w-16 animate-pulse rounded bg-slate-200" />
        <template v-else>
          <div class="mt-1 flex items-baseline gap-2">
            <span class="text-2xl font-bold tabular-nums text-rose-600">{{ coverageAlertCount }}</span>
            <span class="text-xs text-slate-500">{{ t('resources_dashboard.kpi_gaps_unit') }}</span>
          </div>
          <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ t('resources_dashboard.kpi_gaps_hint') }}</p>
        </template>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs font-medium text-slate-500">{{ t('resources_dashboard.kpi_forecast') }}</span>
          <span class="text-[10px] text-slate-400">{{ t('resources_dashboard.kpi_forecast_hint') }}</span>
        </div>
        <div class="mt-2 h-[72px] w-full" role="img" :aria-label="t('resources_dashboard.kpi_forecast')">
          <svg class="h-full w-full overflow-visible" viewBox="0 0 120 48" preserveAspectRatio="none">
            <defs>
              <linearGradient id="rd-fill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="rgb(13 148 136)" stop-opacity="0.2" />
                <stop offset="100%" stop-color="rgb(13 148 136)" stop-opacity="0" />
              </linearGradient>
            </defs>
            <polyline
              :points="forecastCapacityPoints"
              fill="none"
              stroke="rgb(148 163 184)"
              stroke-width="0.8"
              stroke-dasharray="3 2"
              vector-effect="non-scaling-stroke"
            />
            <polygon :points="forecastDemandFill" fill="url(#rd-fill)" />
            <polyline
              :points="forecastDemandPoints"
              fill="none"
              stroke="rgb(13 148 136)"
              stroke-width="1.2"
              vector-effect="non-scaling-stroke"
            />
          </svg>
        </div>
      </div>
    </div>

    <!-- 3-column workspace -->
    <div class="flex flex-col gap-4 xl:flex-row xl:items-stretch">
      <!-- Left: resource lists -->
      <aside
        class="flex w-full shrink-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm xl:w-[min(100%,300px)] xl:max-w-[320px]"
      >
        <div class="border-b border-slate-200 p-3">
          <label class="block">
            <span class="sr-only">{{ t('resources_dashboard.search') }}</span>
            <input
              v-model="sideSearch"
              type="search"
              class="w-full rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              :placeholder="t('resources_dashboard.search_placeholder')"
            />
          </label>
        </div>
        <div class="min-h-[200px] flex-1 space-y-2 overflow-y-auto p-2 xl:max-h-[calc(100dvh-16rem)]">
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="cursor-pointer list-none px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              {{ t('resources_dashboard.col_drivers') }}
              <span class="font-normal text-slate-500">({{ filteredDrivers.length }}/{{ drivers.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="d in filteredDrivers" :key="'d' + d.id">
                <RouterLink
                  :to="`/resources/drivers/${d.id}`"
                  class="flex items-center gap-2 rounded-md px-2 py-1.5 text-xs transition hover:bg-teal-50"
                >
                  <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-[11px] font-semibold text-teal-900"
                  >
                    {{ initials(d.full_name) }}
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-slate-900">{{ d.full_name }}</span>
                    <span class="block truncate text-[10px] text-slate-500">{{ availabilityLabel(d) }}</span>
                  </span>
                </RouterLink>
              </li>
            </ul>
          </details>
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="cursor-pointer list-none px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              {{ t('resources_dashboard.col_vehicles') }}
              <span class="font-normal text-slate-500">({{ filteredVehicles.length }}/{{ vehicles.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="v in filteredVehicles" :key="'v' + v.id" class="px-2 py-1.5 text-xs">
                <div class="font-mono font-semibold text-slate-900">{{ v.license_plate }}</div>
                <div class="text-[10px] text-slate-500">{{ vehicleStatusLabel(v.status) }}</div>
              </li>
            </ul>
          </details>
          <details class="group rounded-lg border border-slate-100 bg-slate-50/50 open:bg-white" open>
            <summary
              class="cursor-pointer list-none px-2 py-2 text-xs font-semibold text-slate-800 marker:content-none [&::-webkit-details-marker]:hidden"
            >
              {{ t('resources_dashboard.col_suppliers') }}
              <span class="font-normal text-slate-500">({{ filteredSuppliers.length }}/{{ suppliers.length }})</span>
            </summary>
            <ul class="space-y-1 border-t border-slate-100 px-1 pb-2 pt-1">
              <li v-for="p in filteredSuppliers" :key="'p' + p.id" class="px-2 py-1.5 text-xs">
                <div class="truncate font-medium text-slate-900">{{ p.name }}</div>
                <div class="text-[10px] text-slate-500">{{ p.is_active ? t('resources_dashboard.supplier_active') : t('resources_dashboard.supplier_inactive') }}</div>
              </li>
            </ul>
          </details>
        </div>
      </aside>

      <!-- Center: timeline -->
      <section class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-3">
          <div
            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-600"
            role="list"
            :aria-label="t('dispatcher_board.legend_aria')"
          >
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-emerald-500" />
              {{ t('dispatcher_board.legend_assigned') }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-sky-500" />
              {{ t('dispatcher_board.legend_progress') }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5">
              <span class="h-2 w-2 shrink-0 rounded-sm bg-rose-500" />
              {{ t('dispatcher_board.legend_conflict') }}
            </span>
          </div>
        </div>
        <div class="overflow-x-auto">
          <div class="min-w-[720px] p-3">
            <div class="mb-1 flex text-[10px] text-slate-500">
              <div class="w-[140px] shrink-0" />
              <div class="grid min-w-0 flex-1" :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }">
                <div
                  v-for="h in hourSlots"
                  :key="h"
                  class="border-l border-slate-200 pl-1 text-left tabular-nums"
                >
                  {{ String(h).padStart(2, '0') }}:00
                </div>
              </div>
            </div>
            <div v-if="loading && !trips.length" class="py-12 text-center text-sm text-slate-500">
              {{ t('dispatcher_board.loading') }}
            </div>
            <template v-else>
              <div
                v-for="(row, rowIdx) in timelineRows"
                :key="row.key"
                class="flex border-b border-slate-100"
              >
                <div class="flex w-[140px] shrink-0 flex-col justify-center border-r border-slate-200 py-2 pr-2 text-xs">
                  <span class="truncate font-medium text-slate-800">{{ row.label }}</span>
                  <span v-if="row.sub" class="truncate text-[10px] text-rose-600">{{ row.sub }}</span>
                  <span v-else-if="row.meta" class="truncate text-[10px] text-slate-500">{{ row.meta }}</span>
                </div>
                <div class="relative min-h-[52px] min-w-0 flex-1 bg-slate-50/50">
                  <div
                    class="pointer-events-none absolute inset-0 grid"
                    :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }"
                  >
                    <div v-for="h in hourSlots" :key="`g-${row.key}-${h}`" class="border-l border-slate-200/90" />
                  </div>
                  <div
                    v-if="nowLinePct !== null"
                    class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-teal-500"
                    :style="{ left: `${nowLinePct}%` }"
                  >
                    <span
                      v-if="rowIdx === 0"
                      class="absolute -top-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded border border-slate-200 bg-white px-1 py-0.5 text-[9px] font-medium text-slate-700 shadow-sm"
                    >
                      {{ nowLabel }}
                    </span>
                  </div>
                  <button
                    v-for="bar in row.bars"
                    :key="bar.trip.id"
                    type="button"
                    class="absolute top-1.5 z-[5] flex h-9 items-center overflow-hidden rounded border px-1.5 text-left text-[10px] font-medium leading-tight shadow-sm transition hover:opacity-95"
                    :class="[bar.toneClass, selectedTrip?.id === bar.trip.id ? 'ring-2 ring-teal-500 ring-offset-1' : '']"
                    :style="{ left: `${bar.left}%`, width: `max(${bar.width}%, 2%)` }"
                    :title="`#${bar.trip.id}`"
                    @click="selectedTrip = bar.trip"
                  >
                    <span class="truncate">#{{ bar.trip.id }}</span>
                    <span
                      v-if="bar.conflict"
                      class="ml-0.5 inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[8px] text-white"
                    >
                      !
                    </span>
                  </button>
                </div>
              </div>
              <p v-if="!timelineRows.length && !loading" class="py-8 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.timeline_empty') }}
              </p>
            </template>
          </div>
        </div>
      </section>

      <!-- Right: action & constraints -->
      <aside
        class="flex w-full shrink-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm xl:w-[min(100%,320px)]"
      >
        <div class="border-b border-slate-200 p-3">
          <h2 class="text-sm font-semibold text-slate-900">{{ t('resources_dashboard.panel_title') }}</h2>
          <p class="mt-0.5 text-[11px] text-slate-500">{{ t('resources_dashboard.panel_subtitle') }}</p>
        </div>
        <div v-if="!selectedTrip" class="flex flex-1 flex-col items-center justify-center gap-2 p-6 text-center text-sm text-slate-500">
          {{ t('resources_dashboard.panel_empty') }}
        </div>
        <div v-else class="flex-1 space-y-4 overflow-y-auto p-3 text-sm">
          <div>
            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ t('resources_dashboard.trip_label') }}</div>
            <div class="mt-1 font-semibold text-slate-900">{{ tripTitle(selectedTrip) }}</div>
            <div class="mt-1 font-mono text-xs text-teal-700">#{{ selectedTrip.id }}</div>
            <span
              class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-800"
            >
              {{ labelTripStatus(selectedTrip.status) }}
            </span>
          </div>
          <dl class="space-y-2 text-xs">
            <div class="flex justify-between gap-2">
              <dt class="text-slate-500">{{ t('resources_dashboard.field_driver') }}</dt>
              <dd class="text-right font-medium text-slate-900">{{ selectedTrip.driver?.full_name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
              <dt class="text-slate-500">{{ t('resources_dashboard.field_vehicle') }}</dt>
              <dd class="text-right font-medium text-slate-900">{{ selectedTrip.vehicle?.license_plate ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
              <dt class="text-slate-500">{{ t('resources_dashboard.field_time') }}</dt>
              <dd class="text-right tabular-nums text-slate-900">
                {{ fmtTime(selectedTrip.depart_at) }} – {{ fmtTime(tripEndAt(selectedTrip)) }}
              </dd>
            </div>
          </dl>
          <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-3">
            <RouterLink
              class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
              :to="`/trips/${selectedTrip.id}`"
            >
              {{ t('resources_dashboard.action_open_trip') }}
            </RouterLink>
            <button
              type="button"
              class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
              @click="selectedTrip = null"
            >
              {{ t('resources_dashboard.action_clear') }}
            </button>
          </div>
          <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-3 text-[11px] text-slate-600">
            <div class="font-semibold text-slate-800">{{ t('resources_dashboard.compliance_title') }}</div>
            <ul class="mt-2 space-y-1.5">
              <li class="flex items-center gap-2">
                <span class="text-emerald-600">✓</span>
                {{ t('resources_dashboard.compliance_placeholder') }}
              </li>
            </ul>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { listDrivers, listTransportProviders, listVehicles } from '../../api/operational'
import { listTrips } from '../../api/trips'
import { labelTripStatus } from '../../util/labels'
import { shortViDayLabel, toLocalDateKey } from '../../util/dates'

const { t, locale } = useI18n()

const GRID_START = 6
const GRID_END = 22
const hourSlots = []
for (let h = GRID_START; h < GRID_END; h++) hourSlots.push(h)

const viewMode = ref('day')
const viewModes = computed(() => [
  { id: 'day', label: t('resources_dashboard.view_day') },
  { id: 'week', label: t('resources_dashboard.view_week') },
  { id: 'month', label: t('resources_dashboard.view_month') },
])

const loading = ref(false)
const loadError = ref('')
const trips = ref([])
const selectedTrip = ref(null)
const selectedDate = ref(new Date())
const sideSearch = ref('')

const statsLoading = ref(false)
const vehiclesTotal = ref(0)
const vehiclesReady = ref(0)
const vehiclesInUse = ref(0)
const driversEmployed = ref(0)
const driversAvailable = ref(0)

const drivers = ref([])
const vehicles = ref([])
const suppliers = ref([])

const dayKey = computed(() => toLocalDateKey(selectedDate.value))

const dayTitle = computed(() => {
  const k = dayKey.value
  const today = toLocalDateKey(new Date())
  if (k === today) return `${t('dispatcher_board.today')}, ${shortViDayLabel(k)}`
  return shortViDayLabel(k)
})

const vehiclesOperational = computed(() => vehiclesReady.value + vehiclesInUse.value)

const searchLower = computed(() => sideSearch.value.trim().toLowerCase())

const filteredDrivers = computed(() => {
  const q = searchLower.value
  if (!q) return drivers.value
  return drivers.value.filter((d) => {
    const name = (d.full_name ?? '').toLowerCase()
    return name.includes(q) || String(d.id).includes(q)
  })
})

const filteredVehicles = computed(() => {
  const q = searchLower.value
  if (!q) return vehicles.value
  return vehicles.value.filter((v) => (v.license_plate ?? '').toLowerCase().includes(q))
})

const filteredSuppliers = computed(() => {
  const q = searchLower.value
  if (!q) return suppliers.value
  return suppliers.value.filter((p) => (p.name ?? '').toLowerCase().includes(q))
})

function dr(trip) {
  return trip.dispatch_request ?? trip.dispatchRequest
}

function origin(trip) {
  return dr(trip)?.origin ?? ''
}

function dest(trip) {
  return dr(trip)?.destination ?? ''
}

function tripTitle(trip) {
  const a = origin(trip)
  const b = dest(trip)
  if (a && b) return `${a} → ${b}`
  return a || b || t('dispatcher_board.untitled_trip')
}

function fmtTime(v) {
  if (!v) return '—'
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Date(v).toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
}

function hourValue(d) {
  const x = new Date(d)
  return x.getHours() + x.getMinutes() / 60 + x.getSeconds() / 3600
}

function tripEndAt(trip) {
  const r = dr(trip)
  const end = r?.arrive_by ?? trip.arrive_by
  if (end) return new Date(end)
  const s = new Date(trip.depart_at)
  return new Date(s.getTime() + 60 * 60 * 1000)
}

function pctRange(trip) {
  const start = hourValue(trip.depart_at)
  const end = hourValue(tripEndAt(trip))
  const span = GRID_END - GRID_START
  const left = ((Math.max(GRID_START, start) - GRID_START) / span) * 100
  const right = ((Math.min(GRID_END, end) - GRID_START) / span) * 100
  const width = Math.max(right - left, 1.5)
  return { left, width: Math.min(width, 100 - left) }
}

function barTone(trip, conflict) {
  if (conflict) return 'border-rose-400 bg-rose-100 text-rose-900'
  if (trip.status === 'in_progress') return 'border-sky-400 bg-sky-100 text-sky-900'
  if (['assigned', 'driver_confirmed'].includes(trip.status)) {
    return 'border-emerald-400 bg-emerald-100 text-emerald-900'
  }
  return 'border-slate-300 bg-slate-200 text-slate-800'
}

function computeConflicts(tripList) {
  const byDriver = new Map()
  for (const trip of tripList) {
    const id = trip.driver_id ?? 0
    if (!byDriver.has(id)) byDriver.set(id, [])
    byDriver.get(id).push(trip)
  }
  const conflictIds = new Set()
  for (const group of byDriver.values()) {
    const sorted = [...group].sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at))
    for (let i = 0; i < sorted.length; i++) {
      for (let j = i + 1; j < sorted.length; j++) {
        const a = sorted[i]
        const b = sorted[j]
        const aEnd = tripEndAt(a).getTime()
        const bStart = new Date(b.depart_at).getTime()
        if (bStart < aEnd) {
          conflictIds.add(a.id)
          conflictIds.add(b.id)
        } else {
          break
        }
      }
    }
  }
  return conflictIds
}

const timelineTrips = computed(() =>
  trips.value.filter((x) => !['pending', 'approved', 'cancelled'].includes(x.status)),
)

const conflictIds = computed(() => computeConflicts(timelineTrips.value))

const coverageAlertCount = computed(() => conflictIds.value.size)

const timelineRows = computed(() => {
  const list = timelineTrips.value
  const byDriver = new Map()
  const unassigned = []

  for (const trip of list) {
    if (!trip.driver_id) {
      unassigned.push(trip)
      continue
    }
    const name = trip.driver?.full_name ?? `#${trip.driver_id}`
    const plate = trip.vehicle?.license_plate
    const key = `d-${trip.driver_id}`
    if (!byDriver.has(key)) {
      byDriver.set(key, { key, label: name, meta: plate ?? '', trips: [] })
    }
    byDriver.get(key).trips.push(trip)
  }

  const rows = []
  if (unassigned.length) {
    rows.push({
      key: 'unassigned',
      label: t('dispatcher_board.row_unassigned'),
      sub: '',
      meta: '',
      trips: unassigned,
    })
  }
  for (const r of byDriver.values()) {
    rows.push({ key: r.key, label: r.label, sub: '', meta: r.meta, trips: r.trips })
  }

  for (const row of rows) {
    row.bars = row.trips.map((trip) => {
      const { left, width } = pctRange(trip)
      const conflict = conflictIds.value.has(trip.id)
      return {
        trip,
        left,
        width,
        conflict,
        toneClass: barTone(trip, conflict),
      }
    })
  }

  return rows
})

const nowLinePct = computed(() => {
  const today = toLocalDateKey(new Date())
  if (dayKey.value !== today) return null
  const now = hourValue(new Date())
  if (now < GRID_START || now > GRID_END) return null
  const span = GRID_END - GRID_START
  return ((now - GRID_START) / span) * 100
})

const nowLabel = computed(() => {
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Date().toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
})

/** 24 buckets: trips starting in that hour (timeline day) */
const tripsPerHour = computed(() => {
  const buckets = Array(24).fill(0)
  for (const trip of timelineTrips.value) {
    const h = new Date(trip.depart_at).getHours()
    buckets[h]++
  }
  return buckets
})

const forecastDemandPoints = computed(() => buildSparklinePoints(tripsPerHour.value, 120, 40, 4))
const forecastDemandFill = computed(() => {
  const pts = forecastDemandPoints.value
  if (!pts) return ''
  const base = pts.split(' ')
  if (!base.length) return ''
  const first = base[0].split(',')[0]
  const last = base[base.length - 1].split(',')[0]
  return `${pts} ${last},48 ${first},48`
})

/** Flat “capacity” curve from active drivers (normalized) */
const forecastCapacityPoints = computed(() => {
  const cap = Math.max(1, driversEmployed.value || 1)
  const flat = Array(24).fill(cap * 0.6)
  return buildSparklinePoints(flat, 120, 40, 4)
})

function buildSparklinePoints(values, w, h, pad) {
  const max = Math.max(...values, 1)
  const pts = values.map((v, i) => {
    const x = pad + ((w - pad * 2) * i) / (values.length - 1 || 1)
    const y = h - pad - ((h - pad * 2) * v) / max
    return `${x.toFixed(1)},${y.toFixed(1)}`
  })
  return pts.join(' ')
}

function pct(part, total) {
  const t = Number(total) || 0
  const p = Number(part) || 0
  if (t <= 0) return '0%'
  return `${Math.min(100, Math.round((p / t) * 100))}%`
}

function initials(name) {
  if (!name || typeof name !== 'string') return '?'
  const p = name.trim().split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase()
}

function availabilityLabel(d) {
  const a = d.availability_status
  const e = d.employment_status
  if (e && e !== 'active') return e
  if (a === 'available') return t('resources_dashboard.avail_available')
  if (a === 'busy') return t('resources_dashboard.avail_busy')
  if (a === 'offline') return t('resources_dashboard.avail_offline')
  return a ?? '—'
}

function vehicleStatusLabel(s) {
  const map = {
    ready: t('resources.vehicle_status_ready'),
    in_use: t('resources.vehicle_status_in_use'),
    maintenance: t('resources.status_maintenance'),
    broken: t('resources_dashboard.vehicle_broken'),
  }
  return map[s] ?? s ?? '—'
}

function shiftDay(delta) {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + delta)
  selectedDate.value = d
}

function goToday() {
  selectedDate.value = new Date()
}

async function loadStats() {
  statsLoading.value = true
  try {
    const [vAll, vReady, vUse, dEmployed, dAvail] = await Promise.all([
      listVehicles({ per_page: 1 }),
      listVehicles({ per_page: 1, status: 'ready' }),
      listVehicles({ per_page: 1, status: 'in_use' }),
      listDrivers({ per_page: 1, employment_status: 'active' }),
      listDrivers({ per_page: 1, employment_status: 'active', availability_status: 'available' }),
    ])
    vehiclesTotal.value = vAll.meta?.total ?? 0
    vehiclesReady.value = vReady.meta?.total ?? 0
    vehiclesInUse.value = vUse.meta?.total ?? 0
    driversEmployed.value = dEmployed.meta?.total ?? 0
    driversAvailable.value = dAvail.meta?.total ?? 0
  } finally {
    statsLoading.value = false
  }
}

async function loadLists() {
  const [dRes, vRes, pRes] = await Promise.all([
    listDrivers({ per_page: 80, employment_status: 'active' }),
    listVehicles({ per_page: 80 }),
    listTransportProviders({ per_page: 40 }),
  ])
  drivers.value = dRes.items ?? []
  vehicles.value = vRes.items ?? []
  suppliers.value = pRes.items ?? []
}

async function loadTrips() {
  loading.value = true
  loadError.value = ''
  try {
    const k = dayKey.value
    const tr = await listTrips({ from: k, to: k, per_page: 100, page: 1 })
    trips.value = tr.items ?? []
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? t('dispatcher_board.load_error')
    trips.value = []
  } finally {
    loading.value = false
  }
}

async function loadAll() {
  await Promise.all([loadStats(), loadLists(), loadTrips()])
}

watch(dayKey, () => {
  selectedTrip.value = null
  loadTrips()
})

onMounted(() => {
  loadAll()
})
</script>
