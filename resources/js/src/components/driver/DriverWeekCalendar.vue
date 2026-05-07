<template>
  <section class="rounded-3xl border border-[#86c2b5]/20 bg-[#142421] p-4 shadow-lg shadow-black/15 ring-1 ring-[#86c2b5]/10">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
      <h2 class="text-base font-bold text-[#86c2b5]">
        {{ t('driver_home.calendar_title') }}
      </h2>
      <p class="text-sm tabular-nums font-semibold text-[#86c2b5]/80">
        {{ weekRangeLabel }}
      </p>
    </div>

    <div v-if="loading" class="grid grid-cols-7 gap-1.5">
      <div v-for="n in 7" :key="n" class="flex flex-col items-center gap-2 py-1">
        <div class="h-3 w-6 animate-pulse rounded bg-[#86c2b5]/15" />
        <div class="h-10 w-10 animate-pulse rounded-full bg-[#0d1f1c]/80" />
        <div class="h-3.5 w-4 animate-pulse rounded bg-[#86c2b5]/10" />
      </div>
    </div>

    <div v-else class="grid grid-cols-7 gap-1 sm:gap-1.5">
      <button
        v-for="day in weekDays"
        :key="day.iso"
        type="button"
        class="flex flex-col items-center gap-1.5 rounded-xl py-1 text-center transition hover:bg-[#86c2b5]/5 active:scale-[0.98]"
        :class="selectedIso === day.iso ? 'ring-2 ring-[#86c2b5]/60 ring-offset-2 ring-offset-[#142421]' : ''"
        :aria-pressed="selectedIso === day.iso"
        @click="selectedIso = day.iso"
      >
        <span
          class="text-[11px] font-bold uppercase leading-none tracking-wide sm:text-xs"
          :class="
            day.isToday ? 'text-[#86c2b5]' : selectedIso === day.iso ? 'text-[#86c2b5]/90' : 'text-slate-400'
          "
        >
          {{ day.abbr }}
        </span>

        <div
          class="flex h-10 min-h-[40px] w-10 min-w-[40px] items-center justify-center rounded-full text-sm font-bold tabular-nums leading-none transition-colors"
          :class="
            selectedIso === day.iso
              ? day.isToday
                ? 'bg-[#86c2b5] text-[#0d1f1c] shadow-lg shadow-[#86c2b5]/30 ring-2 ring-[#86c2b5]/50'
                : 'bg-[#1a2f2b] text-white shadow-md ring-2 ring-[#86c2b5]/45'
              : day.isToday
                ? 'bg-[#86c2b5]/35 text-[#86c2b5] ring-1 ring-[#86c2b5]/35'
                : day.tripCount > 0
                  ? 'bg-[#1a2f2b] text-white ring-1 ring-[#86c2b5]/35'
                  : 'bg-[#0d1f1c]/70 text-slate-500'
          "
        >
          {{ day.date }}
        </div>

        <span
          class="min-h-[14px] text-[11px] font-bold tabular-nums leading-none sm:text-xs"
          :class="
            day.tripCount > 0 ? 'text-[#86c2b5]' : 'invisible text-transparent'
          "
          :aria-hidden="day.tripCount === 0"
        >
          {{ day.tripCount > 0 ? day.tripCount : '0' }}
        </span>
      </button>
    </div>

    <!-- Horizontal timeline -->
    <div v-if="!loading" class="mt-5">
      <div class="mb-2 flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ t('driver_home.calendar_timeline_hint') }}
        </p>
        <p class="text-[11px] tabular-nums text-[#86c2b5]/70">
          {{ selectedDayShortLabel }}
        </p>
      </div>

      <div
        class="relative overflow-x-auto rounded-2xl border border-slate-700/40 bg-[#070f18] [-webkit-overflow-scrolling:touch]"
      >
        <div class="relative min-h-[120px] min-w-[min(100%,520px)] px-2 pb-3 pt-8 sm:min-w-full">
          <!-- Vertical grid -->
          <div
            class="pointer-events-none absolute inset-x-2 top-8 bottom-3 flex"
            aria-hidden="true"
          >
            <div
              v-for="tick in hourTicks"
              :key="tick.minutes"
              class="absolute top-0 bottom-0 w-px bg-slate-600/25"
              :style="{ left: `${tick.pct}%` }"
            />
          </div>

          <!-- Hour labels -->
          <div class="absolute inset-x-2 top-2 flex h-5">
            <span
              v-for="tick in hourTicks"
              :key="'l-' + tick.minutes"
              class="absolute -translate-x-1/2 text-[10px] font-medium tabular-nums text-slate-500 sm:text-[11px]"
              :style="{ left: `${tick.pct}%` }"
            >
              {{ tick.label }}
            </span>
          </div>

          <!-- Trip bars -->
          <div
            class="relative mx-0 mt-1"
            :style="{ minHeight: `${Math.max(72, layout.laneCount * LANE_STRIDE + 8)}px` }"
          >
            <RouterLink
              v-for="block in layout.blocks"
              :key="block.trip.id"
              :to="`/driver/trips/${block.trip.id}`"
              class="group absolute flex min-h-[46px] flex-col justify-center rounded-xl px-2 py-1.5 transition hover:z-10 hover:brightness-110 active:scale-[0.99]"
              :class="block.variant === 'blue' ? cardBlue : cardTeal"
              :style="{
                left: `${block.leftPct}%`,
                width: `${block.widthPct}%`,
                top: `${block.lane * LANE_STRIDE}px`,
              }"
            >
              <p class="truncate text-[11px] font-bold leading-tight text-white sm:text-xs">
                {{ block.title }}
              </p>
              <p
                class="truncate font-mono text-[10px] font-semibold sm:text-[11px]"
                :class="block.variant === 'blue' ? 'text-sky-300/90' : 'text-cyan-200/90'"
              >
                {{ block.refLabel }}
              </p>
            </RouterLink>
          </div>

          <p
            v-if="!layout.blocks.length && tripsForDay.length === 0"
            class="px-2 pb-6 pt-4 text-center text-sm text-slate-500"
          >
            {{ t('driver_home.calendar_no_trips_day') }}
          </p>
        </div>
      </div>
    </div>

    <p class="mt-3 border-t border-[#86c2b5]/15 pt-3 text-center text-xs leading-relaxed text-[#86c2b5]/70">
      {{ t('driver_home.calendar_legend_trips') }}
    </p>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t, locale } = useI18n()

