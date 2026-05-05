<template>
  <section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md shadow-slate-900/5 ring-1 ring-blue-600/10 dark:border-slate-700/80 dark:bg-slate-950/30 dark:shadow-none dark:ring-blue-500/20 print:hidden"
    :aria-label="t('trip_detail.coordination.title')"
  >
    <div class="space-y-3 p-3">
      <!-- Header row: title + tabs + funnel -->
      <div class="flex flex-wrap items-center gap-2">
        <span class="shrink-0 text-sm font-semibold text-slate-700 dark:text-slate-200">
          {{ t('trip_detail.coordination.title') }}
        </span>

        <div class="flex-1" />

        <!-- Funnel dropdown -->
        <details class="group relative shrink-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-left hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 [&::-webkit-details-marker]:hidden"
          >
            <FunnelIcon class="h-3.5 w-3.5 shrink-0 text-slate-500" aria-hidden="true" />
            <span class="max-w-[6rem] truncate text-xs text-slate-600 dark:text-slate-300 sm:max-w-[9rem]">
              {{ t('trip_detail.coordination.toolbar_funnel_label') }}
            </span>
            <ChevronDownIcon
              class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-open:rotate-180"
              aria-hidden="true"
            />
          </summary>
          <div
            class="absolute right-0 top-[calc(100%+6px)] z-[100] min-w-[240px] max-w-[min(100vw-1.5rem,300px)] rounded-xl border border-slate-200 bg-white p-2.5 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="border-b border-slate-100 pb-1.5 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400">
              {{ t('trip_detail.coordination.toolbar_funnel_applied') }}
            </div>
            <ul class="mt-2 max-h-[min(40vh,200px)] space-y-1.5 overflow-y-auto text-xs text-slate-700 dark:text-slate-300">
              <li v-for="(line, idx) in coordinationFunnelLines" :key="'cf-' + idx">{{ line }}</li>
              <li v-if="!coordinationFunnelLines.length" class="text-slate-400 dark:text-slate-500">
                {{ t('trip_detail.coordination.toolbar_funnel_empty') }}
              </li>
            </ul>
          </div>
        </details>
      </div>

      <!-- Reschedule block -->
      <div
        v-if="canRescheduleTrip"
        class="rounded-xl border border-indigo-200/80 bg-indigo-50/80 p-2 dark:border-indigo-800/50 dark:bg-indigo-950/40"
      >
        <div class="flex flex-nowrap items-center gap-2 overflow-x-auto">
          <span class="shrink-0 text-xs font-semibold text-indigo-800 dark:text-indigo-200">
            {{ t('trip_detail.reschedule.title') }}
          </span>
          <input
            :value="rescheduleDepartLocal"
            type="datetime-local"
            class="min-w-0 flex-1 shrink rounded-lg border border-indigo-100 bg-white px-2 py-1.5 text-xs outline-none ring-2 ring-transparent focus:border-indigo-300 focus:ring-indigo-100 dark:border-indigo-800 dark:bg-slate-900 dark:text-slate-100 sm:min-w-[10rem]"
            @input="onRescheduleDateInput"
          />
          <button
            type="button"
            class="shrink-0 rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            :disabled="rescheduling"
            @click="$emit('reschedule')"
          >
            <span v-if="rescheduling" class="mr-1 inline-block h-2.5 w-2.5 animate-spin rounded-full border border-white/50 border-t-white" />
            {{ t('trip_detail.reschedule.save') }}
          </button>
        </div>
        <div
          v-if="rescheduleMsg"
          class="mt-1.5 rounded-lg border px-2 py-1.5 text-xs font-medium"
          :class="rescheduleFeedbackIsError ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'"
          role="status"
        >
          {{ rescheduleMsg }}
        </div>
      </div>

      <!-- Resource selection: vehicle / driver / provider -->
      <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-3 dark:border-slate-700/60 dark:bg-slate-900/40">
        <VehicleCard
          v-if="showInternalVehicleCard && selectedVehicleForCard"
          :vehicle="selectedVehicleForCard"
          :busy="vehicleCardBusy"
          @change="$emit('vehicle-card-change')"
        />
        <ConflictBanner
          :conflict="vehicleConflictBanner"
          @pick-again="$emit('conflict-pick-again')"
          @keep-anyway="$emit('conflict-keep')"
        />
        <DriverCard
          v-if="showInternalDriverCard && selectedDriverForCard"
          :driver="selectedDriverForCard"
          :busy="driverCardBusy"
          @change="$emit('driver-card-change')"
        />
        <ResourcePanel
          v-if="tripId"
          ref="resourcePanelRef"
          :trip-id="tripId"
          :trip-date="scheduleDateKeyForList"
          :needed-seats="neededSeats"
          :available-count="suitableVehiclesCount"
          :busy-vehicle-ids="busyVehicleIds"
          :busy-driver-ids="busyDriverIds"
          :trip-snapshot="tripSnapshot"
          :can-quick-create-vendor="canQuickCreateProvider"
          :hide-internal-vehicle-section="showInternalVehicleCard"
          :hide-internal-driver-section="showInternalDriverCard"
          @update:resources="$emit('update:resources', $event)"
          @create-vendor="$emit('create-vendor')"
        />
      </div>

      <!-- Overlapping trips -->
      <div
        v-if="canAssign && overlappingOtherTrips.length"
        class="rounded-xl border border-slate-200/80 bg-white p-2.5 dark:border-slate-700/60 dark:bg-slate-900/40"
      >
        <div class="text-xs font-semibold text-slate-600 dark:text-slate-400">
          {{ t('trip_detail.coordination.overlap_section_title') }}
        </div>
        <ul class="mt-1.5 max-h-36 space-y-1 overflow-y-auto text-[11px]">
          <li
            v-for="row in overlappingOtherTrips"
            :key="row.id"
            class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
          >
            <RouterLink
              :to="tripDetailPathFor(row.id)"
              class="font-semibold text-sky-700 underline-offset-2 hover:underline"
            >
              #{{ row.id }}
            </RouterLink>
            <span class="tabular-nums text-slate-600">{{ fmtTime(row.depart_at) }}</span>
            <span class="min-w-0 text-slate-700">{{ row.label }}</span>
            <span v-if="row.driverName || row.vehiclePlate" class="text-slate-500">
              <template v-if="row.driverName">{{ row.driverName }}</template>
              <template v-if="row.driverName && row.vehiclePlate"> · </template>
              <template v-if="row.vehiclePlate">{{ row.vehiclePlate }}</template>
            </span>
          </li>
        </ul>
      </div>

      <!-- Assign feedback -->
      <div
        v-if="assignMsg"
        class="rounded-lg border px-3 py-2 text-sm font-medium"
        :class="
          assignFeedbackKind === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
            : assignFeedbackKind === 'error'
              ? 'border-rose-200 bg-rose-50 text-rose-950'
              : 'border-slate-200 bg-slate-50 text-slate-800'
        "
        role="alert"
      >
        {{ assignMsg }}
      </div>

      <!-- Internal notes (pinned to bottom) -->
      <div class="border-t border-slate-100 pt-2.5 dark:border-slate-700/60">
        <label class="mb-1.5 block text-xs font-semibold text-slate-500 dark:text-slate-400">
          {{ t('trip_detail.coordination.internal_notes') }}
        </label>
        <textarea
          :value="coordinationNotes"
          rows="2"
          class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm outline-none ring-blue-200 focus:ring dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
          :placeholder="t('trip_detail.coordination.internal_notes_ph')"
          @input="onCoordNotesInput"
        />
      </div>

      <p v-if="!canAssign && !canUpdateStatus" class="text-xs text-slate-500">
        {{ t('trip_detail.coordination.no_permission_assign') }}
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import ResourcePanel from '../dispatch/ResourcePanel.vue'
import ConflictBanner, { type VehicleConflict } from './ConflictBanner.vue'
import DriverCard from './DriverCard.vue'
import VehicleCard from './VehicleCard.vue'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'

