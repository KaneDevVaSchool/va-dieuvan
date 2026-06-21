<template>
  <div
    ref="rootRef"
    class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
  >
    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
      <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
        <div class="min-w-0 w-full basis-full lg:flex-1 lg:basis-auto">
          <p class="truncate text-sm text-slate-600 dark:text-slate-400">
            {{ t('driver_freq.filters_heading') }}
          </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <FilterVisibilityDropdown
            :open="showFilterPanel"
            :title="t('trips_page.filter_show_controls_title')"
            :hint="t('trips_page.filter_show_controls_hint')"
            @close="$emit('close-filter-panel')"
          >
            <template #trigger>
              <DatagridToolbarActionButton
                icon="filter"
                :active="showFilterPanel"
                test-id="driver-freq-toolbar-filter"
                @click="$emit('toggle-filter-panel')"
              >
                {{ t('driver_freq.toolbar_filter') }}
              </DatagridToolbarActionButton>
            </template>
            <li v-for="fd in filterControlDefs" :key="'driver-freq-vis-' + fd.id" class="flex items-start gap-2">
              <input
                :id="'driver-freq-filter-vis-' + fd.id"
                :checked="filterControlVisible[fd.id]"
                type="checkbox"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                :data-testid="`driver-freq-filter-vis-${fd.id}`"
                @change="$emit('toggle-filter-control', fd.id, $event.target.checked)"
              />
              <label
                :for="'driver-freq-filter-vis-' + fd.id"
                class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
              >
                {{ fd.label }}
              </label>
            </li>
          </FilterVisibilityDropdown>

          <button
            type="button"
            class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
            :title="t('driver_freq.clear_filters')"
            data-testid="driver-freq-reset-filters"
            @click="$emit('reset-filters')"
          >
            <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
          </button>

          <details v-if="canExport" ref="exportMenuRef" class="group relative">
            <summary class="list-none [&::-webkit-details-marker]:hidden">
              <DatagridToolbarActionButton
                icon="export"
                :disabled="!!exporting || loading"
                test-id="driver-freq-filters-export"
                @click.prevent
              >
                {{ t('driver_freq.toolbar_export') }}
              </DatagridToolbarActionButton>
            </summary>
            <div
              class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
              @click.stop
            >
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="driver-freq-filters-export-xlsx"
                :disabled="!!exporting || loading"
                @click="$emit('export-xlsx')"
              >
                {{ t('driver_freq.btn_export_xlsx') }}
              </button>
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="driver-freq-filters-export-pdf"
                :disabled="!!exporting || loading"
                @click="$emit('export-pdf')"
              >
                {{ t('driver_freq.btn_export_pdf') }}
              </button>
            </div>
          </details>

          <button
            type="button"
            class="df-freq-refresh"
            :disabled="loading"
            data-testid="driver-freq-reload"
            @click="$emit('reload')"
          >
            <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            <template v-else>{{ t('driver_freq.btn_refresh') }}</template>
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="hasVisibleBarFilters"
      class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
    >
      <DatagridFilterField v-if="filterControlVisible.year">
        <select
          :value="filters.year"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('driver_freq.filter_vis_year')"
          data-testid="driver-freq-filter-year"
          @change="$emit('patch-filter', { year: Number($event.target.value) })"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.quarter">
        <select
          :value="filters.quarter"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('driver_freq.filter_vis_quarter')"
          data-testid="driver-freq-filter-quarter"
          @change="$emit('patch-filter', { quarter: $event.target.value })"
        >
          <option value="">{{ t('driver_freq.filter_quarter') }}</option>
          <option value="q1">{{ t('driver_freq.filter_quarter_q1') }}</option>
          <option value="q2">{{ t('driver_freq.filter_quarter_q2') }}</option>
          <option value="q3">{{ t('driver_freq.filter_quarter_q3') }}</option>
          <option value="q4">{{ t('driver_freq.filter_quarter_q4') }}</option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.driverId">
        <select
          :value="filters.driverId"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('driver_freq.filter_vis_driverId')"
          data-testid="driver-freq-filter-driver"
          @change="$emit('patch-filter', { driverId: $event.target.value })"
        >
          <option value="">{{ t('driver_freq.filter_driver') }}</option>
          <option v-for="d in driverOptions" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.vehiclePlate">
        <select
          :value="filters.vehiclePlate"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('driver_freq.filter_vis_vehiclePlate')"
          data-testid="driver-freq-filter-vehicle"
          @change="$emit('patch-filter', { vehiclePlate: $event.target.value })"
        >
          <option value="">{{ t('driver_freq.filter_vehicle') }}</option>
          <option v-for="v in vehicleOptions" :key="v.id" :value="v.plate">{{ v.plate }}</option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.tripType">
        <select
          :value="filters.tripType"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('driver_freq.filter_vis_tripType')"
          data-testid="driver-freq-filter-trip-type"
          @change="$emit('patch-filter', { tripType: $event.target.value })"
        >
          <option v-for="opt in tripTypeOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('driver_freq.filter_trip_type') : opt.label }}
          </option>
        </select>
      </DatagridFilterField>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import DatagridToolbarActionButton from '../shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../shared/ui/FilterVisibilityDropdown.vue'
import { useDetailsAutoClose, useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'

defineProps({
  filters: { type: Object, required: true },
  filterControlVisible: { type: Object, required: true },
  filterControlDefs: { type: Array, required: true },
  yearOptions: { type: Array, required: true },
  driverOptions: { type: Array, default: () => [] },
  vehicleOptions: { type: Array, default: () => [] },
  tripTypeOptions: { type: Array, required: true },
  hasVisibleBarFilters: { type: Boolean, default: false },
  showFilterPanel: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  canExport: { type: Boolean, default: false },
  exporting: { type: String, default: null },
})

defineEmits([
  'export-xlsx',
  'export-pdf',
  'reload',
  'reset-filters',
  'patch-filter',
  'toggle-filter-panel',
  'close-filter-panel',
  'toggle-filter-control',
])

const { t } = useI18n()
const rootRef = ref(null)
const exportMenuRef = ref(null)
useDetailsAutoCloseWithin(rootRef)
useDetailsAutoClose(exportMenuRef)

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'
</script>

<style scoped>
.df-freq-refresh {
  @apply inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-teal-800 px-4 text-sm font-medium text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600/30 disabled:opacity-50 dark:bg-teal-700 dark:hover:bg-teal-600;
}
</style>
