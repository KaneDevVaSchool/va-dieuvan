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
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
        :disabled="genLoading"
        @click="runGenerateMonth"
      >
        <ArrowPathIcon class="h-4 w-4" :class="genLoading ? 'animate-spin' : ''" />
        Tạo lịch tháng
      </button>
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
        <p class="mt-1 text-xs text-slate-400">Chưa có dữ liệu tháng này — dùng «Tạo lịch tháng».</p>
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
              <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">
                {{ row.note ?? '' }}
                <button type="button" class="ml-2 text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400" @click="openEdit(row)">Sửa</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :open="!!editRow" title="Sửa ngày lịch" :description="editRow?.date ?? ''" @close="editRow = null">
      <form v-if="editRow" class="space-y-3" @submit.prevent="saveEdit">
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Loại ngày</label>
          <select v-model="editForm.day_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            <option value="school_day">Ngày học</option>
            <option value="makeup_day">Học bù</option>
            <option value="holiday">Nghỉ lễ</option>
            <option value="weekend">Cuối tuần</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Học kỳ (bắt buộc với ngày học / học bù)</label>
          <select v-model="editForm.semester" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
            <option :value="''">—</option>
            <option value="1">HK 1</option>
            <option value="2">HK 2</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ghi chú</label>
          <input v-model="editForm.note" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="editRow = null">Đóng</button>
          <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white" :disabled="editLoading">Lưu</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Modal from '../../components/ui/Modal.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { dayTypePillClass, labelDayType } from '../../constants/policyTripStatus.js'
import { generateSchoolCalendarMonth, listSchoolCalendars, updateSchoolCalendarDay } from '../../api/p2p.js'

const { t } = useI18n()
const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

const today = new Date()
const filterYearMonth = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`)
const filterDayType = ref('')
const loading = ref(false)
const genLoading = ref(false)
const items = ref([])
const editRow = ref(null)
const editLoading = ref(false)
const editForm = reactive({ day_type: 'school_day', semester: '1', note: '' })
const genSchoolYear = ref('2025-2026')
const genSemester = ref('1')

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

function openEdit(row) {
  editRow.value = row
  editForm.day_type = row.day_type
  editForm.semester = row.semester != null ? String(row.semester) : ''
  editForm.note = row.note ?? ''
}

async function saveEdit() {
  if (!editRow.value) return
  editLoading.value = true
  try {
    await updateSchoolCalendarDay(editRow.value.date, {
      day_type: editForm.day_type,
      semester: editForm.semester === '' ? null : Number(editForm.semester),
      note: editForm.note || null,
    })
    editRow.value = null
    await load()
  } catch {
    // interceptor
  } finally {
    editLoading.value = false
  }
}

async function runGenerateMonth() {
  const [year, month] = filterYearMonth.value.split('-')
  genLoading.value = true
  try {
    await generateSchoolCalendarMonth({
      year: Number(year),
      month: Number(month),
      school_year: genSchoolYear.value,
      semester: Number(genSemester.value),
    })
    await load()
  } catch {
    // interceptor
  } finally {
    genLoading.value = false
  }
}

onMounted(load)
watch(filterYearMonth, load)
</script>
