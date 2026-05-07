<template>
  <section class="rounded-3xl bg-[#0f1816] shadow-xl shadow-black/25">
    <!-- Section header -->
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-2 px-5 pb-4 pt-5">
      <h2 class="text-xl font-bold text-[#7fdcc8]">
        {{ t('driver_home.calendar_title') }}
      </h2>
      <p class="shrink-0 text-base font-semibold tabular-nums text-[#7fdcc8]/70">
        {{ weekRangeLabel }}
      </p>
    </div>

    <!-- Day picker -->
    <div class="px-3 pb-4 sm:px-4">
      <div v-if="loading" class="grid grid-cols-7 gap-1">
        <div v-for="n in 7" :key="n" class="flex flex-col items-center gap-2 py-1">
          <div class="h-3 w-6 animate-pulse rounded bg-[#7fdcc8]/15" />
          <div class="h-14 w-14 max-w-full animate-pulse rounded-full bg-[#070f0d]/90" />
          <div class="h-4 w-5 animate-pulse rounded-md bg-[#7fdcc8]/10" />
        </div>
      </div>

      <div v-else class="grid grid-cols-7 gap-0.5">
        <button
          v-for="day in weekDays"
          :key="day.iso"
          type="button"
          class="flex min-h-[76px] w-full flex-col items-center gap-1.5 rounded-xl py-2 transition active:scale-[0.95]"
          :class="selectedIso === day.iso ? 'bg-[#7fdcc8]/10' : 'hover:bg-[#7fdcc8]/5'"
          :aria-pressed="selectedIso === day.iso"
          @click="selectedIso = day.iso"
        >
          <!-- Day abbreviation -->
          <span
            class="text-xs font-bold uppercase leading-none tracking-wide"
            :class="
              day.isToday
                ? 'text-[#7fdcc8]'
                : selectedIso === day.iso
                  ? 'text-[#7fdcc8]/80'
                  : 'text-slate-400'
            "
          >
            {{ day.abbr }}
          </span>

          <!-- Date circle -->
          <div
            class="flex h-12 w-12 max-w-full items-center justify-center rounded-full text-lg font-bold tabular-nums leading-none transition-colors"
            :class="
              selectedIso === day.iso
                ? day.isToday
                  ? 'bg-[#7fdcc8] text-[#070f0d] shadow-md shadow-[#7fdcc8]/30'
                  : 'bg-[#1a2826] text-white ring-1 ring-[#7fdcc8]/30'
                : day.isToday
                  ? 'bg-[#7fdcc8]/25 text-[#7fdcc8]'
                  : day.tripCount > 0
                    ? 'bg-[#142421] text-white'
                    : 'text-slate-500'
            "
          >
            {{ day.date }}
          </div>

          <!-- Trip count badge -->
          <span
            class="tabular-nums text-xs font-bold leading-none"
            :class="
              day.tripCount > 0 ? 'text-[#7fdcc8]' : 'text-transparent'
            "
            :aria-hidden="day.tripCount === 0"
          >
            {{ day.tripCount > 0 ? day.tripCount : '\u00a0' }}
          </span>
        </button>
      </div>
    </div>

    <!-- Divider -->
    <div class="mx-4 border-t border-[#7fdcc8]/10 sm:mx-5" />

    <!-- Day's timeline -->
    <div class="px-4 py-4 sm:px-5">
      <!-- Loading skeleton (timeline-ish) -->
      <div v-if="loading" class="space-y-3">
        <div
          v-for="n in 3"
          :key="n"
          class="flex items-stretch gap-3"
        >
          <div class="flex w-14 shrink-0 flex-col items-center pt-4">
            <div class="h-4 w-10 animate-pulse rounded bg-[#7fdcc8]/15" />
            <div class="mt-2 h-3 w-3 animate-pulse rounded-full bg-[#7fdcc8]/10" />
            <div class="mt-2 flex min-h-[24px] w-full flex-1 justify-center">
              <div class="w-px flex-1 animate-pulse bg-[#7fdcc8]/8" />
            </div>
          </div>
          <div class="mb-3 min-h-[100px] flex-1 animate-pulse rounded-2xl bg-[#0a1c1a]/60 ring-1 ring-[#7fdcc8]/8" />
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="tripsForDay.length === 0"
        class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-[#7fdcc8]/18 bg-[#070f0d]/40 px-4 py-10 text-center"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          class="h-14 w-14 text-[#7fdcc8]/30"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
          />
        </svg>
        <p class="text-base font-semibold text-[#7fdcc8]/55">
          {{ t('driver_home.calendar_no_trips_day') }}
        </p>
      </div>

      <!-- Timeline spine -->
      <div v-else class="flex flex-col">
        <div
          v-for="(trip, i) in tripsForDay"
          :key="trip.id"
          class="flex items-stretch gap-0"
        >
          <!-- Left column: time + dot + connector -->
          <div class="flex h-full w-14 shrink-0 flex-col items-center pt-4">
            <span class="shrink-0 text-sm font-extrabold tabular-nums leading-none text-white/90">
              {{ formatTripTime(trip) }}
            </span>
            <div
              class="mt-2 h-3 w-3 shrink-0 rounded-full ring-2 ring-[#0f1816]"
              :class="statusDotClass(trip)"
              aria-hidden="true"
            />
            <div
              v-if="i < tripsForDay.length - 1"
              class="mt-1 flex min-h-[20px] w-full flex-1 justify-center"
            >
              <div class="w-px flex-1 bg-[#7fdcc8]/15" />
            </div>
          </div>

          <!-- Card -->
          <RouterLink
            :to="`/driver/trips/${trip.id}`"
            class="mb-3 ml-3 min-w-0 flex-1 overflow-hidden rounded-2xl bg-[#0a1c1a] ring-1 ring-[#7fdcc8]/12 transition active:scale-[0.99]"
            :class="
              tripVariant(trip) === 'blue'
                ? 'hover:ring-sky-400/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-400/35'
                : 'hover:ring-teal-400/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-400/35'
            "
          >
            <!-- Top row -->
            <div class="flex flex-wrap items-center gap-2 border-b border-[#7fdcc8]/10 px-4 py-3">
              <span
                class="shrink-0 rounded-md bg-white/10 px-2 py-0.5 text-sm font-extrabold uppercase tracking-wide text-white"
              >
                {{ tripTypeBadgeText(trip) }}
              </span>
              <span class="min-w-0 flex-1 truncate text-sm font-semibold text-white/65">
                {{ tripServiceFullName(trip) }}
              </span>
              <span
                class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold"
                :class="statusChipClass(trip)"
              >
                {{ statusLabelForTrip(trip) }}
              </span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-4 w-4 shrink-0 text-[#7fdcc8]/35 sm:hidden"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>

            <!-- Route -->
            <div class="space-y-2 px-4 py-3">
              <div class="flex items-start gap-2.5">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
                <p class="line-clamp-2 text-base font-bold leading-snug text-white">
                  {{ tripOrigin(trip) }}
                </p>
              </div>
              <div class="flex items-start gap-2.5">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-sky-400" aria-hidden="true" />
                <p class="line-clamp-2 text-base font-medium leading-snug text-[#7fdcc8]/65">
                  {{ tripDestination(trip) }}
                </p>
              </div>
            </div>

            <!-- Footer -->
            <div
              class="flex flex-wrap items-center gap-3 border-t border-[#7fdcc8]/10 px-4 py-2.5 text-sm tabular-nums text-slate-500"
            >
              <span class="font-semibold">{{ tripRefLabel(trip) }}</span>
              <span v-if="formatTripArriveTime(trip)">
                → {{ formatTripArriveTime(trip) }}
              </span>
            </div>
          </RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  tripDestination,
  tripOrigin,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t, locale } = useI18n()

const selectedIso = ref('')

const DAY_ABBR_VI = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
const DAY_ABBR_EN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function hhmm(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  return d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit', hour12: false })
}

function formatTripTime(trip) {
  return hhmm(trip?.depart_at || trip?.dispatch_request?.depart_at) || '—'
}

function formatTripArriveTime(trip) {
  return hhmm(trip?.arrive_by || trip?.dispatch_request?.arrive_by)
}

function tripStatusNorm(trip) {
  return String(trip?.status ?? '').trim().toLowerCase()
}

function statusDotClass(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'in_progress') return 'bg-sky-400'
  if (s === 'driver_confirmed') return 'bg-amber-400'
  if (s === 'completed') return 'bg-emerald-400'
  if (s === 'cancelled') return 'bg-slate-500'
  return 'bg-[#7fdcc8]'
}

