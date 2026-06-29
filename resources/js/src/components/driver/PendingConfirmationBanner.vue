<template>
  <div
    class="overflow-hidden rounded-3xl border-2 border-amber-500/65 bg-[#141008]/95 shadow-xl shadow-black/40 ring-1 ring-amber-400/35"
    role="region"
    :aria-label="t('driver_home.group_pending')"
  >
    <!-- Header -->
    <div class="px-4 pt-4 pb-3 sm:px-5 sm:pt-5">
      <template v-if="loading">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 shrink-0 animate-pulse rounded-xl bg-amber-500/20" />
          <div class="min-w-0 flex-1 space-y-2">
            <div class="h-6 w-52 max-w-full animate-pulse rounded-lg bg-amber-500/15" />
            <div class="h-5 w-36 animate-pulse rounded bg-amber-500/10" />
          </div>
        </div>
      </template>
      <template v-else>
        <div class="flex items-start gap-3 sm:gap-4">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 ring-1 ring-amber-400/35"
            aria-hidden="true"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-6 w-6 text-amber-200"
            >
              <path
                fill-rule="evenodd"
                d="M10 2a.75.75 0 0 1 .75.75v.258a33.186 33.186 0 0 1 9.665 5.571.75.75 0 0 1-.372 1.347A31.033 31.033 0 0 0 18 12c0 1.684.264 3.29.733 4.774a.75.75 0 0 1-.37 1.007 24.988 24.988 0 0 1-4.866 2.395.75.75 0 0 1-.82-.37 13.082 13.082 0 0 0-8.634-5.495.75.75 0 0 1-.74-.18 13.082 13.082 0 0 0-8.634 5.495.75.75 0 0 1-.82.37 24.983 24.983 0 0 1-4.865-2.396.75.75 0 0 1-.37-1.006A31.343 31.343 0 0 0 2 12c0-1.684.264-3.29.733-4.774a.75.75 0 0 1-.372-1.347 33.156 33.156 0 0 1 9.665-5.571V2.75A.75.75 0 0 1 10 2Zm-3.03 15.273a15.45 15.45 0 0 1 6.03 0 .762.762 0 0 1 .314.148.73.73 0 0 1 .272.323 22.005 22.005 0 0 1-6.59 1.077c-2.308 0-4.498-.466-6.59-1.077a.723.723 0 0 1 .586-1.47 15.445 15.445 0 0 1 6.03 0Z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-base font-extrabold leading-snug text-amber-50 sm:text-lg">
              {{ bannerTitle }}
            </p>
            <p v-if="pendingCount > 0" class="mt-1 text-sm font-medium text-amber-200/75 sm:text-base">
              {{ bannerSubline }}
            </p>
          </div>
        </div>

        <p
          v-if="actionError"
          class="mt-3 rounded-xl border border-rose-500/45 bg-rose-950/55 px-4 py-2.5 text-sm font-medium leading-snug text-rose-50 ring-1 ring-rose-600/25 sm:text-base"
          role="alert"
        >
          {{ actionError }}
        </p>
      </template>
    </div>

    <!-- Loading rows -->
    <div v-if="loading" class="space-y-3 border-t border-amber-500/25 px-4 py-4 sm:px-5">
      <div
        v-for="s in 2"
        :key="s"
        class="animate-pulse space-y-2 rounded-xl bg-amber-950/30 px-3 py-3.5"
      >
        <div class="flex gap-2">
          <div class="h-7 w-12 rounded bg-amber-500/15" />
          <div class="h-7 w-16 rounded bg-amber-500/10" />
          <div class="h-7 w-14 rounded bg-amber-500/12" />
        </div>
        <div class="h-6 w-28 rounded bg-amber-500/10" />
        <div class="h-4 w-full rounded bg-amber-500/10" />
        <div class="h-4 w-5/6 rounded bg-amber-500/08" />
        <div class="flex gap-2 pt-1">
          <div class="h-11 flex-1 rounded-lg bg-amber-500/15" />
          <div class="h-11 flex-1 rounded-lg bg-amber-500/12 sm:hidden" />
        </div>
      </div>
    </div>

    <!-- Danh sách chuyến — thẻ DriverTripCard + xác nhận / từ chối -->
    <div
      v-if="pendingCount > 0"
      :id="tripListId"
      class="space-y-3 border-t border-amber-500/25 px-4 py-4 sm:px-5"
      data-testid="pending-confirmation-trip-list"
    >
      <DriverTripCard
        v-for="row in displayRows"
        :key="row.rowKey"
        :trip="row.trip"
        layout="stacked"
        class="w-full min-w-0 max-w-none"
        suppress-card-navigation
        show-pending-actions
        :busy="isTripBusy(row.trip)"
        data-testid="pending-confirmation-trip-card"
        @confirm="onConfirm"
        @decline="openDeclineModal"
      />
    </div>

    <!-- Decline modal -->
    <Teleport to="body">
      <div
        v-if="declineModalOpen"
        class="fixed inset-0 z-[80] flex items-end justify-center bg-black/60 px-3 pb-[max(1rem,env(safe-area-inset-bottom))] pt-12 sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="decline-modal-title"
        @click.self="closeDeclineModal"
      >
        <div
          class="w-full max-w-md rounded-3xl bg-[#0f1816] p-5 shadow-2xl shadow-black/60"
          @click.stop
        >
          <h2 id="decline-modal-title" class="text-lg font-bold text-white">
            {{ modalTitle }}
          </h2>
          <p v-if="declineTrip" class="mt-2 text-sm text-[#7fdcc8]/80">
            {{ declineTripSummary }}
          </p>

          <template v-if="declineStep === 'reason'">
            <p class="mt-4 text-sm text-slate-400">
              {{ modalSubtitle }}
            </p>
            <label
              class="mt-3 block text-xs font-semibold uppercase tracking-wide text-[#7fdcc8]/70"
            >
              {{ modalReasonLabel }}
            </label>
            <textarea
              v-model="declineReason"
              rows="4"
              class="driver-field-ios mt-2 w-full resize-y rounded-xl border border-white/10 bg-[#070f0d] px-3 py-2.5 text-white placeholder:text-slate-600 focus:border-[#7fdcc8]/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/25"
              :placeholder="t('driver_home.pending_decline_reason_placeholder')"
              autocomplete="off"
            />
          </template>

          <template v-else>
            <p class="mt-4 text-sm font-semibold text-amber-200/95">
              {{ modalConfirmTitle }}
            </p>
            <p class="mt-1 text-sm text-slate-400">
              {{ modalConfirmHint }}
            </p>
            <div class="mt-3 rounded-xl bg-[#070f0d] px-3 py-2.5 text-sm text-slate-200">
              {{ declineReason.trim() }}
            </div>
          </template>

          <p v-if="declineModalError" class="mt-3 text-sm font-medium text-rose-300" role="alert">
            {{ declineModalError }}
          </p>

          <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
            <button
              type="button"
              class="order-last min-h-[48px] w-full rounded-xl bg-transparent px-4 text-sm font-semibold text-slate-400 hover:bg-white/5 sm:order-first sm:w-auto"
              :disabled="busyId != null"
              @click="declineStep === 'confirm' ? (declineStep = 'reason') : closeDeclineModal()"
            >
              {{
                declineStep === 'confirm'
                  ? t('driver_home.pending_decline_back')
                  : t('driver_home.pending_decline_cancel')
              }}
            </button>
            <button
              v-if="declineStep === 'reason'"
              type="button"
              class="min-h-[48px] w-full rounded-xl bg-[#7fdcc8] px-4 text-sm font-bold text-[#070f0d] shadow-md shadow-[#7fdcc8]/20 hover:bg-[#6bcfb8] active:scale-[0.99] sm:w-auto sm:min-w-[9rem]"
              :disabled="busyId != null"
              @click="goDeclineConfirmStep"
            >
              {{ t('driver_home.pending_decline_next') }}
            </button>
            <button
              v-else
              type="button"
              class="min-h-[48px] w-full rounded-xl bg-rose-600 px-4 text-sm font-bold text-white shadow-md hover:bg-rose-500 active:scale-[0.99] sm:w-auto sm:min-w-[9rem]"
              :disabled="busyId != null"
              @click="submitDeclineConfirmed"
            >
              {{ modalConfirmBtn }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatApiError } from '../../api/http'
