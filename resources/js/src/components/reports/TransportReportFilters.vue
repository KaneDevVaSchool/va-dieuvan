<template>
  <AppFilterBar>
    <div ref="transportReportFilterBarRef" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
      <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('dashboard_analytics.filter_applied_title') }}
        </p>
        <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-if="preset !== 'all'" class="flex justify-between gap-2">
            <span class="text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.filter_period_label') }}</span>
            <span class="max-w-[11rem] truncate text-right font-medium">{{ currentPresetLabel }}</span>
          </li>
          <li v-if="preset !== 'all' || rangeFrom || rangeTo" class="flex justify-between gap-2 tabular-nums">
            <span class="text-slate-500 dark:text-slate-400">{{ t('dashboard_analytics.filter_dates_label') }}</span>
            <span class="text-right font-medium">
              {{ rangeDisplayFormatted }}
              <span
                v-if="rangeValid && rangeDaySpan > 0"
                class="ml-1 text-xs text-violet-700 dark:text-violet-300"
              >
                ({{ t('dashboard_analytics.date_range_span', { n: rangeDaySpan }) }})
              </span>
            </span>
          </li>
          <li v-for="(row, i) in activeFilterLines" :key="i" class="flex justify-between gap-2">
            <span class="text-slate-500 dark:text-slate-400">{{ row?.label ?? '—' }}</span>
            <span class="max-w-[11rem] truncate text-right font-medium">{{ row?.value ?? '—' }}</span>
          </li>
          <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
            {{ t('filter_bar.empty') }}
          </li>
        </ul>
        <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
            {{ t('trips_page.filter_show_controls_title') }}
          </p>
          <p class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
            {{ t('trips_page.filter_show_controls_hint') }}
          </p>
          <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
            <li v-for="opt in filterBarVisibilityOptions" :key="'rep-vis-' + opt.id" class="flex items-start gap-2">
              <input
                :id="`reports-filter-vis-${opt.id}`"
                v-model="filterBarVisible[opt.id]"
                type="checkbox"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900"
              />
              <label
                :for="`reports-filter-vis-${opt.id}`"
                class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
              >
                {{ t(opt.labelKey) }}
              </label>
            </li>
          </ul>
        </div>
        <button
          type="button"
          class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="resetFilters(); closeFilterMenu()"
        >
          {{ t('dashboard_analytics.filter_clear_all') }}
        </button>
      </AppFilterFunnelMenu>

      <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
        :title="t('filter_bar.clear_icon')"
        :aria-label="t('filter_bar.clear_icon')"
        @click="resetFilters"
      >
        <span class="relative inline-flex">
          <FunnelIcon class="h-5 w-5" />
          <XMarkIcon
            class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
          />
        </span>
      </button>
    </div>

    <div
      v-if="hasVisibleBarFilters"
      class="mt-2 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-2 border-t border-violet-100/80 pt-2 dark:border-violet-900/30 sm:gap-x-3"
    >
      <select
        v-if="filterBarVisible.period"
        :value="preset"
        class="h-9 max-w-[min(100%,12rem)] shrink-0 rounded-md border-0 bg-white/90 px-2 text-sm font-medium shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
        :class="preset === 'all' ? 'text-slate-600 dark:text-slate-400' : 'text-slate-900 dark:text-slate-100'"
        :aria-label="t('dashboard_analytics.filter_period_label')"
        @change="onPresetSelectChange($event.target.value)"
      >
        <option v-for="p in presetDefs" :key="p.id" :value="p.id">{{ p.label }}</option>
      </select>

      <AppFilterDropdown
        v-if="filterBarVisible.dates"
        :panel-title="t('dashboard_analytics.date_range_title')"
        :show-chip-label="false"
        :summary-text="rangeDisplayFormatted"
        :active="preset !== 'all' || !!(rangeFrom || rangeTo)"
        :aria-label="t('dashboard_analytics.filter_dates_label')"
        full-width-summary
        panel-class="fixed inset-x-3 top-20 z-[200] max-h-[min(75vh,28rem)] w-auto overflow-y-auto overflow-x-hidden p-0 sm:absolute sm:inset-x-auto sm:left-0 sm:right-auto sm:top-[calc(100%+8px)] sm:z-[100] sm:max-h-[min(70vh,32rem)] sm:w-[min(100vw-1.5rem,320px)]"
      >
        <div
          class="overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-b from-white via-white to-slate-50/95 shadow-2xl shadow-violet-500/20 ring-1 ring-slate-900/5 dark:border-violet-800/45 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/50 dark:ring-slate-950/50 sm:shadow-xl sm:ring-0"
        >
          <div class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-indigo-50/40 px-3 py-2.5 dark:border-violet-900/40 dark:from-violet-950/40 dark:to-indigo-950/20">
            <div class="flex items-start gap-2">
              <CalendarDaysIcon class="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" aria-hidden="true" />
              <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                {{ t('dashboard_analytics.date_range_title') }}
              </p>
            </div>
          </div>
          <div class="p-3">
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="chip in dateQuickChips"
                :key="chip.kind"
                type="button"
                class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50/80 hover:text-teal-900 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-100"
                @click="applyQuickDateRange(chip.kind)"
              >
                {{ chip?.label ?? '—' }}
              </button>
            </div>
            <div class="mt-3 space-y-3">
              <div class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50">
                <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="rep-range-from">
                  {{ t('dashboard_analytics.range_from') }}
                </label>
                <input
                  id="rep-range-from"
                  v-model="rangeFrom"
                  type="date"
                  :max="rangeTo || undefined"
                  class="rep-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  @change="onRangeFromChange"
                />
              </div>
              <div
                class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner dark:border-slate-600 dark:bg-slate-950/50"
              >
                <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="rep-range-to">
                  {{ t('dashboard_analytics.range_to') }}
                </label>
                <input
                  id="rep-range-to"
                  v-model="rangeTo"
                  type="date"
                  :min="rangeFrom || undefined"
                  class="rep-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  @change="onRangeToChange"
                />
              </div>
            </div>
            <button
              v-if="preset === 'custom'"
              type="button"
              class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition hover:from-teal-700 hover:to-teal-600 disabled:opacity-50"
              :disabled="loading || !rangeValid"
              @click="reloadSummary"
            >
              {{ t('dashboard_analytics.apply_range') }}
            </button>
          </div>
        </div>
      </AppFilterDropdown>

      <template v-for="fd in visibleDimensionFilters" :key="fd.id">
        <details class="group relative min-w-0">
          <summary
            class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <span class="max-w-[9rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100 sm:max-w-[10rem]">
              {{ fd.summary }}
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li
                v-for="(opt, optIdx) in (fd.options || []).filter((o) => o != null && typeof o === 'object')"
                :key="fd.id + '-' + optIdx + '-' + String(opt?.header ? 'hdr' : opt?.value ?? '')"
              >
                <div
                  v-if="opt.header"
                  class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                  role="presentation"
                >
                  {{ opt?.label ?? '—' }}
                </div>
                <button
                  v-else
                  type="button"
                  :class="[
                    'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                    fd.isSelected(opt?.value)
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                  ]"
                  @click="onDimensionPick(fd, opt?.value, $event)"
                >
                  {{ opt?.label ?? '—' }}
                </button>
              </li>
            </ul>
          </div>
        </details>
      </template>
    </div>
  </AppFilterBar>
