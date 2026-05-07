<template>
  <div
    class="min-h-full w-full max-w-[390px] overflow-x-hidden bg-[#09180f] pb-2 text-white sm:max-w-none"
    :style="{ '--accent': '#7fdcc8' }"
  >
    <header
      class="sticky top-0 z-[25] flex items-center justify-between gap-2 border-b border-[rgba(255,255,255,0.06)] bg-[#09180f]/90 px-3 py-3 backdrop-blur-md [-webkit-backdrop-filter:blur(12px)]"
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
        <h1 class="min-w-0 truncate text-lg font-bold tracking-tight">
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

    <div class="space-y-3 px-3 pt-3 pb-6">
      <div v-if="searchOpen" class="rounded-2xl border border-[rgba(255,255,255,0.08)] bg-[#0f2318] px-3 py-2">
        <input
          v-model.trim="searchQ"
          type="search"
          autocomplete="off"
          class="w-full rounded-lg bg-transparent px-2 py-2 text-sm text-white placeholder:text-[#64748b] focus:outline-none focus:ring-1 focus:ring-[#7fdcc8]/50"
          :placeholder="t('trip_history_page.search_placeholder')"
        />
      </div>

      <TripStatsCard :stats="stats" :loading="statsLoading" />

      <TripFilterPills v-model="filterStatus" />

      <WeekCalendar v-model="selectedDate" :trip-dates="weekTripDateKeys" />

      <p
        v-if="displayErrorMsg"
        class="rounded-xl border border-amber-700/40 bg-amber-950/30 px-3 py-2 text-sm text-amber-100"
      >
        {{ displayErrorMsg }}
      </p>

      <div v-if="isLoading && filteredTrips.length === 0" class="space-y-3 pt-2">
        <div v-for="n in 3" :key="n" class="animate-pulse rounded-2xl border border-white/5 bg-[#0f2318] p-4">
          <div class="flex gap-3">
            <div class="h-12 w-12 rounded-lg bg-[#7fdcc8]/10" />
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-3 w-28 rounded bg-white/10" />
              <div class="h-3 w-full rounded bg-white/[0.07]" />
              <div class="h-3 w-4/5 rounded bg-white/[0.05]" />
            </div>
          </div>
        </div>
      </div>

      <TripEmptyState v-else-if="!isLoading && filteredTrips.length === 0" @reset="onResetFilters" />

      <template v-else>
        <p class="pt-2 text-sm font-semibold text-white">
          {{ dayGroupTitle }}
          <span v-if="filteredTrips.length" class="tabular-nums text-[#94a3b8]"> ({{ filteredTrips.length }}) </span>
        </p>
        <ul class="mt-2 space-y-3">
          <li v-for="trip in filteredTrips" :key="trip.id">
            <TripCard :trip="trip" />
          </li>
        </ul>

        <div ref="sentinelEl" class="h-px w-full shrink-0" aria-hidden="true" />

        <button
          v-if="showLoadMoreFallback"
          type="button"
          class="mt-4 flex w-full min-h-[48px] items-center justify-center rounded-2xl border border-[rgba(255,255,255,0.1)] bg-[#0f2318] py-3 text-sm font-semibold text-[#7fdcc8] transition hover:bg-[#0f2318]/80 disabled:opacity-50"
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
import TripFilterPills from '../../components/trips/TripFilterPills.vue'
import WeekCalendar from '../../components/trips/WeekCalendar.vue'
import TripCard from '../../components/trips/TripCard.vue'
import TripEmptyState from '../../components/trips/TripEmptyState.vue'

const { t } = useI18n()

const { trips, stats, isLoading, error, hasMore, fetch: fetchTrips, loadMore } = useTripHistory()

const selectedDate = ref(new Date())
const filterStatus = ref('all')
const searchOpen = ref(false)
const searchQ = ref('')
const sentinelEl = ref(null)

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

function endOfWeekSundayDay(d) {
  const start = startOfWeekMonday(d)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  return end
}

const selectedYmd = computed(() => ymd(selectedDate.value))

const fetchParams = computed(() => {
  const from = ymd(startOfWeekMonday(selectedDate.value))
  const to = ymd(endOfWeekSundayDay(selectedDate.value))
  const p = { date_from: from, date_to: to }
  if (filterStatus.value !== 'all') {
    p.status = filterStatus.value
  }
  return p
})

const weekTripDateKeys = computed(() => {
  const fromY = ymd(startOfWeekMonday(selectedDate.value))
  const toY = ymd(endOfWeekSundayDay(selectedDate.value))
  const set = new Set()
  for (const tr of trips.value) {
    const key = tr.depart_date
    if (!key || key < fromY || key > toY) continue
    set.add(key)
  }
  return [...set]
})

const filteredTrips = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  const day = selectedYmd.value
  let list = trips.value.filter((x) => (x.depart_date || '') === day)
  if (q) {
    list = list.filter((x) => {
      const blob = [
        x.pickup_location,
        x.dropoff_location,
        x.trip_number,
        String(x.id),
        x.type,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
      return blob.includes(q)
    })
  }
  return list.slice().sort((a, b) => {
    const cmp = String(b.pickup_time || '').localeCompare(String(a.pickup_time || ''))
    if (cmp !== 0) return cmp
    return (b.id || 0) - (a.id || 0)
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
  filterStatus.value = 'all'
  searchQ.value = ''
  selectedDate.value = new Date()
  searchOpen.value = false
}

watch([selectedDate, filterStatus], () => {
  void reload()
})

watch([hasMore, () => sentinelEl.value, () => filteredTrips.length], () => {
  void nextTick(() => setupIntersectionObserver())
})

onMounted(() => {
  void reload()
})

onBeforeUnmount(() => {
  teardownObserver()
})
</script>
