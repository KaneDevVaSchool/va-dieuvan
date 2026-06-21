<template>
  <div
    v-if="hasVisibleBarFilters"
    class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
  >
    <DatagridFilterField v-if="filterControlVisible.class_name">
      <select
        :value="filters.class_name"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_class')"
        data-testid="tp-student-filter-class"
        @change="$emit('patch-filter', { class_name: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_class') }}</option>
        <option v-for="c in filterOptions.classes" :key="c" :value="c">{{ c }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.transport_status">
      <select
        :value="filters.transport_status"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_transport_status')"
        data-testid="tp-student-filter-transport-status"
        @change="$emit('patch-filter', { transport_status: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_transport_status') }}</option>
        <option v-for="opt in transportStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.program_id">
      <select
        :value="filters.program_id"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_program')"
        data-testid="tp-student-filter-program"
        @change="$emit('patch-filter', { program_id: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_program') }}</option>
        <option v-for="p in filterOptions.programs" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.grade">
      <select
        :value="filters.grade"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_grade')"
        data-testid="tp-student-filter-grade"
        @change="$emit('patch-filter', { grade: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_grade') }}</option>
        <option v-for="g in filterOptions.grades" :key="g" :value="g">{{ g }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.student_status">
      <select
        :value="filters.student_status"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_student_status')"
        data-testid="tp-student-filter-student-status"
        @change="$emit('patch-filter', { student_status: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_student_status') }}</option>
        <option v-for="opt in studentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.gender">
      <select
        :value="filters.gender"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_gender')"
        data-testid="tp-student-filter-gender"
        @change="$emit('patch-filter', { gender: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_gender') }}</option>
        <option v-for="g in filterOptions.genders || []" :key="g.value" :value="g.value">{{ g.label }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.pickup_point && (filterOptions.pickup_points || []).length">
      <select
        :value="filters.pickup_point"
        :class="FILTER_CONTROL_CLASS"
        :aria-label="t('tp_student_page.filter_pickup')"
        data-testid="tp-student-filter-pickup"
        @change="$emit('patch-filter', { pickup_point: $event.target.value })"
      >
        <option value="">{{ t('tp_student_page.filter_pickup') }}</option>
        <option v-for="p in filterOptions.pickup_points" :key="p" :value="p">{{ p }}</option>
      </select>
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.parent_phone">
      <input
        :value="filters.parent_phone"
        type="search"
        :class="FILTER_CONTROL_CLASS"
        :placeholder="t('tp_student_page.filter_parent_phone')"
        :aria-label="t('tp_student_page.filter_parent_phone')"
        data-testid="tp-student-filter-parent-phone"
        @input="$emit('patch-filter', { parent_phone: $event.target.value })"
      />
    </DatagridFilterField>

    <DatagridFilterField v-if="filterControlVisible.address_contains" class="sm:col-span-2 xl:col-span-2">
      <input
        :value="filters.address_contains"
        type="search"
        :class="FILTER_CONTROL_CLASS"
        :placeholder="t('tp_student_page.filter_address')"
        :aria-label="t('tp_student_page.filter_address')"
        data-testid="tp-student-filter-address"
        @input="$emit('patch-filter', { address_contains: $event.target.value })"
      />
    </DatagridFilterField>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import DatagridFilterField from '../shared/ui/DatagridFilterField.vue'

defineProps({
  filters: { type: Object, required: true },
  filterControlVisible: { type: Object, required: true },
  filterOptions: { type: Object, required: true },
  hasVisibleBarFilters: { type: Boolean, default: false },
  transportStatusOptions: { type: Array, required: true },
  studentStatusOptions: { type: Array, required: true },
})

defineEmits(['patch-filter'])

const { t } = useI18n()

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'
</script>
