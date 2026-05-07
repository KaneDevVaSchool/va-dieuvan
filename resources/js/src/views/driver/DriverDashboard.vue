<template>
  <div class="max-w-lg mx-auto w-full space-y-6 pb-28 sm:max-w-2xl">
    <!-- 1 — Gradient header -->
    <DriverHeader
      :user="user"
      :avatarUrl="avatarUrl"
      :initials="initials"
    />

    <!-- 2 — Error banner -->
    <p
      v-if="errorMsg"
      class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
    >
      {{ errorMsg }}
    </p>

    <!-- 2b — Chuyến chờ xác nhận -->
    <PendingConfirmationBanner
      v-if="!loading && pendingTrips.length"
      :trips="pendingTrips"
      @updated="refreshTrips"
    />

    <!-- 3 — Tổng quan & analytics -->
    <DriverStatsGrid :stats="stats" :loading="loading" />
    <DriverAnalyticsSection :raw-trips="rawTrips" :loading="loading" />

    <!-- 4 — Danh sách chuyến sắp tới -->
    <section v-if="!loading && queueTrips.length" class="px-0">
      <div class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          {{ t('driver_home.section_upcoming') }}
        </h2>
        <RouterLink
          to="/driver/schedule"
          class="text-sm font-semibold text-sky-600 active:opacity-70 dark:text-sky-400"
        >
          {{ t('driver_home.see_all') }}
        </RouterLink>
      </div>
      <ul class="space-y-3">
        <li v-for="trip in queueTrips" :key="trip.id">
          <TripListItem :trip="trip" />
        </li>
      </ul>
    </section>

    <!-- 5 — Skeleton additional rows while loading -->
    <template v-if="loading">
      <div class="space-y-3">
        <div class="animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800 h-16" />
        <div class="animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800 h-16" />
      </div>
    </template>

    <!-- 6 — Floating actions -->
    <div class="pointer-events-none fixed bottom-24 right-4 z-30 flex flex-col items-end gap-3">
      <!-- Call dispatcher -->
      <a
        v-if="dispatcherPhone"
        :href="`tel:${dispatcherPhone}`"
        class="pointer-events-auto flex items-center gap-2 rounded-2xl bg-slate-900 px-4 text-white shadow-lg active:scale-95 dark:bg-white dark:text-slate-900"
        style="min-height: 48px;"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0">
          <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 16.352V17.5a1.5 1.5 0 0 1-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 0 1 2.43 8.326 13.019 13.019 0 0 1 2 5V3.5z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-semibold">{{ t('driver_home.float_call') }}</span>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { getDriverSummary } from '../../api/driver'
import { listTrips } from '../../api/trips'
import { useAuthStore } from '../../store'
import { useNotificationStore } from '../../store/notificationCenter'
import DriverHeader from '../../components/driver/DriverHeader.vue'
import TripListItem from '../../components/driver/TripListItem.vue'
import PendingConfirmationBanner from '../../components/driver/PendingConfirmationBanner.vue'
import DriverStatsGrid from '../../components/driver/DriverStatsGrid.vue'
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

const doneStatuses = new Set(['completed', 'cancelled'])
const pendingStatuses = new Set(['assigned', 'driver_confirmed', 'pending', 'approved'])

function tripStatusNorm(t) {
  return String(t?.status ?? '').trim().toLowerCase()
}

/** Chỉ chuyến đã gán cho tài xế đăng nhập (`trip.driver_id` khớp hồ sơ tài xế). */
const rawTrips = computed(() => {
  const id = myDriverId.value
  if (id == null) return []
  return rawListItems.value.filter(
    (t) => t.driver_id != null && Number(t.driver_id) === Number(id),
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

// Các chuyến (không huỉ/xong, không chờ xác nhận); gồm cả in_progress trong danh sách
const queueTrips = computed(() => {
  return rawTrips.value
    .filter((x) => {
      const st = tripStatusNorm(x)
      if (doneStatuses.has(st)) return false
      if (pendingStatuses.has(st)) return false
      return true
    })
    .slice()
    .sort((a, b) => {
      if (tripStatusNorm(a) === 'in_progress' && tripStatusNorm(b) !== 'in_progress') return -1
      if (tripStatusNorm(b) === 'in_progress' && tripStatusNorm(a) !== 'in_progress') return 1
      return (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0)
    })
    .slice(0, 6)
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
      listTrips({ from: ymd(past), to: ymd(horizon), per_page: 200 }),
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

// Làm mới badge thông báo khi có chuyến chờ gấp (đồng bộ với inbox / PWA)
watch(
  () => pendingTrips.value.some((x) => x.dispatch_request?.is_urgent),
  (urgent) => {
    if (urgent) void notifStore.refreshBadges()
  },
)

onMounted(() => fetchData(true))
</script>
