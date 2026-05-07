<template>
  <div
    class="min-h-screen w-full overflow-x-hidden bg-[#0d1f1c] text-white pb-[calc(7rem+env(safe-area-inset-bottom))] pt-[env(safe-area-inset-top)]"
  >
    <div class="mx-auto w-full min-w-0 max-w-lg px-4 sm:max-w-2xl sm:px-5">
      <DriverHeader
        :user="user"
        :avatarUrl="avatarUrl"
        :initials="initials"
      />

      <div class="min-w-0 space-y-5">
        <p
          v-if="errorMsg"
          class="rounded-2xl border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-600/30"
        >
          {{ errorMsg }}
        </p>

        <PendingConfirmationBanner
          v-if="myDriverId != null && (loading || pendingTrips.length)"
          :trips="pendingTrips"
          :loading="loading"
          @updated="refreshTrips"
        />

        <DriverStatsGrid :stats="stats" :loading="loading" />

        <div class="border-t border-[#86c2b5]/15" />

        <DriverWeekCalendar :raw-trips="rawTrips" :loading="loading" />

        <div class="border-t border-[#86c2b5]/15" />

        <DriverAnalyticsSection :raw-trips="rawTrips" :loading="loading" />
      </div>

      <!-- Sticky-ish FAB zone + safe area above tab bar -->
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
import { useI18n } from 'vue-i18n'
import { getDriverSummary } from '../../api/driver'
import { listTripsAll } from '../../api/trips'
import { useAuthStore } from '../../store'
import { useNotificationStore } from '../../store/notificationCenter'
import DriverHeader from '../../components/driver/DriverHeader.vue'
import PendingConfirmationBanner from '../../components/driver/PendingConfirmationBanner.vue'
import DriverStatsGrid from '../../components/driver/DriverStatsGrid.vue'
import DriverWeekCalendar from '../../components/driver/DriverWeekCalendar.vue'
import DriverAnalyticsSection from '../../components/driver/DriverAnalyticsSection.vue'

const { t } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()

const loading = ref(true)
const errorMsg = ref('')
/** Mọi chuyến từ API (trước khi lọc theo tài xế đăng nhập) */
const rawListItems = ref([])
/** `drivers.id` của user hiện tại (từ GET /driver/summary) */
const myDriverId = ref(null)

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

/** Chỉ chuyến đã gán cho tài xế đăng nhập (`trip.driver_id` khớp hồ sơ tài xế). */
const rawTrips = computed(() => {
  const id = myDriverId.value
  if (id == null) return []
  return rawListItems.value.filter(
    (x) => x.driver_id != null && Number(x.driver_id) === Number(id),
  )
})

// Trips chờ xác nhận (chưa in_progress); chuyến gấp lên trước
const pendingTrips = computed(() => {
  const list = rawTrips.value.filter((x) => pendingStatuses.has(tripStatusNorm(x)))
  return list
    .slice()
    .sort((a, b) => {
      const ua = a.dispatch_request?.is_urgent ? 1 : 0
      const ub = b.dispatch_request?.is_urgent ? 1 : 0
      if (ub !== ua) return ub - ua
      return (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0)
    })
})

const stats = computed(() => ({
  total: rawTrips.value.length,
  completed: rawTrips.value.filter((x) => tripStatusNorm(x) === 'completed').length,
  inProgress: rawTrips.value.filter((x) => tripStatusNorm(x) === 'in_progress').length,
  pending: pendingTrips.value.length,
  cancelled: rawTrips.value.filter((x) => tripStatusNorm(x) === 'cancelled').length,
}))

const dispatcherPhone = import.meta.env.VITE_DISPATCHER_PHONE || null

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
}

async function refreshTrips() {
  await fetchData(false)
}

watch(
  () => pendingTrips.value.some((x) => x.dispatch_request?.is_urgent),
  (urgent) => {
    if (urgent) void notifStore.refreshBadges()
  },
)

onMounted(() => fetchData(true))
</script>
