<template>
  <section class="rounded-3xl bg-[#0f1816] shadow-xl shadow-black/25">
    <!-- Section header -->
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-2 px-5 pt-5 pb-4">
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
          <div
            class="h-14 w-14 max-w-full animate-pulse rounded-full bg-[#070f0d]/90"
          />
          <div class="h-2 w-2 animate-pulse rounded-full bg-[#7fdcc8]/10" />
        </div>
      </div>

      <div v-else class="grid grid-cols-7 gap-0.5">
        <button
          v-for="day in weekDays"
          :key="day.iso"
          type="button"
          class="flex min-h-[72px] w-full flex-col items-center gap-1.5 rounded-xl py-2 transition active:scale-[0.95]"
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

          <!-- Trip count dot -->
          <span
            class="h-2 w-2 rounded-full transition-colors"
            :class="
              day.tripCount > 0
                ? selectedIso === day.iso
                  ? 'bg-[#7fdcc8]'
                  : 'bg-[#7fdcc8]/50'
                : 'bg-transparent'
            "
            :aria-hidden="true"
          />
        </button>
      </div>
    </div>

    <!-- Divider -->
    <div class="mx-4 border-t border-[#7fdcc8]/10 sm:mx-5" />

    <!-- Day's trip list (vertical) -->
    <div class="px-4 py-4 sm:px-5">
      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-3">
        <div
          v-for="n in 3"
          :key="n"
          class="flex gap-3 rounded-2xl bg-[#0a1c1a]/60 p-4 ring-1 ring-[#7fdcc8]/8"
        >
          <div class="h-14 w-[60px] animate-pulse rounded-xl bg-[#7fdcc8]/10" />
          <div class="flex-1 space-y-2 pt-1">
            <div class="flex gap-2">
              <div class="h-5 w-14 animate-pulse rounded-md bg-[#7fdcc8]/15" />
              <div class="h-5 w-10 animate-pulse rounded-md bg-[#7fdcc8]/8" />
            </div>
            <div class="h-4 w-3/4 animate-pulse rounded bg-[#7fdcc8]/10" />
            <div class="h-4 w-2/4 animate-pulse rounded bg-[#7fdcc8]/8" />
          </div>
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

      <!-- Trip cards (vertical list) -->
      <div v-else class="space-y-3">
        <RouterLink
          v-for="trip in tripsForDay"
          :key="trip.id"
          :to="`/driver/trips/${trip.id}`"
          class="flex items-start gap-4 rounded-2xl bg-[#0a1c1a] p-4 ring-1 transition active:scale-[0.99]"
          :class="
            tripVariant(trip) === 'blue'
              ? 'ring-sky-400/20 hover:ring-sky-400/35'
              : 'ring-teal-400/20 hover:ring-teal-400/35'
          "
        >
          <!-- Time column -->
          <div class="flex shrink-0 flex-col items-center gap-2">
            <div
              class="min-w-[60px] rounded-xl px-3 py-2.5 text-center"
              :class="
                tripVariant(trip) === 'blue'
                  ? 'bg-sky-500/12 ring-1 ring-sky-400/20'
                  : 'bg-teal-500/12 ring-1 ring-teal-400/20'
              "
            >
              <span class="text-2xl font-extrabold tabular-nums leading-none text-white">
                {{ formatTripTime(trip) }}
              </span>
            </div>
            <!-- Arrive time if available -->
            <span v-if="formatTripArriveTime(trip)" class="text-xs font-semibold tabular-nums text-slate-500">
              → {{ formatTripArriveTime(trip) }}
            </span>
          </div>

          <!-- Info column -->
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span
                class="rounded-md bg-white/10 px-2 py-0.5 text-sm font-extrabold uppercase tracking-wide text-white"
              >
                {{ tripServiceTypeCalendarLabel(trip, t) }}
              </span>
              <span
                class="text-sm font-bold tabular-nums"
                :class="
                  tripVariant(trip) === 'blue' ? 'text-sky-300/70' : 'text-teal-300/70'
                "
              >
                {{ tripRefLabel(trip) }}
              </span>
            </div>
            <p class="mt-2 line-clamp-1 text-base font-bold text-white">
              {{ tripOrigin(trip) }}
            </p>
            <p class="mt-0.5 line-clamp-1 text-base font-medium text-[#7fdcc8]/60">
              → {{ tripDestination(trip) }}
            </p>
          </div>

          <!-- Chevron -->
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="mt-1 h-5 w-5 shrink-0 text-[#7fdcc8]/30"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
              clip-rule="evenodd"
            />
          </svg>
        </RouterLink>
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
  tripServiceTypeCalendarLabel,
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
