<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ t('cargo_page.hero_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.hero_subtitle') }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          to="/dispatch-requests/new"
          class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700"
          data-testid="cargo-cta-new"
        >
          <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.cta_new_request') }}
        </RouterLink>
        <RouterLink
          :to="{ path: '/requests', query: { trip_type: 'cargo' } }"
          class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
          data-testid="cargo-link-requests"
        >
          {{ t('cargo_page.link_cargo_requests') }}
        </RouterLink>
      </div>
    </div>

    <!-- KPI Summary Strip -->
    <CargoSummaryBar
      :stats="kpiStats"
      :loading="kpiLoading"
      :active-status="filters.status"
      @quick-filter="onKpiQuickFilter"
    />

    <!-- Datagrid card -->
    <div
      ref="cargoDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <!-- Toolbar row -->
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="cargo-list-search"
              :placeholder="t('cargo_page.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              data-testid="cargo-toolbar-search"
              @enter="flushSearch"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('cargo_page.filter_show_controls_title')"
              :hint="t('cargo_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="cargo-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('cargo_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'cargo-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`cargo-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`cargo-filter-vis-${fd.key}`"
                />
                <label
                  :for="`cargo-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <div v-if="canManageCargoData" class="relative" data-cargo-data-panel>
              <DatagridToolbarActionButton
                icon="data"
                :active="showDataMenu"
                test-id="cargo-toolbar-data"
                @click="toggleDataMenu"
              >
                {{ t('cargo_page.toolbar_data') }}
              </DatagridToolbarActionButton>
              <div
                v-if="showDataMenu"
                class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[280px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900"
              >
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-rose-950/30"
                  :disabled="!(meta.total ?? 0) || bulkSubmitting"
                  data-testid="cargo-data-purge-soft"
                  @click="openPurgeAll(false); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-rose-900 dark:text-rose-200">
                    {{ t('cargo_page.purge_all_soft') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-rose-700/80 dark:text-rose-300/80">
                    {{ t('cargo_page.purge_all_soft_hint', { n: meta.total ?? 0 }) }}
                  </span>
                </button>
                <button
                  type="button"
                  class="flex w-full flex-col px-3 py-2 text-left hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-red-950/30"
                  :disabled="!(meta.total ?? 0) || bulkSubmitting"
                  data-testid="cargo-data-purge-permanent"
                  @click="openPurgeAll(true); showDataMenu = false"
                >
                  <span class="text-sm font-medium text-red-950 dark:text-red-200">
                    {{ t('cargo_page.purge_all_permanent') }}
                  </span>
                  <span class="mt-0.5 text-[11px] leading-snug text-red-800/80 dark:text-red-300/80">
                    {{ t('cargo_page.purge_all_permanent_hint', { n: meta.total ?? 0 }) }}
                  </span>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Filter row -->
      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.status">
          <select
            v-model="filters.status"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('filter_bar.status')"
            data-testid="cargo-filter-status"
            @change="onFilterChange"
          >
            <option v-for="opt in statusFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.from"
            :placeholder="t('dashboard_analytics.range_from')"
            :max-date="filters.to || null"
            input-id="cargo-filter-from"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.to"
            :placeholder="t('dashboard_analytics.range_to')"
            :min-date="filters.from || null"
            input-id="cargo-filter-to"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model.number="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('filter_bar.per_page')"
            data-testid="cargo-filter-per-page"
            @change="onFilterChange"
          >
            <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>
      </div>
    </div>

    <!-- List -->
    <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">
      {{ t('cargo_page.loading') }}
    </div>
    <div v-else class="space-y-3 md:space-y-4">
      <CargoCard
        v-for="shipment in items"
        :key="shipment.id"
        :shipment="shipment"
      />
      <div
        v-if="!items.length"
        class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
      >
        {{ t('cargo_page.empty') }}
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="(meta.total ?? 0) > 0"
      class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between"
    >
      <p class="text-slate-600 dark:text-slate-400">
        {{ t('cargo_page.page_range', { from: pageFrom, to: pageTo, total: meta.total ?? 0 }) }}
      </p>
      <div class="flex flex-wrap items-center justify-end gap-1">
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) <= 1"
          :aria-label="t('cargo_page.prev')"
          data-testid="cargo-prev-page"
          @click="goPage((meta.current_page ?? 1) - 1)"
        >
          <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          v-for="n in pageNumbers"
          :key="'p-' + n"
          type="button"
          :class="[
            'h-9 min-w-[2.25rem] rounded-lg px-2 text-sm font-medium tabular-nums',
            n === (meta.current_page ?? 1)
              ? 'bg-teal-600 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
          ]"
          @click="goPage(n)"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
          :aria-label="t('cargo_page.next')"
          data-testid="cargo-next-page"
          @click="goPage((meta.current_page ?? 1) + 1)"
        >
          <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="purgeAllOpen"
        class="fixed inset-0 z-[191] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cargo-purge-all-title"
        data-testid="cargo-purge-all-modal"
        @click.self="closePurgeAll"
      >
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900">
          <div class="border-b border-slate-100 bg-red-50/90 px-5 py-4 dark:border-slate-700 dark:bg-red-950/40">
            <h2 id="cargo-purge-all-title" class="text-base font-semibold text-slate-900 dark:text-white">
              {{ t('cargo_page.purge_all_modal_title') }}
            </h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ t('cargo_page.purge_all_modal_lead') }}</p>
          </div>
          <div class="space-y-4 px-5 py-4">
            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
              {{ purgePermanent ? t('cargo_page.purge_all_mode_permanent') : t('cargo_page.purge_all_mode_soft') }}
              · {{ meta.total ?? 0 }}
            </p>
            <div>
              <label for="cargo-purge-confirm" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                {{ t('cargo_page.purge_all_confirm_label') }}
              </label>
              <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                {{ t('cargo_page.purge_all_confirm_hint', { phrase: purgeRequiredPhrase }) }}
              </p>
              <p
                v-if="purgeCooldownSeconds > 0"
                class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                role="status"
                data-testid="cargo-purge-rate-limit"
              >
                {{ t('cargo_page.purge_all_rate_limit_wait', { seconds: purgeCooldownSeconds }) }}
              </p>
              <input
                id="cargo-purge-confirm"
                v-model="purgeConfirmPhrase"
                type="text"
                autocomplete="off"
                class="input mt-2 h-10 w-full text-sm"
                data-testid="cargo-purge-confirm-input"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3 dark:border-slate-700 dark:bg-slate-900/80">
            <button
              type="button"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
              data-testid="cargo-purge-cancel"
              @click="closePurgeAll"
            >
              {{ t('requests_page.bulk_confirm_cancel') }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="
                bulkSubmitting ||
                purgeCooldownSeconds > 0 ||
                purgeConfirmPhrase !== purgeRequiredPhrase
              "
              data-testid="cargo-purge-submit"
              @click="submitPurgeAll"
            >
              {{
                bulkSubmitting
                  ? t('cargo_page.purge_all_submitting')
                  : t('cargo_page.purge_all_submit', { n: meta.total ?? 0 })
              }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import CargoSummaryBar from '../../components/cargo/CargoSummaryBar.vue'
import CargoCard from '../../components/cargo/CargoCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import { listCargoShipments, purgeAllCargoShipments } from '../../api/cargo'
import { labelCargoStatus } from '../../util/labels'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useNotificationStore } from '../../store/notificationCenter'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const notifStore = useNotificationStore()
const auth = useAuthStore()

const loading = ref(false)
const kpiLoading = ref(false)
const items = ref([])
const meta = ref({})

const kpiStats = ref({
  total: 0,
  pending: 0,
  picked_up: 0,
  in_transit: 0,
  delivered: 0,
  failed: 0,
  cancelled: 0,
})

const searchInput = ref('')
const searchDebounce = ref(null)
const cargoDatagridRef = ref(null)
useDetailsAutoCloseWithin(cargoDatagridRef)

const canManageCargoData = computed(() => auth.hasPermission('cargo.manage'))
const showDataMenu = ref(false)
const purgeAllOpen = ref(false)
const purgePermanent = ref(false)
const purgeConfirmPhrase = ref('')
const purgeCooldownSeconds = ref(0)
const bulkSubmitting = ref(false)
let purgeCooldownTimer = null

const purgeRequiredPhrase = computed(() => `XOA ${meta.value.total ?? 0}`)

function closeToolbarMenus() {
  showDataMenu.value = false
}

function toggleDataMenu() {
  closeFilterPanel()
  showDataMenu.value = !showDataMenu.value
}

function onDatagridDocMouseDown(ev) {
  const t = ev.target
  if (!(t instanceof Element)) return
  if (t.closest('[data-cargo-data-panel]')) return
  closeToolbarMenus()
}

function clearPurgeCooldownTimer() {
  if (purgeCooldownTimer) {
    clearInterval(purgeCooldownTimer)
    purgeCooldownTimer = null
  }
  purgeCooldownSeconds.value = 0
}

function startPurgeCooldown(seconds) {
  clearPurgeCooldownTimer()
  const n = Math.max(1, Math.min(120, Math.floor(Number(seconds) || 35)))
  purgeCooldownSeconds.value = n
  purgeCooldownTimer = setInterval(() => {
    purgeCooldownSeconds.value -= 1
    if (purgeCooldownSeconds.value <= 0) {
      clearPurgeCooldownTimer()
    }
  }, 1000)
}

function openPurgeAll(permanent) {
  purgePermanent.value = permanent
  purgeConfirmPhrase.value = ''
  purgeAllOpen.value = true
}

function closePurgeAll() {
  if (bulkSubmitting.value) return
  purgeAllOpen.value = false
  purgeConfirmPhrase.value = ''
}

async function submitPurgeAll() {
  if (
    bulkSubmitting.value ||
    purgeCooldownSeconds.value > 0 ||
    purgeConfirmPhrase.value !== purgeRequiredPhrase.value
  ) {
    return
  }
  bulkSubmitting.value = true
  try {
    const params = {
      ...listParams(),
      permanent: purgePermanent.value,
      confirm_phrase: purgeConfirmPhrase.value,
      expected_count: meta.value.total ?? 0,
    }
    delete params.page
    delete params.per_page
    const res = await purgeAllCargoShipments(params)
    const n = res.deleted ?? 0
    showAppSuccess(
      purgePermanent.value
        ? t('cargo_page.purge_all_done_permanent', { n })
        : t('cargo_page.purge_all_done_soft', { n }),
    )
    purgeAllOpen.value = false
    purgeConfirmPhrase.value = ''
    await reloadKpis()
    await reload()
  } catch (e) {
    if (e?.response?.status === 429) {
      const headers = e.response.headers || {}
      const raw = headers['retry-after'] ?? headers['Retry-After']
      startPurgeCooldown(raw ?? 35)
    }
    showAppErrorFromApi(e)
  } finally {
    bulkSubmitting.value = false
  }
}

const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const CARGO_FILTER_CONTROLS = [
  { key: 'status', label: '', default: false },
  { key: 'date_range', label: '', default: false },
  { key: 'per_page', label: '', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useVisibleFilterControls(CARGO_FILTER_CONTROLS, 'va-dieuvan.cargo.visible-filters.v1')

const FILTER_LABEL_KEYS = {
  status: 'filter_bar.status',
  date_range: 'trips_page.filter_vis_dates',
  per_page: 'filter_bar.per_page',
}

const filterControlDefs = computed(() =>
  CARGO_FILTER_CONTROLS.map((fd) => ({
    key: fd.key,
    label: t(FILTER_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const filters = reactive({
  status: '',
  from: '',
  to: '',
  q: '',
  page: 1,
  per_page: 20,
})

const rangeValid = computed(() => {
  const { from, to } = filters
  if (!from && !to) return true
  if (!from || !to) return false
  return from <= to
})

const CARGO_STATUS_VALUES = ['pending', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled']

const statusFilterOptions = computed(() => [
  { value: '', label: t('filter_bar.status') },
  ...CARGO_STATUS_VALUES.map((s) => ({ value: s, label: labelCargoStatus(s) })),
])

const perPageFilterOptions = computed(() => [
  { value: 10, label: '10' },
  { value: 20, label: t('filter_bar.per_page') },
  { value: 50, label: '50' },
  { value: 100, label: '100' },
])

const pageFrom = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return (cur - 1) * pp + 1
})

const pageTo = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return Math.min(cur * pp, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const delta = 2
  const start = Math.max(1, cur - delta)
  const end = Math.min(last, cur + delta)
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

function listParams() {
  const p = {
    status: filters.status || undefined,
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q?.trim() || undefined,
    page: filters.page,
    per_page: filters.per_page,
  }
  Object.keys(p).forEach((k) => {
    if (p[k] === '' || p[k] === undefined || p[k] === null) delete p[k]
  })
  return p
}

function kpiBaseParams() {
  return {
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q?.trim() || undefined,
    per_page: 1,
    page: 1,
  }
}

async function reloadKpis() {
  if (!rangeValid.value) return
  kpiLoading.value = true
  try {
    const base = kpiBaseParams()
    const [rPend, rPickedUp, rInTransit, rDone, rFail, rCanc] = await Promise.all([
      listCargoShipments({ ...base, status: 'pending' }),
      listCargoShipments({ ...base, status: 'picked_up' }),
      listCargoShipments({ ...base, status: 'in_transit' }),
      listCargoShipments({ ...base, status: 'delivered' }),
      listCargoShipments({ ...base, status: 'failed' }),
      listCargoShipments({ ...base, status: 'cancelled' }),
    ])
    const pending = rPend.meta?.total ?? 0
    const pickedUp = rPickedUp.meta?.total ?? 0
    const inTransit = rInTransit.meta?.total ?? 0
    const delivered = rDone.meta?.total ?? 0
    const failed = rFail.meta?.total ?? 0
    const cancelled = rCanc.meta?.total ?? 0
    kpiStats.value = {
      pending,
      picked_up: pickedUp,
      in_transit: inTransit,
      delivered,
      failed,
      cancelled,
      total: pending + pickedUp + inTransit + delivered + failed + cancelled,
    }
  } catch {
    kpiStats.value = { total: 0, pending: 0, picked_up: 0, in_transit: 0, delivered: 0, failed: 0, cancelled: 0 }
  } finally {
    kpiLoading.value = false
  }
}

async function reload() {
  if (!rangeValid.value) return
  loading.value = true
  try {
    const res = await listCargoShipments(listParams())
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    void notifStore.refreshBadges()
  } finally {
    loading.value = false
  }
}

function onKpiQuickFilter(payload) {
  const { kind, value } = payload
  if (kind === 'reset') {
    filters.status = ''
  } else if (kind === 'status') {
    filters.status = filters.status === value ? '' : value
  }
  onFilterChange()
}

function onFilterChange() {
  if (!rangeValid.value) return
  filters.page = 1
  reloadKpis()
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.from = ''
  filters.to = ''
  filters.q = ''
  searchInput.value = ''
  filters.per_page = 20
  filters.page = 1
  closeFilterPanel()
  reloadKpis()
  reload()
}

function goPage(n) {
  const last = meta.value.last_page ?? 1
  if (n < 1 || n > last) return
  filters.page = n
  reload()
}

function flushSearch() {
  filters.q = searchInput.value.trim()
  onFilterChange()
}

watch(searchInput, () => {
  clearTimeout(searchDebounce.value)
  searchDebounce.value = setTimeout(() => {
    const next = searchInput.value.trim()
    if (next !== filters.q) {
      filters.q = next
      onFilterChange()
    }
  }, 350)
})

onMounted(() => {
  document.addEventListener('mousedown', onDatagridDocMouseDown)
  reloadKpis()
  reload()
})

onActivated(() => {
  reloadKpis()
  reload()
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onDatagridDocMouseDown)
  clearPurgeCooldownTimer()
})
</script>
