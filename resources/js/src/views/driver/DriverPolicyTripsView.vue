<template>
  <div class="min-h-full w-full max-w-[430px] bg-driver-bg pb-6 text-driver-ink sm:max-w-none">
    <header
      class="sticky top-0 z-[25] flex items-center gap-2 border-b border-white/5 bg-driver-bg/90 px-3 py-3 backdrop-blur-md"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <RouterLink to="/driver" class="flex h-10 w-10 items-center justify-center rounded-full text-[#7fdcc8] transition hover:bg-white/5 active:scale-95">
        <span class="sr-only">{{ t('driver_policy.back') }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
      </RouterLink>
      <h1 class="min-w-0 flex-1 truncate text-xl font-bold tracking-tight">{{ t('driver_policy.title') }}</h1>
      <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-[#7fdcc8] ring-1 ring-white/10 transition hover:bg-white/5" @click="load">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="h-5 w-5" :class="loading ? 'animate-spin' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m-4.252 0a8.25 8.25 0 0 0 13.803 3.7l3.181-3.182m0-4.991-3.181 3.182a8.25 8.25 0 0 1-13.803-3.7" /></svg>
      </button>
    </header>

    <div class="px-3 pt-3">
      <div v-if="loading" class="py-16 text-center text-sm text-driver-ink/60">{{ t('driver_policy.loading') }}</div>
      <div v-else-if="!trips.length" class="py-16 text-center">
        <p class="text-sm font-medium text-driver-ink/70">{{ t('driver_policy.empty') }}</p>
        <p class="mt-1 text-xs text-driver-ink/40">{{ t('driver_policy.empty_hint') }}</p>
      </div>

      <div v-else class="space-y-2.5">
        <RouterLink
          v-for="trip in trips"
          :key="trip.id"
          :to="{ name: 'driverPolicyAttendance', params: { id: trip.id } }"
          class="block rounded-2xl border border-white/8 bg-white/[0.03] p-4 transition active:scale-[0.99]"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-base font-semibold">{{ trip.route_name }}</span>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(trip.status)">
              {{ labelPolicyTripStatus(trip.status) }}
            </span>
          </div>
          <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-driver-ink/60">
            <span>{{ labelTimeSlot(trip.time_slot) }}</span>
            <span v-if="trip.planned_departure">· {{ trip.planned_departure }}</span>
            <span v-if="trip.vehicle_plate">· {{ trip.vehicle_plate }}</span>
          </div>
          <div class="mt-3 flex items-center gap-4 text-sm">
            <span><b class="text-[#7fdcc8]">{{ trip.boarded_count }}</b>/{{ trip.expected_count }} {{ t('driver_policy.boarded') }}</span>
            <span class="text-rose-300">{{ trip.absent_count }} {{ t('driver_policy.absent') }}</span>
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { listDriverPolicyTrips } from '../../api/driverPolicy'
import { labelPolicyTripStatus, labelTimeSlot } from '../../constants/policyTripStatus'

const { t } = useI18n()
const loading = ref(false)
const trips = ref([])

async function load() {
  loading.value = true
  try {
    trips.value = (await listDriverPolicyTrips('today'))?.items ?? []
  } catch {
    trips.value = []
  } finally {
    loading.value = false
  }
}

function statusClass(status) {
  return status === 'in_progress'
    ? 'bg-[#7fdcc8]/15 text-[#7fdcc8]'
    : 'bg-white/10 text-driver-ink/70'
}

onMounted(load)
</script>
