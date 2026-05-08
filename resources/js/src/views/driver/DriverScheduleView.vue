<template>
  <div
    class="min-h-full w-full max-w-[430px] overflow-x-hidden bg-driver-bg pb-2 text-driver-ink sm:max-w-none"
    :style="{ '--accent': '#7fdcc8' }"
    @touchstart.passive="onTouchStart"
    @touchmove.passive="onTouchMove"
    @touchend.passive="onTouchEnd"
  >
    <!-- Pull-to-refresh indicator -->
    <Transition name="ptr">
      <div
        v-if="ptrVisible"
        class="flex items-center justify-center gap-2 overflow-hidden py-2 text-xs font-semibold text-[#7fdcc8]"
        :style="{ height: `${Math.min(ptrDelta, 56)}px` }"
        aria-live="polite"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
          class="h-4 w-4 transition-transform"
          :class="ptrReleasing ? 'animate-spin' : ''"
          :style="!ptrReleasing ? { transform: `rotate(${Math.min(ptrDelta / 56 * 180, 180)}deg)` } : {}"
          aria-hidden="true"
        >
          <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
        </svg>
        <span>{{ ptrReleasing ? t('trip_history_page.releasing') : t('trip_history_page.pull_to_refresh') }}</span>
      </div>
    </Transition>

    <!-- Header -->
    <header
      class="sticky top-0 z-[25] flex items-center justify-between gap-2 border-b border-[rgba(255,255,255,0.06)] bg-driver-bg/90 px-3 py-3 backdrop-blur-md [-webkit-backdrop-filter:blur(12px)]"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <div class="flex min-w-0 flex-1 items-center gap-2">
        <RouterLink
          to="/driver"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#7fdcc8] transition hover:bg-white/5 active:scale-95"
          :title="t('trip_history_page.back')"
        >
          <span class="sr-only">{{ t('trip_history_page.back') }}</span>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
          </svg>
        </RouterLink>
        <h1 class="min-w-0 truncate text-2xl font-bold tracking-tight sm:text-3xl">
          {{ t('trip_history_page.title') }}
        </h1>
      </div>
      <button
        type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#7fdcc8] ring-1 ring-[rgba(255,255,255,0.08)] transition hover:bg-white/5"
        :aria-pressed="searchOpen ? 'true' : 'false'"
        :title="t('trip_history_page.search')"
        @click="searchOpen = !searchOpen"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
      </button>
    </header>

    <div class="space-y-4 px-3 pt-4 pb-8">
      <!-- Search box -->
      <div v-if="searchOpen" class="rounded-2xl border border-[rgba(255,255,255,0.08)] bg-driver-card px-3 py-2">
        <input
          v-model.trim="searchQ"
          type="search"
          autocomplete="off"
          class="w-full rounded-lg bg-transparent px-2 py-3 text-base text-driver-ink placeholder:text-driver-muted/60 focus:outline-none focus:ring-1 focus:ring-[#7fdcc8]/50"
          :placeholder="t('trip_history_page.search_placeholder')"
        />
      </div>

      <!-- Stats header -->
      <TripStatsCard :stats="stats" :loading="statsLoading" />

      <!-- Week calendar -->
      <WeekCalendar v-model="selectedDate" :trip-dates="weekTripDateKeys" />

      <!-- Error -->
      <p
        v-if="displayErrorMsg"
        class="rounded-xl border border-amber-700/40 bg-amber-950/30 px-3 py-2 text-sm text-amber-100"
      >
        {{ displayErrorMsg }}
      </p>

      <!-- Skeleton loading -->
      <div v-if="isLoading && filteredTrips.length === 0" class="space-y-4 pt-2">
        <div v-for="n in 3" :key="n" class="animate-pulse rounded-2xl border border-white/5 bg-driver-card p-5">
          <div class="flex gap-3">
            <div class="h-14 w-14 rounded-xl bg-[#7fdcc8]/10" />
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-32 rounded bg-white/10" />
              <div class="h-4 w-full rounded bg-white/[0.07]" />
              <div class="h-4 w-4/5 rounded bg-white/[0.05]" />
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <TripEmptyState v-else-if="!isLoading && filteredTrips.length === 0" @reset="onResetFilters" />

      <template v-else>
        <!-- Day section header -->
        <p class="pt-2 text-xl font-semibold text-driver-ink sm:text-2xl">
          {{ dayGroupTitle }}
          <span v-if="filteredTrips.length" class="tabular-nums text-lg text-driver-muted sm:text-xl"> ({{ filteredTrips.length }}) </span>
        </p>

        <!-- Trip cards -->
        <ul class="mt-3 space-y-4">
          <li v-for="trip in filteredTrips" :key="trip.id">
            <TripCard :trip="trip" comfortable />
          </li>
        </ul>

        <!-- Infinite scroll sentinel -->
        <div ref="sentinelEl" class="h-px w-full shrink-0" aria-hidden="true" />

        <!-- Load more fallback -->
        <button
          v-if="showLoadMoreFallback"
          type="button"
          class="mt-4 flex min-h-[52px] w-full items-center justify-center rounded-2xl border border-[rgba(255,255,255,0.1)] bg-driver-card py-3.5 text-base font-semibold text-[#7fdcc8] transition hover:bg-driver-surface disabled:opacity-50"
          :disabled="isLoading"
          @click="() => loadMore(fetchParams)"
        >
          {{ isLoading ? t('trip_history_page.loading') : t('trip_history_page.load_more') }}
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTripHistory } from '../../composables/useTripHistory'
import TripStatsCard from '../../components/trips/TripStatsCard.vue'
import WeekCalendar from '../../components/trips/WeekCalendar.vue'
import TripCard from '../../components/trips/TripCard.vue'
import TripEmptyState from '../../components/trips/TripEmptyState.vue'

