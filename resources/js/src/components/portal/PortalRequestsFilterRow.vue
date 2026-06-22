<script setup>
import { useI18n } from 'vue-i18n'
import FilterDatePicker from '../shared/ui/FilterDatePicker.vue'

const FILTER_CONTROL_CLASS = 'input h-10 w-full text-sm'

defineProps({
  visibleFilters: { type: Object, required: true },
  filterStatus: { type: String, default: 'all' },
  filterTripType: { type: String, default: 'all' },
  filterUrgent: { type: String, default: 'all' },
  filterExtracurricular: { type: String, default: 'all' },
  sort: { type: String, default: 'depart_desc' },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  showExtracurricularFilter: { type: Boolean, default: true },
  showTripTypeFilter: { type: Boolean, default: true },
  showUrgentFilter: { type: Boolean, default: true },
  filterOptions: { type: Array, default: () => [] },
  sortOptions: { type: Array, default: () => [] },
  tripTypeOptions: { type: Array, default: () => [] },
  urgentOptions: { type: Array, default: () => [] },
  extracurricularOptions: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'update:filterStatus',
  'update:filterTripType',
  'update:filterUrgent',
  'update:filterExtracurricular',
  'update:sort',
  'update:dateFrom',
  'update:dateTo',
  'filter-change',
])

const { t } = useI18n()

function onFieldChange() {
  emit('filter-change')
}
</script>

<template>
  <div
    class="grid grid-cols-1 gap-3 border-b border-slate-100 px-4 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 sm:px-5"
    data-testid="portal-requests-filter-row"
  >
    <div v-if="visibleFilters.status">
      <select
        :value="filterStatus"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('portal.filter_label_status')"
        data-testid="portal-filter-status"
        @change="emit('update:filterStatus', $event.target.value); onFieldChange()"
      >
        <option v-for="opt in filterOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
      </select>
    </div>

    <div v-if="visibleFilters.sort">
      <select
        :value="sort"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('portal.filter_label_sort')"
        data-testid="portal-filter-sort"
        @change="emit('update:sort', $event.target.value); onFieldChange()"
      >
        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </div>

    <div v-if="showTripTypeFilter && visibleFilters.trip_type">
      <select
        :value="filterTripType"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('portal.filter_label_trip_type')"
        data-testid="portal-filter-trip-type"
        @change="emit('update:filterTripType', $event.target.value); onFieldChange()"
      >
        <option v-for="opt in tripTypeOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
      </select>
    </div>

    <div v-if="showUrgentFilter && visibleFilters.urgent">
      <select
        :value="filterUrgent"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('portal.filter_label_urgent')"
        data-testid="portal-filter-urgent"
        @change="emit('update:filterUrgent', $event.target.value); onFieldChange()"
      >
        <option v-for="opt in urgentOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
      </select>
    </div>

    <div v-if="showExtracurricularFilter && visibleFilters.extracurricular">
      <select
        :value="filterExtracurricular"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('portal.filter_label_extracurricular')"
        data-testid="portal-filter-extracurricular"
        @change="emit('update:filterExtracurricular', $event.target.value); onFieldChange()"
      >
        <option v-for="opt in extracurricularOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
      </select>
    </div>

    <div
      v-if="visibleFilters.date_range"
      class="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2 xl:col-span-2"
    >
      <FilterDatePicker
        :model-value="dateFrom"
        :placeholder="t('portal.filter_date_from')"
        @update:model-value="emit('update:dateFrom', $event)"
      />
      <FilterDatePicker
        :model-value="dateTo"
        :placeholder="t('portal.filter_date_to')"
        @update:model-value="emit('update:dateTo', $event)"
      />
    </div>
  </div>
</template>
