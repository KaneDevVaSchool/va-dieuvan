<template>
  <div class="rounded-3xl border border-[#86c2b5]/20 bg-[#111d1b] shadow-xl shadow-black/20 ring-1 ring-[#86c2b5]/10">
    <!-- Header -->
    <div class="border-b border-[#86c2b5]/15 px-4 py-4 sm:px-5">
      <div class="flex items-start gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#86c2b5]/15 ring-1 ring-[#86c2b5]/30">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-[#86c2b5]" aria-hidden="true">
            <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z" />
          </svg>
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-lg font-bold leading-snug text-white sm:text-xl">
            {{ loading ? t('driver_home.pending_loading_hint') : t('driver_home.pending_banner_title', { n: trips.length }) }}
          </p>
          <p v-if="!loading" class="mt-1 text-sm text-[#86c2b5]/70">
            {{ t('driver_home.pending_banner_sub') }}
          </p>
          <p
            v-if="hasUrgent && !loading"
            class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-950/70 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-100 ring-1 ring-rose-600/40"
          >
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400/90" />
            {{ t('driver_home.pending_urgent_hint') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4 px-4 py-4 sm:px-5">
      <p class="text-sm text-[#86c2b5]/70">{{ t('driver_home.pending_loading_hint') }}</p>
      <div v-for="s in 3" :key="s" class="rounded-2xl border border-[#86c2b5]/15 bg-[#142421]/80 p-4">
        <div class="h-5 w-40 animate-pulse rounded bg-[#86c2b5]/15" />
        <div class="mt-4 space-y-3">
          <div class="h-24 animate-pulse rounded-xl bg-[#86c2b5]/10" />
          <div class="h-24 animate-pulse rounded-xl bg-[#86c2b5]/10" />
        </div>
      </div>
    </div>

    <div v-else class="divide-y divide-[#86c2b5]/15">
      <TripGroupSection
        :title="t('driver_home.group_urgent')"
        :trips="sortedUrgent"
        :open="open.urgent"
        :busy-id="busyId"
        @toggle="toggle('urgent')"
        @confirm="onConfirm"
        @decline="onDecline"
      />
      <TripGroupSection
        :title="t('driver_home.group_upcoming')"
        :trips="sortedUpcoming"
        :open="open.upcoming"
        :busy-id="busyId"
        @toggle="toggle('upcoming')"
        @confirm="onConfirm"
        @decline="onDecline"
      />
      <TripGroupSection
        :title="t('driver_home.group_confirmed')"
        :trips="sortedConfirmed"
        :open="open.confirmed"
        :busy-id="busyId"
        @toggle="toggle('confirmed')"
        @confirm="onConfirm"
        @decline="onDecline"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { updateTripStatus } from '../../api/trips'
import { isTripUrgent } from '../../composables/useDriverTripDisplay'
import TripGroupSection from './TripGroupSection.vue'

const props = defineProps({
  trips: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['updated'])

const { t } = useI18n()

const busyId = ref(null)

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
