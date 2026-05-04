<template>
  <AppFilterBar>
    <div class="mb-3 border-b border-slate-200 pb-3">
      <p class="text-sm font-semibold text-slate-800">
        {{ t('reports_page.filters_heading') }}
      </p>
      <p class="mt-0.5 text-xs text-slate-600">
        {{ t('reports_page.filters_hint') }}
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
      <details class="group relative">
        <summary
          class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
        >
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
            <span
              v-if="r.activeFilterCount > 0"
              class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
            >
              {{ r.activeFilterCount }}
            </span>
          </span>
          <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
        </summary>
        <div
          class="absolute left-0 top-[calc(100%+8px)] z-40 min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
        >
          <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
            {{ t('dashboard_analytics.filter_applied_title') }}
          </p>
          <div class="p-3 pt-2">
            <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
              <li class="font-medium text-slate-900 dark:text-slate-100">{{ r.currentPresetLabel }}</li>
              <li class="tabular-nums text-slate-600 dark:text-slate-400">
                {{ r.rangeDisplayFormatted }}
                <span
                  v-if="r.rangeValid && r.rangeDaySpan > 0"
                  class="ml-1.5 inline-block rounded-md bg-violet-100/90 px-1.5 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-950/70 dark:text-violet-200"
                >
                  {{ t('dashboard_analytics.date_range_span', { n: r.rangeDaySpan }) }}
                </span>
              </li>
              <li v-for="(row, i) in r.activeFilterLines" :key="i" class="border-t border-slate-100 pt-2 dark:border-slate-700">
                <span class="text-slate-500 dark:text-slate-400">{{ row.label }}:</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ row.value }}</span>
              </li>
            </ul>
            <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                {{ t('dashboard_analytics.filter_optional_title') }}
              </p>
              <p class="mt-0.5 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
                {{ t('dashboard_analytics.filter_optional_hint') }}
              </p>
              <ul class="mt-2 max-h-[min(50vh,240px)] space-y-2 overflow-y-auto pr-0.5">
                <li v-for="fd in r.dimensionFilters" :key="fd.id" class="flex items-start gap-2">
                  <input
                    :id="'rep-bar-vis-' + fd.id"
                    v-model="r.dimensionFilterBarVisible[fd.id]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                  />
                  <label
                    :for="'rep-bar-vis-' + fd.id"
                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                  >
                    {{ fd.label }}
                  </label>
                </li>
              </ul>
            </div>
            <button
              type="button"
              class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
              @click="r.resetFilters"
            >
              {{ t('dashboard_analytics.filter_clear_all') }}
            </button>
          </div>
        </div>
      </details>
      <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
        <details class="group relative min-w-0">
          <summary
            class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
              {{ t('dashboard_analytics.filter_period_label') }}
            </span>
            <span class="max-w-[10rem] min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">
              {{ r.currentPresetLabel }}
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white py-1 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
          >
            <ul class="max-h-[min(60vh,320px)] space-y-0.5 overflow-y-auto px-1 py-1">
              <li v-for="p in r.presetDefs" :key="p.id">
                <button
                  type="button"
                  :class="[
                    'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                    r.preset === p.id
                      ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                      : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                  ]"
                  @click="r.applyPreset(p.id)"
                >
                  {{ p.label }}
                </button>
              </li>
            </ul>
          </div>
        </details>
        <details class="group relative min-w-0">
          <summary
            class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
          >
            <CalendarDaysIcon class="h-4 w-4 shrink-0 text-violet-500 dark:text-violet-400" aria-hidden="true" />
            <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
              {{ t('dashboard_analytics.filter_dates_label') }}
            </span>
            <span class="flex min-w-0 max-w-[11rem] items-center gap-1.5 sm:max-w-[14rem]">
              <span class="min-w-0 truncate text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                {{ r.rangeDisplayFormatted }}
              </span>
              <span
                v-if="r.rangeValid && r.rangeDaySpan > 0"
                class="shrink-0 rounded-md bg-violet-100/90 px-1.5 py-px text-[10px] font-bold tabular-nums text-violet-800 dark:bg-violet-950/70 dark:text-violet-200"
              >
                {{ r.rangeDaySpan }}
              </span>
            </span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+8px)] z-40 w-[min(100vw-1rem,22rem)] overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-b from-white via-white to-slate-50/95 shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/45 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/40 dark:ring-slate-950/50 sm:left-auto sm:right-0 sm:w-[20.5rem]"
          >
            <div class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-indigo-50/40 px-3 py-2.5 dark:border-violet-900/40 dark:from-violet-950/40 dark:to-indigo-950/20">
              <div class="flex items-start gap-2">
                <CalendarDaysIcon class="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                    {{ t('dashboard_analytics.date_range_title') }}
                  </p>
                  <p class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-400">
                    {{ t('dashboard_analytics.date_range_hint') }}
                  </p>
                </div>
              </div>
            </div>
            <div class="p-3">
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="chip in r.dateQuickChips"
                  :key="chip.kind"
                  type="button"
                  class="rounded-lg border border-slate-200/90 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50/80 hover:text-teal-900 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:border-teal-700 dark:hover:bg-teal-950/40 dark:hover:text-teal-100"
                  @click="r.applyQuickDateRange(chip.kind)"
                >
                  {{ chip.label }}
                </button>
              </div>
              <div class="mt-3 space-y-3">
                <div
                  class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner shadow-slate-200/20 dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                >
                  <label
                    class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    for="rep-range-from"
                  >
                    {{ t('dashboard_analytics.range_from') }}
                  </label>
                  <input
                    id="rep-range-from"
                    v-model="r.rangeFrom"
                    type="date"
                    :max="r.rangeTo || undefined"
                    class="rep-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    @change="r.onRangeFromChange"
                  />
                </div>
                <div class="flex items-center justify-center gap-2 px-1">
                  <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                  <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-violet-800 dark:bg-violet-950/80 dark:text-violet-200">
                    {{ r.rangeValid ? t('dashboard_analytics.date_range_span', { n: r.rangeDaySpan }) : '—' }}
                  </span>
                  <span class="h-px flex-1 bg-gradient-to-r from-transparent via-violet-200 to-transparent dark:via-violet-800/60" />
                </div>
                <div
                  class="rounded-xl border border-slate-200/80 bg-white/90 p-2.5 shadow-inner shadow-slate-200/20 dark:border-slate-600 dark:bg-slate-950/50 dark:shadow-none"
                >
                  <label
                    class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    for="rep-range-to"
                  >
                    {{ t('dashboard_analytics.range_to') }}
                  </label>
                  <input
                    id="rep-range-to"
                    v-model="r.rangeTo"
                    type="date"
                    :min="r.rangeFrom || undefined"
                    class="rep-date-input mt-1.5 h-10 w-full rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 text-sm font-medium tabular-nums text-slate-900 shadow-sm transition focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    @change="r.onRangeToChange"
                  />
                </div>
              </div>
              <button
                v-if="r.preset === 'custom'"
                type="button"
                class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-600/25 transition hover:from-teal-700 hover:to-teal-600 disabled:opacity-50 dark:shadow-teal-900/30"
                :disabled="r.loading || !r.rangeValid"
                @click="r.reloadSummary"
              >
                {{ t('dashboard_analytics.apply_range') }}
              </button>
              <p v-if="!r.rangeValid" class="mt-2 text-center text-xs text-rose-600 dark:text-rose-400">
                {{ t('dashboard_analytics.range_invalid') }}
              </p>
            </div>
          </div>
        </details>
        <template v-for="fd in r.visibleDimensionFilters" :key="fd.id">
          <details class="group relative min-w-0">
            <summary
              class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{{ fd.label }}</span>
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
                  v-for="(opt, optIdx) in (fd.options || []).filter((o) => o && typeof o === 'object')"
                  :key="fd.id + '-' + optIdx + '-' + String(opt.value)"
                >
                  <button
                    type="button"
                    :class="[
                      'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                      fd.isSelected(opt.value)
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                    ]"
                    @click="fd.pick(opt.value)"
                  >
                    {{ opt.label ?? '—' }}
                  </button>
                </li>
              </ul>
            </div>
          </details>
        </template>
      </div>
    </div>
  </AppFilterBar>
</template>

<script setup>
import { CalendarDaysIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import AppFilterBar from '../filters/AppFilterBar.vue'
import { useTransportReportSummary } from '../../composables/useTransportReportSummary'

const { t } = useI18n()
const r = useTransportReportSummary()
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
