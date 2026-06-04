<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.absence_report_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.absence_report_subtitle') }}</p>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-3 text-xs">
      <span class="font-medium text-slate-600 dark:text-slate-400">Chú giải:</span>
      <span class="inline-flex items-center gap-1"><span class="text-base">✓</span> Có mặt</span>
      <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 font-medium text-sky-800 dark:bg-sky-950/50 dark:text-sky-200">V Vắng có phép</span>
      <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-800 dark:bg-rose-950/40 dark:text-rose-100">N Vắng không phép</span>
      <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-900 dark:bg-amber-950/40 dark:text-amber-100">L Hủy muộn</span>
    </div>

    <!-- Filter Bar -->
    <div class="relative z-40">
      <AppFilterBar>
        <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <!-- Week start -->
            <AppFilterDropdown :summary-text="weekLabel" panel-class="min-w-[220px] p-3">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tuần bắt đầu từ</label>
              <input v-model="filterWeekStart" type="date" class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800" />
              <div class="mt-2 flex flex-wrap gap-1">
                <button v-for="wk in quickWeeks" :key="wk.label" type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800" @click="filterWeekStart = wk.start">{{ wk.label }}</button>
              </div>
            </AppFilterDropdown>

            <!-- Slot filter -->
            <AppFilterDropdown :summary-text="filterSlot ? labelTimeSlot(filterSlot) : 'Tất cả ca'" panel-class="min-w-[160px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in slotOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterSlot === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterSlot = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
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

    <!-- Pivot Table -->
    <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
      </div>
      <div v-else-if="!rows.length" class="flex flex-col items-center justify-center py-16 text-center">
        <ChartBarIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ t('p2p_policy_page.developing') }}</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
              <th class="whitespace-nowrap py-3 pl-4 pr-3 min-w-[160px]">Học sinh</th>
              <th v-for="day in weekDays" :key="day.date" class="whitespace-nowrap py-3 px-2 text-center" :class="day.isToday ? 'text-teal-600 dark:text-teal-400' : ''">
                <div>{{ day.dow }}</div>
                <div class="font-normal normal-case tracking-normal text-slate-400">{{ day.shortDate }}</div>
              </th>
              <th class="whitespace-nowrap py-3 px-2 text-center">Tổng vắng</th>
              <th class="whitespace-nowrap py-3 px-2 text-center">Có phép</th>
              <th class="whitespace-nowrap py-3 pr-4 text-center">Không phép</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="row in rows" :key="row.student_id" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="whitespace-nowrap py-2.5 pl-4 pr-3 font-medium text-slate-900 dark:text-slate-100">{{ row.student_name }}</td>
              <td v-for="day in weekDays" :key="day.date" class="py-2.5 px-2 text-center">
                <span v-if="cellSymbol(row, day.date)" :class="['inline-flex items-center justify-center rounded-full w-6 h-6 text-xs font-bold', cellClass(row, day.date)]">
                  {{ cellSymbol(row, day.date) }}
                </span>
                <span v-else class="text-slate-300 dark:text-slate-600">✓</span>
              </td>
              <td class="py-2.5 px-2 text-center tabular-nums font-bold text-rose-600 dark:text-rose-400">{{ row.total_absent ?? 0 }}</td>
              <td class="py-2.5 px-2 text-center tabular-nums text-sky-700 dark:text-sky-300">{{ row.absent_reported ?? 0 }}</td>
              <td class="py-2.5 pr-4 text-center tabular-nums text-rose-600 dark:text-rose-400">{{ row.absent_no_notice ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { labelTimeSlot } from '../../constants/policyTripStatus.js'
import { getAbsenceReport } from '../../api/p2p.js'

const { t } = useI18n()
const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

// Default to Monday of current week
function getMondayOfWeek(d = new Date()) {
  const day = d.getDay() // 0 = Sun
  const diff = day === 0 ? -6 : 1 - day
  const monday = new Date(d)
  monday.setDate(d.getDate() + diff)
  return monday.toISOString().slice(0, 10)
}

const filterWeekStart = ref(getMondayOfWeek())
const filterSlot = ref('')
const loading = ref(false)
const reportData = ref(null)

const slotOptions = [
  { value: '', label: 'Tất cả ca' },
  { value: 'morning', label: 'Sáng' },
  { value: 'afternoon', label: 'Chiều' },
]

// 5 weekdays T2–T6
const weekDays = computed(() => {
  const start = new Date(filterWeekStart.value)
  const today = new Date().toISOString().slice(0, 10)
  const DOW = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
  return Array.from({ length: 5 }, (_, i) => {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    const iso = d.toISOString().slice(0, 10)
    return {
      date: iso,
      dow: DOW[d.getDay()],
      shortDate: `${d.getDate()}/${d.getMonth() + 1}`,
      isToday: iso === today,
    }
  })
})

const weekLabel = computed(() => {
  if (!filterWeekStart.value) return 'Tuần'
  const start = new Date(filterWeekStart.value)
  const end = new Date(start)
  end.setDate(start.getDate() + 4)
  return `${start.getDate()}/${start.getMonth() + 1} – ${end.getDate()}/${end.getMonth() + 1}/${end.getFullYear()}`
})

// Quick week jump helpers
const quickWeeks = computed(() => {
  const now = new Date()
  const thisMonday = getMondayOfWeek(now)
  const prevMonday = new Date(now)
  prevMonday.setDate(prevMonday.getDate() - 7)
  return [
    { label: 'Tuần này', start: thisMonday },
    { label: 'Tuần trước', start: getMondayOfWeek(prevMonday) },
  ]
})

const rows = computed(() => reportData.value?.students ?? [])

// Absence cell helpers
function absenceType(row, date) {
  const day = row.days?.[date]
  return day?.absence_reason ?? null
}

const SYMBOL_MAP = {
  absent_reported: 'V',
  absent_no_notice: 'N',
  late_cancellation: 'L',
}
const CLASS_MAP = {
  absent_reported: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200',
  absent_no_notice: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
  late_cancellation: 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
}

function cellSymbol(row, date) {
  return SYMBOL_MAP[absenceType(row, date)] ?? null
}
function cellClass(row, date) {
  return CLASS_MAP[absenceType(row, date)] ?? ''
}

async function load() {
  loading.value = true
  reportData.value = null
  try {
    const params = { week_start: filterWeekStart.value }
    if (filterSlot.value) params.time_slot = filterSlot.value
    const res = await getAbsenceReport(params)
    reportData.value = res
  } catch {
    reportData.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch([filterWeekStart, filterSlot], load)
</script>
