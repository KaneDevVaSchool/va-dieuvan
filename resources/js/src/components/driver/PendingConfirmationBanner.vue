<template>
  <div class="overflow-hidden rounded-3xl bg-[#0f1816] shadow-xl shadow-black/30">
    <!-- Header: operation-style count display -->
    <div class="px-5 pt-6 pb-5">
      <template v-if="loading">
        <div class="flex items-center gap-4">
          <div class="h-14 w-14 animate-pulse rounded-2xl bg-[#7fdcc8]/10" />
          <div class="flex-1 space-y-2">
            <div class="h-8 w-36 animate-pulse rounded-xl bg-[#7fdcc8]/10" />
            <div class="h-5 w-28 animate-pulse rounded-lg bg-[#7fdcc8]/8" />
          </div>
        </div>
      </template>
      <template v-else>
        <div class="flex items-start gap-4">
          <!-- Truck icon -->
          <div
            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#7fdcc8]/10 ring-1 ring-[#7fdcc8]/18"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-8 w-8 text-[#7fdcc8]"
              aria-hidden="true"
            >
              <path d="M6.5 3c-1.051 0-2.093.04-3.125.117A1.49 1.49 0 0 0 2 4.607V10.5h9V4.607c0-.727-.57-1.44-1.375-1.49A41.568 41.568 0 0 0 6.5 3ZM2 12v2.5A1.5 1.5 0 0 0 3.5 16h.041a3 3 0 0 1 5.918 0h.791a.75.75 0 0 0 .75-.75V12H2Z" />
              <path d="M6.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM13.25 5a.75.75 0 0 0-.75.75v8.514a3.001 3.001 0 0 1 4.893 1.486c.077-.111.157-.22.237-.328.075-.103.119-.22.119-.344V10.5a1.5 1.5 0 0 0-.265-.848l-2.154-3.23A1.5 1.5 0 0 0 14.115 6h-.865ZM14.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            </svg>
          </div>

          <!-- Count text -->
          <div class="min-w-0 flex-1">
            <p class="truncate text-xl font-extrabold leading-snug text-white">
              {{ t('driver_home.pending_banner_title', { n: pendingCount }) }}
            </p>
            <p
              v-if="confirmedCount > 0"
              class="mt-2.5 flex items-center gap-1.5 text-base font-semibold text-emerald-400/80"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-4 w-4 shrink-0"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                  clip-rule="evenodd"
                />
              </svg>
              {{ t('driver_home.pending_banner_confirmed', { n: confirmedCount }) }}
            </p>
          </div>
        </div>

        <p
          v-if="actionError"
          class="mt-4 rounded-xl border border-rose-500/45 bg-rose-950/55 px-4 py-3 text-sm font-medium leading-snug text-rose-50 ring-1 ring-rose-600/25"
          role="alert"
        >
          {{ actionError }}
        </p>
      </template>
    </div>

    <!-- Loading skeleton for trip groups -->
    <div v-if="loading" class="space-y-3 px-5 pb-5">
      <div v-for="s in 2" :key="s" class="rounded-2xl bg-[#070f0d]/80 p-4">
        <div class="h-5 w-32 animate-pulse rounded bg-[#7fdcc8]/12" />
        <div class="mt-3 h-28 animate-pulse rounded-xl bg-[#7fdcc8]/8" />
      </div>
    </div>

    <!-- Two sections: pending + confirmed -->
    <div v-else class="divide-y divide-[#7fdcc8]/10 border-t border-[#7fdcc8]/10">
      <TripGroupSection
        :title="t('driver_home.group_pending')"
        :trips="sortedPending"
        :open="open.pending"
        :busy-id="busyId"
        accent="mint"
        show-pending-actions
        @toggle="toggle('pending')"
        @confirm="onConfirm"
        @decline="openDeclineModal"
      />
      <TripGroupSection
        :title="t('driver_home.group_confirmed')"
        :trips="sortedConfirmed"
        :open="open.confirmed"
        :busy-id="busyId"
        accent="emerald"
        @toggle="toggle('confirmed')"
        @start="onStartTrip"
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
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatApiError } from '../../api/http'
import { updateTripStatus } from '../../api/trips'
import { formatDepartForTrip, tripOrigin } from '../../composables/useDriverTripDisplay'
import TripGroupSection from './TripGroupSection.vue'

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

let userTouchedCollapse = false

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

const open = reactive({ pending: true, confirmed: true })

function applyAutoCollapse() {
  if (userTouchedCollapse) return
  if (props.trips.length > 10) {
    open.pending = true
    open.confirmed = false
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
