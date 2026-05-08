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
        <div class="flex items-start gap-4">
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
              <template v-if="pendingCount > 0">
                {{ t('driver_home.pending_banner_title', { n: pendingCount }) }}
              </template>
              <template v-else>
                {{ t('driver_home.pending_banner_confirmed', { n: confirmedCount }) }}
              </template>
            </p>
            <p v-if="pendingCount > 0" class="mt-1 text-sm font-medium text-amber-200/75 sm:text-base">
              {{ t('driver_home.pending_banner_sub') }}
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
    <div v-if="loading" class="space-y-2 border-t border-amber-500/25 px-4 py-4 sm:px-5">
      <div v-for="s in 2" :key="s" class="flex animate-pulse gap-2 rounded-lg bg-amber-950/30 py-3.5 pl-2 pr-2">
        <div class="h-7 w-10 rounded bg-amber-500/15" />
        <div class="h-7 w-14 rounded bg-amber-500/10" />
        <div class="min-w-0 flex-1 rounded bg-amber-500/10" />
        <div class="h-10 w-24 shrink-0 rounded-lg bg-amber-500/15" />
      </div>
    </div>

    <!-- Trip rows -->
    <ul
      v-else
      class="divide-y divide-amber-500/20 border-t border-amber-500/25"
    >
      <li
        v-for="trip in sortedPending"
        :key="`p-${trip.id}`"
        class="flex flex-wrap items-center gap-x-2 gap-y-2 px-4 py-3.5 sm:px-5"
      >
        <span
          class="inline-flex shrink-0 rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide sm:text-sm"
          :class="tripTypeBadgeClass(trip)"
        >
          {{ tripTypeShortLabel(trip) }}
        </span>
        <span class="shrink-0 text-sm font-bold tabular-nums text-white sm:text-base">
          {{ departTime(trip) }}
        </span>
        <span class="min-w-0 flex-1 basis-[8rem] truncate text-sm font-medium text-[#e2e8f0] sm:text-base">
          {{ tripDestination(trip) }}
        </span>
        <div class="ml-auto flex shrink-0 items-center gap-2">
          <button
            type="button"
            class="inline-flex min-h-[48px] min-w-[44px] items-center justify-center rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white shadow shadow-black/30 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="busyId != null"
            @click="onConfirm(trip)"
          >
            {{ t('driver_home.btn_confirm') }}
          </button>
          <button
            type="button"
            class="inline-flex min-h-[48px] min-w-[44px] items-center justify-center rounded-lg border border-rose-400/60 bg-rose-950/40 px-3 text-sm font-bold text-rose-200 transition hover:bg-rose-900/50 disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="busyId != null"
            @click="openDeclineModal(trip)"
          >
            {{ t('driver_home.btn_decline') }}
          </button>
        </div>
      </li>
      <li
        v-for="trip in sortedConfirmed"
        :key="`c-${trip.id}`"
        class="flex flex-wrap items-center gap-x-2 gap-y-2 px-4 py-3.5 sm:px-5"
      >
        <span
          class="inline-flex shrink-0 rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide sm:text-sm"
          :class="tripTypeBadgeClass(trip)"
        >
          {{ tripTypeShortLabel(trip) }}
        </span>
        <span class="shrink-0 text-sm font-bold tabular-nums text-white sm:text-base">
          {{ departTime(trip) }}
        </span>
        <span class="min-w-0 flex-1 basis-[8rem] truncate text-sm font-medium text-[#e2e8f0] sm:text-base">
          {{ tripDestination(trip) }}
        </span>
        <div class="ml-auto flex shrink-0">
          <button
            type="button"
            class="inline-flex min-h-[48px] min-w-[44px] items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-bold text-white shadow shadow-black/30 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="busyId != null"
            @click="onStartTrip(trip.id)"
          >
            {{ t('driver_home.card_start') }}
          </button>
        </div>
      </li>
    </ul>

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
          class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-[#0f1816] p-5 shadow-2xl shadow-black/60"
          @click.stop
        >
          <h2 id="decline-modal-title" class="text-lg font-bold text-white">
            {{ t('driver_home.pending_decline_title') }}
          </h2>
          <p v-if="declineTrip" class="mt-2 text-sm text-[#7fdcc8]/80">
            {{ declineTripSummary }}
          </p>

          <template v-if="declineStep === 'reason'">
            <p class="mt-4 text-sm text-slate-400">
              {{ t('driver_home.pending_decline_subtitle') }}
            </p>
            <label
              class="mt-3 block text-xs font-semibold uppercase tracking-wide text-[#7fdcc8]/70"
            >
              {{ t('driver_home.pending_decline_reason_label') }}
            </label>
            <textarea
              v-model="declineReason"
              rows="4"
              class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-[#070f0d] px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-[#7fdcc8]/50 focus:outline-none focus:ring-2 focus:ring-[#7fdcc8]/25"
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
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatApiError } from '../../api/http'
import { updateTripStatus } from '../../api/trips'
import {
  formatDepartForTrip,
  tripDestination,
  tripOrigin,
} from '../../composables/useDriverTripDisplay'

let tripStatusCooldownUntil = 0

const DECLINE_REASON_MIN = 10

const props = defineProps({
  trips: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['updated'])

const { t, locale } = useI18n()

const busyId = ref(null)
const actionError = ref('')

const declineModalOpen = ref(false)
const declineTrip = ref(null)
const declineStep = ref('reason')
const declineReason = ref('')
const declineModalError = ref('')

function tripStatusNorm(x) {
  return String(x?.status ?? '').trim().toLowerCase()
}

function bucketFor(trip) {
  if (tripStatusNorm(trip) === 'driver_confirmed') return 'confirmed'
  return 'pending'
}

const pendingBucket = computed(() => props.trips.filter((x) => bucketFor(x) === 'pending'))
const confirmedBucket = computed(() => props.trips.filter((x) => bucketFor(x) === 'confirmed'))

function sortByDepart(list) {
  return list
    .slice()
    .sort((a, b) => (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0))
}

const sortedPending = computed(() => sortByDepart(pendingBucket.value))
const sortedConfirmed = computed(() => sortByDepart(confirmedBucket.value))

const pendingCount = computed(() => pendingBucket.value.length)
const confirmedCount = computed(() => confirmedBucket.value.length)

const declineTripSummary = computed(() => {
  const tr = declineTrip.value
  if (!tr) return ''
  const tag = String(tr.trip_code ?? tr.id)
  const loc = locale.value === 'vi' ? 'vi' : 'en'
  const { time } = formatDepartForTrip(tr, loc)
  const origin = tripOrigin(tr)
  return `#${tag} · ${time} · ${origin}`
})

function tripTypeShortLabel(trip) {
  const tt = trip?.dispatch_request?.trip_type
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return 'CT'
  if (tt === 'cargo') return 'HH'
  const raw = (trip?.type || '').toString()
  const up = raw.toUpperCase()
  if (['P2P', 'D2D', 'CT', 'HH', 'CG'].includes(up)) return up === 'CG' ? 'HH' : up
  return raw.length <= 3 && raw ? raw.toUpperCase() : '—'
}

function tripTypeBadgeClass(trip) {
  const lbl = tripTypeShortLabel(trip)
  if (lbl === 'P2P') return 'bg-blue-500/25 text-blue-200 ring-1 ring-blue-400/30'
  if (lbl === 'D2D') return 'bg-violet-500/25 text-violet-200 ring-1 ring-violet-400/30'
  if (lbl === 'CT') return 'bg-amber-500/25 text-amber-100 ring-1 ring-amber-400/35'
  if (lbl === 'HH') return 'bg-orange-500/25 text-orange-100 ring-1 ring-orange-400/35'
  return 'bg-slate-500/20 text-slate-200 ring-1 ring-slate-400/25'
}

function departTime(trip) {
  const loc = locale.value === 'vi' ? 'vi' : 'en'
  return formatDepartForTrip(trip, loc).time
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
    await updateTripStatus(trip.id, { status: 'cancelled', message: declineReason.value.trim() })
    closeDeclineModal()
    emit('updated')
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
    await updateTripStatus(trip.id, { status: 'driver_confirmed' })
    emit('updated')
  } catch (e) {
    actionError.value = formatApiError(e)
    if (e?.response?.status === 429) {
      tripStatusCooldownUntil = Date.now() + 8000
    }
  } finally {
    busyId.value = null
  }
}

async function onStartTrip(tripId) {
  if (tripId == null || busyId.value != null || Date.now() < tripStatusCooldownUntil) return
  busyId.value = tripId
  actionError.value = ''
  try {
    await updateTripStatus(tripId, { status: 'in_progress' })
    emit('updated')
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