function statusChipClass(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'in_progress') return 'bg-sky-500/15 text-sky-200 ring-1 ring-sky-400/30'
  if (s === 'driver_confirmed') return 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30'
  if (s === 'completed') return 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30'
  if (s === 'cancelled') return 'bg-slate-600/25 text-slate-300 ring-1 ring-slate-500/35'
  return 'bg-[#7fdcc8]/12 text-[#7fdcc8] ring-1 ring-[#7fdcc8]/35'
}

function statusLabelForTrip(trip) {
  const s = tripStatusNorm(trip)
  if (s === 'in_progress') return t('driver_home.calendar_status_running')
  if (s === 'driver_confirmed') return t('driver_home.calendar_status_confirmed')
  if (s === 'completed') return t('driver_home.calendar_status_done')
  if (s === 'cancelled') return t('driver_home.calendar_status_cancelled')
  return t('driver_home.calendar_status_waiting')
}

function tripServiceFullName(trip) {
  const tt = trip?.dispatch_request?.trip_type
  if (tt === 'door_to_door') return t('driver_home.svc_name_d2d')
  if (tt === 'point_to_point') return t('driver_home.svc_name_p2p')
  if (tt === 'cargo') return t('driver_home.svc_name_cargo')
  if (tt === 'business') return t('driver_home.svc_name_business')
  return t('driver_home.calendar_svc_other')
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

const tripsForDay = computed(() => {
  const iso = selectedIso.value
  if (!iso) return []
  return props.rawTrips
    .filter((trip) => trip.depart_at && trip.depart_at.slice(0, 10) === iso)
    .slice()
    .sort(
      (a, b) => (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0),
    )
})

function tripVariant(trip) {
  const tt = trip.dispatch_request?.trip_type
  if (tt === 'business' || tt === 'point_to_point') return 'blue'
  return 'teal'
}

function tripRefLabel(trip) {
  const code = trip.trip_code
  if (code != null && String(code).trim() !== '') return `#${String(code).trim()}`
  return `#${trip.id}`
}
</script>
