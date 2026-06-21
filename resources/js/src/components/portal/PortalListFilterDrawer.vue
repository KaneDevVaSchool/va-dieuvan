<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import FilterDatePicker from '../shared/ui/FilterDatePicker.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  filterStatus: { type: String, default: 'all' },
  filterTripType: { type: String, default: 'all' },
  filterUrgent: { type: String, default: 'all' },
  filterExtracurricular: { type: String, default: 'all' },
  sort: { type: String, default: 'depart_desc' },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  showExtracurricularFilter: { type: Boolean, default: true },
  filterOptions: { type: Array, default: () => [] },
  sortOptions: { type: Array, default: () => [] },
  tripTypeOptions: { type: Array, default: () => [] },
  urgentOptions: { type: Array, default: () => [] },
  extracurricularOptions: { type: Array, default: () => [] },
  activeFilterCount: { type: Number, default: 0 },
})

const emit = defineEmits([
  'close',
  'apply',
  'reset',
  'update:filterStatus',
  'update:filterTripType',
  'update:filterUrgent',
  'update:filterExtracurricular',
  'update:sort',
  'update:dateFrom',
  'update:dateTo',
])

const { t } = useI18n()
const panelRef = ref(null)

function onKeydown(e) {
  if (e.key === 'Escape' && props.open) emit('close')
}

watch(
  () => props.open,
  (v) => {
    document.body.style.overflow = v ? 'hidden' : ''
  },
)

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex justify-end" role="presentation">
      <button
        type="button"
        class="absolute inset-0 bg-slate-900/40"
        :aria-label="t('portal.shell.filter_close')"
        data-testid="portal-filter-drawer-backdrop"
        @click="emit('close')"
      />
      <aside
        ref="panelRef"
        class="relative flex h-full w-full max-w-md flex-col bg-white shadow-xl"
        role="dialog"
        aria-modal="true"
        :aria-label="t('portal.filter_toolbar_label')"
        data-testid="portal-filter-drawer"
      >
        <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <div>
            <p class="text-sm font-semibold text-slate-900">{{ t('portal.filter_toolbar_label') }}</p>
            <p v-if="activeFilterCount > 0" class="text-xs text-slate-500">
              {{ t('portal.shell.filter_active_count', { n: activeFilterCount }) }}
            </p>
          </div>
          <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
            :aria-label="t('portal.shell.filter_close')"
            @click="emit('close')"
          >
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </header>

        <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.filter_label_status') }}</span>
            <select
              :value="filterStatus"
              class="input h-10 w-full text-sm"
              data-testid="portal-filter-status"
              @change="emit('update:filterStatus', $event.target.value)"
            >
              <option v-for="opt in filterOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
            </select>
          </label>

          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.filter_label_sort') }}</span>
            <select
              :value="sort"
              class="input h-10 w-full text-sm"
              @change="emit('update:sort', $event.target.value)"
            >
              <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </label>

          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.filter_label_trip_type') }}</span>
            <select
              :value="filterTripType"
              class="input h-10 w-full text-sm"
              @change="emit('update:filterTripType', $event.target.value)"
            >
              <option v-for="opt in tripTypeOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
            </select>
          </label>

          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.filter_label_urgent') }}</span>
            <select
              :value="filterUrgent"
              class="input h-10 w-full text-sm"
              @change="emit('update:filterUrgent', $event.target.value)"
            >
              <option v-for="opt in urgentOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
            </select>
          </label>

          <label v-if="showExtracurricularFilter" class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('portal.filter_label_extracurricular') }}</span>
            <select
              :value="filterExtracurricular"
              class="input h-10 w-full text-sm"
              @change="emit('update:filterExtracurricular', $event.target.value)"
            >
              <option v-for="opt in extracurricularOptions" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
            </select>
          </label>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

        <footer class="flex gap-2 border-t border-slate-100 px-4 py-3">
          <button
            type="button"
            class="h-10 flex-1 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            data-testid="portal-filter-reset"
            @click="emit('reset')"
          >
            {{ t('portal.filter_clear_all') }}
          </button>
          <button
            type="button"
            class="h-10 flex-1 rounded-lg bg-va-800 text-sm font-semibold text-white hover:bg-va-900"
            data-testid="portal-filter-apply"
            @click="emit('apply')"
          >
            {{ t('portal.shell.filter_apply') }}
          </button>
        </footer>
      </aside>
    </div>
  </Teleport>
</template>