const selectedIso = ref('')
const LANE_STRIDE = 54
const MIN_WIDTH_PCT = 13

const cardTeal =
  'border border-cyan-400/45 bg-gradient-to-b from-teal-500/20 to-[#081820]/95 shadow-[0_0_14px_rgba(34,211,238,0.14)]'
const cardBlue =
  'border border-sky-400/50 bg-gradient-to-b from-sky-600/18 to-[#081528]/95 shadow-[0_0_14px_rgba(56,189,248,0.14)]'

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const tripCountByDate = computed(() => {
  const map = {}
  for (const trip of props.rawTrips) {
    const iso = trip.depart_at
    if (!iso) continue
    const key = iso.slice(0, 10)
    map[key] = (map[key] ?? 0) + 1
  }
  return map
})

const DAY_ABBR_VI = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
const DAY_ABBR_EN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const weekDays = computed(() => {
  const today = new Date()
  const todayIso = ymd(today)
  const dow = today.getDay()
  const monday = new Date(today)
  monday.setDate(today.getDate() - ((dow + 6) % 7))

  const isVi = locale.value === 'vi'

  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    const iso = ymd(d)
    return {
      iso,
      date: d.getDate(),
      abbr: isVi ? DAY_ABBR_VI[d.getDay()] : DAY_ABBR_EN[d.getDay()],
      isToday: iso === todayIso,
      tripCount: tripCountByDate.value[iso] ?? 0,
    }
  })
})

watch(
  weekDays,
  (days) => {
    if (!days?.length) return
    const ok = days.some((d) => d.iso === selectedIso.value)
    if (!selectedIso.value || !ok) {
      selectedIso.value = days.find((d) => d.isToday)?.iso ?? days[0].iso
    }
  },
  { immediate: true },
)

const weekRangeLabel = computed(() => {
  const days = weekDays.value
  if (!days.length) return ''
  const start = days[0].iso
  const end = days[days.length - 1].iso
  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  const fmt = (iso) => {
    const [y, m, d] = iso.split('-').map(Number)
    const dt = new Date(y, m - 1, d)
    return dt.toLocaleDateString(loc, { day: '2-digit', month: '2-digit' })
  }
  return `${fmt(start)} – ${fmt(end)}`
})

const selectedDayShortLabel = computed(() => {
  const iso = selectedIso.value
  if (!iso) return ''
  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  const [y, m, d] = iso.split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  return dt.toLocaleDateString(loc, {
    weekday: 'short',
    day: 'numeric',
    month: 'numeric',
  })
})

const tripsForDay = computed(() => {
  const iso = selectedIso.value
  if (!iso) return []
  return props.rawTrips
    .filter((trip) => trip.depart_at && trip.depart_at.slice(0, 10) === iso)
    .slice()
    .sort((a, b) => (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0))
})

