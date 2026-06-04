<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.calendar_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.calendar_subtitle') }}</p>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-2 text-xs">
      <span class="font-medium text-slate-600 dark:text-slate-400">Loại ngày:</span>
      <span v-for="d in dayTypeLegend" :key="d.type" :class="['inline-flex items-center rounded-full px-2.5 py-0.5 font-medium', d.cls]">{{ d.label }}</span>
    </div>

    <!-- Filter bar -->
    <div class="relative z-40">
      <AppFilterBar>
        <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <!-- Year-Month picker -->
            <AppFilterDropdown :summary-text="filterYearMonth" panel-class="min-w-[200px] p-3">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tháng / Năm</label>
              <input v-model="filterYearMonth" type="month" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800" />
            </AppFilterDropdown>

            <!-- day_type filter -->
            <AppFilterDropdown :summary-text="filterDayType ? labelDayType(filterDayType) : 'Loại ngày'" panel-class="min-w-[180px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in dayTypeOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterDayType === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterDayType = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center gap-2 pl-2">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="loading" @click="load">
              <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
              <span class="hidden sm:inline">Làm mới</span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
      </div>
      <div v-else-if="!filteredItems.length" class="flex flex-col items-center justify-center py-16 text-center">
        <CalendarDaysIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ t('p2p_policy_page.developing') }}</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
              <th class="whitespace-nowrap py-3 pl-4 pr-3">Ngày</th>
              <th class="whitespace-nowrap py-3 pr-3">Thứ</th>
              <th class="whitespace-nowrap py-3 pr-3">Loại</th>
              <th class="whitespace-nowrap py-3 pr-3">Học kỳ</th>
              <th class="whitespace-nowrap py-3 pr-3">Năm học</th>
              <th class="whitespace-nowrap py-3 pr-4">Ghi chú</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="row in filteredItems" :key="row.date" :class="['transition', row.day_type === 'holiday' || row.day_type === 'weekend' ? 'bg-slate-50/50 dark:bg-slate-900/30' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40']">
              <td class="whitespace-nowrap py-3 pl-4 pr-3 tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ row.date }}</td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">{{ dayOfWeekLabel(row.date) }}</td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', dayTypePillClass(row.day_type)]">
                  {{ labelDayType(row.day_type) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">
                <span v-if="row.semester" class="inline-flex items-center rounded-md bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-950/40 dark:text-violet-200">HK{{ row.semester }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 tabular-nums text-slate-600 dark:text-slate-400">{{ row.school_year ?? '—' }}</td>
              <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ row.note ?? '' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Import note -->
    <div class="rounded-xl border border-violet-200/60 bg-violet-50/50 px-4 py-3 text-xs text-violet-800 dark:border-violet-800/40 dark:bg-violet-950/20 dark:text-violet-200">
      <span class="font-semibold">Import bulk:</span>
      Dùng <code class="rounded bg-violet-100 px-1 dark:bg-violet-900/40">POST /api/school-calendars/bulk</code>
      để nhập lịch theo học kỳ. Bắt buộc điền cột <code class="rounded bg-violet-100 px-1 dark:bg-violet-900/40">semester</code>
      cho tất cả <code class="rounded bg-violet-100 px-1 dark:bg-violet-900/40">school_day</code> và <code class="rounded bg-violet-100 px-1 dark:bg-violet-900/40">makeup_day</code>
      — xem CRITICAL FIX L1 trong <code>docs/business/p2p.md</code>.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { dayTypePillClass, labelDayType } from '../../constants/policyTripStatus.js'
import { listSchoolCalendars } from '../../api/p2p.js'

const { t } = useI18n()
const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

const today = new Date()
const filterYearMonth = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`)
const filterDayType = ref('')
const loading = ref(false)
const items = ref([])

const dayTypeLegend = [
  { type: 'school_day', label: 'Ngày học', cls: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100' },
  { type: 'makeup_day', label: 'Học bù', cls: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100' },
  { type: 'holiday', label: 'Nghỉ lễ', cls: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100' },
  { type: 'weekend', label: 'Cuối tuần', cls: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' },
]

const dayTypeOptions = [
  { value: '', label: 'Tất cả loại ngày' },
  { value: 'school_day', label: 'Ngày học' },
  { value: 'makeup_day', label: 'Học bù' },
  { value: 'holiday', label: 'Nghỉ lễ' },
  { value: 'weekend', label: 'Cuối tuần' },
]

const filteredItems = computed(() => {
  if (!filterDayType.value) return items.value
  return items.value.filter((r) => r.day_type === filterDayType.value)
})

const DOW = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
function dayOfWeekLabel(dateStr) {
  if (!dateStr) return '—'
  try {
    return DOW[new Date(dateStr).getDay()]
  } catch {
    return '—'
  }
}

async function load() {
  loading.value = true
  try {
    const [year, month] = filterYearMonth.value.split('-')
    const res = await listSchoolCalendars({ year, month })
    items.value = res?.items ?? res ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(filterYearMonth, load)
</script>
