<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import DatagridToolbarSearch from '../shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../shared/ui/DatagridToolbarActionButton.vue'
import DatagridSegmentedControl from '../shared/ui/DatagridSegmentedControl.vue'
import FilterVisibilityDropdown from '../shared/ui/FilterVisibilityDropdown.vue'

const props = defineProps({
  searchInput: { type: String, default: '' },
  quickFilter: { type: String, default: 'all' },
  exportOpen: { type: Boolean, default: false },
  showSuggest: { type: Boolean, default: false },
  searchSuggestLoading: { type: Boolean, default: false },
  searchSuggestions: { type: Array, default: () => [] },
  searchSuggestFocus: { type: Number, default: -1 },
  showFilterPanelDd: { type: Boolean, default: false },
  filterControlDefs: { type: Array, default: () => [] },
  visibleFilters: { type: Object, required: true },
  activeFilterCount: { type: Number, default: 0 },
  /** Override default quick status segments (e.g. extracurricular module). */
  quickFilterOptions: { type: Array, default: null },
})

const emit = defineEmits([
  'update:searchInput',
  'update:quickFilter',
  'update:exportOpen',
  'update:showFilterPanelDd',
  'search-input',
  'search-enter',
  'search-suggest-pick',
  'toggle-filter-panel',
  'close-filter-panel',
  'reset-filters',
  'export-csv',
  'export-excel',
])

const { t } = useI18n()
const exportRef = ref(null)

const quickOptions = computed(() => {
  if (props.quickFilterOptions?.length) return props.quickFilterOptions
  return [
    { value: 'all', label: t('portal.shell.quick_all') },
    { value: 'pending', label: t('portal.filter_pending') },
    { value: 'processing', label: t('portal.shell.quick_processing') },
    { value: 'done', label: t('portal.shell.quick_done') },
  ]
})

function onDocPointerDown(e) {
  if (exportRef.value && !exportRef.value.contains(e.target)) {
    emit('update:exportOpen', false)
  }
}

onMounted(() => document.addEventListener('pointerdown', onDocPointerDown, true))
onBeforeUnmount(() => document.removeEventListener('pointerdown', onDocPointerDown, true))

function toggleExport() {
  emit('close-filter-panel')
  emit('update:exportOpen', !props.exportOpen)
}
</script>

<template>
  <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
    <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
      <div class="relative min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
        <DatagridToolbarSearch
          :model-value="searchInput"
          input-id="portal-list-q"
          :placeholder="t('portal.shell.list_search_placeholder')"
          :aria-label="t('portal.search_placeholder')"
          hide-label
          stretch
          inline-actions
          input-height="h-10"
          @update:model-value="emit('update:searchInput', $event); emit('search-input')"
          @enter="emit('search-enter')"
        />
        <ul
          v-if="showSuggest"
          id="portal-list-q-suggest"
          class="absolute z-[110] mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-slate-900/5"
          role="listbox"
          :aria-label="t('portal.search_suggest_aria')"
        >
          <li v-if="searchSuggestLoading" class="px-3 py-2.5 text-slate-500">
            {{ t('portal.search_suggest_loading') }}
          </li>
          <template v-else-if="searchSuggestions.length">
            <li v-for="(req, idx) in searchSuggestions" :key="req.id" role="presentation">
              <button
                type="button"
                role="option"
                :aria-selected="idx === searchSuggestFocus"
                class="flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition"
                :class="idx === searchSuggestFocus ? 'bg-va-50' : 'hover:bg-slate-50'"
                @mousedown.prevent="emit('search-suggest-pick', req)"
              >
                <slot name="suggest-row" :req="req" />
              </button>
            </li>
          </template>
          <li v-else class="px-3 py-2.5 text-slate-500">{{ t('portal.search_suggest_empty') }}</li>
        </ul>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <FilterVisibilityDropdown
          :open="showFilterPanelDd"
          :title="t('portal.filter_show_controls_title')"
          :hint="t('portal.filter_show_controls_hint')"
          @close="emit('close-filter-panel')"
        >
          <template #trigger>
            <DatagridToolbarActionButton
              icon="filter"
              :active="showFilterPanelDd"
              test-id="portal-toolbar-filter"
              @click="emit('toggle-filter-panel')"
            >
              {{ t('portal.shell.toolbar_filter') }}
            </DatagridToolbarActionButton>
          </template>
          <li
            v-for="fd in filterControlDefs"
            :key="'portal-filter-vis-' + fd.key"
            class="flex items-start gap-2"
          >
            <input
              :id="`portal-filter-vis-${fd.key}`"
              v-model="visibleFilters[fd.key]"
              type="checkbox"
              class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30"
              :data-testid="`portal-filter-vis-${fd.key}`"
            />
            <label
              :for="`portal-filter-vis-${fd.key}`"
              class="cursor-pointer text-sm leading-snug text-slate-700"
            >
              {{ fd.label }}
            </label>
          </li>
        </FilterVisibilityDropdown>

        <button
          v-if="activeFilterCount > 0"
          type="button"
          class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
          :title="t('portal.filter_clear_all')"
          :aria-label="t('portal.filter_clear_all')"
          data-testid="portal-toolbar-reset-filters"
          @click="emit('reset-filters')"
        >
          <FunnelIcon class="h-5 w-5" aria-hidden="true" />
          <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
        </button>

        <div ref="exportRef" class="relative">
          <DatagridToolbarActionButton
            icon="export"
            :active="exportOpen"
            test-id="portal-toolbar-export"
            @click="toggleExport"
          >
            {{ t('portal.shell.toolbar_export') }}
          </DatagridToolbarActionButton>
          <div
            v-if="exportOpen"
            class="absolute right-0 z-50 mt-1 min-w-[10rem] overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-slate-900/5"
            role="menu"
          >
            <button
              type="button"
              role="menuitem"
              class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              data-testid="portal-export-excel"
              @click="emit('export-excel'); emit('update:exportOpen', false)"
            >
              Excel
            </button>
            <button
              type="button"
              role="menuitem"
              class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              data-testid="portal-export-csv"
              @click="emit('export-csv'); emit('update:exportOpen', false)"
            >
              CSV
            </button>
          </div>
        </div>
      </div>

      <div class="ml-auto flex w-full shrink-0 basis-full lg:w-auto lg:basis-auto [&_button]:min-h-10 [&_button]:px-2.5 sm:[&_button]:min-h-0">
        <DatagridSegmentedControl
          :model-value="quickFilter"
          :options="quickOptions"
          :aria-label="t('portal.filter_label_status')"
          @update:model-value="emit('update:quickFilter', $event)"
        />
      </div>
    </div>
  </div>
</template>
