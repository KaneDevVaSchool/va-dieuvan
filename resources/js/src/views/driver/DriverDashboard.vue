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
        class="pointer-events-none absolute -top-10 right-0 h-44 w-44 rounded-full bg-[#5eead4]/[0.05] sm:hidden"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -bottom-28 left-1/2 h-52 w-[min(130vw,26rem)] -translate-x-1/2 rounded-[100%] bg-[#7fdcc8]/[0.08] sm:hidden"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -top-10 right-0 hidden h-44 w-44 rounded-full bg-[#5eead4]/[0.07] blur-3xl sm:block"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -bottom-28 left-1/2 hidden h-52 w-[min(130vw,26rem)] -translate-x-1/2 rounded-[100%] bg-[#7fdcc8]/[0.11] blur-[52px] sm:block"
        aria-hidden="true"
      />

      <div class="relative z-[1]">
        <DriverHeader :user="user" :avatarUrl="avatarUrl" :initials="initials" />
      </div>
    </div>

    <div class="mx-auto w-full min-w-0 max-w-full px-3 pt-4 sm:px-4">
      <div class="min-w-0 space-y-4">
        <DriverPendingConfirmationSection
          v-if="dash.myDriverId != null && (dash.loadingInitial || dash.needsConfirmationTrips.length)"
          :trips="dash.needsConfirmationTrips"
          :loading="dash.loadingInitial && dash.rawListItems.length === 0"
        />

        <p
          v-if="dash.errorMsg"
          class="rounded-2xl border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-600/30"
        >
          {{ dash.errorMsg }}
        </p>

        <UpcomingTripBanner
          v-if="dash.upcomingBannerTrip"
          :trip-id="dash.upcomingBannerTrip.id"
          :depart-at="dash.upcomingDepartIso"
        />

        <DriverUpcomingTripsSection
          :trips="dash.upcomingScheduleTrips"
          :list-loading="dash.listLoadingForUi"
          :start-busy-trip-id="dash.startBusyTripId"
          @start-trip="onStartTrip"
        />
      </div>

      <div
        class="pointer-events-none fixed bottom-[calc(5rem+env(safe-area-inset-bottom))] right-4 z-30 flex max-w-[100vw] flex-col items-end gap-3 pr-[env(safe-area-inset-right)]"
      >
        <a
          v-if="dispatcherPhone"
          :href="`tel:${dispatcherPhone}`"
          class="pointer-events-auto flex min-h-[48px] min-w-[44px] items-center gap-2 rounded-2xl bg-[#9A0036] px-4 text-white shadow-xl shadow-black/40 ring-1 ring-white/10 transition hover:bg-[#850030] active:scale-[0.98]"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-5 w-5 shrink-0"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 16.352V17.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-sm font-semibold">{{ t('driver_home.float_call') }}</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDriverVisiblePoll } from '../../composables/useDriverVisiblePoll'
import { useDriverWebPushBoot } from '../../composables/useDriverWebPushBoot'
import { useAuthStore, useDriverDashboardStore } from '../../store/index'
import { dashPerfMounted } from '../../util/devDriverDashboardPerf'
import { playNotificationChime } from '../../util/notificationChime'
import DriverHeader from '../../components/driver/DriverHeader.vue'
import UpcomingTripBanner from '../../components/driver/UpcomingTripBanner.vue'
import DriverPendingConfirmationSection from '../../components/driver/dashboard/DriverPendingConfirmationSection.vue'
import DriverUpcomingTripsSection from '../../components/driver/dashboard/DriverUpcomingTripsSection.vue'

const { t } = useI18n()
const auth = useAuthStore()
const dash = useDriverDashboardStore()
const { bootDriverOutboundNotifications } = useDriverWebPushBoot()

const user = computed(() => auth.user)
const avatarUrl = computed(() => user.value?.avatar_url || null)
const initials = computed(() => {
  const n = (user.value?.name || '?').trim()
  const parts = n.split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
})

const dispatcherPhone = import.meta.env.VITE_DISPATCHER_PHONE || null

const SEEN_PENDING_INIT_KEY = 'va_driver_pending_seen_init'
const SEEN_PENDING_IDS_KEY = 'va_driver_pending_ids_seen'

