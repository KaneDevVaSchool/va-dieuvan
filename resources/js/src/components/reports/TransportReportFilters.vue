<template>
  <div
    ref="transportReportFilterBarRef"
    class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
  >
    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
      <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
        <div class="min-w-0 w-full basis-full lg:flex-1 lg:basis-auto">
          <p class="truncate text-sm text-slate-600 dark:text-slate-400">
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ rangeDisplayFormatted }}</span>
            <span
              v-if="rangeValid && rangeDaySpan > 0 && preset !== 'all'"
              class="ml-1 text-xs text-violet-700 dark:text-violet-300"
            >
              ({{ t('dashboard_analytics.date_range_span', { n: rangeDaySpan }) }})
            </span>
          </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <FilterVisibilityDropdown
            :open="showFilterPanelDd"
            :title="t('trips_page.filter_show_controls_title')"
            :hint="t('trips_page.filter_show_controls_hint')"
            @close="closeFilterPanel"
          >
            <template #trigger>
              <DatagridToolbarActionButton
                icon="filter"
                :active="showFilterPanelDd"
                test-id="reports-toolbar-filter"
                @click="openFilterPanel()"
              >
                {{ t('requests_page.toolbar_filter') }}
              </DatagridToolbarActionButton>
            </template>
            <li v-for="opt in filterBarVisibilityOptions" :key="'rep-vis-' + opt.id" class="flex items-start gap-2">
              <input
                :id="`reports-filter-vis-${opt.id}`"
                v-model="filterBarVisible[opt.id]"
                type="checkbox"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30"
                :data-testid="`reports-filter-vis-${opt.id}`"
              />
              <label
                :for="`reports-filter-vis-${opt.id}`"
                class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
              >
                {{ t(opt.labelKey) }}
              </label>
            </li>
          </FilterVisibilityDropdown>

          <button
            type="button"
            class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
            :title="t('dashboard_analytics.filter_clear_all')"
            :aria-label="t('dashboard_analytics.filter_clear_all')"
            data-testid="reports-reset-filters"
            @click="resetFilters"
          >
            <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="hasVisibleBarFilters"
      class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
    >
      <DatagridFilterField v-if="filterBarVisible.period">
        <select
          :value="preset"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('dashboard_analytics.filter_period_label')"
          data-testid="reports-filter-period"
          @change="onPresetSelectChange($event.target.value)"
        >
          <option v-for="p in presetDefs" :key="p.id" :value="p.id">{{ p.label }}</option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterBarVisible.dates">
        <FilterDatePicker
          v-model="rangeFrom"
          :placeholder="t('dashboard_analytics.range_from')"
          :max-date="rangeTo || null"
          input-id="reports-filter-from"
          @update:model-value="onDateFilterChange"
        />
      </DatagridFilterField>

      <DatagridFilterField v-if="filterBarVisible.dates">
        <FilterDatePicker
          v-model="rangeTo"
          :placeholder="t('dashboard_analytics.range_to')"
          :min-date="rangeFrom || null"
          input-id="reports-filter-to"
          @update:model-value="onDateFilterChange"
        />
      </DatagridFilterField>

      <DatagridFilterField v-for="fd in visibleDimensionFilters" :key="fd.id">
        <select
          :value="dimensionSelectValue(fd)"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="fd.label"
          :data-testid="`reports-filter-${fd.id}`"
          @change="onDimensionSelect(fd, $event.target.value)"
        >
          <option v-for="(opt, optIdx) in dimensionSelectOptions(fd)" :key="`${fd.id}-${optIdx}`" :value="opt.value">
            {{ opt.value === '' ? fd.label : opt.label }}
          </option>
        </select>
      </DatagridFilterField>

      <div v-if="activeFilterCount > 0" class="col-span-full flex justify-end">
        <button type="button" class="text-xs font-medium text-va-800 hover:underline" @click="resetFilters">
          {{ t('dashboard_analytics.filter_clear_all') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import DatagridToolbarActionButton from '../shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../shared/ui/FilterDatePicker.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'

const { t } = useI18n()
const transportReportFilterBarRef = ref(null)
useDetailsAutoCloseWithin(transportReportFilterBarRef)

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const DIMENSION_VIS_LABEL_KEYS = {
  trip_type: 'requests_page.filter_vis_trip_type',
  channel: 'trips_page.filter_vis_channel',
  paper: 'trips_page.filter_vis_paper',
  urgent: 'trips_page.filter_vis_urgent',
  trip_run: 'trips_page.filter_vis_run',
  fleet: 'trips_page.filter_vis_fleet',
}

const {
  activeFilterCount,
  applyPreset,
  filterBarVisible,
  hasVisibleBarFilters,
  reportFilterBarVisIds,
  onRangeFromChange,
  onRangeToChange,
  preset,
  presetDefs,
  rangeDaySpan,
  rangeDisplayFormatted,
  rangeFrom,
  rangeTo,
  rangeValid,
  reloadSummary,
  resetFilters,
  visibleDimensionFilters,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useTransportReportSummary()

const filterBarVisibilityOptions = computed(() =>
  (reportFilterBarVisIds || []).map((id) => {
    if (id === 'period') return { id, labelKey: 'trips_page.filter_vis_period' }
    if (id === 'dates') return { id, labelKey: 'trips_page.filter_vis_dates' }
    return { id, labelKey: DIMENSION_VIS_LABEL_KEYS[id] || `trips_page.filter_vis_${id}` }
  }),
)

function onPresetSelectChange(id) {
  applyPreset(id)
}

function onDateFilterChange() {
  onRangeFromChange()
  onRangeToChange()
  if (rangeValid.value) reloadSummary()
}

function dimensionSelectOptions(fd) {
  return (fd.options || []).filter((o) => o && !o.header)
}

function dimensionSelectValue(fd) {
  const selected = (fd.options || []).find((o) => o && !o.header && fd.isSelected(o.value))
  return selected?.value ?? ''
}

function onDimensionSelect(fd, raw) {
  fd.pick(raw)
}
</script>
