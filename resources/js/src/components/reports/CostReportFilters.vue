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

          <div v-if="canExport" ref="exportMenuRef" class="relative">
            <DatagridToolbarActionButton
              icon="export"
              :active="showExportMenu"
              :disabled="!!exporting"
              test-id="cost-report-filters-export"
              @click="toggleExportMenu"
            >
              {{ t('cost_report.toolbar_export') }}
            </DatagridToolbarActionButton>
            <div
              v-if="showExportMenu"
              class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[210px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
              @click.stop
            >
              <p class="px-3 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_report.export_group_filtered') }}
              </p>
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="cost-report-filters-export-xlsx"
                :disabled="!!exporting"
                @click="onExportXlsx"
              >
                {{ t('cost_report.btn_export_xlsx') }}
              </button>
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="cost-report-filters-export-pdf"
                :disabled="!!exporting"
                @click="onExportPdf"
              >
                {{ t('cost_report.btn_export_pdf') }}
              </button>
              <div class="my-1 border-t border-slate-100 dark:border-slate-700" />
              <p class="px-3 pb-1 pt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_report.export_group_all') }}
              </p>
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="cost-report-filters-export-xlsx-all"
                :disabled="!!exporting"
                @click="onExportXlsxAll"
              >
                {{ t('cost_report.btn_export_xlsx_all') }}
              </button>
              <button
                type="button"
                class="flex w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                data-testid="cost-report-filters-export-pdf-all"
                :disabled="!!exporting"
                @click="onExportPdfAll"
              >
                {{ t('cost_report.btn_export_pdf_all') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="hasVisibleBarFilters"
      class="grid grid-cols-1 gap-3 border-t border-slate-100 px-4 py-4 sm:grid-cols-2 sm:px-5 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
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

      <DatagridFilterField v-if="filterControlVisible.cost_type">
        <select
          :value="filters.type"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('cost_report.filter_cost_type')"
          data-testid="cost-report-filter-cost-type"
          @change="$emit('patch-filter', { type: $event.target.value })"
        >
          <option v-for="opt in costTypeOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('cost_report.filter_cost_type') : opt.label }}
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

      <DatagridFilterField v-if="filterControlVisible.provider">
        <select
          :value="filters.provider"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('cost_report.filter_provider')"
          data-testid="cost-report-filter-provider"
          @change="$emit('patch-filter', { provider: $event.target.value })"
        >
          <option v-for="opt in providerOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('cost_report.filter_provider') : opt.label }}
          </option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.unit">
        <select
          :value="filters.unit"
          :class="FILTER_CONTROL_CLASS"
          :aria-label="t('cost_report.filter_unit')"
          data-testid="cost-report-filter-unit"
          @change="$emit('patch-filter', { unit: $event.target.value })"
        >
          <option v-for="opt in unitOptions" :key="opt.value || '_all'" :value="opt.value">
            {{ opt.value === '' ? t('cost_report.filter_unit') : opt.label }}
          </option>
        </select>
      </DatagridFilterField>

      <DatagridFilterField v-if="filterControlVisible.amount" class="sm:col-span-2">
        <div class="flex items-center gap-2">
          <input
            :value="filters.min_amount"
            type="number"
            min="0"
            inputmode="numeric"
            :class="FILTER_CONTROL_CLASS"
            :placeholder="t('cost_report.filter_amount_min_ph')"
            :aria-label="t('cost_report.filter_amount_min_ph')"
            data-testid="cost-report-filter-min-amount"
            @input="$emit('patch-filter', { min_amount: $event.target.value })"
          />
          <span class="shrink-0 text-slate-400">—</span>
          <input
            :value="filters.max_amount"
            type="number"
            min="0"
            inputmode="numeric"
            :class="FILTER_CONTROL_CLASS"
            :placeholder="t('cost_report.filter_amount_max_ph')"
            :aria-label="t('cost_report.filter_amount_max_ph')"
            data-testid="cost-report-filter-max-amount"
            @input="$emit('patch-filter', { max_amount: $event.target.value })"
          />
        </div>
      </DatagridFilterField>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DatagridToolbarActionButton from '../shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../shared/ui/FilterDatePicker.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useExportDetailsMenu } from '../../composables/useExportDetailsMenu.js'

defineProps({
  filters: { type: Object, required: true },
  filterControlVisible: { type: Object, required: true },
  filterControlDefs: { type: Array, required: true },
  tripTypeOptions: { type: Array, required: true },
  costTypeOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, required: true },
  fleetOptions: { type: Array, required: true },
  providerOptions: { type: Array, default: () => [] },
  unitOptions: { type: Array, default: () => [] },
  hasVisibleBarFilters: { type: Boolean, default: false },
  showFilterPanel: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  exporting: { type: String, default: null },
  canExport: { type: Boolean, default: true },
})

const emit = defineEmits([
  'export-xlsx',
  'export-pdf',
  'export-xlsx-all',
  'export-pdf-all',
  'patch-filter',
  'toggle-filter-panel',
  'close-filter-panel',
  'toggle-filter-control',
])

const { t } = useI18n()
const { exportMenuRef, showExportMenu, toggleExportMenu, closeExportMenu } = useExportDetailsMenu()
const rootRef = ref(null)
useDetailsAutoCloseWithin(rootRef)

function onExportXlsx() {
  closeExportMenu()
  emit('export-xlsx')
}

function onExportPdf() {
  closeExportMenu()
  emit('export-pdf')
}

function onExportXlsxAll() {
  closeExportMenu()
  emit('export-xlsx-all')
}

function onExportPdfAll() {
  closeExportMenu()
  emit('export-pdf-all')
}

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'
</script>

