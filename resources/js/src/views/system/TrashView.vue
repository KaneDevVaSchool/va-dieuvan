<template>
  <div class="min-w-0 space-y-6 pb-10">

    <!-- Page header -->
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between dark:border-slate-700">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ t('trash_page.title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('trash_page.subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
        :disabled="loadingSummary || loadingItems"
        data-testid="trash-reload"
        @click="reload"
      >
        <ArrowPathIcon class="h-4 w-4" :class="(loadingSummary || loadingItems) ? 'animate-spin' : ''" aria-hidden="true" />
        {{ t('trash_page.reload') }}
      </button>
    </div>

    <!-- KPI summary strip -->
    <TrashSummaryBar
      :summary="summary"
      :loading="loadingSummary"
      :active-type="activeType"
      @quick-filter="onKpiQuickFilter"
    />

    <!-- Data card -->
    <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">

      <!-- Toolbar row -->
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">

          <!-- Search -->
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="trash-search"
              :placeholder="t('trash_page.search_ph')"
              :aria-label="t('trash_page.search_aria')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
            />
          </div>

          <!-- Action buttons -->
          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('trash_page.filter_panel_title')"
              :hint="t('trash_page.filter_panel_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="trash-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('trash_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li
                v-for="fd in filterControlDefs"
                :key="'trash-vis-' + fd.key"
                class="flex items-start gap-2"
              >
                <input
                  :id="`trash-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`trash-filter-vis-${fd.key}`"
                />
                <label
                  :for="`trash-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <!-- Bulk restore -->
            <button
              v-if="selected.size > 0"
              type="button"
              class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
              :disabled="busyRestore"
              data-testid="trash-bulk-restore"
              @click="confirmBulkRestore"
            >
              <ArrowUturnUpIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trash_page.btn_restore') }} ({{ selected.size }})
            </button>

            <!-- Bulk force-delete -->
            <button
              v-if="selected.size > 0"
              type="button"
              class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-950/30 dark:text-rose-400"
              :disabled="busyDelete"
              data-testid="trash-bulk-force-delete"
              @click="confirmBulkForceDelete"
            >
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trash_page.btn_force_delete') }} ({{ selected.size }})
            </button>
          </div>

        </div>
      </div>

      <!-- Filter row (date_range) -->
      <Transition name="fade-slide">
        <div
          v-if="hasFilterRow"
          class="grid grid-cols-1 gap-3 border-b border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
        >
          <div v-if="visibleFilters.date_range" class="min-w-0 w-full sm:col-span-2 xl:col-span-2">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <FilterDatePicker
                v-model="filters.from"
                :placeholder="t('trash_page.filter_from')"
                :max-date="filters.to || null"
                input-id="trash-filter-from"
                @update:model-value="onDateFilterChange"
              />
              <FilterDatePicker
                v-model="filters.to"
                :placeholder="t('trash_page.filter_to')"
                :min-date="filters.from || null"
                input-id="trash-filter-to"
                @update:model-value="onDateFilterChange"
              />
            </div>
          </div>

          <div v-if="activeFilterCount > 0" class="col-span-full flex justify-end">
            <button
              type="button"
              class="text-xs font-medium text-va-800 hover:text-va-700 dark:text-va-300"
              data-testid="trash-clear-filters"
              @click="clearFilters"
            >
              {{ t('trash_page.clear_filters') }}
            </button>
          </div>
        </div>
      </Transition>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-800/30">
              <th class="w-10 px-4 py-2.5">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-va-700 focus:ring-va-600/30 dark:border-slate-600"
                  :checked="allOnPageSelected"
                  :indeterminate="someSelected"
                  data-testid="trash-select-all"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trash_page.col_type') }}
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trash_page.col_record') }}
              </th>
              <th class="hidden px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell dark:text-slate-400">
                {{ t('trash_page.col_deleted_at') }}
              </th>
              <th class="w-32 px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trash_page.col_actions') }}
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">

            <!-- Loading skeleton -->
            <template v-if="loadingItems">
              <tr v-for="i in 8" :key="`sk-${i}`">
                <td class="px-4 py-3">
                  <div class="h-4 w-4 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
                </td>
                <td class="px-3 py-3">
                  <div class="h-5 w-20 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
                </td>
                <td class="px-3 py-3">
                  <div class="h-4 w-48 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
                  <div class="mt-1.5 h-3 w-32 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
                </td>
                <td class="hidden px-3 py-3 sm:table-cell">
                  <div class="h-4 w-28 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
                </td>
                <td class="px-3 py-3">
                  <div class="ml-auto h-7 w-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-700" />
                </td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="items.length === 0">
              <td colspan="5" class="px-5 py-16 text-center">
                <TrashIcon class="mx-auto mb-3 h-10 w-10 text-slate-300 dark:text-slate-600" aria-hidden="true" />
                <p class="font-semibold text-slate-700 dark:text-slate-300">{{ t('trash_page.empty_title') }}</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('trash_page.empty_body') }}</p>
              </td>
            </tr>

            <!-- Data rows -->
            <tr
              v-for="item in items"
              :key="`${item.type}-${item.id}`"
              class="group transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30"
              :class="selected.has(itemKey(item)) ? 'bg-va-50/30 dark:bg-va-900/10' : ''"
            >
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-va-700 focus:ring-va-600/30 dark:border-slate-600"
                  :checked="selected.has(itemKey(item))"
                  :data-testid="`trash-row-check-${item.type}-${item.id}`"
                  @change="toggleItem(item)"
                />
              </td>
              <td class="px-3 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                  :class="typeConfig(item.type)?.badgeClass ?? 'bg-slate-100 text-slate-600'"
                >
                  {{ typeConfig(item.type)?.label ?? item.type }}
                </span>
              </td>
              <td class="max-w-xs px-3 py-3">
                <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ item.label }}</p>
                <p v-if="item.sublabel" class="mt-0.5 truncate text-[11px] text-slate-400">{{ item.sublabel }}</p>
              </td>
              <td class="hidden whitespace-nowrap px-3 py-3 text-sm text-slate-500 sm:table-cell dark:text-slate-400">
                {{ formatDate(item.deleted_at) }}
              </td>
              <td class="px-3 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                    :disabled="busyRestore"
                    :data-testid="`trash-restore-${item.type}-${item.id}`"
                    @click="singleRestore(item)"
                  >
                    <ArrowUturnUpIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('trash_page.btn_restore') }}
                  </button>
                  <button
                    type="button"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-xs font-medium text-rose-600 transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-400"
                    :disabled="busyDelete"
                    :title="t('trash_page.btn_force_delete')"
                    :data-testid="`trash-force-delete-${item.type}-${item.id}`"
                    @click="singleForceDelete(item)"
                  >
                    <TrashIcon class="h-3.5 w-3.5" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="meta && meta.last_page > 1"
        class="flex items-center justify-between border-t border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5"
      >
        <p class="text-xs text-slate-400 dark:text-slate-500">
          {{ t('trash_page.pagination_info', { from: paginationFrom, to: paginationTo, total: meta.total }) }}
        </p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="currentPage <= 1"
            data-testid="trash-prev-page"
            @click="goPage(currentPage - 1)"
          >
            <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <span class="min-w-[4.5rem] text-center text-xs font-medium text-slate-600 dark:text-slate-300">
            {{ t('trash_page.page_of', { cur: currentPage, last: meta.last_page }) }}
          </span>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="currentPage >= meta.last_page"
            data-testid="trash-next-page"
            @click="goPage(currentPage + 1)"
          >
            <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </div>

    </div>

    <!-- Confirm restore dialog -->
    <Teleport to="body">
      <div
        v-if="confirmRestore"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
        @click.self="confirmRestore = null"
      >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
          <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ t('trash_page.confirm_restore_title') }}</h2>
          <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ t('trash_page.confirm_restore_body', { count: confirmRestore.ids.length }) }}</p>
          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
              @click="confirmRestore = null"
            >{{ t('trash_page.cancel') }}</button>
            <button
              type="button"
              class="inline-flex h-9 items-center rounded-xl bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
              :disabled="busyRestore"
              data-testid="trash-confirm-restore-ok"
              @click="executeRestore"
            >{{ t('trash_page.confirm_ok') }}</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirm force-delete dialog -->
    <Teleport to="body">
      <div
        v-if="confirmDelete"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
        @click.self="confirmDelete = null"
      >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900">
          <div class="mb-3 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-6 w-6 shrink-0 text-rose-500" aria-hidden="true" />
            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ t('trash_page.confirm_delete_title') }}</h2>
          </div>
          <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('trash_page.confirm_delete_body', { count: confirmDelete.ids.length }) }}</p>
          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
              @click="confirmDelete = null"
            >{{ t('trash_page.cancel') }}</button>
            <button
              type="button"
              class="inline-flex h-9 items-center rounded-xl bg-rose-600 px-4 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50"
              :disabled="busyDelete"
              data-testid="trash-confirm-delete-ok"
              @click="executeForceDelete"
            >{{ t('trash_page.confirm_delete_ok') }}</button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowPathIcon,
  ArrowUturnUpIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  TrashIcon,
  TruckIcon,
  ClipboardDocumentListIcon,
  RectangleStackIcon,
  AcademicCapIcon,
  CalendarDaysIcon,
  UserGroupIcon,
  Bars3Icon,
  BuildingOfficeIcon,
} from '@heroicons/vue/24/outline'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import TrashSummaryBar from '../../components/system/TrashSummaryBar.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { getTrashSummary, listTrash, restoreTrashItems, forceDeleteTrashItems } from '../../api/trash'

