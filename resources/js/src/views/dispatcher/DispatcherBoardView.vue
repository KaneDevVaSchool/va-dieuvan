<template>
  <div class="space-y-4 text-slate-900">
    <div class="mx-auto max-w-[1920px] space-y-4">
      <!-- Header -->
      <header class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-lg font-semibold tracking-tight text-slate-900 md:text-xl">
            {{ t('dispatcher_board.title') }}
          </h1>
          <p class="mt-0.5 text-sm text-slate-600">
            {{ t('dispatcher_board.subtitle') }}
          </p>
        </div>
        <nav
          class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-slate-500"
          aria-label="Process"
        >
          <span
            v-for="(step, i) in processSteps"
            :key="step"
            class="inline-flex items-center gap-1.5"
          >
            <span
              :class="
                step === 'Assign'
                  ? 'rounded border border-teal-300 bg-teal-50 px-2 py-0.5 font-medium text-teal-800'
                  : ''
              "
            >{{ step }}</span>
            <span v-if="i < processSteps.length - 1" class="text-slate-400">›</span>
          </span>
        </nav>
      </header>

      <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        {{ loadError }}
      </div>

      <div class="flex flex-col gap-4 xl:flex-row xl:items-stretch">
        <!-- Trip queue -->
        <aside
          id="dispatcher-trip-queue"
          class="flex w-full shrink-0 flex-col rounded-xl border border-slate-200 bg-white shadow-sm xl:w-[min(100%,380px)]"
        >
          <div class="border-b border-slate-200 p-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">
                  {{ t('dispatcher_board.queue_title') }}
                  <span class="font-normal text-slate-500">({{ queueTrips.length }})</span>
                </h2>
                <p v-if="pendingRequestsCount" class="mt-0.5 text-xs text-slate-500">
                  {{ t('dispatcher_board.pending_requests_hint', { n: pendingRequestsCount }) }}
                </p>
              </div>
              <RouterLink
                class="shrink-0 text-xs font-medium text-teal-700 hover:text-teal-800"
                to="/requests?status=pending"
              >
                {{ t('dispatcher_board.open_requests') }}
              </RouterLink>
            </div>
            <div class="mt-3 flex flex-wrap gap-1.5">
              <button
                v-for="tab in queueTabs"
                :key="tab.id"
                type="button"
                class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors"
                :class="
                  queueFilter === tab.id
                    ? 'bg-teal-600 text-white shadow-sm'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                "
                @click="queueFilter = tab.id"
              >
                {{ tab.label }}
                <span
                  class="tabular-nums"
                  :class="queueFilter === tab.id ? 'text-teal-100' : 'text-slate-500'"
                >({{ tab.count }})</span>
              </button>
            </div>
            <label class="mt-3 block">
              <span class="sr-only">{{ t('dispatcher_board.search') }}</span>
              <input
                v-model="queueSearch"
                type="search"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                :placeholder="t('dispatcher_board.search_placeholder')"
              />
            </label>
          </div>
          <div class="min-h-[280px] flex-1 space-y-2 overflow-y-auto p-3 xl:max-h-[calc(100dvh-14rem)]">
            <div v-if="loading && !trips.length" class="py-8 text-center text-sm text-slate-500">
              {{ t('dispatcher_board.loading') }}
            </div>
            <template v-else>
              <RouterLink
                v-for="trip in filteredQueueTrips"
                :key="trip.id"
                :to="`/trips/${trip.id}`"
                class="block rounded-lg border border-slate-200 bg-slate-50/80 p-3 transition-colors hover:border-slate-300 hover:bg-white"
              >
                <div class="flex items-start justify-between gap-2">
                  <span class="font-mono text-xs font-semibold text-teal-700">#{{ trip.id }}</span>
                  <span
                    v-if="queueBadge(trip)"
                    class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                    :class="queueBadge(trip).class"
                  >
                    {{ queueBadge(trip).text }}
                  </span>
                </div>
                <div class="mt-1 text-sm font-medium text-slate-900">
                  {{ tripTitle(trip) }}
                </div>
                <div class="mt-1 flex flex-wrap gap-2 text-[11px] text-slate-500">
                  <span>{{ labelTripType(tripType(trip)) }}</span>
                  <span v-if="tripType(trip) === 'cargo'" class="text-slate-400">·</span>
                  <span v-if="tripType(trip) === 'cargo'">{{ t('dispatcher_board.cargo') }}</span>
                </div>
                <div class="mt-2 flex gap-2 border-l-2 border-slate-300 pl-2 text-[11px] leading-snug text-slate-600">
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-800">{{ fmtTime(trip.depart_at) }}</div>
                    <div class="truncate">{{ origin(trip) || '—' }}</div>
                  </div>
                  <div class="text-slate-400">→</div>
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-800">{{ fmtArrive(trip) }}</div>
                    <div class="truncate">{{ dest(trip) || '—' }}</div>
                  </div>
                </div>
              </RouterLink>
              <p v-if="!filteredQueueTrips.length" class="py-6 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.queue_empty') }}
              </p>
            </template>
          </div>
        </aside>

        <!-- Timeline -->
        <section
          class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md shadow-slate-500/[0.07] ring-1 ring-slate-100/90 dark:border-slate-700/80 dark:bg-slate-900/40 dark:shadow-none dark:ring-slate-800/80"
        >
          <div class="space-y-3 border-b border-slate-100/90 bg-gradient-to-r from-teal-50/80 via-white to-sky-50/45 p-3.5 dark:from-teal-950/30 dark:via-slate-900 dark:to-sky-950/25 dark:border-slate-700/80">
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="shiftDay(-1)"
              >
                ‹
              </button>
              <span class="min-w-[10rem] text-center text-sm font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ dayTitle }}
              </span>
              <button
                type="button"
                class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                @click="shiftDay(1)"
              >
                ›
              </button>
              <button
                type="button"
                class="rounded-lg border border-teal-200/90 bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/60 dark:text-teal-100 dark:hover:bg-teal-900/50"
                @click="goToday"
              >
                {{ t('dispatcher_board.today') }}
              </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div
                class="flex flex-wrap items-center gap-2"
                role="list"
                :aria-label="t('dispatcher_board.legend_aria')"
              >
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50/90 px-2.5 py-1 text-[11px] font-medium text-emerald-900 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500 shadow-sm shadow-emerald-600/40" />
                  {{ t('dispatcher_board.legend_assigned') }}
                </span>
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-sky-200/80 bg-sky-50/90 px-2.5 py-1 text-[11px] font-medium text-sky-900 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/40 dark:text-sky-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-sky-500 shadow-sm shadow-sky-600/40" />
                  {{ t('dispatcher_board.legend_progress') }}
                </span>
                <span role="listitem" class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/80 bg-rose-50/90 px-2.5 py-1 text-[11px] font-medium text-rose-900 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100">
                  <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500 shadow-sm shadow-rose-600/40" />
                  {{ t('dispatcher_board.legend_conflict') }}
                </span>
              </div>
              <button
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200/90 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-teal-200 hover:bg-teal-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                :title="t('dispatcher_board.bulk_assign_hint')"
                @click="goBulkAssign"
              >
                <Square2StackIcon class="h-4 w-4 text-slate-600" aria-hidden="true" />
                {{ t('dispatcher_board.bulk_assign') }}
              </button>
            </div>
          </div>

          <div class="overflow-x-auto [scrollbar-width:thin]">
            <div class="min-w-[720px] bg-gradient-to-b from-slate-50/40 to-white p-3 dark:from-slate-950/50 dark:to-slate-900/30">
              <!-- Hour labels -->
              <div class="mb-1.5 flex text-[10px] font-medium text-slate-500 dark:text-slate-400">
                <div class="w-[140px] shrink-0" />
                <div class="grid min-w-0 flex-1" :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }">
                  <div
                    v-for="h in hourSlots"
                    :key="h"
                    class="border-l border-slate-200/90 pl-1.5 text-left tabular-nums first:border-l-0 dark:border-slate-600/80"
                  >
                    {{ String(h).padStart(2, '0') }}:00
                  </div>
                </div>
              </div>

              <div
                v-for="(row, rowIdx) in timelineRows"
                :key="row.key"
                class="flex border-b border-slate-100/90 last:border-b-0 dark:border-slate-700/60"
              >
                <div class="flex w-[140px] shrink-0 flex-col justify-center border-r border-slate-200/90 bg-white/60 py-2.5 pr-2.5 text-xs dark:border-slate-700/80 dark:bg-slate-900/30">
                  <span class="truncate font-medium text-slate-800 dark:text-slate-100">{{ row.label }}</span>
                  <span v-if="row.sub" class="truncate text-[10px] text-rose-600">{{ row.sub }}</span>
                  <span v-else-if="row.meta" class="truncate text-[10px] text-slate-500 dark:text-slate-400">{{ row.meta }}</span>
                </div>
                <div class="relative min-h-[64px] min-w-0 flex-1 bg-slate-50/60 dark:bg-slate-950/20">
                  <div
                    class="pointer-events-none absolute inset-0 grid"
                    :style="{ gridTemplateColumns: `repeat(${hourSlots.length}, minmax(0, 1fr))` }"
                  >
                    <div
                      v-for="h in hourSlots"
                      :key="`g-${row.key}-${h}`"
                      class="border-l border-slate-200/70 first:border-l-0 dark:border-slate-700/50"
                    />
                  </div>
                  <!-- now line (once per scroll area — duplicate on each row for alignment) -->
                  <div
                    v-if="nowLinePct !== null"
                    class="pointer-events-none absolute bottom-0 top-0 z-10 w-px bg-teal-500"
                    :style="{ left: `${nowLinePct}%` }"
                  >
                    <span
                      v-if="rowIdx === 0"
                      class="absolute -top-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md border border-slate-200/90 bg-white px-1.5 py-0.5 text-[9px] font-semibold text-slate-700 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                    >{{ nowLabel }}</span>
                  </div>
                  <RouterLink
                    v-for="bar in row.bars"
                    :key="bar.trip.id"
                    :to="`/trips/${bar.trip.id}`"
                    class="absolute top-2 z-[5] flex min-h-[2.75rem] items-start gap-1 overflow-hidden rounded-lg border px-2 py-1.5 text-left shadow-md transition hover:brightness-[0.98] hover:shadow-lg dark:hover:brightness-110"
                    :class="bar.toneClass"
                    :style="{ left: `${bar.left}%`, width: `max(${bar.width}%, ${MIN_TIMELINE_BAR_PCT}%)` }"
                    :title="`${tripTitle(bar.trip)} · ${fmtTime(bar.trip.depart_at)} · ${labelTripStatus(bar.trip.status)}`"
                  >
                    <span class="min-w-0 flex-1">
                      <span class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0">
                        <span class="font-semibold tabular-nums">#{{ bar.trip.id }}</span>
                        <span class="text-[9px] font-normal opacity-85">{{ fmtTime(bar.trip.depart_at) }}</span>
                      </span>
                      <span class="mt-0.5 block truncate text-[9px] font-normal leading-snug opacity-90">{{ tripTitle(bar.trip) }}</span>
                    </span>
                    <span
                      v-if="bar.conflict"
                      class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[9px] font-bold text-white shadow-sm"
                    >!</span>
                  </RouterLink>
                </div>
              </div>

              <p v-if="!timelineRows.length && !loading" class="py-8 text-center text-sm text-slate-500">
                {{ t('dispatcher_board.timeline_empty') }}
              </p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Square2StackIcon } from '@heroicons/vue/24/outline'
