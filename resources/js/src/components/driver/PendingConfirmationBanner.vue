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
              {{ t('driver_home.pending_banner_title', { n: pendingCount }) }}
            </p>
            <p v-if="pendingCount > 0" class="mt-1 text-sm font-medium text-amber-200/75 sm:text-base">
              {{ bannerSubline }}
            </p>
            <p
              v-if="!listExpanded && hasTripRows"
              class="mt-1.5 text-xs font-medium text-amber-300/70 sm:text-sm"
            >
              {{ t('driver_home.pending_collapsed_count', { n: bannerRows.length }) }}
            </p>
          </div>
          <button
            v-if="hasTripRows"
            type="button"
            class="-mr-1 flex h-11 min-w-[44px] shrink-0 items-center justify-center rounded-xl text-amber-200/90 ring-1 ring-amber-500/30 transition hover:bg-amber-500/15 hover:text-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
            :aria-expanded="listExpanded"
            :aria-controls="tripListId"
            @click="toggleListExpanded"
          >
            <ChevronDownIcon v-if="!listExpanded" class="h-6 w-6" aria-hidden="true" />
            <ChevronUpIcon v-else class="h-6 w-6" aria-hidden="true" />
            <span class="sr-only">
              {{
                listExpanded
                  ? t('driver_home.pending_banner_collapse_list')
                  : t('driver_home.pending_banner_expand_list')
              }}
            </span>
          </button>
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

    <!-- Trip rows -->
    <div
      v-else
      v-show="listExpanded"
      class="border-t border-amber-500/25"
    >
      <TransitionGroup
        :id="tripListId"
        name="pending-banner"
        tag="ul"
        class="divide-y divide-amber-500/20"
      >
        <li
          v-for="{ trip, dateLine } in bannerRows"
          :key="trip.id"
          class="pending-banner-item flex flex-col gap-3 px-4 py-3.5 sm:px-5"
        >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
          <!-- Summary + toggle chi tiết từng chuyến -->
          <div class="min-w-0 flex-1">
            <button
              type="button"
              class="flex w-full min-w-0 items-start gap-2 rounded-xl py-0.5 text-left transition hover:bg-amber-950/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/50 sm:-mx-1 sm:px-1"
              :aria-expanded="isItemDetailsOpen(trip.id)"
              :aria-controls="itemDetailsDomId(trip.id)"
              @click="toggleItemDetails(trip.id)"
            >
              <div class="min-w-0 flex-1 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex shrink-0 rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide sm:text-sm"
                    :class="tripTypeBadgeClass(trip)"
                  >
                    {{ tripTypeBadgeText(trip) }}
                  </span>
                  <span class="text-sm font-semibold text-slate-400">
                    {{ tripCodeDisplay(trip) }}
                  </span>
                  <span
                    v-if="isTripUrgent(trip)"
                    class="inline-flex shrink-0 rounded-md bg-rose-600/90 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white ring-1 ring-rose-400/40"
                  >
                    {{ t('driver_home.urgent_badge') }}
                  </span>
                </div>

                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                  <span class="text-lg font-bold tabular-nums leading-none text-white sm:text-xl">
                    {{ departOrRange(trip) }}
                  </span>
                  <span
                    v-if="dateLine"
                    class="text-xs font-medium text-slate-400 sm:text-sm"
                  >
                    {{ dateLine }}
                  </span>
                </div>
              </div>
              <span
                class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-amber-200/90 ring-1 ring-amber-500/25 sm:mt-0"
                aria-hidden="true"
              >
                <ChevronDownIcon
                  class="h-5 w-5 transition-transform duration-200"
                  :class="isItemDetailsOpen(trip.id) ? 'rotate-180' : ''"
                />
              </span>
              <span class="sr-only">
                {{
                  isItemDetailsOpen(trip.id)
                    ? t('driver_home.pending_item_collapse_details')
                    : t('driver_home.pending_item_expand_details')
                }}
              </span>
            </button>

            <div
              :id="itemDetailsDomId(trip.id)"
              v-show="isItemDetailsOpen(trip.id)"
              class="space-y-0.5 border-t border-amber-500/15 pt-3 mt-2"
              role="region"
            >
              <div class="flex min-w-0 items-start gap-2">
                <span class="mt-0.5 shrink-0 text-[10px] leading-none text-emerald-400" aria-hidden="true">●</span>
                <div class="min-w-0 flex-1">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    {{ t('driver_home.pending_pickup') }}
                  </p>
                  <p class="break-words text-[13px] font-medium leading-snug text-[#e2e8f0]">
                    {{ tripOrigin(trip) }}
                  </p>
                </div>
              </div>
              <div class="ml-[5px] h-3 w-px shrink-0 bg-white/15" aria-hidden="true" />
              <div class="flex min-w-0 items-start gap-2">
                <span class="mt-0.5 shrink-0 text-[10px] leading-none text-rose-400" aria-hidden="true">●</span>
                <div class="min-w-0 flex-1">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    {{ t('driver_home.pending_dropoff') }}
                  </p>
                  <p class="break-words text-[13px] font-medium leading-snug text-[#e2e8f0]">
                    {{ tripDestination(trip) }}
                  </p>
                </div>
              </div>

              <p v-if="bannerMetaLine(trip)" class="pt-1 text-xs leading-snug text-slate-400">
                {{ bannerMetaLine(trip) }}
              </p>
            </div>
          </div>

          <div
            class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:pt-0.5"
          >
            <div
              class="grid min-h-[48px] w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:gap-2"
            >
              <button
                type="button"
                class="inline-flex min-h-[48px] min-w-0 flex-1 items-center justify-center rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white shadow shadow-black/30 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-45 sm:min-w-[7rem]"
                :disabled="busyId != null"
                @click="onConfirm(trip)"
              >
                {{ t('driver_home.btn_confirm') }}
              </button>
              <button
                type="button"
                class="inline-flex min-h-[48px] min-w-0 flex-1 items-center justify-center rounded-lg border border-rose-400/60 bg-rose-950/40 px-3 text-sm font-bold text-rose-200 transition hover:bg-rose-900/50 disabled:cursor-not-allowed disabled:opacity-45 sm:min-w-[7rem]"
                :disabled="busyId != null"
                @click="openDeclineModal(trip)"
              >
                {{ trip._tp ? t('driver_home.btn_busy') : t('driver_home.btn_decline') }}
              </button>
            </div>
          </div>
        </div>
        </li>
      </TransitionGroup>
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
              {{ t('driver_home.pending_decline_confirm_title') }}
            </p>
            <p class="mt-1 text-sm text-slate-400">
              {{ t('driver_home.pending_decline_confirm_hint') }}
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
              {{ t('driver_home.pending_decline_confirm_btn') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, TransitionGroup } from 'vue'
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { formatApiError } from '../../api/http'
import { useDriverDashboardStore } from '../../store/driverDashboard'
import {
  formatDepartForTrip,
  isTripUrgent,
  tripDestination,
  tripOrigin,
  tripOutboundInboundTimeRange,
  tripPassengerLine,
  tripRequesterLine,
  tripTypeBadgeClass,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'

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

const sortedPending = computed(() => sortByDepart(props.trips))

const bannerRows = computed(() => {
  const tag = locale.value === 'vi' ? 'vi' : 'en'
  return sortedPending.value.map((trip) => ({
    trip,
    dateLine: formatDepartForTrip(trip, tag).dateLine,
  }))
})

const tripListId = 'pending-confirmation-trip-list'
const listExpanded = ref(true)

const hasTripRows = computed(() => bannerRows.value.length > 0)

function toggleListExpanded() {
  listExpanded.value = !listExpanded.value
}

const itemDetailsExpanded = ref(/** @type Record<string, boolean> */ ({}))

function itemDetailsDomId(tripId) {
  return `pending-trip-details-${tripId}`
}

function isItemDetailsOpen(tripId) {
  const k = String(tripId)
  return itemDetailsExpanded.value[k] === true
}

function toggleItemDetails(tripId) {
  const k = String(tripId)
  const nextOpen = !itemDetailsExpanded.value[k]
  itemDetailsExpanded.value = { ...itemDetailsExpanded.value, [k]: nextOpen }
}

const pendingCount = computed(() => props.trips.length)

const bannerSubline = computed(() => {
  const rows = props.trips
  if (!rows.length) return ''
  const allTp = rows.every((tr) => tr?._tp?.day_id)
  if (allTp) return t('driver_home.pending_banner_sub_tp')
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

function tripCodeDisplay(trip) {
  return trip?.trip_number || `#${trip?.id ?? ''}`
}

function localeTag() {
  return locale.value === 'vi' ? 'vi' : 'en'
}

function departOrRange(trip) {
  const tag = localeTag()
  const range = tripOutboundInboundTimeRange(trip, tag, t)
  if (range) return range
  return formatDepartForTrip(trip, tag).time
}

function bannerMetaLine(trip) {
  const parts = []
  const pl = tripPassengerLine(trip, t)
  if (pl) parts.push(pl)
  const rq = tripRequesterLine(trip)
  if (rq) parts.push(t('driver_home.pending_requester', { name: rq }))
  return parts.join(' · ')
}

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
  busyId.value = trip.id
  actionError.value = ''
  declineModalError.value = ''
  try {
    await dash.declineTripOptimistic(trip, declineReason.value.trim())
    closeDeclineModal()
  } catch (e) {
    declineModalError.value = formatApiError(e)
    if (e?.response?.status === 429) {
      tripStatusCooldownUntil = Date.now() + 8000
    }
  } finally {
    busyId.value = null
  }
}

async function onConfirm(trip) {
  if (busyId.value != null || Date.now() < tripStatusCooldownUntil) return
  busyId.value = trip.id
  actionError.value = ''
  try {
    await dash.confirmTripOptimistic(trip)
  } catch (e) {
    actionError.value = formatApiError(e)
    if (e?.response?.status === 429) {
      tripStatusCooldownUntil = Date.now() + 8000
    }
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
.pending-banner-item {
  will-change: transform, opacity;
}
.pending-banner-enter-active,
.pending-banner-leave-active {
  transition: opacity 0.22s ease-out, transform 0.22s ease-out;
}
.pending-banner-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}
.pending-banner-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
.pending-banner-move {
  transition: transform 0.22s ease-out;
}
</style>