</template>

<script setup>
import { computed, onActivated, onMounted, ref } from 'vue'
import { CalendarDaysIcon, ChevronDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import AppFilterBar from '../filters/AppFilterBar.vue'
import AppFilterDropdown from '../filters/AppFilterDropdown.vue'
import AppFilterFunnelMenu from '../filters/AppFilterFunnelMenu.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'

const { t } = useI18n()
const transportReportFilterBarRef = ref(null)
const filterMenuRef = ref(null)
useDetailsAutoCloseWithin(transportReportFilterBarRef)

const DIMENSION_VIS_LABEL_KEYS = {
  trip_type: 'requests_page.filter_vis_trip_type',
  channel: 'trips_page.filter_vis_channel',
  paper: 'trips_page.filter_vis_paper',
  urgent: 'trips_page.filter_vis_urgent',
  trip_run: 'trips_page.filter_vis_run',
  fleet: 'trips_page.filter_vis_fleet',
}

function closeParentDetails(ev) {
  const el = ev?.currentTarget
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

const {
  activeFilterCount,
  activeFilterLines,
  applyPreset,
  applyQuickDateRange,
  currentPresetLabel,
  dateQuickChips,
  filterBarVisible,
  resetFilterBarVisibility,
  hasVisibleBarFilters,
  reportFilterBarVisIds,
  loading,
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
} = useTransportReportSummary()

const filterBarVisibilityOptions = computed(() =>
  (reportFilterBarVisIds || []).map((id) => {
    if (id === 'period') return { id, labelKey: 'trips_page.filter_vis_period' }
    if (id === 'dates') return { id, labelKey: 'trips_page.filter_vis_dates' }
    return { id, labelKey: DIMENSION_VIS_LABEL_KEYS[id] || `trips_page.filter_vis_${id}` }
  }),
)

function onReportFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

function onPresetSelectChange(id) {
  applyPreset(id)
}

function onDimensionPick(fd, value, ev) {
  fd.pick(value)
  closeParentDetails(ev)
}

onMounted(() => {
  onReportFilterBarEnter()
})

onActivated(() => {
  onReportFilterBarEnter()
})
</script>

<style scoped>
.rep-date-input {
  color-scheme: light dark;
}
.rep-date-input::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.55;
}
.rep-date-input:hover::-webkit-calendar-picker-indicator {
  opacity: 1;
}
</style>
