<template>
  <div
    ref="rootRef"
    class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
  >
    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
      <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
        <div class="min-w-0 w-full basis-full lg:flex-1 lg:basis-auto">
          <p class="truncate text-sm text-slate-600 dark:text-slate-400">
            {{ t('cost_report.filters_heading') }}
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
                test-id="cost-report-toolbar-filter"
                @click="$emit('toggle-filter-panel')"
              >
                {{ t('cost_report.toolbar_filter') }}
              </DatagridToolbarActionButton>
            </template>
            <li v-for="fd in filterControlDefs" :key="'cost-report-vis-' + fd.id" class="flex items-start gap-2">
              <input
                :id="'cost-report-filter-vis-' + fd.id"
                :checked="filterControlVisible[fd.id]"
                type="checkbox"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                :data-testid="`cost-report-filter-vis-${fd.id}`"
                @change="$emit('toggle-filter-control', fd.id, $event.target.checked)"
              />
              <label
                :for="'cost-report-filter-vis-' + fd.id"
                class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
              >
                {{ fd.label }}
              </label>
            </li>
          </FilterVisibilityDropdown>

          <button
            type="button"
            class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
            :title="t('dashboard_analytics.filter_clear_all')"
            data-testid="cost-report-reset-filters"
            @click="$emit('reset-filters')"
          >
            <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
          </button>

          <button
            type="button"
            class="tr-rev-refresh"
            :disabled="loading"
            data-testid="cost-report-reload"
            @click="$emit('reload')"
          >
            <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
            <template v-else>{{ t('cost_report.btn_refresh') }}</template>
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="hasVisibleBarFilters"
      class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
    >
      <DatagridFilterField v-if="filterControlVisible.date" class="sm:col-span-2 xl:col-span-2">
        <FilterDatePicker
          :model-value="filters.from"
          :placeholder="t('dashboard_analytics.range_from')"
          :max-date="filters.to || null"
          input-id="cost-report-filter-from"
          @update:model-value="$emit('patch-filter', { from: $event })"
        />
      </DatagridFilterField>
      <DatagridFilterField v-if="filterControlVisible.date">
        <FilterDatePicker
          :model-value="filters.to"
          :placeholder="t('dashboard_analytics.range_to')"
          :min-date="filters.from || null"
          input-id="cost-report-filter-to"
          @update:model-value="$emit('patch-filter', { to: $event })"
        />
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.trip_type">
        <select
          :value="filters.trip_type"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('cost_report.filter_trip_type')"
          data-testid="cost-report-filter-trip-type"
          @change="$emit('patch-filter', { trip_type: $event.target.value })"
        >
          <option v-for="opt in tripTypeOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('cost_report.filter_trip_type') : opt.label }}
          </option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.status">
        <select
          :value="filters.status"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('filter_bar.status')"
          data-testid="cost-report-filter-status"
          @change="$emit('patch-filter', { status: $event.target.value })"
        >
          <option v-for="opt in statusOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('filter_bar.status') : opt.label }}
          </option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.fleet_mode">
        <select
          :value="filters.fleet_mode"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('dashboard_analytics.filter_fleet')"
          data-testid="cost-report-filter-fleet"
          @change="$emit('patch-filter', { fleet_mode: $event.target.value })"
        >
          <option v-for="opt in fleetOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('dashboard_analytics.filter_fleet') : opt.label }}
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
import FilterDatePicker from '../shared/ui/FilterDatePicker.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'

defineProps({
  filters: { type: Object, required: true },
  filterControlVisible: { type: Object, required: true },
  filterControlDefs: { type: Array, required: true },
  tripTypeOptions: { type: Array, required: true },
  statusOptions: { type: Array, required: true },
  fleetOptions: { type: Array, required: true },
  hasVisibleBarFilters: { type: Boolean, default: false },
  showFilterPanel: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

defineEmits([
  'reload',
  'reset-filters',
  'patch-filter',
  'toggle-filter-panel',
  'close-filter-panel',
  'toggle-filter-control',
])

const { t } = useI18n()
const rootRef = ref(null)
useDetailsAutoCloseWithin(rootRef)

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'
</script>

<style scoped>
.tr-rev-refresh {
  @apply inline-flex h-10 shrink-0 items-center justify-center rounded-lg bg-teal-800 px-4 text-sm font-medium text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600/30 disabled:opacity-50 dark:bg-teal-700 dark:hover:bg-teal-600;
}
</style>
