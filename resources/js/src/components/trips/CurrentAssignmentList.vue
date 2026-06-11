<template>
  <div class="space-y-4" data-testid="current-assignment-list">
    <div v-if="vehicles.length">
      <div class="mb-2 flex items-center justify-between gap-2">
        <h3 class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">
          {{ t('trip_detail.coordination.resource_section_internal_title') }}
          <span class="ml-1 tabular-nums text-slate-400">({{ vehicles.length }})</span>
        </h3>
        <button
          v-if="allowChange"
          type="button"
          class="shrink-0 text-[11px] font-semibold text-[#8B1A1A] hover:underline dark:text-[#e85c5c]"
          data-testid="current-assignment-edit-vehicles"
          @click="$emit('edit-vehicles')"
        >
          {{ t('trip_detail.coordination.assignment_list_edit') }}
        </button>
      </div>
      <ul class="divide-y divide-slate-200/80 overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-950/30">
        <li
          v-for="(v, idx) in vehicles"
          :key="`v-${v.id ?? idx}`"
          class="flex items-center gap-3 px-3 py-2.5"
        >
          <div
            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-[#1D9E75] dark:bg-emerald-950/40 dark:text-[#4dcf9a]"
          >
            <TruckIcon class="size-4" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="truncate text-[13px] font-semibold tabular-nums text-slate-900 dark:text-slate-100">
              <EmptyValue :value="v.license_plate" empty-key="trip_detail.empty.plate" />
            </div>
            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400">
              <EmptyValue :value="v.type" empty-key="trip_detail.empty.vehicle_type" />
              <span v-if="v.seat_count" class="text-emerald-700 dark:text-emerald-400">
                {{ t('trip_detail.coordination.seats_n', { n: v.seat_count }) }}
              </span>
            </div>
          </div>
          <span
            v-if="v.busy"
            class="shrink-0 rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300"
          >
            {{ t('trip_detail.coordination.resource_busy_badge') }}
          </span>
          <span
            v-else-if="idx === 0"
            class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
          >
            {{ t('trip_detail.coordination.assignment_list_primary') }}
          </span>
        </li>
      </ul>
    </div>

    <div v-if="drivers.length">
      <div class="mb-2 flex items-center justify-between gap-2">
        <h3 class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">
          {{ t('trip_detail.coordination.resource_section_driver_title') }}
          <span class="ml-1 tabular-nums text-slate-400">({{ drivers.length }})</span>
        </h3>
        <button
          v-if="allowChange"
          type="button"
          class="shrink-0 text-[11px] font-semibold text-[#8B1A1A] hover:underline dark:text-[#e85c5c]"
          data-testid="current-assignment-edit-drivers"
          @click="$emit('edit-drivers')"
        >
          {{ t('trip_detail.coordination.assignment_list_edit') }}
        </button>
      </div>
      <ul class="divide-y divide-slate-200/80 overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-950/30">
        <li
          v-for="(d, idx) in drivers"
          :key="`d-${d.id ?? idx}`"
          class="flex items-center gap-3 px-3 py-2.5"
        >
          <div
            class="flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200/80 bg-sky-50 text-xs font-bold text-[#378ADD] dark:border-slate-600 dark:bg-sky-950/40 dark:text-[#6cb3f5]"
          >
            {{ initials(d.full_name) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="truncate text-[13px] font-semibold text-slate-900 dark:text-slate-100">
              <EmptyValue :value="d.full_name" empty-key="trip_detail.empty.driver" />
            </div>
            <div v-if="d.phone" class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">
              {{ d.phone }}
            </div>
          </div>
          <span
            v-if="d.busy"
            class="shrink-0 rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300"
          >
            {{ t('trip_detail.coordination.driver_busy') }}
          </span>
          <span
            v-else-if="idx === 0"
            class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400"
          >
            {{ t('trip_detail.coordination.assignment_list_primary') }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { TruckIcon } from '@heroicons/vue/24/outline'
import EmptyValue from '../ui/EmptyValue.vue'

export type AssignmentListVehicle = {
  id?: number | string | null
  license_plate?: string | null
  type?: string | null
  seat_count?: number | null
  busy?: boolean
}

export type AssignmentListDriver = {
  id?: number | string | null
  full_name?: string | null
  phone?: string | null
  busy?: boolean
}

withDefaults(
  defineProps<{
    vehicles: AssignmentListVehicle[]
    drivers: AssignmentListDriver[]
    allowChange?: boolean
  }>(),
  { allowChange: true, vehicles: () => [], drivers: () => [] },
)

defineEmits<{
  'edit-vehicles': []
  'edit-drivers': []
}>()

const { t } = useI18n()

function initials(name: string | null | undefined) {
  const n = String(name ?? '').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}
</script>
