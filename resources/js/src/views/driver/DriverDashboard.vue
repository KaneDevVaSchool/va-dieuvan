<template>
  <div
    class="min-h-full w-full overflow-x-hidden bg-[#020B0B] pb-[calc(7rem+env(safe-area-inset-bottom))] text-[#eaf8f5]"
  >
    <div
      class="relative isolate w-full overflow-hidden rounded-b-[1.75rem] shadow-[0_10px_40px_-8px_rgba(34,211,238,0.18)] ring-1 ring-[#7fdcc8]/10 -mt-[env(safe-area-inset-top,0px)] pt-[env(safe-area-inset-top,0px)]"
    >
      <div
        class="pointer-events-none absolute inset-x-0 top-0 z-0 h-[env(safe-area-inset-top,0px)] bg-[#020B0B]"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute inset-0 bg-gradient-to-b from-[#020B0B] to-[#031818]"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -top-10 right-0 h-44 w-44 rounded-full bg-[#5eead4]/[0.07] blur-3xl"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -bottom-28 left-1/2 h-52 w-[min(130vw,26rem)] -translate-x-1/2 rounded-[100%] bg-[#7fdcc8]/[0.11] blur-[52px]"
        aria-hidden="true"
      />

      <div class="relative z-[1]">
        <DriverHeader
          :user="user"
          :avatarUrl="avatarUrl"
          :initials="initials"
        />
      </div>
    </div>

    <div class="mx-auto w-full min-w-0 max-w-full px-3 pt-4 sm:px-4">
      <div class="min-w-0 space-y-4">
        <DriverIosPushCard />

        <TripStatsCard :stats="monthlyTripStats" :loading="statsLoadingDisplay" />

        <p
          v-if="errorMsg"
          class="rounded-2xl border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-600/30"
        >
          {{ errorMsg }}
        </p>

        <UpcomingTripBanner
          v-if="upcomingBannerTrip"
          :trip-id="upcomingBannerTrip.id"
          :depart-at="upcomingDepartIso"
        />

        <PendingConfirmationBanner
          v-if="myDriverId != null && (loading || pendingTrips.length)"
          :trips="pendingTrips"
          :loading="loading"
          @updated="refreshTrips"
        />

        <div class="flex items-end justify-between gap-2 pt-1">
          <h2 class="text-sm font-bold uppercase tracking-wide text-[#64748b]">
            {{ t('driver_home.today_trips_section') }}
          </h2>
          <RouterLink
            to="/driver/schedule"
            class="shrink-0 text-base font-bold text-[#4ade80] transition hover:text-[#7fdcc8]"
          >
            {{ t('driver_home.view_schedule') }} →
          </RouterLink>
        </div>

        <div v-if="loading && todayTripsSorted.length === 0" class="space-y-3">
          <div
            v-for="n in 3"
            :key="n"
            class="animate-pulse rounded-2xl border border-white/5 bg-[#0f1816] p-5"
          >
            <div class="h-5 w-40 rounded bg-white/10" />
            <div class="mt-4 h-8 w-24 rounded bg-white/10" />
            <div class="mt-3 h-4 w-full rounded bg-white/[0.06]" />
            <div class="mt-2 h-4 w-4/5 rounded bg-white/[0.05]" />
          </div>
        </div>

        <DriverTodayEmptyState v-else-if="!loading && todayTripsSorted.length === 0" />

        <ul v-else class="space-y-4 pb-2">
          <li v-for="trip in todayTripsSorted" :key="trip.id">
            <DriverTripCard :trip="trip" @start="onStartTrip" />
          </li>
        </ul>
      </div>

      <div
        class="pointer-events-none fixed bottom-[calc(5rem+env(safe-area-inset-bottom))] right-4 z-30 flex max-w-[100vw] flex-col items-end gap-3 pr-[env(safe-area-inset-right)]"
      >
        <a
          v-if="dispatcherPhone"
          :href="`tel:${dispatcherPhone}`"
          class="pointer-events-auto flex min-h-[48px] min-w-[44px] items-center gap-2 rounded-2xl bg-[#9A0036] px-4 text-white shadow-xl shadow-black/40 ring-1 ring-white/10 transition hover:bg-[#850030] active:scale-[0.98]"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0" aria-hidden="true">
            <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 16.352V17.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-semibold">{{ t('driver_home.float_call') }}</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getDriverSummary } from '../../api/driver'
import { listTripsAll, updateTripStatus } from '../../api/trips'
import { useDriverWebPushBoot } from '../../composables/useDriverWebPushBoot'
import { useTripHistory } from '../../composables/useTripHistory'
import { useAuthStore } from '../../store'
import TripStatsCard from '../../components/trips/TripStatsCard.vue'
import DriverHeader from '../../components/driver/DriverHeader.vue'
import PendingConfirmationBanner from '../../components/driver/PendingConfirmationBanner.vue'
import UpcomingTripBanner from '../../components/driver/UpcomingTripBanner.vue'
import DriverTripCard from '../../components/driver/DriverTripCard.vue'
import DriverTodayEmptyState from '../../components/driver/DriverTodayEmptyState.vue'
import DriverIosPushCard from '../../components/driver/DriverIosPushCard.vue'

const { t } = useI18n()
const auth = useAuthStore()
const { bootDriverOutboundNotifications } = useDriverWebPushBoot()

const loading = ref(true)
const errorMsg = ref('')
const rawListItems = ref([])
const myDriverId = ref(null)
const startBusy = ref(false)

const {
  stats: monthlyTripStats,
  isLoading: monthlyTripHistoryLoading,
  fetch: fetchDriverTripHistorySlice,
} = useTripHistory()

const user = computed(() => auth.user)
const avatarUrl = computed(() => user.value?.avatar_url || null)
const initials = computed(() => {
  const n = (user.value?.name || '?').trim()
  const parts = n.split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
})

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const pendingStatuses = new Set(['assigned', 'driver_confirmed', 'pending', 'approved'])

function tripStatusNorm(x) {
  return String(x?.status ?? '').trim().toLowerCase()
}

function tripDepartYmd(trip) {
  if (trip.depart_date && typeof trip.depart_date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(trip.depart_date)) {
    return trip.depart_date.slice(0, 10)
  }
  if (trip.depart_at) {
    return ymd(new Date(trip.depart_at))
  }
  return null
}

function sortTier(status) {
  const s = String(status ?? '').trim().toLowerCase()
  if (s === 'in_progress') return 0
  if (['driver_confirmed', 'approved'].includes(s)) return 1
  if (['pending', 'assigned'].includes(s)) return 2
  if (s === 'completed') return 3
  if (s === 'cancelled') return 4
  return 5
}

function sortTodayTrips(list) {
  return [...list].sort((a, b) => {
    const ta = sortTier(a.status)
    const tb = sortTier(b.status)
    if (ta !== tb) return ta - tb
    const da = new Date(a.depart_at).getTime() || 0
    const db = new Date(b.depart_at).getTime() || 0
    if (ta >= 3) return db - da
    return da - db
  })
}

const rawTrips = computed(() => {
  const id = myDriverId.value
  if (id == null) return []
  return rawListItems.value.filter(
    (x) => x.driver_id != null && Number(x.driver_id) === Number(id),
  )
})

const pendingTrips = computed(() => {
  return rawTrips.value
    .filter((x) => pendingStatuses.has(tripStatusNorm(x)))
    .slice()
    .sort((a, b) => (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0))
})

const todayYmd = computed(() => ymd(new Date()))

const todayTripsSorted = computed(() => {
  const day = todayYmd.value
  const list = rawTrips.value.filter((x) => tripDepartYmd(x) === day)
  return sortTodayTrips(list)
})

const upcomingBannerTrip = computed(() => {
  const now = Date.now()
  const limit = now + 120 * 60 * 1000
  const eligible = rawTrips.value.filter((x) => {
    const s = tripStatusNorm(x)
    if (!['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) return false
    const dep = x.depart_at ? new Date(x.depart_at).getTime() : NaN
    if (!Number.isFinite(dep) || dep < now || dep > limit) return false
    return true
  })
  eligible.sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at))
  return eligible[0] ?? null
})

const upcomingDepartIso = computed(() => {
  const t0 = upcomingBannerTrip.value?.depart_at
  if (!t0) return ''
  const d = new Date(t0)
  return Number.isNaN(d.getTime()) ? '' : d.toISOString()
})

const dispatcherPhone = import.meta.env.VITE_DISPATCHER_PHONE || null

const statsLoadingDisplay = computed(
  () => monthlyTripHistoryLoading.value && monthlyTripStats.value == null,
)

function driverTripHistoryParamsForStats() {
  const d = ymd(new Date())
  return { date_from: d, date_to: d }
}

async function fetchMonthlyDriverStats() {
  try {
    await fetchDriverTripHistorySlice(driverTripHistoryParamsForStats(), false)
  } catch {
  }
}

async function fetchData(showLoader = true) {
  if (showLoader) loading.value = true
  errorMsg.value = ''
  const now = new Date()
  const past = new Date(now)
  past.setDate(past.getDate() - 30)
  const horizon = new Date(now)
  horizon.setDate(horizon.getDate() + 21)
  try {
    const [sum, listRes] = await Promise.all([
      getDriverSummary(),
      listTripsAll({ from: ymd(past), to: ymd(horizon), per_page: 100 }),
    ])
    myDriverId.value = sum?.driver?.id ?? null
    rawListItems.value = listRes?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    myDriverId.value = null
    rawListItems.value = []
  } finally {
    if (showLoader) loading.value = false
  }
  await fetchMonthlyDriverStats()
}

async function refreshTrips() {
  await fetchData(false)
}

async function onStartTrip(tripId) {
  if (tripId == null || startBusy.value) return
  startBusy.value = true
  try {
    await updateTripStatus(tripId, { status: 'in_progress' })
    await fetchData(false)
  } catch {
    errorMsg.value = t('driver_trip_detail.status_err')
  } finally {
    startBusy.value = false
  }
}

let knownPendingIds = null

async function pushNewTripNotif(count) {
  if (!('Notification' in window) || Notification.permission !== 'granted') return
  const title = t('driver_home.notif_new_trip_title')
  const options = {
    body: t('driver_home.notif_new_trip_body', { n: count }),
    icon: '/icons/pwa-192.png',
    badge: '/icons/pwa-192.png',
    tag: 'new-trip',
    renotify: true,
  }
  try {
    if ('serviceWorker' in navigator) {
      const reg = await navigator.serviceWorker.ready
      await reg.showNotification(title, options)
      return
    }
  } catch {
    /* fall through */
  }
  try {
    new Notification(title, options)
  } catch {
    /* ignore */
  }
}

watch(
  () => pendingTrips.value.map((x) => x.id).join(','),
  (newKey) => {
    const ids = new Set(newKey ? newKey.split(',').map(Number) : [])
    if (knownPendingIds === null) {
      knownPendingIds = ids
      return
    }
    const added = [...ids].filter((id) => !knownPendingIds.has(id))
    if (added.length > 0) void pushNewTripNotif(added.length)
    knownPendingIds = ids
  },
)

onMounted(async () => {
  await bootDriverOutboundNotifications()
  await fetchData(true)
})
</script>
