<template>
  <div class="min-w-0 space-y-5 pb-10">

    <!-- Page header -->
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-end sm:justify-between dark:border-slate-700">
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

    <!-- KPI type filter strip -->
    <section
      class="kpi-strip rounded-xl border border-slate-200/80 bg-gradient-to-b from-slate-50/90 to-white shadow-sm dark:border-slate-700 dark:from-slate-800/60 dark:to-slate-900"
      aria-label="Thống kê thùng rác"
    >
      <div class="px-5 pb-4 pt-4">
        <div class="mb-3 flex items-center justify-between gap-2">
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-brand/80">THỐNG KÊ</p>
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('trash_page.kpi_title') }}</h2>
          </div>
          <p class="text-[11px] text-slate-400">{{ t('trash_page.kpi_hint') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-9">
          <!-- All -->
          <button
            type="button"
            class="kpi-card kpi-card--brand"
            :class="activeType === null ? 'kpi-card--active' : 'kpi-card--interactive'"
            :aria-pressed="activeType === null"
            data-testid="trash-filter-all"
            @click="setTypeFilter(null)"
          >
            <div class="kpi-card__inner">
              <div class="kpi-card__icon-cell">
                <TrashIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="kpi-card__body">
                <p class="kpi-card__label">{{ t('trash_page.type_all') }}</p>
                <p class="kpi-card__value">{{ loadingSummary ? '…' : fmtCount(summary?.counts?.total ?? 0) }}</p>
              </div>
            </div>
          </button>

          <!-- Per model type -->
          <button
            v-for="cfg in typeConfigs"
            :key="cfg.type"
            type="button"
            class="kpi-card"
            :class="[`kpi-card--${cfg.tone}`, activeType === cfg.type ? 'kpi-card--active' : 'kpi-card--interactive']"
            :aria-pressed="activeType === cfg.type"
            :data-testid="`trash-filter-${cfg.type}`"
            @click="setTypeFilter(cfg.type)"
          >
            <div class="kpi-card__inner">
              <div class="kpi-card__icon-cell">
                <component :is="cfg.icon" class="h-5 w-5" aria-hidden="true" />
              </div>
              <div class="kpi-card__body">
                <p class="kpi-card__label">{{ cfg.label }}</p>
                <p class="kpi-card__value">{{ loadingSummary ? '…' : fmtCount(summary?.counts?.[cfg.type] ?? 0) }}</p>
              </div>
            </div>
          </button>
        </div>
      </div>
    </section>

    <!-- Data card -->
    <div class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">

      <!-- Toolbar -->
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:flex-1 lg:basis-auto">
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

          <!-- Bulk actions (visible when rows selected) -->
          <template v-if="selected.size > 0">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-50 dark:border-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
              :disabled="busyRestore"
              data-testid="trash-bulk-restore"
              @click="confirmBulkRestore"
            >
              <ArrowUturnUpIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trash_page.btn_restore') }} ({{ selected.size }})
            </button>
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100 disabled:opacity-50 dark:border-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
              :disabled="busyDelete"
              data-testid="trash-bulk-force-delete"
              @click="confirmBulkForceDelete"
            >
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
              {{ t('trash_page.btn_force_delete') }} ({{ selected.size }})
            </button>
          </template>

          <span v-if="activeType !== null" class="ml-auto shrink-0 text-xs text-slate-400">
            {{ t('trash_page.showing_type', { type: activeTypeLabel }) }}
          </span>
        </div>
      </div>

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
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trash_page.col_deleted_at') }}
              </th>
              <th class="w-32 px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trash_page.col_actions') }}
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
            <!-- Loading -->
            <template v-if="loadingItems">
              <tr v-for="i in 6" :key="`sk-${i}`">
                <td class="px-4 py-3"><div class="h-4 w-4 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></td>
                <td class="px-3 py-3"><div class="h-5 w-20 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" /></td>
                <td class="px-3 py-3">
                  <div class="h-4 w-48 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
                  <div class="mt-1.5 h-3 w-32 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
                </td>
                <td class="px-3 py-3"><div class="h-4 w-28 animate-pulse rounded bg-slate-200 dark:bg-slate-700" /></td>
                <td class="px-3 py-3"><div class="h-8 w-20 animate-pulse rounded-xl bg-slate-200 dark:bg-slate-700 ml-auto" /></td>
              </tr>
            </template>

            <!-- Empty -->
            <tr v-else-if="items.length === 0">
              <td colspan="5" class="px-5 py-16 text-center">
                <TrashIcon class="mx-auto mb-3 h-10 w-10 text-slate-300 dark:text-slate-600" />
                <p class="font-medium text-slate-700 dark:text-slate-300">{{ t('trash_page.empty_title') }}</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('trash_page.empty_body') }}</p>
              </td>
            </tr>

            <!-- Rows -->
            <tr
              v-for="item in items"
              :key="`${item.type}-${item.id}`"
              class="group transition hover:bg-slate-50/70 dark:hover:bg-slate-800/30"
              :class="selected.has(itemKey(item)) ? 'bg-va-50/40 dark:bg-va-900/10' : ''"
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
                  class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                  :class="typeConfig(item.type)?.badgeClass"
                >
                  {{ typeConfig(item.type)?.label ?? item.type }}
                </span>
              </td>
              <td class="px-3 py-3 max-w-xs">
                <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ item.label }}</p>
                <p v-if="item.sublabel" class="truncate text-[11px] text-slate-400 mt-0.5">{{ item.sublabel }}</p>
              </td>
              <td class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                {{ formatDate(item.deleted_at) }}
              </td>
              <td class="px-3 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                    :disabled="busyRestore"
                    :data-testid="`trash-restore-${item.type}-${item.id}`"
                    @click="singleRestore(item)"
                  >
                    <ArrowUturnUpIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('trash_page.btn_restore') }}
                  </button>
                  <button
                    type="button"
                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2 text-xs font-medium text-rose-700 transition hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-400"
                    :disabled="busyDelete"
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

      <!-- Pagination (only when a specific type is selected) -->
      <div
        v-if="activeType !== null && meta && meta.last_page > 1"
        class="flex items-center justify-between border-t border-slate-100 px-5 py-3 dark:border-slate-700"
      >
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ t('trash_page.pagination_info', { from: paginationFrom, to: paginationTo, total: meta.total }) }}
        </p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-400"
            :disabled="currentPage <= 1"
            data-testid="trash-prev-page"
            @click="goPage(currentPage - 1)"
          >
            <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <span class="px-2 text-sm text-slate-600 dark:text-slate-400">{{ currentPage }} / {{ meta.last_page }}</span>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-400"
            :disabled="currentPage >= meta.last_page"
            data-testid="trash-next-page"
            @click="goPage(currentPage + 1)"
          >
            <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm restore modal -->
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

    <!-- Confirm force-delete modal -->
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
import { computed, onMounted, ref, watch } from 'vue'
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
import { getTrashSummary, listTrash, restoreTrashItems, forceDeleteTrashItems } from '../../api/trash'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'

