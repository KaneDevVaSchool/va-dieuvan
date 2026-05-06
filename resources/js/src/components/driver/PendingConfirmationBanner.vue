<template>
  <div
    class="overflow-hidden rounded-2xl border-2 border-amber-400/70 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 shadow-xl ring-1 ring-amber-500/30"
  >
    <!-- Header -->
    <div class="flex items-start gap-4 border-b border-amber-400/25 px-5 py-4">
      <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-400/25">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-amber-300">
          <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z" />
        </svg>
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-xl font-bold leading-snug text-white sm:text-2xl">
          {{ t('driver_home.pending_banner_title', { n: trips.length }) }}
        </p>
        <p class="mt-1 text-sm text-white/65">
          {{ t('driver_home.pending_banner_sub') }}
        </p>
        <p
          v-if="hasUrgent"
          class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-500/25 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-200 ring-1 ring-rose-400/40"
        >
          <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400" />
          {{ t('driver_home.pending_urgent_hint') }}
        </p>
      </div>
    </div>

    <!-- Trip rows -->
    <ul class="divide-y divide-white/10">
      <li v-for="trip in trips" :key="trip.id" class="px-5 py-4">
        <!-- Top row: type + urgent + time -->
        <div class="flex flex-wrap items-center gap-2 gap-y-2">
          <span class="rounded-lg bg-white/12 px-2.5 py-1 text-sm font-bold text-white">
            {{ tripTypeBadge(trip) }}
          </span>
          <span
            v-if="isUrgent(trip)"
            class="rounded-lg bg-rose-500/90 px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm"
          >
            {{ t('driver_home.urgent_badge') }}
          </span>
          <span class="ml-auto text-lg font-bold tabular-nums text-amber-200">
            {{ formatDepart(trip).time }}
          </span>
        </div>
        <p v-if="formatDepart(trip).dateLine" class="mt-0.5 text-sm text-white/55">
          {{ formatDepart(trip).dateLine }}
        </p>

        <!-- Pickup / Dropoff -->
        <div class="mt-3 space-y-2.5">
          <div class="flex gap-3">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/25">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-emerald-300">
                <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 0 0 .315-.142c.088-.027.195-.077.315-.142z" clip-rule="evenodd" />
              </svg>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-white/45">
                {{ t('driver_home.pending_pickup') }}
              </p>
              <p class="text-base font-semibold leading-snug text-white">
                {{ origin(trip) }}
              </p>
            </div>
          </div>
          <div class="flex gap-3">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-500/25">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-sky-300">
                <path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 1ZM5.05 3.05a.75.75 0 0 1 1.06 0l1.062 1.06A.75.75 0 1 1 6.11 5.173L5.05 4.11a.75.75 0 0 1 0-1.06Zm9.9 0a.75.75 0 0 1 0 1.06l-1.06 1.062a.75.75 0 0 1-1.062-1.061l1.061-1.061a.75.75 0 0 1 1.06 0ZM10 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-7.75 2a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm14.5 0a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5ZM5.05 16.95a.75.75 0 0 1 1.06-1.06l-1.06-1.062a.75.75 0 0 0-1.061 1.06l1.06 1.062Zm9.9-1.06a.75.75 0 0 0-1.061-1.061l-1.062 1.06a.75.75 0 0 1 1.06 1.062l1.062-1.061ZM10 16a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 16Z" clip-rule="evenodd" />
              </svg>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-white/45">
                {{ t('driver_home.pending_dropoff') }}
              </p>
              <p class="text-base font-semibold leading-snug text-white">
                {{ destination(trip) }}
              </p>
            </div>
          </div>
        </div>

        <p v-if="requesterLine(trip)" class="mt-2 text-sm text-white/60">
          {{ t('driver_home.pending_requester', { name: requesterLine(trip) }) }}
        </p>
        <p
          v-if="passengerLine(trip)"
          class="text-sm text-white/55"
        >
          {{ passengerLine(trip) }}
        </p>

        <!-- Action buttons -->
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            :disabled="busyId === trip.id"
            class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-500 text-base font-bold text-white shadow-lg active:scale-[0.98] disabled:opacity-60"
            style="min-height: 52px"
            @click="onConfirm(trip)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd" />
            </svg>
            {{ t('driver_home.btn_confirm') }}
          </button>
          <button
            type="button"
            :disabled="busyId === trip.id"
            class="flex flex-1 items-center justify-center gap-2 rounded-2xl border-2 border-white/25 bg-white/10 text-base font-bold text-white active:scale-[0.98] disabled:opacity-60"
            style="min-height: 52px"
            @click="onDecline(trip)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0">
              <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
            </svg>
            {{ t('driver_home.btn_decline') }}
          </button>
        </div>

        <RouterLink
          :to="`/driver/trips/${trip.id}`"
          class="mt-2 flex w-full items-center justify-center rounded-xl py-2.5 text-sm font-semibold text-amber-200/95 underline-offset-2 active:opacity-80"
        >
          {{ t('driver_home.pending_view_detail') }}
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { updateTripStatus } from '../../api/trips'

const props = defineProps({
  trips: { type: Array, required: true },
})

const emit = defineEmits(['updated'])

const { t } = useI18n()
const busyId = ref(null)

function isUrgent(trip) {
  return !!trip?.dispatch_request?.is_urgent
}

const hasUrgent = computed(() => props.trips.some((trip) => isUrgent(trip)))

function formatDepart(trip) {
  const iso = trip.depart_at || trip.dispatch_request?.depart_at
  if (!iso) return { time: '—', dateLine: '' }
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return { time: '—', dateLine: '' }
  const time = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })
  const dateLine = d.toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  })
  return { time, dateLine }
}

function origin(trip) {
  const o = (trip.dispatch_request?.origin || '').trim()
  return o || '—'
}

function destination(trip) {
  const o = (trip.dispatch_request?.destination || '').trim()
  return o || '—'
}

function requesterLine(trip) {
  return trip.dispatch_request?.requester?.name?.trim() || ''
}

function passengerLine(trip) {
  const dr = trip.dispatch_request
  const n = dr?.passenger_count
  if (n != null && n !== '') return t('driver_home.pending_passengers', { n })
  return ''
}

function tripTypeBadge(trip) {
  const tt = trip.dispatch_request?.trip_type
  if (tt === 'door_to_door') return 'D2D'
  if (tt === 'point_to_point') return 'P2P'
  if (tt === 'business') return 'CT'
  if (tt === 'cargo') return 'CG'
  return 'TR'
}

async function onConfirm(trip) {
  if (busyId.value) return
  busyId.value = trip.id
  try {
    await updateTripStatus(trip.id, { status: 'in_progress' })
    emit('updated')
  } finally {
    busyId.value = null
  }
}

async function onDecline(trip) {
  if (busyId.value) return
  busyId.value = trip.id
  try {
    await updateTripStatus(trip.id, { status: 'cancelled' })
    emit('updated')
  } finally {
    busyId.value = null
  }
}
</script>
