<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.calendar_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('p2p_policy_page.calendar_subtitle') }}</p>
      </div>
    </div>

    <!-- Controls -->
    <div class="grid grid-cols-2 items-end gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Input v-model="month" type="month" :label="t('p2p_policy_page.cal_month')" />
      <Input v-model="schoolYear" :label="t('p2p_policy_page.field_school_year')" placeholder="2025-2026" />
      <Select v-model="defaultSemester" :label="t('p2p_policy_page.cal_default_semester')">
        <option value="1">{{ t('p2p_policy_page.semester_1') }}</option>
        <option value="2">{{ t('p2p_policy_page.semester_2') }}</option>
      </Select>
      <Button :loading="generating" @click="generateMonth">{{ t('p2p_policy_page.cal_generate') }}</Button>
    </div>

    <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">
      {{ t('p2p_policy_page.cal_legend') }}
    </p>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>

    <div v-else class="rounded-xl border border-slate-200 bg-white p-3">
      <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-medium text-slate-400">
        <div v-for="d in weekdayLabels" :key="d" class="py-1">{{ d }}</div>
      </div>
      <div class="mt-1 grid grid-cols-7 gap-1.5">
        <div v-for="(cell, i) in calendarCells" :key="i">
          <button
            v-if="cell"
            type="button"
            class="flex h-20 w-full flex-col items-start gap-1 rounded-lg border p-1.5 text-left transition hover:ring-2 hover:ring-teal-300"
            :class="cell.entry ? 'border-slate-200' : 'border-dashed border-slate-200 bg-slate-50/50'"
            @click="openDay(cell)"
          >
            <span class="text-xs font-semibold text-slate-700">{{ cell.day }}</span>
            <PolicyPill v-if="cell.entry" :text="labelDayType(cell.entry.day_type)" :pill-class="dayTypePillClass(cell.entry.day_type)" />
            <span v-if="cell.entry?.semester" class="text-[10px] text-slate-400">{{ t('p2p_policy_page.semester_n', { n: cell.entry.semester }) }}</span>
          </button>
          <div v-else class="h-20"></div>
        </div>
      </div>
    </div>

    <!-- Edit day modal -->
    <Modal :open="!!editDay" :title="editDay ? t('p2p_policy_page.cal_edit_day', { date: editDay.date }) : ''" @close="editDay = null">
      <div v-if="editDay" class="space-y-4">
        <Select v-model="dayForm.day_type" :label="t('p2p_policy_page.cal_day_type')">
          <option value="school_day">{{ labelDayType('school_day') }}</option>
          <option value="makeup_day">{{ labelDayType('makeup_day') }}</option>
          <option value="holiday">{{ labelDayType('holiday') }}</option>
          <option value="weekend">{{ labelDayType('weekend') }}</option>
        </Select>
        <Select v-if="needsSemester" v-model="dayForm.semester" :label="t('p2p_policy_page.field_semester')" required>
          <option value="1">{{ t('p2p_policy_page.semester_1') }}</option>
          <option value="2">{{ t('p2p_policy_page.semester_2') }}</option>
        </Select>
        <Input v-model="dayForm.note" :label="t('p2p_policy_page.cal_note')" />
        <p v-if="dayError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ dayError }}</p>
        <div class="flex justify-end gap-2 pt-1">
          <Button variant="secondary" :disabled="savingDay" @click="editDay = null">{{ t('common.cancel') }}</Button>
          <Button :loading="savingDay" @click="saveDay">{{ t('common.save') }}</Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import Modal from '../../components/ui/Modal.vue'
import PolicyPill from '../../components/p2p/PolicyPill.vue'
import { listSchoolCalendars, generateSchoolCalendarMonth, updateSchoolCalendarDay } from '../../api/p2p'
import { formatApiError } from '../../api/http'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { labelDayType, dayTypePillClass } from '../../constants/policyTripStatus'

const { t } = useI18n()
const weekdayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

const month = ref(new Date().toISOString().slice(0, 7)) // YYYY-MM
const schoolYear = ref(defaultSchoolYear())
const defaultSemester = ref('1')

const loading = ref(false)
const generating = ref(false)
const entries = ref([])

const editDay = ref(null)
const dayForm = reactive({ day_type: 'school_day', semester: '1', note: '' })
const savingDay = ref(false)
const dayError = ref('')

const needsSemester = computed(() => ['school_day', 'makeup_day'].includes(dayForm.day_type))

const yearMonth = computed(() => {
  const [y, m] = month.value.split('-').map(Number)
  return { year: y, month: m }
})

const entryByDate = computed(() => {
  const map = {}
  for (const e of entries.value) map[e.date] = e
  return map
})

/** Lưới lịch: ô null cho khoảng trống đầu tháng (bắt đầu từ Thứ 2). */
const calendarCells = computed(() => {
  const { year, month: m } = yearMonth.value
  const first = new Date(year, m - 1, 1)
  const daysInMonth = new Date(year, m, 0).getDate()
  const leading = (first.getDay() + 6) % 7 // 0=Mon
  const cells = []
  for (let i = 0; i < leading; i++) cells.push(null)
  for (let d = 1; d <= daysInMonth; d++) {
    const date = `${year}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    cells.push({ day: d, date, entry: entryByDate.value[date] || null })
  }
  return cells
})

async function load() {
  loading.value = true
  try {
    const res = await listSchoolCalendars(yearMonth.value)
    entries.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(month, load)

async function generateMonth() {
  generating.value = true
  try {
    const res = await generateSchoolCalendarMonth({
      ...yearMonth.value,
      school_year: schoolYear.value,
      semester: Number(defaultSemester.value),
    })
    showAppSuccess(t('p2p_policy_page.cal_generated_ok', { count: res?.imported ?? 0 }))
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    generating.value = false
  }
}

function openDay(cell) {
  if (!cell.entry) {
    showAppSuccess(t('p2p_policy_page.cal_generate_first'), t('p2p_policy_page.cal_no_entry'))
    return
  }
  editDay.value = cell
  dayError.value = ''
  dayForm.day_type = cell.entry.day_type
  dayForm.semester = String(cell.entry.semester ?? defaultSemester.value)
  dayForm.note = cell.entry.note ?? ''
}

async function saveDay() {
  savingDay.value = true
  dayError.value = ''
  try {
    await updateSchoolCalendarDay(editDay.value.date, {
      day_type: dayForm.day_type,
      semester: needsSemester.value ? Number(dayForm.semester) : null,
      note: dayForm.note || null,
    })
    editDay.value = null
    load()
  } catch (err) {
    dayError.value = formatApiError(err)
  } finally {
    savingDay.value = false
  }
}

function defaultSchoolYear() {
  const now = new Date()
  const y = now.getFullYear()
  return now.getMonth() + 1 >= 8 ? `${y}-${y + 1}` : `${y - 1}-${y}`
}

onMounted(load)
</script>
