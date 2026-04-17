<template>
  <div class="space-y-4">
    <AppFilterBar>
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('filter_bar.active_title') }}
          </p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-if="filters.status" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.status') }}</span>
              <span class="font-medium">{{ labelTripStatus(filters.status) }}</span>
            </li>
            <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.depart_range') }}</span>
              <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
            </li>
            <li v-if="filters.per_page !== 20" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
              <span class="font-medium">{{ filters.per_page }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">{{ t('filter_bar.empty') }}</li>
          </ul>
          <button
            type="button"
            class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="resetFilters(); closeFilterMenu()"
          >
            {{ t('filter_bar.clear_all') }}
          </button>
        </AppFilterFunnelMenu>

        <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <AppFilterDropdown
            :label="t('filter_bar.status')"
            :summary-text="filters.status ? labelTripStatus(filters.status) : t('filter_bar.all')"
            summary-text-class="max-w-[10rem]"
            panel-class="min-w-[220px] py-1"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li v-for="opt in statusOptions" :key="opt.value === '' ? '_all' : opt.value">
                <button
                  type="button"
                  class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                  :class="
                    filters.status === opt.value
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                  "
                  @click="applyFilterPatch($event, { status: opt.value })"
                >
                  {{ opt.label }}
                </button>
              </li>
            </ul>
          </AppFilterDropdown>

          <AppFilterDropdown
            :label="t('filter_bar.depart_range')"
            :summary-text="dateRangeSummary"
            full-width-summary
            panel-class="w-[min(100vw-1.5rem,320px)] p-3 sm:w-max"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input
                v-model="filters.from"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
              <span class="hidden text-slate-300 dark:text-slate-600 sm:inline">—</span>
              <input
                v-model="filters.to"
                type="date"
                class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                @change="onFilterDropdownChange"
              />
            </div>
          </AppFilterDropdown>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
            :title="t('filter_bar.clear_icon')"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" />
              <XMarkIcon
                class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
              />
            </span>
          </button>
          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />
          <button
            type="button"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-white/70 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-slate-100"
            :aria-expanded="extraFiltersOpen"
            @click="extraFiltersOpen = !extraFiltersOpen"
          >
            {{ t('filter_bar.more') }}
            <PlusCircleIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div
        v-show="extraFiltersOpen"
        class="mt-3 flex flex-wrap items-center gap-4 border-t border-violet-100/80 pt-3 dark:border-violet-900/30"
      >
        <label class="inline-flex items-center gap-2">
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ t('filter_bar.per_page') }}</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
            @change="onFilterChange"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
      </div>
    </AppFilterBar>

    <Card :title="t('trips_page.list_title')">
      <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('trips_page.loading') }}</div>
      <div v-else class="space-y-2">
        <RouterLink
          v-for="trip in items"
          :key="trip.id"
          :to="`/trips/${trip.id}`"
          class="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-slate-500"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ t('trips_page.trip_label', { id: trip.id }) }} · {{ labelTripStatus(trip.status) }}
              </div>
              <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ fmt(trip.depart_at) }}
                <span v-if="trip.vehicle"> · {{ t('trips_page.vehicle', { plate: trip.vehicle.license_plate }) }}</span>
              </div>
            </div>
            <span class="text-xs text-slate-400">→</span>
          </div>
        </RouterLink>
        <div v-if="!items.length" class="text-sm text-slate-500 dark:text-slate-400">{{ t('trips_page.empty') }}</div>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-slate-500 dark:text-slate-400">{{ t('trips_page.total', { n: meta.total ?? 0 }) }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">
            {{ t('trips_page.prev') }}
          </Button>
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
            {{ t('trips_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { FunnelIcon, PlusCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { listTrips } from '../../api/trips'
import { labelTripStatus } from '../../util/labels'

const { t } = useI18n()
const route = useRoute()
const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', from: '', to: '', page: 1, per_page: 20 })
const filterMenuRef = ref(null)
const extraFiltersOpen = ref(false)

const TRIP_STATUS_VALUES = [
  'pending',
  'approved',
  'assigned',
  'driver_confirmed',
  'in_progress',
  'completed',
  'cancelled',
  'incident',
]

const statusOptions = computed(() => [
  { value: '', label: t('filter_bar.all') },
  ...TRIP_STATUS_VALUES.map((s) => ({ value: s, label: labelTripStatus(s) })),
])

const dateRangeSummary = computed(() => {
  if (!filters.from && !filters.to) return t('filter_bar.all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.status) n++
  if (filters.from || filters.to) n++
  if (filters.per_page !== 20) n++
  return n
})

function applyStatusFromRoute() {
  const s = route.query.status
  filters.status = typeof s === 'string' && s ? s : ''
}

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function onFilterChange() {
  filters.page = 1
  reload()
}

function applyFilterPatch(ev, patch) {
  Object.assign(filters, patch)
  closeParentDetails(ev)
  onFilterChange()
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  onFilterChange()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

function resetFilters() {
  filters.from = ''
  filters.to = ''
  filters.per_page = 20
  filters.page = 1
  applyStatusFromRoute()
  reload()
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listTrips(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

onMounted(() => {
  applyStatusFromRoute()
  reload()
})

watch(
  () => route.query.status,
  () => {
    applyStatusFromRoute()
    filters.page = 1
    reload()
  },
)
</script>
