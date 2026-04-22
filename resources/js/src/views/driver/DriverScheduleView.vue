<template>
  <div class="driver-schedule -mx-3 w-[calc(100%+1.5rem)] max-w-lg sm:-mx-4 sm:mx-auto sm:w-full sm:max-w-2xl">
    <!-- Header -->
    <div class="bg-gradient-to-br from-slate-900 via-[#0d1f3a] to-slate-900 px-4 pb-5 pt-3 text-white sm:rounded-2xl sm:shadow-md">
      <div class="flex items-start justify-between gap-2">
        <div class="flex min-w-0 flex-1 items-center gap-2.5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-400/20 ring-1 ring-sky-300/30"
          >
            <TruckIcon class="h-7 w-7 text-sky-200" aria-hidden="true" />
          </div>
          <div class="min-w-0">
            <p class="text-sm text-white/80">{{ t('driver_schedule_page.hello') }}</p>
            <p class="truncate text-lg font-bold">{{ user?.name || '—' }}</p>
          </div>
        </div>
        <button
          type="button"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-amber-200 ring-1 ring-white/20"
          :title="t('driver_schedule_page.notifications_hint')"
          @click="onOpenNotifications"
        >
          <BellIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <p class="mt-3 text-sm text-slate-300">
        {{ longDateLabel }}
      </p>
      <h1 class="mt-1 text-2xl font-bold tracking-tight">
        {{ t('driver_schedule_page.title') }}
      </h1>

      <!-- Tiến độ -->
      <div
        v-if="!loading"
        class="mt-4 rounded-2xl border border-white/10 bg-white/5 px-3 py-3 backdrop-blur"
      >
        <div class="flex items-center justify-between gap-2 text-sm">
          <span class="text-white/80">{{ t('driver_schedule_page.progress_label') }}</span>
          <span class="shrink-0 text-right font-bold tabular-nums">
            {{ doneCount }} / {{ activeTotal }} {{ t('driver_schedule_page.trip_unit') }}
            <span v-if="activeTotal" class="ml-1 text-emerald-300"
              >{{ progressPct }}%</span
            >
          </span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
          <div
            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
            :style="{ width: progressPct + '%' }"
          />
        </div>
      </div>
    </div>

    <div class="mt-0 space-y-4 bg-slate-50/90 px-3 pb-6 pt-4 dark:bg-slate-950/40 sm:px-0">
      <p
        v-if="errorMsg"
        class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
      >
        {{ errorMsg }}
      </p>

      <!-- TIMELINE -->
      <div v-if="!loading" class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('driver_schedule_page.timeline') }}
        </p>
        <div class="flex justify-between gap-0.5 overflow-x-auto pb-1">
          <div
            v-for="h in timelineHours"
            :key="h"
            class="flex min-w-[2.5rem] flex-1 flex-col items-center"
          >
            <div
              class="mb-0.5 flex h-5 w-5 items-center justify-center rounded"
              :class="hourHasTrip(h) ? 'text-amber-500' : 'text-transparent'"
            >
              <MapPinIcon v-if="hourHasTrip(h)" class="h-3.5 w-3.5" />
            </div>
            <div
              class="h-1.5 w-full max-w-10 rounded-full"
              :class="hourHasTrip(h) ? 'bg-amber-400' : 'bg-slate-200 dark:bg-slate-700'"
            />
            <span class="mt-1.5 text-[9px] font-medium tabular-nums text-slate-500 dark:text-slate-400">
              {{ String(h).padStart(2, '0') }}:00
            </span>
          </div>
        </div>
      </div>

      <!-- Cảnh báo chuyến đang chạy -->
      <div
        v-if="inProgressTrip"
        class="overflow-hidden rounded-2xl border-2 border-amber-300/80 bg-amber-50/95 shadow-sm dark:border-amber-700/60 dark:bg-amber-950/40"
      >
        <div class="flex gap-2 px-3 py-2.5">
          <BoltIcon class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
          <div class="min-w-0">
            <p class="text-sm font-bold text-amber-900 dark:text-amber-100">
              {{ t('driver_schedule_page.active_trip_title') }}
            </p>
            <p class="mt-0.5 text-xs text-amber-800/90 dark:text-amber-200/90">
              {{ activeTripLine(inProgressTrip) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Bộ lọc -->
      <div class="flex gap-1.5 overflow-x-auto pb-0.5">
        <button
          v-for="tab in filterTabs"
          :key="tab.id"
          type="button"
          class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
          :class="
            filterId === tab.id
              ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900'
              : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
          "
          @click="filterId = tab.id"
        >
          {{ tab.label }} ({{ tab.count }})
        </button>
      </div>

      <!-- Danh sách -->
      <div>
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('driver_schedule_page.list_header', { n: filteredTrips.length }) }}
        </p>
        <p
          v-if="!loading && !filteredTrips.length"
          class="rounded-2xl border border-dashed border-slate-200 bg-white px-3 py-8 text-center text-sm text-slate-500 dark:border-slate-600 dark:bg-slate-900/50"
        >
          {{ t('driver_schedule_page.empty') }}
        </p>
        <ul v-else class="space-y-3">
          <li
            v-for="trip in filteredTrips"
            :key="trip.id"
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/70"
          >
            <div class="flex items-start justify-between gap-2 border-b border-slate-100 px-3 py-2 dark:border-slate-700/80">
              <div class="flex flex-wrap items-center gap-1.5">
                <span
                  class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold"
                  :class="statusBadgeClass(trip.status)"
                >
                  {{ statusLabelTr(trip.status) }}
                </span>
                <span
                  class="inline-flex rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                >
                  {{ tripTypeShort(trip) }}
                </span>
              </div>
              <span class="shrink-0 text-[11px] font-medium text-slate-400">#TR-{{ trip.id }}</span>
            </div>

            <div class="flex gap-3 px-3 py-2.5">
              <img
                v-if="requesterAvatar(trip)"
                :src="requesterAvatar(trip)"
                alt=""
                class="h-12 w-12 rounded-full object-cover"
              />
              <div
                v-else
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-200"
              >
                {{ requesterInitials(trip) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white">
                  {{ requesterName(trip) }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ requesterSub(trip) }}
                </p>
              </div>
              <a
                v-if="requesterPhone(trip)"
                :href="`tel:${requesterPhone(trip)}`"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-800 transition hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                :title="t('driver_schedule_page.call')"
              >
                <PhoneIcon class="h-5 w-5" />
              </a>
            </div>

            <div class="border-t border-slate-100 px-3 py-2 dark:border-slate-700/80">
              <div class="relative pl-1">
                <div class="absolute left-[7px] top-1 bottom-1 w-px bg-slate-200 dark:bg-slate-600" />
                <div class="space-y-3 pl-4">
                  <div class="relative">
                    <span
                      class="absolute -left-4 top-1.5 h-2.5 w-2.5 rounded-full border-2 border-slate-300 bg-white dark:border-slate-500 dark:bg-slate-800"
                    />
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                      {{ timeHm(trip.depart_at) }} · {{ t('driver_schedule_page.pickup') }}
                    </p>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                      {{ trip.dispatch_request?.origin || '—' }}
                    </p>
                  </div>
                  <div class="relative">
                    <span
                      class="absolute -left-4 top-1.5 h-2.5 w-2.5 rounded-full border-2 border-slate-800 bg-slate-800 dark:border-sky-400 dark:bg-sky-400"
                    />
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                      {{ timeHm(trip.arrive_by || trip.dispatch_request?.arrive_by) }} ·
                      {{ t('driver_schedule_page.dropoff') }}
                    </p>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                      {{ trip.dispatch_request?.destination || '—' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-3 pb-3">
              <RouterLink
                :to="`/driver/trips/${trip.id}`"
                class="flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-bold text-white transition active:scale-[0.99]"
                :class="
                  trip.status === 'in_progress'
                    ? 'bg-slate-900 hover:bg-slate-800 dark:bg-sky-800 dark:hover:bg-sky-700'
                    : 'bg-slate-800 hover:bg-slate-700 dark:bg-slate-700'
                "
              >
                {{ ctaLabel(trip.status) }}
                <ArrowRightIcon class="h-4 w-4" />
              </RouterLink>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  ArrowRightIcon,
  BoltIcon,
  BellIcon,
  MapPinIcon,
  PhoneIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { listTrips } from '../../api/trips'
import { useAuthStore } from '../../store'
import { useNotificationStore } from '../../store/notificationCenter'

const { t } = useI18n()
const auth = useAuthStore()
const notif = useNotificationStore()

function onOpenNotifications() {
  notif.openPanel()
  void notif.requestBrowserNotificationPermission()
  void notif.refreshBadges()
}

const user = computed(() => auth.user)
const loading = ref(true)
const errorMsg = ref('')
const raw = ref([])

const filterId = ref('all')

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const longDateLabel = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})

const todayTrips = computed(() => {
  const list = raw.value || []
  return list
    .filter((x) => x.status !== 'cancelled')
    .slice()
    .sort((a, b) => {
      const ta = new Date(a.depart_at).getTime() || 0
      const tb = new Date(b.depart_at).getTime() || 0
      return ta - tb
    })
})

const activeTotal = computed(() => todayTrips.value.length)
const doneCount = computed(
  () => todayTrips.value.filter((x) => x.status === 'completed').length,
)
const progressPct = computed(() => {
  if (!activeTotal.value) return 0
  return Math.min(100, Math.round((doneCount.value / activeTotal.value) * 100))
})

const inProgressTrip = computed(() => todayTrips.value.find((x) => x.status === 'in_progress'))

const timelineHours = computed(() => {
  const out = []
  for (let h = 6; h <= 13; h += 1) out.push(h)
  return out
})

function tripDepartHour(t) {
  if (!t.depart_at) return null
  const d = new Date(t.depart_at)
  if (Number.isNaN(d.getTime())) return null
  return d.getHours()
}

function hourHasTrip(h) {
  return todayTrips.value.some((t) => tripDepartHour(t) === h)
}

const pendingStatuses = new Set(['assigned', 'driver_confirmed', 'pending', 'approved'])

function kindOf(t) {
  if (t.status === 'in_progress') return 'running'
  if (t.status === 'completed') return 'done'
  if (pendingStatuses.has(t.status)) return 'pending'
  return 'other'
}

const filterTabs = computed(() => {
  const trips = todayTrips.value
  return [
    { id: 'all', count: trips.length, label: t('driver_schedule_page.tab_all') },
    {
      id: 'pending',
      count: trips.filter((x) => kindOf(x) === 'pending').length,
      label: t('driver_schedule_page.tab_pending'),
    },
    {
      id: 'running',
      count: trips.filter((x) => x.status === 'in_progress').length,
      label: t('driver_schedule_page.tab_running'),
    },
  ]
})

const filteredTrips = computed(() => {
  const trips = todayTrips.value
  if (filterId.value === 'pending') return trips.filter((x) => kindOf(x) === 'pending')
  if (filterId.value === 'running') return trips.filter((x) => x.status === 'in_progress')
  return trips
})

function timeHm(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: true })
}