import { listTrips } from '../../api/trips'
import { listRequests } from '../../api/requests'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { shortViDayLabel, toLocalDateKey } from '../../util/dates'

const { t } = useI18n()
const router = useRouter()

const GRID_START = 6
const GRID_END = 22
const PROCESS = ['Request', 'Review', 'Approve', 'Assign', 'Execute', 'Complete']

const processSteps = PROCESS
const hourSlots = []
for (let h = GRID_START; h < GRID_END; h++) hourSlots.push(h)

const loading = ref(false)
const loadError = ref('')
const trips = ref([])
const pendingRequestsCount = ref(0)
const selectedDate = ref(new Date())
const queueFilter = ref('all')
const queueSearch = ref('')

const queueTabs = computed(() => {
  const q = queueTrips.value
  return [
    { id: 'all', label: t('dispatcher_board.tab_all'), count: q.length },
    {
      id: 'urgent',
      label: t('dispatcher_board.tab_urgent'),
      count: q.filter((x) => dr(x)?.is_urgent).length,
    },
    {
      id: 'cargo',
      label: t('dispatcher_board.tab_cargo'),
      count: q.filter((x) => tripType(x) === 'cargo').length,
    },
    {
      id: 'd2d',
      label: t('dispatcher_board.tab_d2d'),
      count: q.filter((x) => tripType(x) === 'door_to_door').length,
    },
  ]
})