import { isOptimisticLockConflict } from '../../util/tripLock'
import { driverTripListRowKey } from '../../util/driverScheduleLeg'
import { useDriverDashboardStore } from '../../store/driverDashboard'
import { formatDepartForTrip, tripOrigin } from '../../composables/useDriverTripDisplay'
import { tpDriverTripCanConfirm } from '../../composables/useTpDriverSlotActions'
import DriverTripCard from './DriverTripCard.vue'

let tripStatusCooldownUntil = 0

const DECLINE_REASON_MIN = 10

const props = defineProps({
  trips: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})

const dash = useDriverDashboardStore()

const { t, locale } = useI18n()

const busyId = ref(null)
const actionError = ref('')

const declineModalOpen = ref(false)
const declineTrip = ref(null)
const declineStep = ref('reason')
const declineReason = ref('')
const declineModalError = ref('')

function sortByDepart(list) {
  function departMs(t) {
    if (t?.depart_at) {
      const n = new Date(t.depart_at).getTime()
      return Number.isFinite(n) ? n : 0
    }
    if (t?.depart_date && /^\d{4}-\d{2}-\d{2}/.test(String(t.depart_date))) {
      const [y, m, d] = String(t.depart_date)
        .slice(0, 10)
        .split('-')
        .map(Number)
      return new Date(y, m - 1, d, 12, 0, 0, 0).getTime()
    }
    return 0
  }
  return list.slice().sort((a, b) => departMs(a) - departMs(b))
}