function statusLabelTr(st) {
  const k = `trips_page.trip_status.${st}`
  const tr = t(k)
  return tr === k ? st : tr
}

function statusBadgeClass(st) {
  if (st === 'in_progress') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200'
  if (st === 'completed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function tripTypeShort(trip) {
  const x = trip.dispatch_request?.trip_type
  if (x === 'door_to_door') return 'D2D'
  if (x === 'point_to_point') return 'P2P'
  if (x === 'business') return t('trips_page.trip_type.business').split(' ')[0] || 'CT'
  if (x === 'cargo') return 'Cargo'
  return '—'
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

function requesterSub(trip) {
  const phone =
    trip.dispatch_request?.requester?.phone || trip.dispatch_request?.requester?.employee_code
  const extra = trip.dispatch_request?.notes
  if (phone && extra) return `${phone} · ${String(extra).slice(0, 40)}`
  if (phone) return String(phone)
  if (extra) return String(extra).slice(0, 64)
  return trip.dispatch_request?.destination || ''
}

function requesterPhone(trip) {
  return trip.dispatch_request?.requester?.phone || null
}

function activeTripLine(trip) {
  const t = tripTypeLabel(trip)
  const a = (trip.dispatch_request?.origin || '').trim()
  const b = (trip.dispatch_request?.destination || '').trim()
  if (a && b) return `${t} · ${a} → ${b}`
  return t
}

function tripTypeLabel(trip) {
  const raw = trip.dispatch_request?.trip_type
  if (!raw) return 'P2P'
  const k = `trips_page.trip_type.${raw}`
  const tr = t(k)
  return tr === k ? raw : tr
}

function ctaLabel(st) {
  if (st === 'in_progress') return t('driver_schedule_page.btn_continue')
  if (st === 'completed') return t('driver_schedule_page.btn_view')
  return t('driver_schedule_page.btn_start')
}

onMounted(async () => {
  loading.value = true
  errorMsg.value = ''
  const now = new Date()
  const d = ymd(now)
  try {
    const res = await listTrips({ from: d, to: d, per_page: 100 })
    raw.value = res?.items ?? []
  } catch {
    errorMsg.value = t('driver_home.load_error')
    raw.value = []
  } finally {
    loading.value = false
  }
})
</script>