function durationMinutes(trip) {
  const dep = new Date(trip.depart_at).getTime()
  const arrRaw = trip.arrive_by || trip.dispatch_request?.arrive_by
  const arr = arrRaw ? new Date(arrRaw).getTime() : NaN
  if (!Number.isFinite(dep)) return 45
  if (!Number.isFinite(arr) || arr <= dep) return 45
  return Math.min(Math.round((arr - dep) / 60000), 240)
}

function departMinutes(trip) {
  const d = new Date(trip.depart_at)
  if (Number.isNaN(d.getTime())) return null
  return d.getHours() * 60 + d.getMinutes()
}

function computeTimeWindow(trips) {
  const DEF_START = 8 * 60
  const DEF_END = 18 * 60
  if (!trips.length) return { start: DEF_START, end: DEF_END }
  let min = Infinity
  let max = -Infinity
  for (const trip of trips) {
    const startMin = departMinutes(trip)
    if (startMin == null) continue
    const endMin = startMin + durationMinutes(trip)
    min = Math.min(min, startMin)
    max = Math.max(max, endMin)
  }
  if (!Number.isFinite(min)) return { start: DEF_START, end: DEF_END }
  min = Math.max(0, min - 45)
  max = Math.min(24 * 60, max + 45)
  if (max - min < 180) {
    const mid = (min + max) / 2
    min = Math.max(0, mid - 90)
    max = Math.min(24 * 60, mid + 90)
  }
  return { start: Math.floor(min / 30) * 30, end: Math.ceil(max / 30) * 30 }
}

const timeWindow = computed(() => computeTimeWindow(tripsForDay.value))

const hourTicks = computed(() => {
  const { start, end } = timeWindow.value
  const span = Math.max(end - start, 60)
  const step = span <= 240 ? 60 : 120
  const ticks = []
  let m = Math.ceil(start / step) * step
  if (m < start) m += step
  for (; m <= end; m += step) {
    ticks.push({
      minutes: m,
      label: `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`,
      pct: ((m - start) / span) * 100,
    })
  }
  if (!ticks.length) {
    ticks.push({
      minutes: start,
      label: `${String(Math.floor(start / 60)).padStart(2, '0')}:${String(start % 60).padStart(2, '0')}`,
      pct: 0,
    })
  }
  return ticks
})

function tripVariant(trip) {
  const tt = trip.dispatch_request?.trip_type
  if (tt === 'business' || tt === 'point_to_point') return 'blue'
  return 'teal'
}

function tripTitle(trip) {
  const o = (trip.dispatch_request?.origin || '').trim()
  if (!o) return t('driver_home.line_route')
  return o.length > 26 ? `${o.slice(0, 24)}…` : o
}

function tripRefLabel(trip) {
  const code = trip.trip_code
  if (code != null && String(code).trim() !== '') return `#${String(code).trim()}`
  return `#${trip.id}`
}

const layout = computed(() => {
  const trips = tripsForDay.value
  const { start, end } = timeWindow.value
  const spanSafe = Math.max(end - start, 1)

  const rawBlocks = trips
    .map((trip) => {
      const startMin = departMinutes(trip)
      if (startMin == null) return null

      const dur = durationMinutes(trip)
      const endMin = startMin + dur

      const overlapStart = Math.max(startMin, start)
      const overlapEnd = Math.min(endMin, end)
      if (overlapEnd <= overlapStart) return null

      const leftPct = ((overlapStart - start) / spanSafe) * 100
      let widthPct = ((overlapEnd - overlapStart) / spanSafe) * 100
      widthPct = Math.max(MIN_WIDTH_PCT, widthPct)
      const widthAdj = Math.min(widthPct, 100 - leftPct)

      return {
        trip,
        title: tripTitle(trip),
        refLabel: tripRefLabel(trip),
        variant: tripVariant(trip),
        leftPct: Math.max(0, Math.min(leftPct, 100 - MIN_WIDTH_PCT)),
        widthPct: Math.max(MIN_WIDTH_PCT, widthAdj),
        startMin: overlapStart,
        endMin: overlapEnd,
      }
    })
    .filter(Boolean)

  rawBlocks.sort((a, b) => a.startMin - b.startMin || a.endMin - b.endMin)

  const laneEnds = []
  for (const block of rawBlocks) {
    let lane = 0
    while (lane < laneEnds.length && laneEnds[lane] > block.startMin) lane++
    if (lane === laneEnds.length) laneEnds.push(block.endMin)
    else laneEnds[lane] = Math.max(laneEnds[lane], block.endMin)
    block.lane = lane
  }

  return {
    blocks: rawBlocks,
    laneCount: Math.max(1, laneEnds.length),
  }
})
</script>
