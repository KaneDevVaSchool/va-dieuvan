<template>
  <article class="overflow-hidden rounded-2xl bg-[#0a1c1a] ring-1 ring-[#7fdcc8]/15 shadow-lg shadow-black/25">
    <!-- Top bar: service badge + full name + code + confirmed chip -->
    <div
      class="flex items-center gap-2.5 border-b border-[#7fdcc8]/10 bg-[#061210]/70 px-5 py-3"
    >
      <span
        class="shrink-0 rounded-lg bg-[#7fdcc8]/15 px-2.5 py-1 text-sm font-extrabold uppercase tracking-wide text-[#7fdcc8]"
      >
        {{ tripTypeBadgeText(trip) }}
      </span>
      <span v-if="serviceFullName" class="min-w-0 flex-1 truncate text-sm font-semibold text-white/65">
        {{ serviceFullName }}
      </span>
      <span class="shrink-0 text-sm font-bold tabular-nums text-white/40">
        {{ tripRef }}
      </span>
      <span
        v-if="isConfirmed"
        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500/12 px-2.5 py-1 text-sm font-bold text-emerald-300 ring-1 ring-emerald-500/25"
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
        {{ t('driver_home.status_confirmed') }}
      </span>
    </div>

    <!-- Main body -->
    <div class="px-5 py-5">
      <!-- Pickup time — horizontal: big time left, label+date right -->
      <div class="flex items-end gap-3">
        <p class="shrink-0 text-4xl font-extrabold tabular-nums leading-none tracking-tight text-white">
          {{ depart.time }}
        </p>
        <div class="min-w-0 flex-1 pb-0.5">
          <p class="text-[0.65rem] font-bold uppercase tracking-widest text-[#7fdcc8]/50">
            {{ t('driver_home.pending_pickup_time_label') }}
          </p>
          <p v-if="depart.dateLine" class="mt-0.5 truncate text-sm font-medium text-[#7fdcc8]/70">
            {{ depart.dateLine }}
          </p>
        </div>
      </div>

      <!-- Origin / Destination -->
      <div class="mt-5 space-y-3.5">
        <div class="flex items-start gap-3.5">
          <span
            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/15"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-5 w-5 text-emerald-400"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 0 0 .315-.142c.088-.027.195-.077.315-.142z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-wide text-[#7fdcc8]/50">
              {{ t('driver_home.pending_pickup') }}
            </p>
            <p class="mt-0.5 line-clamp-2 text-xl font-bold leading-snug text-white">
              {{ tripOrigin(trip) }}
            </p>
          </div>
        </div>

        <div class="flex items-start gap-3.5">
          <span
            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-500/15"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-5 w-5 text-sky-400"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 0 0 .315-.142c.088-.027.195-.077.315-.142z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-wide text-[#7fdcc8]/50">
              {{ t('driver_home.pending_dropoff') }}
            </p>
            <p class="mt-0.5 line-clamp-2 text-xl font-bold leading-snug text-white">
              {{ tripDestination(trip) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Additional info rows -->
      <div class="mt-4 space-y-2.5">
        <!-- Requester -->
        <div v-if="requester" class="flex items-center gap-2.5">
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/50"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-4 w-4 text-slate-300"
              aria-hidden="true"
            >
              <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
            </svg>
          </div>
          <span class="text-base font-semibold text-white/75">
            {{ t('driver_home.pending_requester', { name: requester }) }}
          </span>
        </div>

        <!-- Vehicle -->
        <div v-if="vehicleLine" class="flex items-center gap-2.5">
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/50"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-4 w-4 text-slate-300"
              aria-hidden="true"
            >
              <path d="M6.5 3c-1.051 0-2.093.04-3.125.117A1.49 1.49 0 0 0 2 4.607V10.5h9V4.607c0-.727-.57-1.44-1.375-1.49A41.568 41.568 0 0 0 6.5 3ZM2 12v2.5A1.5 1.5 0 0 0 3.5 16h.041a3 3 0 0 1 5.918 0h.791a.75.75 0 0 0 .75-.75V12H2Z" />
              <path d="M6.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM13.25 5a.75.75 0 0 0-.75.75v8.514a3.001 3.001 0 0 1 4.893 1.486c.077-.111.157-.22.237-.328.075-.103.119-.22.119-.344V10.5a1.5 1.5 0 0 0-.265-.848l-2.154-3.23A1.5 1.5 0 0 0 14.115 6h-.865ZM14.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
            </svg>
          </div>
          <span class="text-base font-semibold text-white/75">{{ vehicleLine }}</span>
        </div>

        <!-- Passengers -->
        <div v-if="passengerLine" class="flex items-center gap-2.5">
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/50"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-4 w-4 text-slate-300"
              aria-hidden="true"
            >
              <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM14.5 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 0 0-1.588-3.755 4.502 4.502 0 0 1 5.874 2.636.818.818 0 0 1-.36.98A7.465 7.465 0 0 1 14.5 16Z" />
            </svg>
          </div>
          <span class="text-base font-semibold text-white/75">{{ passengerLine }}</span>
        </div>

        <!-- Distance -->
        <div v-if="distanceLine" class="flex items-center gap-2.5">
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/50"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-4 w-4 text-slate-300"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 0 0 .315-.142c.088-.027.195-.077.315-.142z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <span class="text-base font-semibold text-white/75">{{ distanceLine }}</span>
        </div>

        <!-- Notes -->
        <div
          v-if="notesLine"
          class="mt-1 rounded-xl bg-[#070f0d]/70 px-4 py-3 ring-1 ring-white/5"
        >
          <p class="text-[0.65rem] font-bold uppercase tracking-widest text-[#7fdcc8]/50">
            {{ t('driver_home.pending_notes_label') }}
          </p>
          <p class="mt-1 line-clamp-3 text-base font-medium leading-snug text-white/80">
            {{ notesLine }}
          </p>
        </div>
      </div>

      <!-- CTA buttons (pending only) -->
      <div v-if="!isConfirmed" class="mt-5 flex flex-row gap-2.5">
        <button
          type="button"
          :disabled="busy"
          class="flex min-h-[60px] flex-1 items-center justify-center gap-2.5 rounded-2xl bg-[#7fdcc8] px-4 text-lg font-extrabold text-[#070f0d] shadow-md shadow-[#7fdcc8]/25 transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
          @click="openConfirmModal"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-5 w-5 shrink-0"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
              clip-rule="evenodd"
            />
          </svg>
          {{ t('driver_home.btn_confirm') }}
        </button>
        <button
          type="button"
          :disabled="busy"
          class="flex min-h-[60px] flex-1 items-center justify-center gap-2.5 rounded-2xl border border-[#7fdcc8]/35 bg-transparent px-4 text-lg font-bold text-[#7fdcc8]/85 transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
          @click="$emit('decline', trip)"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-5 w-5 shrink-0"
            aria-hidden="true"
          >
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
          {{ t('driver_home.btn_decline') }}
        </button>
      </div>

      <!-- View detail link -->
      <RouterLink
        :to="`/driver/trips/${trip.id}`"
        class="mt-3 flex w-full min-h-[52px] items-center justify-center gap-2 rounded-xl py-2 text-base font-semibold text-[#7fdcc8]/75 transition hover:text-[#7fdcc8] active:opacity-80"
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
            d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
            clip-rule="evenodd"
          />
        </svg>
        {{ t('driver_home.pending_view_detail') }}
      </RouterLink>
    </div>
  </article>

  <!-- Confirm modal -->
  <Teleport to="body">
    <div
      v-if="confirmModalOpen"
      class="fixed inset-0 z-[80] flex items-end justify-center bg-black/60 px-3 pb-[max(1rem,env(safe-area-inset-bottom))] pt-12 sm:items-center sm:p-6"
      @click.self="confirmModalOpen = false"
    >
      <div
        class="w-full max-w-md overflow-hidden rounded-3xl bg-[#0f1816] shadow-2xl shadow-black/60"
        @click.stop
      >
        <div class="px-5 pt-6 pb-4">
          <div class="flex items-start gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#7fdcc8]/12 ring-1 ring-[#7fdcc8]/20"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-6 w-6 text-[#7fdcc8]"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h2 class="text-lg font-bold text-white">
                {{ t('driver_home.pending_confirm_modal_title') }}
              </h2>
              <p class="mt-1 text-base font-medium text-[#7fdcc8]/70">
                {{ confirmSummary }}
              </p>
              <p class="mt-1 text-sm text-slate-500">
                {{ t('driver_home.pending_confirm_modal_hint') }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-row gap-2.5 px-5 pb-5">
          <button
            type="button"
            class="flex min-h-[52px] flex-1 items-center justify-center rounded-xl border border-white/10 bg-transparent text-base font-semibold text-slate-400 transition active:bg-white/5"
            @click="confirmModalOpen = false"
          >
            {{ t('driver_home.pending_confirm_modal_cancel') }}
          </button>
          <button
            type="button"
            class="flex min-h-[52px] flex-1 items-center justify-center rounded-xl bg-[#7fdcc8] text-base font-extrabold text-[#070f0d] shadow-md shadow-[#7fdcc8]/20 transition active:scale-[0.98]"
            @click="doConfirm"
          >
            {{ t('driver_home.pending_confirm_modal_btn') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  formatDepartForTrip,
  tripDestination,
  tripOrigin,
  tripPassengerLine,
  tripRequesterLine,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'

const props = defineProps({
  trip: { type: Object, required: true },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'decline'])

const { t, locale } = useI18n()

const depart = computed(() =>
  formatDepartForTrip(props.trip, locale.value === 'vi' ? 'vi' : 'en'),
)

const tripRef = computed(() => {
  const code = props.trip?.trip_code
  if (code != null && String(code).trim() !== '') return `#${String(code).trim()}`
  return `#${props.trip?.id}`
})

const isConfirmed = computed(() =>
  String(props.trip?.status ?? '').trim().toLowerCase() === 'driver_confirmed',
)

const serviceFullName = computed(() => {
  const tt = props.trip?.dispatch_request?.trip_type
  if (tt === 'door_to_door') return t('driver_home.svc_name_d2d')
  if (tt === 'point_to_point') return t('driver_home.svc_name_p2p')
  if (tt === 'cargo') return t('driver_home.svc_name_cargo')
  if (tt === 'business') return t('driver_home.svc_name_business')
  return ''
})

// ─── Confirm modal ────────────────────────────────────────────
const confirmModalOpen = ref(false)

const confirmSummary = computed(() => {
  const time = depart.value.time
  const origin = tripOrigin(props.trip)
  return time && time !== '—' ? `${time} · ${origin}` : origin
})

function openConfirmModal() {
  confirmModalOpen.value = true
}

function doConfirm() {
  confirmModalOpen.value = false
  emit('confirm', props.trip)
}

const requester = computed(() => tripRequesterLine(props.trip))

const passengerLine = computed(() => tripPassengerLine(props.trip, t))

const vehicleLine = computed(() => {
  const v = props.trip?.vehicle
  if (!v) return ''
  const parts = [v.name, v.plate_number].filter(Boolean)
  return parts.join(' · ')
})

const distanceLine = computed(() => {
  const dr = props.trip?.dispatch_request
  const km = dr?.distance_km ?? props.trip?.distance_km
  const min = dr?.estimated_duration_min ?? props.trip?.estimated_duration_min
  if (!km && !min) return ''
  if (km && min) return t('driver_home.pending_distance', { km, min })
  if (km) return `${km} km`
  return `~${min} phút`
})

const notesLine = computed(() => (props.trip?.dispatch_request?.notes || '').trim())
</script>
