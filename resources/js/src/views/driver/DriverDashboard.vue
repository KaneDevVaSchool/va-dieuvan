<template>
  <div class="max-w-lg mx-auto w-full space-y-4 pb-2 sm:max-w-2xl">
    <!-- Header: mobile-first, full-bleed trong vùng scroll -->
    <div class="-mx-3 rounded-b-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 px-4 pb-5 pt-4 text-white shadow-lg sm:-mx-4 sm:px-5">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
          <p class="text-sm text-white/80">{{ t('driver_home.hello') }}</p>
          <h1 class="mt-0.5 truncate text-xl font-bold tracking-tight sm:text-2xl">
            {{ user?.name || '—' }}
          </h1>
        </div>
        <div class="shrink-0">
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt=""
            class="h-12 w-12 rounded-full border-2 border-white/20 object-cover"
          />
          <div
            v-else
            class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/20 bg-white/10 text-lg font-semibold"
          >
            {{ initials }}
          </div>
        </div>
      </div>

      <div
        class="mt-4 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5 backdrop-blur-sm"
      >
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
          <TruckIcon class="h-6 w-6 text-white" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold">
            {{ vehicleLine }}
          </p>
          <p class="truncate text-xs text-white/70">
            {{ displayPlate }}
          </p>
        </div>
        <div class="shrink-0 flex items-center gap-1.5 text-xs font-medium text-emerald-300">
          <span class="h-2 w-2 rounded-full bg-emerald-400" />
          {{ vehicleStatusLabel }}
        </div>
      </div>
    </div>

    <!-- Thống kê 2×2 -->
    <div class="grid grid-cols-2 gap-3">
      <div
        v-for="s in statCards"
        :key="s.key"
        class="rounded-2xl border border-slate-200/90 bg-white p-3.5 shadow-sm dark:border-slate-700 dark:bg-slate-900/60"
      >
        <div class="flex items-center gap-2.5">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            :class="s.iconWrap"
          >
            <component :is="s.icon" class="h-5 w-5" :class="s.iconClass" />
          </div>
          <div class="min-w-0">
            <p class="text-xl font-bold tabular-nums text-slate-900 dark:text-white">
              {{ loading ? '…' : s.value }}
            </p>
            <p class="text-[11px] font-medium leading-snug text-slate-600 dark:text-slate-400">
              {{ s.label }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <p v-if="errorMsg" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
      {{ errorMsg }}
    </p>

    <!-- Thao tác nhanh -->
    <div>
      <h2 class="mb-2.5 text-base font-bold text-slate-900 dark:text-white">
        {{ t('driver_home.section_quick') }}
      </h2>
      <div class="grid grid-cols-4 gap-2">
        <RouterLink
          v-for="a in quickActions"
          :key="a.to"
          :to="a.to"
          class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200/90 bg-white py-3.5 text-center shadow-sm transition active:scale-[0.98] dark:border-slate-700 dark:bg-slate-900/60"
        >
          <span
            class="flex h-11 w-11 items-center justify-center rounded-2xl"
            :class="a.circle"
          >
            <component :is="a.icon" class="h-5 w-5" :class="a.iconClass" />
          </span>
          <span class="px-0.5 text-[10px] font-medium leading-tight text-slate-700 dark:text-slate-200">
            {{ t(a.labelKey) }}
          </span>
        </RouterLink>
      </div>
    </div>

    <!-- Chuyến sắp tới -->
    <div>
      <div class="mb-2.5 flex items-center justify-between gap-2">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">
          {{ t('driver_home.section_upcoming') }}
        </h2>
        <RouterLink
          to="/driver/schedule"
          class="text-sm font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400"
        >
          {{ t('driver_home.see_all') }}
        </RouterLink>
      </div>

    <p
      v-if="!loading && !upcomingTrips.length"
      class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3 py-6 text-center text-sm text-slate-500 dark:border-slate-600 dark:bg-slate-900/40 dark:text-slate-400"
    >
      {{ t('driver_home.empty_trips') }}
    </p>

    <ul v-else class="space-y-3">
        <li v-for="trip in upcomingTrips" :key="trip.id">
          <div
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/60"
          >
            <div class="flex items-start justify-between gap-2 border-b border-slate-100 px-3 py-2.5 dark:border-slate-700/80">
              <span
                class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold"
                :class="statusStyle(trip.status).badge"
              >
                {{ statusStyle(trip.status).label }}
              </span>
              <span class="shrink-0 text-xs tabular-nums text-slate-500 dark:text-slate-400">
                {{ timeRange(trip) }}
              </span>
            </div>
            <div class="flex gap-3 px-3 py-3">
              <img
                v-if="requesterAvatar(trip)"
                :src="requesterAvatar(trip)"
                alt=""
                class="h-11 w-11 shrink-0 rounded-full object-cover"
              />
              <div
                v-else
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200"
              >
                {{ requesterInitials(trip) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white">
                  {{ requesterName(trip) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                  {{ tripSubline(trip) }}
                </p>
              </div>
            </div>
            <div class="px-3 pb-3">
              <RouterLink
                :to="`/driver/trips/${trip.id}`"
                class="flex w-full items-center justify-center rounded-xl px-3 py-2.5 text-sm font-semibold transition active:scale-[0.99]"
                :class="primaryBtnClass(trip.status)"
              >
                {{ primaryBtnLabel(trip.status) }}
              </RouterLink>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  CalendarDaysIcon,
  CheckCircleIcon,
  ClockIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  PlusCircleIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import { getDriverSummary } from '../../api/driver'
import { listTrips } from '../../api/trips'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const errorMsg = ref('')
const summary = ref(null)
const rawTrips = ref([])

const user = computed(() => auth.user)
const avatarUrl = computed(() => user.value?.avatar_url || null)
const initials = computed(() => {
  const n = (user.value?.name || '?').trim()
  const p = n.split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
})

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const statCards = computed(() => {
  const s = summary.value?.stats
  return [
    {
      key: 'today',
      value: s?.trips_today ?? 0,
      label: t('driver_home.stats_today'),
      icon: CalendarDaysIcon,
      iconWrap: 'bg-sky-100 dark:bg-sky-950/50',
      iconClass: 'text-sky-600 dark:text-sky-400',
    },
    {
      key: 'month',
      value: s?.trips_this_month ?? 0,
      label: t('driver_home.stats_month'),
      icon: CheckCircleIcon,
      iconWrap: 'bg-emerald-100 dark:bg-emerald-950/50',
      iconClass: 'text-emerald-600 dark:text-emerald-400',
    },
    {
      key: 'costs',
      value: s?.pending_trip_costs ?? 0,
      label: t('driver_home.stats_pending_costs'),
      icon: DocumentTextIcon,
      iconWrap: 'bg-amber-100 dark:bg-amber-950/50',
      iconClass: 'text-amber-600 dark:text-amber-400',
    },
    {
      key: 'maint',
      value: s?.maintenance_due_soon ?? 0,
      label: t('driver_home.stats_maintenance'),
      icon: WrenchScrewdriverIcon,
      iconWrap: 'bg-slate-200 dark:bg-slate-700/80',
      iconClass: 'text-slate-600 dark:text-slate-300',
    },
  ]
})

const quickActions = [
  { to: '/driver/schedule', labelKey: 'driver_home.action_schedule', icon: CalendarDaysIcon, circle: 'bg-sky-100 dark:bg-sky-950/50', iconClass: 'text-sky-600' },
  { to: '/driver/costs', labelKey: 'driver_home.action_add_cost', icon: PlusCircleIcon, circle: 'bg-amber-100 dark:bg-amber-950/50', iconClass: 'text-amber-600' },
  { to: '/driver/schedule', labelKey: 'driver_home.action_history', icon: ClockIcon, circle: 'bg-emerald-100 dark:bg-emerald-950/50', iconClass: 'text-emerald-600' },
  { to: '/profile', labelKey: 'driver_home.action_settings', icon: Cog6ToothIcon, circle: 'bg-slate-200 dark:bg-slate-700/80', iconClass: 'text-slate-600' },
]

function pickVehicle(trips) {
  for (const tr of trips) {
    if (tr.vehicle?.license_plate) return tr.vehicle
  }
  return null
}

const vehicle = computed(() => summary.value?.vehicle || pickVehicle(rawTrips.value))

const displayPlate = computed(() => vehicle.value?.license_plate || '—')

const vehicleLine = computed(() => {
  const v = vehicle.value
  if (!v) return t('driver_home.no_vehicle')
  const type = (v.type || '').trim()
  const seats = v.seat_count != null ? `${v.seat_count} chỗ` : ''
  if (type && seats) return `${type} ${seats}`
  if (type) return type
  if (seats) return seats
  return t('driver_home.no_vehicle')
})

const vehicleStatusLabel = computed(() => {
  const st = (vehicle.value?.status || '').toLowerCase()
  if (st === 'active' || st === 'operational' || st === 'running') return t('driver_home.vehicle_active')
  return t('driver_home.vehicle_idle')
})

function formatHm(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })
}

function timeRange(trip) {
  const start = formatHm(trip.depart_at)
  const end = formatHm(trip.arrive_by || trip.dispatch_request?.arrive_by)
  if (start && end) return `${start} - ${end}`
  return start || '—'
}

function tripTypeLabel(trip) {
  const raw = trip.dispatch_request?.trip_type
  if (!raw || raw === 'unspecified') return t('driver_home.line_route')
  const key = `trips_page.trip_type.${raw}`
  const translated = t(key)
  return translated === key ? raw : translated
}

function requesterName(trip) {
  return trip.dispatch_request?.requester?.name?.trim() || trip.dispatch_request?.origin || '—'
}

function requesterAvatar(trip) {
  return trip.dispatch_request?.requester?.avatar_url || null
}

function requesterInitials(trip) {
  const n = requesterName(trip)
  if (n === '—') return '?'
  const p = n.split(/\s+/)
  if (p.length >= 2) return (p[0][0] + p[p.length - 1][0]).toUpperCase()
  return n.slice(0, 2).toUpperCase()
}

function tripSubline(trip) {
  const type = tripTypeLabel(trip)
  const dest = (trip.dispatch_request?.destination || '').trim()
  if (dest) return `${type} · ${dest}`
  return type
}

const doneStatuses = new Set(['completed', 'cancelled'])

const upcomingTrips = computed(() => {
  const list = rawTrips.value.filter((x) => !doneStatuses.has(x.status))
  return list
    .slice()
    .sort((a, b) => {
      if (a.status === 'in_progress' && b.status !== 'in_progress') return -1
      if (b.status === 'in_progress' && a.status !== 'in_progress') return 1
      const ta = new Date(a.depart_at).getTime() || 0
      const tb = new Date(b.depart_at).getTime() || 0
      return ta - tb
    })
    .slice(0, 6)
})

function statusStyle(st) {
  const key = `trips_page.trip_status.${st}`
  const translated = t(key)
  const label = translated === key ? t('driver_home.trip_pending') : translated
  if (st === 'in_progress') {
    return {
      label,
      badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200',
    }
  }
  return {
    label,
    badge: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  }
}

function primaryBtnLabel(st) {
  if (st === 'in_progress') return t('driver_home.btn_continue')
  if (st === 'completed') return t('driver_home.btn_view')
  return t('driver_home.btn_start')
}

function primaryBtnClass(st) {
  if (st === 'in_progress') {
    return 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900'
  }
  return 'bg-slate-100 text-slate-900 hover:bg-slate-200/90 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700'
}

onMounted(async () => {
  loading.value = true
  errorMsg.value = ''
  const now = new Date()
  const horizon = new Date(now)
  horizon.setDate(horizon.getDate() + 21)

  try {
    const [sum, listRes] = await Promise.all([
      getDriverSummary(),
      listTrips({ from: ymd(now), to: ymd(horizon), per_page: 60 }),
    ])
    summary.value = sum
    rawTrips.value = listRes?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    summary.value = null
    rawTrips.value = []
  } finally {
    loading.value = false
  }
})
</script>