const tripRows = computed(() => {
  const list = Array.isArray(props.trips) ? props.trips : []
  return sortByDepart(list).map((trip, index) => {
    const baseKey = driverTripListRowKey(trip) || String(trip?.id ?? '')
    const rowKey = baseKey ? `${baseKey}::${index}` : `pending-row-${index}`
    return { trip, rowKey }
  })
})

const displayRows = computed(() => {
  const rows = tripRows.value
  if (rows.length > 0) return rows
  const list = Array.isArray(props.trips) ? props.trips : []
  return sortByDepart(list).map((trip, index) => ({
    trip,
    rowKey: `pending-fallback-${index}`,
  }))
})

function isTripBusy(trip) {
  if (busyId.value == null) return false
  const k = driverTripListRowKey(trip) || trip?.id
  return String(busyId.value) === String(k)
}

const tripListId = 'pending-confirmation-trip-list'

const pendingCount = computed(() => props.trips.length)

const pendingAllTp = computed(() => {
  const rows = props.trips
  if (!rows.length) return false
  return rows.every((tr) => tr?._tp?.day_id)
})

const bannerTitle = computed(() => {
  const n = pendingCount.value
  if (pendingAllTp.value) return t('driver_home.pending_banner_title_tp', { n })
  return t('driver_home.pending_banner_title', { n })
})

const bannerSubline = computed(() => {
  if (!props.trips.length) return ''
  if (pendingAllTp.value) return t('driver_home.pending_banner_sub_tp')
  return t('driver_home.pending_banner_sub')
})

const declineTripSummary = computed(() => {
  const tr = declineTrip.value
  if (!tr) return ''
  const tag = String(tr.trip_code ?? tr.id)
  const loc = locale.value === 'vi' ? 'vi' : 'en'
  const { time } = formatDepartForTrip(tr, loc)
  const origin = tripOrigin(tr)
  return `#${tag} · ${time} · ${origin}`
})

// Chuyến đưa đón định kì (TP) dùng luồng "báo bận"; chuyến REQ dùng luồng "từ chối".
const isBusyFlow = computed(() => declineTrip.value?._tp != null)
const modalTitle = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_title') : t('driver_home.pending_decline_title'),
)
const modalSubtitle = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_subtitle') : t('driver_home.pending_decline_subtitle'),
)
const modalReasonLabel = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_reason_label') : t('driver_home.pending_decline_reason_label'),
)
const modalConfirmTitle = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_confirm_title') : t('driver_home.pending_decline_confirm_title'),
)
const modalConfirmHint = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_confirm_hint') : t('driver_home.pending_decline_confirm_hint'),
)
const modalConfirmBtn = computed(() =>
  isBusyFlow.value ? t('driver_home.busy_confirm_btn') : t('driver_home.pending_decline_confirm_btn'),
)

function closeDeclineModal() {
  declineModalOpen.value = false
  declineTrip.value = null
  declineStep.value = 'reason'
  declineReason.value = ''
  declineModalError.value = ''
}

function openDeclineModal(trip) {
  if (busyId.value != null || Date.now() < tripStatusCooldownUntil) return
  declineTrip.value = trip
  declineReason.value = ''
  declineStep.value = 'reason'
  declineModalError.value = ''
  declineModalOpen.value = true
}

function goDeclineConfirmStep() {
  declineModalError.value = ''
  const s = declineReason.value.trim()
  if (s.length < DECLINE_REASON_MIN) {
    declineModalError.value = t('driver_home.pending_decline_reason_required', { n: DECLINE_REASON_MIN })
    return
  }
  declineStep.value = 'confirm'
}

async function submitDeclineConfirmed() {
  const trip = declineTrip.value
  if (!trip || busyId.value != null || Date.now() < tripStatusCooldownUntil) return
  busyId.value = driverTripListRowKey(trip) || trip.id
  actionError.value = ''
  declineModalError.value = ''
  try {
    if (trip._tp) {
      await dash.reportBusyTpDayOptimistic(trip, declineReason.value.trim())
    } else {
      await dash.declineTripOptimistic(trip, declineReason.value.trim())
    }
    closeDeclineModal()
  } catch (e) {
    declineModalError.value = isOptimisticLockConflict(e)
      ? t('driver_home.conflict_refreshed')
      : formatApiError(e)
    if (e?.response?.status === 429) {
      tripStatusCooldownUntil = Date.now() + 8000
    }
  } finally {
    busyId.value = null
  }
}

async function onConfirm(trip) {
  if (busyId.value != null || Date.now() < tripStatusCooldownUntil) return
  if (!tpDriverTripCanConfirm(trip)) return
  busyId.value = driverTripListRowKey(trip) || trip.id
  actionError.value = ''
  try {
    await dash.confirmTripOptimistic(trip)
  } catch (e) {
    // Store đã tự làm mới danh sách khi xung đột — báo nhẹ nhàng thay vì lỗi kỹ thuật.
    actionError.value = isOptimisticLockConflict(e)
      ? t('driver_home.conflict_refreshed')
      : formatApiError(e)
    if (e?.response?.status === 429) {
      tripStatusCooldownUntil = Date.now() + 8000
    }
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
</style>
