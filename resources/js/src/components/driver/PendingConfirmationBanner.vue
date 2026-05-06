<template>
  <div
    class="overflow-hidden rounded-2xl border border-amber-400/60 bg-gradient-to-br from-slate-900 to-slate-800 shadow-lg"
  >
    <!-- Header -->
    <div class="flex items-start gap-3 border-b border-amber-400/20 px-4 py-3">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-400/20">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-amber-400">
          <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z" />
        </svg>
      </span>
      <div class="min-w-0">
        <p class="text-base font-bold text-white">
          {{ t('driver_home.pending_banner_title', { n: trips.length }) }}
        </p>
        <p class="text-xs text-white/60">
          {{ t('driver_home.pending_banner_sub') }}
        </p>
      </div>
    </div>

    <!-- Trip rows -->
    <ul class="divide-y divide-white/10">
      <li v-for="trip in trips" :key="trip.id" class="px-4 py-3">
        <div class="flex items-center gap-3">
          <!-- Type badge -->
          <span class="shrink-0 rounded-lg bg-white/10 px-2 py-1 text-xs font-bold text-white">
            {{ tripTypeBadge(trip) }}
          </span>
          <!-- Time + destination -->
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-white tabular-nums">
              {{ formatHm(trip.depart_at) }}
              <span v-if="destination(trip)" class="font-normal text-white/70"> · {{ destination(trip) }}</span>
            </p>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="mt-2.5 flex gap-2">
          <button
            type="button"
            :disabled="busyId === trip.id"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-500 text-sm font-bold text-white active:scale-[0.98] disabled:opacity-60"
            style="min-height: 44px;"
            @click="onConfirm(trip)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd" />
            </svg>
            {{ t('driver_home.btn_confirm') }}
          </button>
          <button
            type="button"
            :disabled="busyId === trip.id"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/20 bg-white/10 text-sm font-bold text-white active:scale-[0.98] disabled:opacity-60"
            style="min-height: 44px;"
            @click="onDecline(trip)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
              <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
            </svg>
            {{ t('driver_home.btn_decline') }}
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { updateTripStatus } from '../../api/trips'

const props = defineProps({
  trips: { type: Array, required: true },
})

const emit = defineEmits(['updated'])

const { t } = useI18n()
const busyId = ref(null)

function formatHm(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })
}

function destination(trip) {
  return (trip.dispatch_request?.destination || '').trim()
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