const { t, locale } = useI18n()

// ─── State ───────────────────────────────────────────────────────────────────

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

// ─── Type configs ─────────────────────────────────────────────────────────────

const typeConfigs = computed(() => [
  {
    type: 'dispatch_request',
    label: t('trash_page.type_dispatch_request'),
    icon: ClipboardDocumentListIcon,
    tone: 'sky',
    badgeClass: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
  },
  {
    type: 'driver',
    label: t('trash_page.type_driver'),
    icon: TruckIcon,
    tone: 'violet',
    badgeClass: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
  },
  {
    type: 'vehicle',
    label: t('trash_page.type_vehicle'),
    icon: RectangleStackIcon,
    tone: 'amber',
    badgeClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  },
  {
    type: 'transport_provider',
    label: t('trash_page.type_transport_provider'),
    icon: BuildingOfficeIcon,
    tone: 'emerald',
    badgeClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  },
  {
    type: 'tp_student',
    label: t('trash_page.type_tp_student'),
    icon: AcademicCapIcon,
    tone: 'rose',
    badgeClass: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
  },
  {
    type: 'tp_program',
    label: t('trash_page.type_tp_program'),
    icon: CalendarDaysIcon,
    tone: 'brand',
    badgeClass: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
  },
  {
    type: 'role',
    label: t('trash_page.type_role'),
    icon: UserGroupIcon,
    tone: 'slate',
    badgeClass: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
  },
  {
    type: 'menu_item',
    label: t('trash_page.type_menu_item'),
    icon: Bars3Icon,
    tone: 'slate',
    badgeClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
  },
])

const activeTypeLabel = computed(() => typeConfigs.value.find(c => c.type === activeType.value)?.label ?? '')

// ─── Computed ────────────────────────────────────────────────────────────────

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

// ─── Helpers ─────────────────────────────────────────────────────────────────

function itemKey(item) {
  return `${item.type}::${item.id}`
}

function typeConfig(type) {
  return typeConfigs.value.find(c => c.type === type) ?? null
}