const dayKey = computed(() => toLocalDateKey(selectedDate.value))

const dayTitle = computed(() => {
  const k = dayKey.value
  const today = toLocalDateKey(new Date())
  if (k === today) return `${t('dispatcher_board.today')}, ${shortViDayLabel(k)}`
  return shortViDayLabel(k)
})

function dr(trip) {
  return trip.dispatch_request ?? trip.dispatchRequest
}

function tripType(trip) {
  return dr(trip)?.trip_type ?? ''
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
  return new Date(v).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function fmtArrive(trip) {
  const r = dr(trip)
  const ab = r?.arrive_by ?? trip.arrive_by
  return ab ? fmtTime(ab) : '—'
}

/** Chuyến cần phân công / xử lý tiếp */
const queueTrips = computed(() => {
  return trips.value.filter((x) => ['pending', 'approved'].includes(x.status))
})

const filteredQueueTrips = computed(() => {
  let list = queueTrips.value
  if (queueFilter.value === 'urgent') list = list.filter((x) => dr(x)?.is_urgent)
  else if (queueFilter.value === 'cargo') list = list.filter((x) => tripType(x) === 'cargo')
  else if (queueFilter.value === 'd2d') list = list.filter((x) => tripType(x) === 'door_to_door')

  const q = queueSearch.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((x) => {
    const idStr = String(x.id)
    const hay = [idStr, origin(x), dest(x), tripTitle(x)].join(' ').toLowerCase()
    return hay.includes(q)
  })
})

function queueBadge(trip) {
  const r = dr(trip)
  if (r?.status === 'approved') {
    return { text: t('dispatcher_board.badge_approved'), class: 'bg-sky-100 text-sky-800' }
  }
  if (r?.is_urgent) {
    return { text: t('dispatcher_board.badge_urgent'), class: 'bg-rose-100 text-rose-800' }
  }
  return null
}

function hourValue(d) {
  const x = new Date(d)
  return x.getHours() + x.getMinutes() / 60 + x.getSeconds() / 3600
}

function tripEnd(trip) {
  const r = dr(trip)
  const end = r?.arrive_by ?? trip.arrive_by
  if (end) return new Date(end)
  const s = new Date(trip.depart_at)
  return new Date(s.getTime() + 60 * 60 * 1000)
}

/** Đủ rộng để hiển thị mã chuyến, giờ và tuyến */
const MIN_TIMELINE_BAR_PCT = 12

function pctRange(trip) {
  const start = hourValue(trip.depart_at)
  const end = hourValue(tripEnd(trip))
  const span = GRID_END - GRID_START
  const left = ((Math.max(GRID_START, start) - GRID_START) / span) * 100
  const right = ((Math.min(GRID_END, end) - GRID_START) / span) * 100
  const width = Math.max(right - left, MIN_TIMELINE_BAR_PCT)
  return { left, width: Math.min(width, 100 - left) }
}

function barTone(trip, conflict) {
  if (conflict) {
    return 'border-rose-400 bg-rose-100 text-rose-900'
  }
  if (trip.status === 'in_progress') {
    return 'border-sky-400 bg-sky-100 text-sky-900'
  }
  if (['assigned', 'driver_confirmed'].includes(trip.status)) {
    return 'border-emerald-400 bg-emerald-100 text-emerald-900'
  }
  return 'border-slate-300 bg-slate-200 text-slate-800'
}

/** Cặp chuyến trùng giờ trên cùng tài xế */
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
        const aEnd = tripEnd(a).getTime()
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

/** Trên lịch: chuyến đã phân công / đang chạy — không trùng với hàng đợi (pending, approved). */
const timelineTrips = computed(() =>
  trips.value.filter((x) => !['pending', 'approved', 'cancelled'].includes(x.status)),
)

const conflictIds = computed(() => computeConflicts(timelineTrips.value))

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
      byDriver.set(key, {
        key,
        label: name,
        meta: plate ?? '',
        trips: [],
      })
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
    rows.push({
      key: r.key,
      label: r.label,
      sub: '',
      meta: r.meta,
      trips: r.trips,
    })
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

const nowLabel = computed(() =>
  new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
)

function shiftDay(delta) {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + delta)
  selectedDate.value = d
}

function goToday() {
  selectedDate.value = new Date()
}

/** Mở danh sách chuyến (lọc chờ xử lý) để gán xe / tài xế từng chuyến — API bulk chưa có. */
function goBulkAssign() {
  router.push({ path: '/trips', query: { status: 'pending' } })
}

async function load() {
  loading.value = true
  loadError.value = ''
  try {
    const k = dayKey.value
    const [tr, req] = await Promise.all([
      listTrips({ from: k, to: k, per_page: 100, page: 1 }),
      listRequests({ status: 'pending', per_page: 1, page: 1 }),
    ])
    trips.value = tr.items ?? []
    pendingRequestsCount.value = req.meta?.total ?? 0
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? t('dispatcher_board.load_error')
    trips.value = []
  } finally {
    loading.value = false
  }
}

watch(dayKey, () => {
  load()
})

onMounted(load)
</script>