const { t } = useI18n()

const { trips, stats, isLoading, error, hasMore, fetch: fetchTrips, loadMore } = useTripHistory()

const selectedDate = ref(new Date())
const searchOpen = ref(false)
const searchQ = ref('')
const sentinelEl = ref(null)

// ── Pull-to-refresh state ──────────────────────────────────────────────
const ptrStartY = ref(null)
const ptrDelta = ref(0)
const ptrReleasing = ref(false)
const PTR_THRESHOLD = 60

const ptrVisible = computed(() => ptrDelta.value > 4 || ptrReleasing.value)

function onTouchStart(ev) {
  const scrollEl = typeof document !== 'undefined'
    ? (document.getElementById('app-main-scroll') || document.documentElement)
    : null
  const scrollTop = scrollEl ? scrollEl.scrollTop : window.scrollY
  if (scrollTop > 2) return
  ptrStartY.value = ev.touches[0]?.clientY ?? null
  ptrDelta.value = 0
}

function onTouchMove(ev) {
  if (ptrStartY.value == null || isLoading.value) return
  const dy = (ev.touches[0]?.clientY ?? 0) - ptrStartY.value
  if (dy <= 0) {
    ptrDelta.value = 0
    return
  }
  ptrDelta.value = dy
}

function onTouchEnd() {
  if (ptrStartY.value == null) return
  if (ptrDelta.value >= PTR_THRESHOLD && !isLoading.value) {
    ptrReleasing.value = true
    ptrDelta.value = PTR_THRESHOLD
    void reload().finally(() => {
      ptrReleasing.value = false
      ptrDelta.value = 0
    })
  } else {
    ptrDelta.value = 0
  }
  ptrStartY.value = null
}

// ── Helpers ───────────────────────────────────────────────────────────

/** @type {IntersectionObserver | null} */
let listObserver = null

