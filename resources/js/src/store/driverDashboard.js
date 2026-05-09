import { defineStore } from 'pinia'
import {
  DRIVER_TRIPS_FETCH_MAX_PAGES,
  DRIVER_TRIPS_LIST_MAX_PER_PAGE,
  getDriverSummary,
  listDriverTrips,
  listDriverTripPagesAfterFirst,
} from '../api/driver'
import { updateTripStatus } from '../api/trips'
import { showAppErrorFromApi, showAppSuccess } from '../composables/appMessage'
import { i18n } from '../i18n'
import {
  dashPerfApiEnd,
  dashPerfApiStart,
  dashPerfHydrateDone,
  dashPerfSkipStale,
} from '../util/devDriverDashboardPerf'

const CACHE_KEY = 'va_driver_dash_snap_v1'
const CACHE_SCHEMA = 1
const STALE_MS = 30_000
const SILENT_REFETCH_DEBOUNCE_MS = 700

function t(key, params = undefined) {
  return params ? i18n.global.t(key, params) : i18n.global.t(key)
}

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function tripStatusNorm(x) {
  return String(x?.status ?? '').trim().toLowerCase()
}

function tripDepartYmd(trip) {
  if (trip.depart_at) {
    const d = new Date(trip.depart_at)
    if (!Number.isNaN(d.getTime())) return ymd(d)
  }
  if (trip.depart_date && typeof trip.depart_date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(trip.depart_date)) {
    const [y, m, day] = trip.depart_date.slice(0, 10).split('-').map(Number)
    return ymd(new Date(y, m - 1, day, 12, 0, 0, 0))
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

function sortScheduleTrips(list) {
  return [...list].sort((a, b) => {
    const ta = sortTier(a.status)
    const tb = sortTier(b.status)
    if (ta !== tb) return ta - tb
    const da = new Date(a.depart_at ?? a.depart_date ?? 0).getTime() || 0
    const db = new Date(b.depart_at ?? b.depart_date ?? 0).getTime() || 0
    if (ta >= 3) return db - da
    return da - db
  })
}

function emptyStatsShape() {
  return {
    total: 0,
    completed: 0,
    cancelled: 0,
    total_km: null,
    completed_this_month: 0,
    cancelled_this_month: 0,
    completed_growth_pct: null,
    overdue_count: 0,
  }
}

function mapHistoryStatsFromApi(raw) {
  if (raw == null || typeof raw !== 'object') return emptyStatsShape()
  const s = raw
  return {
    total: s.total ?? 0,
    completed: s.completed ?? s.completed_this_month ?? 0,
    cancelled: s.cancelled ?? s.cancelled_this_month ?? 0,
    total_km: s.total_km ?? null,
    completed_this_month: s.completed_this_month ?? s.completed ?? 0,
    cancelled_this_month: s.cancelled_this_month ?? s.cancelled ?? 0,
    completed_growth_pct: s.completed_growth_pct ?? null,
    overdue_count: s.overdue_count ?? 0,
  }
}

function cloneTrip(trip) {
  try {
    return JSON.parse(JSON.stringify(trip))
  } catch {
    return { ...trip }
  }
}

/** `summary.driver` có thể null nhưng list trip vẫn có một `driver_id` duy nhất — tránh ẩn toàn list. */
function resolveEffectiveDriverId(idFromSummary, items) {
  if (idFromSummary != null && idFromSummary !== '') {
    const n = Number(idFromSummary)
    if (!Number.isNaN(n)) return n
  }
  const uniq = new Set()
  for (const x of items) {
    if (x.driver_id == null || x.driver_id === '') continue
    const n = Number(x.driver_id)
    if (!Number.isNaN(n)) uniq.add(n)
  }
  if (uniq.size === 1) return [...uniq][0]
  return null
}

function normalizedDashboardEndYmd(dashboardDateTo) {
  const s = dashboardDateTo != null ? String(dashboardDateTo).trim() : ''
  const head = s.length >= 10 ? s.slice(0, 10) : ''
  if (/^\d{4}-\d{2}-\d{2}$/.test(head)) return head
  const h = new Date()
  h.setDate(h.getDate() + 21)
  return ymd(h)
}

export const useDriverDashboardStore = defineStore('driverDashboard', {
  state: () => ({
    myDriverId: null,
    rawListItems: [],
    lastFetchedAt: 0,
    dashboardDateFrom: '',
    dashboardDateTo: '',
    errorMsg: '',
    loadingInitial: false,
    loadingSilent: false,
    monthlyTripStats: null,
    /** @type {ReturnType<typeof setTimeout> | null} */
    silentRefetchTimer: null,
    startBusyTripId: null,
  }),

  getters: {
    rawTrips(state) {
      const items = state.rawListItems
      const id = state.myDriverId
      if (id == null) {
        return items.slice()
      }
      const matched = items.filter(
        (x) => x.driver_id != null && Number(x.driver_id) === Number(id),
      )
      if (matched.length > 0) {
        return matched
      }
      return items.slice()
    },

    needsConfirmationTrips() {
      const need = new Set(['assigned', 'pending', 'approved'])
      return this.rawTrips
        .filter((x) => need.has(tripStatusNorm(x)))
        .slice()
        .sort(
          (a, b) =>
            (new Date(a.depart_at ?? a.depart_date ?? 0).getTime() || 0) -
            (new Date(b.depart_at ?? b.depart_date ?? 0).getTime() || 0),
        )
    },

    upcomingScheduleTrips() {
      const start = ymd(new Date())
      const end = normalizedDashboardEndYmd(this.dashboardDateTo)
      return sortScheduleTrips(
        this.rawTrips.filter((x) => {
          const d = tripDepartYmd(x)
          if (d == null) return false
          if (d < start || d > end) return false
          const s = tripStatusNorm(x)
          if (s === 'completed' || s === 'cancelled') return false
          return true
        }),
      )
    },

    tripsForMonthCharts() {
      const now = new Date()
      const startYmd = ymd(new Date(now.getFullYear(), now.getMonth(), 1))
      const endYmd = ymd(new Date(now.getFullYear(), now.getMonth() + 1, 0))
      return this.rawTrips.filter((x) => {
        const d = tripDepartYmd(x)
        return d != null && d >= startYmd && d <= endYmd
      })
    },

    upcomingBannerTrip() {
      const now = Date.now()
      const limit = now + 120 * 60 * 1000
      const eligible = this.rawTrips.filter((x) => {
        const s = tripStatusNorm(x)
        if (!['pending', 'assigned', 'driver_confirmed', 'approved'].includes(s)) return false
        const dep = x.depart_at
          ? new Date(x.depart_at).getTime()
          : x.depart_date
            ? new Date(x.depart_date).getTime()
            : NaN
        if (!Number.isFinite(dep) || dep < now || dep > limit) return false
        return true
      })
      eligible.sort(
        (a, b) =>
          new Date(a.depart_at ?? a.depart_date ?? 0) - new Date(b.depart_at ?? b.depart_date ?? 0),
      )
      return eligible[0] ?? null
    },

    upcomingDepartIso() {
      const tr = this.upcomingBannerTrip
      const t0 = tr?.depart_at
      if (!t0) return ''
      const d = new Date(t0)
      return Number.isNaN(d.getTime()) ? '' : d.toISOString()
    },

    statsLoadingDisplay(state) {
      return state.loadingInitial && state.monthlyTripStats == null
    },

    analyticsSectionLoading(state) {
      return state.loadingInitial && state.rawListItems.length === 0
    },

    listLoadingForUi(state) {
      return state.loadingInitial && state.rawListItems.length === 0
    },
  },

  actions: {
    hydrateFromCache() {
      const t0 = typeof performance !== 'undefined' ? performance.now() : 0
      let hit = false
      try {
        if (typeof sessionStorage === 'undefined') return
        const raw = sessionStorage.getItem(CACHE_KEY)
        if (!raw) return
        const snap = JSON.parse(raw)
        if (snap?.v !== CACHE_SCHEMA) return
        if (!Array.isArray(snap.rawListItems)) return
        this.myDriverId = snap.myDriverId ?? null
        this.rawListItems = snap.rawListItems
        this.lastFetchedAt = Number(snap.savedAt) || 0
        this.dashboardDateFrom = snap.dashboardDateFrom || ''
        this.dashboardDateTo = snap.dashboardDateTo || ''
        if (snap.monthlyTripStats != null && typeof snap.monthlyTripStats === 'object') {
          this.monthlyTripStats = mapHistoryStatsFromApi(snap.monthlyTripStats)
        }
        this.myDriverId = resolveEffectiveDriverId(this.myDriverId, this.rawListItems)
        hit = true
      } catch {
        /* ignore */
      } finally {
        const ms = typeof performance !== 'undefined' ? performance.now() - t0 : 0
        dashPerfHydrateDone(ms, hit)
      }
    },

    persistToCache() {
      try {
        if (typeof sessionStorage === 'undefined') return
        const payload = {
          v: CACHE_SCHEMA,
          savedAt: Date.now(),
          myDriverId: this.myDriverId,
          rawListItems: this.rawListItems,
          dashboardDateFrom: this.dashboardDateFrom,
          dashboardDateTo: this.dashboardDateTo,
          monthlyTripStats: this.monthlyTripStats,
        }
        sessionStorage.setItem(CACHE_KEY, JSON.stringify(payload))
      } catch {
        /* ignore */
      }
    },

    tripSnapshot(tripId) {
      const row = this.rawListItems.find((x) => Number(x.id) === Number(tripId))
      return row ? cloneTrip(row) : null
    },

    patchTripInList(tripId, partial) {
      const idx = this.rawListItems.findIndex((x) => Number(x.id) === Number(tripId))
      if (idx < 0) return
      const cur = this.rawListItems[idx]
      const next = { ...cur, ...partial }
      this.rawListItems = this.rawListItems.slice(0, idx).concat([next], this.rawListItems.slice(idx + 1))
    },

    replaceTripInList(tripId, tripOrNull) {
      const idx = this.rawListItems.findIndex((x) => Number(x.id) === Number(tripId))
      if (idx < 0) return
      if (tripOrNull == null) {
        this.rawListItems = this.rawListItems.filter((x) => Number(x.id) !== Number(tripId))
        return
      }
      this.rawListItems = this.rawListItems
        .slice(0, idx)
        .concat([tripOrNull], this.rawListItems.slice(idx + 1))
    },

    removeTrip(tripId) {
      this.rawListItems = this.rawListItems.filter((x) => Number(x.id) !== Number(tripId))
    },

    insertTrip(trip) {
      this.rawListItems = [...this.rawListItems, trip]
    },

    scheduleSilentRefetch() {
      if (typeof window === 'undefined') return
      if (this.silentRefetchTimer != null) {
        clearTimeout(this.silentRefetchTimer)
      }
      this.silentRefetchTimer = window.setTimeout(() => {
        this.silentRefetchTimer = null
        void this.fetchDashboard({ silent: true, force: true })
      }, SILENT_REFETCH_DEBOUNCE_MS)
    },

    async fetchDashboard({ silent = false, force = false } = {}) {
      if (!force && this.lastFetchedAt && Date.now() - this.lastFetchedAt < STALE_MS) {
        dashPerfSkipStale()
        return
      }

      const now = new Date()
      const past = new Date(now)
      past.setDate(past.getDate() - 30)
      const horizon = new Date(now)
      horizon.setDate(horizon.getDate() + 21)
      const dateFrom = ymd(past)
      const dateTo = ymd(horizon)

      if (!silent) {
        this.loadingInitial = true
      } else {
        this.loadingSilent = true
      }
      if (!silent) {
        this.errorMsg = ''
      }

      dashPerfApiStart({ dateFrom, dateTo, silent })

      try {
        const dateQuery = { date_from: dateFrom, date_to: dateTo }
        const [sum, page1] = await Promise.all([
          getDriverSummary(),
          listDriverTrips({
            ...dateQuery,
            per_page: DRIVER_TRIPS_LIST_MAX_PER_PAGE,
            page: 1,
          }),
        ])

        const batch1 = page1?.items ?? []
        const st = page1?.stats ?? null
        const lastPage = Math.min(
          Number(page1?.meta?.last_page ?? 1),
          DRIVER_TRIPS_FETCH_MAX_PAGES,
        )

        this.rawListItems = batch1
        this.myDriverId = resolveEffectiveDriverId(sum?.driver?.id ?? null, batch1)
        this.monthlyTripStats =
          st != null && typeof st === 'object' ? mapHistoryStatsFromApi(st) : emptyStatsShape()
        this.dashboardDateFrom = dateFrom
        this.dashboardDateTo = dateTo

        if (!silent) {
          this.loadingInitial = false
        }

        if (lastPage > 1) {
          try {
            const restItems = await listDriverTripPagesAfterFirst(
              dateQuery,
              DRIVER_TRIPS_LIST_MAX_PER_PAGE,
              lastPage,
            )
            const merged = [...batch1, ...restItems]
            this.rawListItems = merged
            this.myDriverId = resolveEffectiveDriverId(sum?.driver?.id ?? null, merged)
          } catch {
            void this.scheduleSilentRefetch()
          }
        }

        this.lastFetchedAt = Date.now()
        this.persistToCache()
        if (!silent) {
          this.errorMsg = ''
        }
      } catch {
        if (!silent) {
          const msg = t('driver_home.load_error')
          this.errorMsg = typeof msg === 'string' ? msg : 'Load error'
          this.myDriverId = null
          this.rawListItems = []
          this.monthlyTripStats = emptyStatsShape()
        }
      } finally {
        dashPerfApiEnd({
          silent,
          tripCount: this.rawListItems.length,
        })
        if (!silent) this.loadingInitial = false
        else this.loadingSilent = false
      }
    },

    async refreshTripsQuiet() {
      await this.fetchDashboard({ silent: true, force: true })
    },

    async confirmTripOptimistic(trip) {
      if (!trip?.id) return
      const backup = this.tripSnapshot(trip.id)
      this.patchTripInList(trip.id, { status: 'driver_confirmed' })
      try {
        await updateTripStatus(trip.id, { status: 'driver_confirmed' })
        showAppSuccess(t('driver_home.toast_confirm_ok'), t('driver_home.toast_action_title'))
        this.scheduleSilentRefetch()
      } catch (e) {
        if (backup) this.replaceTripInList(trip.id, backup)
        else this.refreshTripsQuiet()
        throw e
      }
    },

    async declineTripOptimistic(trip, message) {
      if (!trip?.id) return
      const backup = this.tripSnapshot(trip.id)
      this.removeTrip(trip.id)
      try {
        await updateTripStatus(trip.id, { status: 'cancelled', message })
        showAppSuccess(t('driver_home.toast_decline_ok'), t('driver_home.toast_action_title'))
        this.scheduleSilentRefetch()
      } catch (e) {
        if (backup) this.insertTrip(backup)
        else this.refreshTripsQuiet()
        throw e
      }
    },

    async startTripOptimistic(tripId) {
      if (tripId == null || this.startBusyTripId != null) return
      const backup = this.tripSnapshot(tripId)
      this.startBusyTripId = tripId
      try {
        this.patchTripInList(tripId, { status: 'in_progress' })
        await updateTripStatus(tripId, { status: 'in_progress' })
        showAppSuccess(t('driver_home.toast_start_ok'), t('driver_home.toast_action_title'))
        this.scheduleSilentRefetch()
      } catch (e) {
        if (backup) this.replaceTripInList(tripId, backup)
        else this.refreshTripsQuiet()
        const fallback = t('driver_trip_detail.status_err')
        showAppErrorFromApi(e, typeof fallback === 'string' ? fallback : 'Error')
        throw e
      } finally {
        this.startBusyTripId = null
      }
    },
  },
})