const props = defineProps<{
  canAssign: boolean
  canUpdateStatus: boolean
  canRescheduleTrip: boolean
  canQuickCreateProvider: boolean
  rescheduleDepartLocal: string
  rescheduling: boolean
  rescheduleMsg: string
  rescheduleFeedbackIsError: boolean
  showInternalVehicleCard: boolean
  selectedVehicleForCard: object | null
  vehicleCardBusy: boolean
  showInternalDriverCard: boolean
  selectedDriverForCard: object | null
  driverCardBusy: boolean
  vehicleConflictBanner: VehicleConflict | null
  tripId: number | null
  scheduleDateKeyForList: string
  neededSeats: number
  suitableVehiclesCount: number
  busyVehicleIds: number[]
  busyDriverIds: number[]
  tripSnapshot: object | null
  overlappingOtherTrips: { id: number; depart_at: string; label: string; driverName: string; vehiclePlate: string }[]
  coordinationNotes: string
  assignMsg: string
  assignFeedbackKind: string
  coordinationFunnelLines: string[]
}>()

const emit = defineEmits<{
  reschedule: []
  'vehicle-card-change': []
  'driver-card-change': []
  'conflict-pick-again': []
  'conflict-keep': []
  'update:resources': [payload: unknown]
  'create-vendor': []
  'update:rescheduleDepartLocal': [value: string]
  'update:coordinationNotes': [value: string]
}>()

const { t, locale } = useI18n()
const route = useRoute()

const resourcePanelRef = ref<InstanceType<typeof ResourcePanel> | null>(null)
defineExpose({ resourcePanel: resourcePanelRef })

function tripDetailPathFor(id: number) {
  return route.path.startsWith('/driver') ? `/driver/trips/${id}` : staffPath(`/trips/${id}`)
}

function fmtTime(v: string) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '-'
}

function onRescheduleDateInput(e: Event) {
  emit('update:rescheduleDepartLocal', (e.target as HTMLInputElement).value)
}

function onCoordNotesInput(e: Event) {
  emit('update:coordinationNotes', (e.target as HTMLTextAreaElement).value)
}
</script>