const { t, locale } = useI18n()

// ─── Filter controls (datagrid-toolbar pattern) ────────────────────────────────

const FILTER_CONTROLS = [
  { key: 'date_range', label: t('trash_page.filter_vis_date_range'), default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(FILTER_CONTROLS, 'va-dieuvan.trash.visible-filters.v1')

const filters = reactive({ from: '', to: '' })

const activeFilterCount = computed(() => {
  return (filters.from ? 1 : 0) + (filters.to ? 1 : 0)
})

function clearFilters() {
  filters.from = ''
  filters.to = ''
  currentPage.value = 1
  loadItems()
}

function onDateFilterChange() {
  currentPage.value = 1
  loadItems()
}

// ─── State ─────────────────────────────────────────────────────────────────────

const summary        = ref(null)
const loadingSummary = ref(false)
const items          = ref([])
const meta           = ref(null)
const loadingItems   = ref(false)
const currentPage    = ref(1)
const searchInput    = ref('')
const activeType     = ref(null)
const selected       = ref(new Set())
const busyRestore    = ref(false)
const busyDelete     = ref(false)
const confirmRestore = ref(null)
const confirmDelete  = ref(null)

// ─── Type badge configs ────────────────────────────────────────────────────────

const TYPE_BADGE = {
  dispatch_request:   { label: () => t('trash_page.type_dispatch_request'), badgeClass: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' },
  driver:             { label: () => t('trash_page.type_driver'),            badgeClass: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' },
  vehicle:            { label: () => t('trash_page.type_vehicle'),           badgeClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' },
  transport_provider: { label: () => t('trash_page.type_transport_provider'), badgeClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' },
  tp_student:         { label: () => t('trash_page.type_tp_student'),        badgeClass: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' },
  tp_program:         { label: () => t('trash_page.type_tp_program'),        badgeClass: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300' },
  role:               { label: () => t('trash_page.type_role'),              badgeClass: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' },
  menu_item:          { label: () => t('trash_page.type_menu_item'),         badgeClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' },
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const pageIds = computed(() => items.value.map(itemKey))

const allOnPageSelected = computed(() =>
  pageIds.value.length > 0 && pageIds.value.every(k => selected.value.has(k))
)
const someSelected = computed(() =>
  pageIds.value.some(k => selected.value.has(k)) && !allOnPageSelected.value
)
const paginationFrom = computed(() =>
  meta.value ? (meta.value.current_page - 1) * meta.value.per_page + 1 : 1
)
const paginationTo = computed(() =>
  meta.value ? Math.min(meta.value.current_page * meta.value.per_page, meta.value.total) : 0
)

// ─── Helpers ──────────────────────────────────────────────────────────────────

function itemKey(item) {
  return `${item.type}::${item.id}`
}

function typeConfig(type) {
  const cfg = TYPE_BADGE[type]
  if (!cfg) return null
  return { label: cfg.label(), badgeClass: cfg.badgeClass }
}

function formatDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString(locale.value === 'en' ? 'en-US' : 'vi-VN')
  } catch {
    return String(v)
  }
}

// ─── Data loading ─────────────────────────────────────────────────────────────

let searchTimer = null

async function loadSummary() {
  loadingSummary.value = true
  try {
    summary.value = await getTrashSummary()
  } catch {
    summary.value = null
  } finally {
    loadingSummary.value = false
  }
}

async function loadItems() {
  loadingItems.value = true
  selected.value = new Set()
  try {
    const result = await listTrash({
      type:     activeType.value ?? undefined,
      q:        searchInput.value || undefined,
      from:     filters.from || undefined,
      to:       filters.to || undefined,
      page:     currentPage.value,
      per_page: 20,
    })
    items.value = result.items ?? []
    meta.value  = result.meta ?? null
  } catch {
    items.value = []
    meta.value  = null
  } finally {
    loadingItems.value = false
  }
}

function reload() {
  loadSummary()
  loadItems()
}

onMounted(reload)

watch(activeType, () => {
  currentPage.value = 1
  loadItems()
})

watch(searchInput, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    loadItems()
  }, 350)
})

// ─── KPI quick-filter ─────────────────────────────────────────────────────────

function onKpiQuickFilter({ type }) {
  activeType.value = type
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function goPage(n) {
  currentPage.value = n
  loadItems()
}

// ─── Selection ────────────────────────────────────────────────────────────────

function toggleSelectAll() {
  if (allOnPageSelected.value) {
    for (const k of pageIds.value) selected.value.delete(k)
  } else {
    for (const k of pageIds.value) selected.value.add(k)
  }
  selected.value = new Set(selected.value)
}

function toggleItem(item) {
  const k = itemKey(item)
  const next = new Set(selected.value)
  if (next.has(k)) {
    next.delete(k)
  } else {
    next.add(k)
  }
  selected.value = next
}

// ─── Bulk / single actions ────────────────────────────────────────────────────

function groupSelectedByType() {
  const byType = {}
  for (const key of selected.value) {
    const sep = key.indexOf('::')
    const type = key.slice(0, sep)
    const id   = parseInt(key.slice(sep + 2), 10)
    if (!byType[type]) byType[type] = []
    byType[type].push(id)
  }
  return byType
}

function confirmBulkRestore() {
  confirmRestore.value = { byType: groupSelectedByType(), ids: [...selected.value] }
}

function confirmBulkForceDelete() {
  confirmDelete.value = { byType: groupSelectedByType(), ids: [...selected.value] }
}

function singleRestore(item) {
  confirmRestore.value = { byType: { [item.type]: [item.id] }, ids: [itemKey(item)] }
}

function singleForceDelete(item) {
  confirmDelete.value = { byType: { [item.type]: [item.id] }, ids: [itemKey(item)] }
}

async function executeRestore() {
  if (!confirmRestore.value) return
  busyRestore.value = true
  try {
    for (const [type, ids] of Object.entries(confirmRestore.value.byType)) {
      await restoreTrashItems({ type, ids })
    }
    confirmRestore.value = null
    selected.value = new Set()
    await Promise.all([loadSummary(), loadItems()])
  } finally {
    busyRestore.value = false
  }
}

async function executeForceDelete() {
  if (!confirmDelete.value) return
  busyDelete.value = true
  try {
    for (const [type, ids] of Object.entries(confirmDelete.value.byType)) {
      await forceDeleteTrashItems({ type, ids })
    }
    confirmDelete.value = null
    selected.value = new Set()
    await Promise.all([loadSummary(), loadItems()])
  } finally {
    busyDelete.value = false
  }
}
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
