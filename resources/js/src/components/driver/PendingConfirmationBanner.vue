<template>
  <div class="rounded-3xl bg-[#0f1816] shadow-xl shadow-black/30">
    <!-- Header -->
    <div class="border-b border-[#7fdcc8]/12 px-4 py-4 sm:px-5">
      <div class="flex items-start gap-3">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#7fdcc8]/12">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-7 w-7 text-[#7fdcc8]" aria-hidden="true">
            <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z" />
          </svg>
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-xl font-bold leading-snug text-white sm:text-2xl">
            {{ t('driver_home.pending_banner_title', { n: trips.length }) }}
          </p>
          <p
            v-if="hasUrgent && !loading"
            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-950/70 px-3 py-1.5 text-sm font-bold uppercase tracking-wide text-rose-100 ring-1 ring-rose-600/40"
          >
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400/90" />
            {{ t('driver_home.pending_urgent_hint') }}
          </p>
          <p
            v-if="actionError"
            class="mt-3 rounded-xl border border-rose-500/45 bg-rose-950/55 px-3 py-2 text-sm font-medium leading-snug text-rose-50 ring-1 ring-rose-600/25"
            role="alert"
          >
            {{ actionError }}
          </p>
        </div>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4 px-4 py-4 sm:px-5">
      <div v-for="s in 3" :key="s" class="rounded-2xl bg-[#070f0d]/80 p-4">
        <div class="h-5 w-40 animate-pulse rounded bg-[#7fdcc8]/12" />
        <div class="mt-4 space-y-3">
          <div class="h-24 animate-pulse rounded-xl bg-[#7fdcc8]/8" />
          <div class="h-24 animate-pulse rounded-xl bg-[#7fdcc8]/8" />
        </div>
      </div>
    </div>

    <div v-else class="divide-y divide-[#7fdcc8]/12">
      <TripGroupSection
        :title="t('driver_home.group_urgent')"
        :trips="sortedUrgent"
        :open="open.urgent"
        :busy-id="busyId"
        @toggle="toggle('urgent')"
        @confirm="onConfirm"
        @decline="openDeclineModal"
      />
      <TripGroupSection
        :title="t('driver_home.group_upcoming')"
        :trips="sortedUpcoming"
        :open="open.upcoming"
        :busy-id="busyId"
        @toggle="toggle('upcoming')"
        @confirm="onConfirm"
        @decline="openDeclineModal"
      />
      <TripGroupSection
        :title="t('driver_home.group_confirmed')"
        :trips="sortedConfirmed"
        :open="open.confirmed"
        :busy-id="busyId"
        @toggle="toggle('confirmed')"
        @confirm="onConfirm"
        @decline="openDeclineModal"
      />
    </div>

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
            <label class="mt-3 block text-xs font-semibold uppercase tracking-wide text-[#7fdcc8]/70">
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
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatApiError } from '../../api/http'
import { updateTripStatus } from '../../api/trips'
import { formatDepartForTrip, isTripUrgent, tripOrigin } from '../../composables/useDriverTripDisplay'
import TripGroupSection from './TripGroupSection.vue'

/** Tránh spam POST /trips/:id/status khi server trả 429 (throttle). */
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

/** Sau khi user chỉnh collapse, không ép auto-collapse lại khi danh sách đổi nhẹ */
let userTouchedCollapse = false

function tripStatusNorm(x) {
  return String(x?.status ?? '').trim().toLowerCase()
}

function bucketFor(trip) {
  if (isTripUrgent(trip)) return 'urgent'
  const s = tripStatusNorm(trip)
  if (s === 'driver_confirmed') return 'confirmed'
  return 'upcoming'
}

const urgentTrips = computed(() => props.trips.filter((x) => bucketFor(x) === 'urgent'))
const upcomingTrips = computed(() => props.trips.filter((x) => bucketFor(x) === 'upcoming'))
const confirmedTrips = computed(() => props.trips.filter((x) => bucketFor(x) === 'confirmed'))

function sortByDepart(list) {
  return list
    .slice()
    .sort((a, b) => (new Date(a.depart_at).getTime() || 0) - (new Date(b.depart_at).getTime() || 0))
}

const sortedUrgent = computed(() => sortByDepart(urgentTrips.value))
const sortedUpcoming = computed(() => sortByDepart(upcomingTrips.value))
const sortedConfirmed = computed(() => sortByDepart(confirmedTrips.value))

const hasUrgent = computed(() => urgentTrips.value.length > 0)

const declineTripSummary = computed(() => {
  const tr = declineTrip.value
  if (!tr) return ''
  const tag = String(tr.trip_code ?? tr.id)
  const loc = locale.value === 'vi' ? 'vi' : 'en'
  const { time } = formatDepartForTrip(tr, loc)
  const origin = tripOrigin(tr)
  return `#${tag} · ${time} · ${origin}`
})

const open = reactive({
  urgent: true,
  upcoming: true,
  confirmed: true,
})

function applyAutoCollapse() {
  if (userTouchedCollapse) return
  const n = props.trips.length
  if (n > 10) {
    open.upcoming = false
    open.confirmed = false
    open.urgent = true
  }
}

watch(
  () => props.loading,
  (isLoading) => {
    if (!isLoading) applyAutoCollapse()
  },
  { immediate: true },
)

function toggle(key) {
  userTouchedCollapse = true
  open[key] = !open[key]
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
  if (busyId.value || Date.now() < tripStatusCooldownUntil) return
  busyId.value = trip.id
  actionError.value = ''
  try {
    await updateTripStatus(trip.id, { status: 'in_progress' })
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
