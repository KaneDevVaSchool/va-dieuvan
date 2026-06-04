<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.trips_title') }}
        </h1>
        <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
          {{ t('p2p_policy_page.trips_subtitle') }}
          <span class="inline-flex items-center gap-1 text-xs">
            <span class="h-2 w-2 rounded-full" :class="liveDotClass"></span>
            {{ t('p2p_policy_page.live_' + live.connection.value) }}
          </span>
        </p>
      </div>
      <div class="flex shrink-0 gap-2">
        <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50" :disabled="loading" @click="reload">
          <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
          {{ t('p2p_policy_page.refresh') }}
        </button>
        <Button @click="showGenerate = true">
          <PlusIcon class="h-4 w-4" /> {{ t('p2p_policy_page.generate_action') }}
        </Button>
      </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Input v-model="filters.date" type="date" :label="t('p2p_policy_page.filter_date')" />
      <Select v-model="filters.timeSlot" :label="t('p2p_policy_page.filter_slot')">
        <option value="">{{ t('p2p_policy_page.slot_all') }}</option>
        <option value="morning">{{ t('p2p_policy_page.slot_morning') }}</option>
        <option value="afternoon">{{ t('p2p_policy_page.slot_afternoon') }}</option>
      </Select>
      <Select v-model="filters.routeId" :label="t('p2p_policy_page.filter_route')">
        <option value="">{{ t('p2p_policy_page.route_all') }}</option>
        <option v-for="r in routes" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
      </Select>
      <Select v-model="filters.status" :label="t('p2p_policy_page.filter_status')">
        <option value="">{{ t('p2p_policy_page.status_all') }}</option>
        <option v-for="s in STATUSES" :key="s" :value="s">{{ labelPolicyTripStatus(s) }}</option>
      </Select>
    </div>

    <!-- Loading / empty -->
    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>
    <div v-else-if="!trips.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <TruckIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">{{ t('p2p_policy_page.trips_empty') }}</p>
      <p class="mt-1 text-xs text-slate-400">{{ t('p2p_policy_page.trips_empty_hint') }}</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[60rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.filter_slot') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_route') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_departure') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_driver') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_vehicle') }}</th>
            <th class="px-3 py-2.5 text-center font-medium">{{ t('p2p_policy_page.col_expected') }}</th>
            <th class="px-3 py-2.5 text-center font-medium">{{ t('p2p_policy_page.col_boarded') }}</th>
            <th class="px-3 py-2.5 text-center font-medium">{{ t('p2p_policy_page.col_absent') }}</th>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_status') }}</th>
            <th class="px-3 py-2.5 text-right font-medium">{{ t('p2p_policy_page.col_actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="trip in trips" :key="trip.id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2.5">{{ labelTimeSlot(trip.time_slot) }}</td>
            <td class="px-3 py-2.5 font-medium text-slate-800">{{ trip.route_name }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ trip.planned_departure || '—' }}</td>
            <td class="px-3 py-2.5">
              <template v-if="trip.driver_name">
                <div class="text-slate-800">{{ trip.driver_name }}</div>
                <div class="text-xs text-slate-400">{{ trip.driver_phone }}</div>
              </template>
              <PolicyPill v-else :text="t('p2p_policy_page.unassigned')" pill-class="bg-amber-100 text-amber-800" />
            </td>
            <td class="px-3 py-2.5 text-slate-600">{{ trip.vehicle_plate || '—' }}</td>
            <td class="px-3 py-2.5 text-center font-medium">{{ trip.expected_count }}</td>
            <td class="px-3 py-2.5 text-center font-medium text-teal-700">{{ trip.boarded_count }}</td>
            <td class="px-3 py-2.5 text-center font-medium text-rose-600">{{ trip.absent_count }}</td>
            <td class="px-3 py-2.5">
              <PolicyPill :text="labelPolicyTripStatus(trip.status)" :pill-class="policyTripStatusPillClass(trip.status)" />
            </td>
            <td class="px-3 py-2.5 text-right">
              <AppRowActionsMenu :aria-label="t('p2p_policy_page.col_actions')">
                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" @click="openDetail(trip)">
                  {{ t('p2p_policy_page.action_detail') }}
                </button>
                <button v-if="canAssign && isPending(trip)" type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" @click="openAssign(trip)">
                  {{ t('p2p_policy_page.action_assign') }}
                </button>
                <button v-if="canCancel && canCancelTrip(trip)" type="button" class="block w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50" @click="openCancel(trip)">
                  {{ t('p2p_policy_page.action_cancel') }}
                </button>
              </AppRowActionsMenu>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modals -->
    <GenerateTripsModal :open="showGenerate" :default-date="filters.date" @close="showGenerate = false" @generated="onGenerated" />
    <AssignDriverModal :open="showAssign" :trip="activeTrip" @close="showAssign = false" @assigned="onTripUpdated" />
    <CancelTripModal :open="showCancel" :trip="activeTrip" @close="showCancel = false" @cancelled="onTripUpdated" />
    <PolicyTripDetailModal :open="showDetail" :trip-id="activeTrip?.id" @close="showDetail = false" @changed="reload" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, PlusIcon, TruckIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import PolicyPill from '../../components/p2p/PolicyPill.vue'
import GenerateTripsModal from '../../components/p2p/GenerateTripsModal.vue'
import AssignDriverModal from '../../components/p2p/AssignDriverModal.vue'
import CancelTripModal from '../../components/p2p/CancelTripModal.vue'
import PolicyTripDetailModal from '../../components/p2p/PolicyTripDetailModal.vue'
import { listPolicyTrips, listPolicyRoutes } from '../../api/p2p'
import { usePolicyTripLiveUpdates } from '../../composables/p2p/usePolicyTripLiveUpdates'
import { useAuthStore } from '../../store'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import {
  POLICY_TRIP_STATUS,
  labelPolicyTripStatus,
  policyTripStatusPillClass,
  labelTimeSlot,
} from '../../constants/policyTripStatus'

const { t } = useI18n()
const auth = useAuthStore()

const STATUSES = Object.values(POLICY_TRIP_STATUS)
const canAssign = computed(() => auth.hasPermission('policy_trip.assign_driver'))
const canCancel = computed(() => auth.hasPermission('policy_trip.cancel'))

const loading = ref(false)
const trips = ref([])
const routes = ref([])

const filters = reactive({
  date: new Date().toISOString().slice(0, 10),
  timeSlot: '',
  routeId: '',
  status: '',
})

const showGenerate = ref(false)
const showAssign = ref(false)
const showCancel = ref(false)
const showDetail = ref(false)
const activeTrip = ref(null)

const live = usePolicyTripLiveUpdates(onLiveChange)
const liveDotClass = computed(() => ({
  sse: 'bg-emerald-500',
  polling: 'bg-amber-500',
  off: 'bg-slate-300',
})[live.connection.value])

function isPending(trip) {
  return [POLICY_TRIP_STATUS.SCHEDULED, POLICY_TRIP_STATUS.ASSIGNED].includes(trip.status)
}
function canCancelTrip(trip) {
  return ![POLICY_TRIP_STATUS.COMPLETED, POLICY_TRIP_STATUS.CANCELLED].includes(trip.status)
}

async function load(silent = false) {
  if (!silent) loading.value = true
  try {
    const res = await listPolicyTrips({
      date: filters.date,
      time_slot: filters.timeSlot || undefined,
      route_id: filters.routeId || undefined,
      status: filters.status || undefined,
    })
    trips.value = res?.items ?? []
  } catch (err) {
    if (!silent) showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function reload() {
  load()
}

async function loadRoutes() {
  try {
    routes.value = (await listPolicyRoutes())?.items ?? []
  } catch {
    routes.value = []
  }
}

/** Live: patch hàng theo id, hoặc reload khi polling. */
function onLiveChange(change) {
  if (change.type === 'reload') {
    load(true)
    return
  }
  const row = change.row
  const target = trips.value.find((x) => x.id === row.id)
  if (target) {
    target.boarded_count = row.boarded_count
    target.absent_count = row.absent_count
    target.expected_count = row.expected_count
    target.status = row.status
  }
}

function startLive() {
  live.start({ date: filters.date, timeSlot: filters.timeSlot || null })
}

// Reload + restart realtime khi đổi bộ lọc
watch(
  () => [filters.date, filters.timeSlot, filters.routeId, filters.status],
  () => load(),
)
watch(
  () => [filters.date, filters.timeSlot],
  () => startLive(),
)

function openDetail(trip) {
  activeTrip.value = trip
  showDetail.value = true
}
function openAssign(trip) {
  activeTrip.value = trip
  showAssign.value = true
}
function openCancel(trip) {
  activeTrip.value = trip
  showCancel.value = true
}

function onTripUpdated(row) {
  const idx = trips.value.findIndex((x) => x.id === row.id)
  if (idx !== -1) trips.value[idx] = row
}

function onGenerated(res) {
  showAppSuccess(t('p2p_policy_page.generate_result', { created: res.created, skipped: res.skipped }))
  showGenerate.value = false
  load()
}

onMounted(async () => {
  await Promise.all([load(), loadRoutes()])
  startLive()
})
</script>