function loadSeenPendingIds() {
  try {
    if (typeof sessionStorage === 'undefined') return null
    if (sessionStorage.getItem(SEEN_PENDING_INIT_KEY) !== '1') return null
    const raw = sessionStorage.getItem(SEEN_PENDING_IDS_KEY) ?? ''
    return new Set(
      raw.split(',').map((x) => Number.parseInt(x, 10)).filter((n) => !Number.isNaN(n)),
    )
  } catch {
    return null
  }
}

function persistSeenPendingSnapshot(ids) {
  try {
    if (typeof sessionStorage === 'undefined') return
    sessionStorage.setItem(SEEN_PENDING_INIT_KEY, '1')
    sessionStorage.setItem(SEEN_PENDING_IDS_KEY, [...ids].sort((a, b) => a - b).join(','))
  } catch {
    /* ignore */
  }
}

let knownPendingIds = loadSeenPendingIds()

const NOTIF_BODY_MAX = 220

function routeLineForNotif(trip) {
  const o = String(trip.origin ?? trip.pickup_location ?? '').trim()
  const d = String(trip.destination ?? trip.dropoff_location ?? '').trim()
  if (o !== '' && d !== '') return `${o} → ${d}`
  if (o !== '') return o
  if (d !== '') return d
  return `#${trip.id}`
}

function departLabelForNotif(trip) {
  const raw = trip.depart_at ?? trip.depart_date
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' })
}

async function pushNewTripNotif(newTrips) {
  if (!newTrips?.length) return
  if (!('Notification' in window) || Notification.permission !== 'granted') return

  const title = t('driver_home.notif_new_trip_title')
  let body = ''
  if (newTrips.length === 1) {
    const tr = newTrips[0]
    const route = routeLineForNotif(tr)
    const time = departLabelForNotif(tr)
    body = time
      ? t('driver_home.notif_new_trip_detail_one', { route, time })
      : t('driver_home.notif_new_trip_detail_one_no_time', { route })
  } else {
    const head = newTrips.slice(0, 2)
    const lines = []
    for (const tr of head) {
      const route = routeLineForNotif(tr)
      const time = departLabelForNotif(tr)
      lines.push(time ? `${route} (${time})` : route)
    }
    body = lines.join(' · ')
    const extra = newTrips.length - head.length
    if (extra > 0) {
      body = `${body} — ${t('driver_home.notif_new_trip_more', { n: extra })}`
    }
  }
  if (body.length > NOTIF_BODY_MAX) {
    body = `${body.slice(0, NOTIF_BODY_MAX - 1)}…`
  }

  const tag =
    newTrips.length === 1 && newTrips[0]?.id != null ? `new-trip-${newTrips[0].id}` : 'new-trip-batch'

  const options = {
    body,
    icon: '/icons/pwa-192.png',
    badge: '/icons/pwa-192.png',
    tag,
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
  () =>
    dash.needsConfirmationTrips
      .map((x) => x.id)
      .sort((a, b) => Number(a) - Number(b))
      .join(','),
  (newKey) => {
    const ids = new Set(newKey ? newKey.split(',').map(Number).filter((n) => !Number.isNaN(n)) : [])
    if (knownPendingIds === null) {
      knownPendingIds = ids
      persistSeenPendingSnapshot(ids)
      return
    }
    const added = [...ids].filter((id) => !knownPendingIds.has(id))
    if (added.length > 0) {
      const objs = dash.needsConfirmationTrips.filter((x) => added.includes(Number(x.id)))
      void pushNewTripNotif(objs)
      playNotificationChime()
    }
    knownPendingIds = ids
    persistSeenPendingSnapshot(ids)
  },
)

const { start: startDriverVisiblePoll } = useDriverVisiblePoll(() =>
  dash.fetchDashboard({ silent: true, force: false }),
)

async function onStartTrip(tripId) {
  try {
    await dash.startTripOptimistic(tripId)
  } catch {
    /* modal từ store */
  }
}

onMounted(async () => {
  await bootDriverOutboundNotifications()
  dash.hydrateFromCache()
  dashPerfMounted()
  if (dash.rawListItems.length > 0) {
    await dash.fetchDashboard({ silent: true, force: true })
  } else {
    await dash.fetchDashboard({ silent: false, force: true })
  }
  startDriverVisiblePoll()
})
</script>