function ymd(d) {
  const x = d instanceof Date ? d : new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function startOfWeekMonday(d) {
  const x = new Date(d)
  x.setHours(12, 0, 0, 0)
  const dow = x.getDay()
  const diff = dow === 0 ? -6 : 1 - dow
  x.setDate(x.getDate() + diff)
  x.setHours(0, 0, 0, 0)
  return x
}

function endOfWeekSunday(d) {
  const start = startOfWeekMonday(d)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  return end
}

// ── Computed ──────────────────────────────────────────────────────────

const selectedYmd = computed(() => ymd(selectedDate.value))

const fetchParams = computed(() => {
  const weekFrom = ymd(startOfWeekMonday(selectedDate.value))
  const weekTo = ymd(endOfWeekSunday(selectedDate.value))
  return {
    date_from: weekFrom,
    date_to: weekTo,
  }
})

const weekTripDateKeys = computed(() => {
  const fromY = ymd(startOfWeekMonday(selectedDate.value))
  const toY = ymd(endOfWeekSunday(selectedDate.value))
  const set = new Set()
  for (const tr of trips.value) {
    const key = typeof tr.depart_date === 'string' ? tr.depart_date.slice(0, 10) : tr.depart_date
    if (!key || key < fromY || key > toY) continue
    set.add(key)
  }
  return [...set]
})

const filteredTrips = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  const day = selectedYmd.value
  let list = trips.value.filter((x) => {
    const raw = x.depart_date || x.pickup_date || ''
    const tripDay =
      typeof raw === 'string' && /^\d{4}-\d{2}-\d{2}/.test(raw) ? raw.slice(0, 10) : raw
    return tripDay === day
  })
  if (q) {
    list = list.filter((x) => {
      const blob = [
        x.pickup_location,
        x.dropoff_location,
        x.origin,
        x.destination,
        x.trip_number,
        String(x.id),
        x.type,
        x.trip_type_label,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
      return blob.includes(q)
    })
  }
  return list.slice().sort((a, b) => {
    const cmp = String(a.pickup_time || '').localeCompare(String(b.pickup_time || ''))
    if (cmp !== 0) return cmp
    return (a.id || 0) - (b.id || 0)
  })
})

const dayGroupTitle = computed(() =>
  selectedDate.value.toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  }),
)

const displayErrorMsg = computed(() => {
  if (error.value === 'fetch_failed') return t('trip_history_page.load_error')
  return ''
})

const statsLoading = computed(() => isLoading.value && stats.value == null)

const observerAvailable = computed(() => typeof IntersectionObserver !== 'undefined')

const showLoadMoreFallback = computed(
  () => hasMore.value && !observerAvailable.value && filteredTrips.value.length > 0,
)

// ── Methods ───────────────────────────────────────────────────────────

function teardownObserver() {
  listObserver?.disconnect()
  listObserver = null
}

function setupIntersectionObserver() {
  teardownObserver()
  if (!observerAvailable.value || !hasMore.value) return
  const el = sentinelEl.value
  const rootEl = typeof document !== 'undefined' ? document.getElementById('app-main-scroll') : null
  if (!el || filteredTrips.value.length === 0) return
  listObserver = new IntersectionObserver(
    (entries) => {
      const hit = entries.some((e) => e.isIntersecting)
      if (hit && hasMore.value && !isLoading.value) {
        void loadMore(fetchParams.value)
      }
    },
    { root: rootEl || null, rootMargin: '120px', threshold: 0 },
  )
  listObserver.observe(el)
}

async function reload() {
  await fetchTrips(fetchParams.value, false)
  await nextTick()
  setupIntersectionObserver()
}

function onResetFilters() {
  searchQ.value = ''
  selectedDate.value = new Date()
  searchOpen.value = false
}

// ── Watchers ──────────────────────────────────────────────────────────

watch(selectedDate, () => {
  void reload()
})

watch([hasMore, () => sentinelEl.value, () => filteredTrips.value.length], () => {
  void nextTick(() => setupIntersectionObserver())
})

// ── Lifecycle ─────────────────────────────────────────────────────────

onMounted(() => {
  void reload()
})

onBeforeUnmount(() => {
  teardownObserver()
})
</script>