function fmtCount(n) {
  return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : String(n)
}

function formatDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString(locale.value === 'en' ? 'en-US' : 'vi-VN')
  } catch {
    return v
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
      type: activeType.value ?? undefined,
      q: searchInput.value || undefined,
      page: currentPage.value,
      per_page: 20,
    })
    items.value  = result.items ?? []
    meta.value   = result.meta ?? null
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

watch(searchInput, (val) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    loadItems()
  }, 350)
})

// ─── Filters / pagination ─────────────────────────────────────────────────────

function setTypeFilter(type) {
  if (activeType.value === type) return
  activeType.value = type
}

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
  if (selected.value.has(k)) {
    selected.value.delete(k)
  } else {
    selected.value.add(k)
  }
  selected.value = new Set(selected.value)
}

// ─── Actions ──────────────────────────────────────────────────────────────────

function groupSelectedByType() {
  const byType = {}
  for (const key of selected.value) {
    const [type, id] = key.split('::')
    if (!byType[type]) byType[type] = []
    byType[type].push(parseInt(id, 10))
  }
  return byType
}

function confirmBulkRestore() {
  const byType = groupSelectedByType()
  confirmRestore.value = { byType, ids: [...selected.value] }
}

function confirmBulkForceDelete() {
  const byType = groupSelectedByType()
  confirmDelete.value = { byType, ids: [...selected.value] }
}

function singleRestore(item) {
  confirmRestore.value = {
    byType: { [item.type]: [item.id] },
    ids: [itemKey(item)],
  }
}

function singleForceDelete(item) {
  confirmDelete.value = {
    byType: { [item.type]: [item.id] },
    ids: [itemKey(item)],
  }
}

async function executeRestore() {
  if (!confirmRestore.value) return
  busyRestore.value = true
  try {
    const byType = confirmRestore.value.byType
    for (const [type, ids] of Object.entries(byType)) {
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
    const byType = confirmDelete.value.byType
    for (const [type, ids] of Object.entries(byType)) {
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
/* KPI card shell — tuỳ chỉnh gọn cho trash module */
.kpi-card {
  @apply relative overflow-hidden rounded-xl border p-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-va-600/50;
}
.kpi-card--interactive {
  @apply cursor-pointer border-dashed border-slate-200 bg-white hover:border-solid hover:shadow-sm dark:border-slate-700 dark:bg-slate-900;
}
.kpi-card--active {
  @apply cursor-pointer border-solid border-slate-300 bg-slate-50 shadow-sm dark:border-slate-600 dark:bg-slate-800/50;
}
.kpi-card--brand.kpi-card--active  { @apply border-teal-400/60 bg-teal-50/60 dark:border-teal-700 dark:bg-teal-950/20; }
.kpi-card--sky.kpi-card--active    { @apply border-sky-400/60 bg-sky-50/60 dark:border-sky-700 dark:bg-sky-950/20; }
.kpi-card--violet.kpi-card--active { @apply border-violet-400/60 bg-violet-50/60 dark:border-violet-700 dark:bg-violet-950/20; }
.kpi-card--amber.kpi-card--active  { @apply border-amber-400/60 bg-amber-50/60 dark:border-amber-700 dark:bg-amber-950/20; }
.kpi-card--emerald.kpi-card--active{ @apply border-emerald-400/60 bg-emerald-50/60 dark:border-emerald-700 dark:bg-emerald-950/20; }
.kpi-card--rose.kpi-card--active   { @apply border-rose-400/60 bg-rose-50/60 dark:border-rose-700 dark:bg-rose-950/20; }
.kpi-card--static {
  @apply cursor-default border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900;
}
.kpi-card__inner {
  @apply flex items-start gap-2.5;
}
.kpi-card__icon-cell {
  @apply flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400;
}
.kpi-card--brand .kpi-card__icon-cell  { @apply bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400; }
.kpi-card--sky .kpi-card__icon-cell    { @apply bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-400; }
.kpi-card--violet .kpi-card__icon-cell { @apply bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400; }
.kpi-card--amber .kpi-card__icon-cell  { @apply bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400; }
.kpi-card--emerald .kpi-card__icon-cell{ @apply bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400; }
.kpi-card--rose .kpi-card__icon-cell   { @apply bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400; }
.kpi-card__body {
  @apply min-w-0 flex-1;
}
.kpi-card__label {
  @apply text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400;
}
.kpi-card__value {
  @apply text-xl font-bold tabular-nums text-slate-800 dark:text-slate-100;
}
</style>
