<template>
  <div class="space-y-4 md:space-y-5">
    <div>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
        {{ t('p2p_policy_page.absence_report_title') }}
      </h1>
      <p class="mt-1 text-sm text-slate-500">{{ t('p2p_policy_page.absence_report_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Input v-model="filters.weekStart" type="date" :label="t('p2p_policy_page.week_start')" />
      <Select v-model="filters.timeSlot" :label="t('p2p_policy_page.filter_slot')">
        <option value="">{{ t('p2p_policy_page.slot_all') }}</option>
        <option value="morning">{{ t('p2p_policy_page.slot_morning') }}</option>
        <option value="afternoon">{{ t('p2p_policy_page.slot_afternoon') }}</option>
      </Select>
      <Select v-model="filters.routeId" :label="t('p2p_policy_page.filter_route')">
        <option value="">{{ t('p2p_policy_page.route_all') }}</option>
        <option v-for="r in routes" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
      </Select>
      <Input v-model="filters.cls" :label="t('p2p_policy_page.field_class')" :placeholder="t('p2p_policy_page.class_ph')" />
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-3 text-xs text-slate-500">
      <span><b class="text-emerald-600">✓</b> {{ t('p2p_policy_page.legend_present') }}</span>
      <span><b class="text-sky-600">✗V</b> {{ labelAbsenceReason('absent_reported') }}</span>
      <span><b class="text-rose-600">✗N</b> {{ labelAbsenceReason('absent_no_notice') }}</span>
      <span><b class="text-amber-600">✗L</b> {{ labelAbsenceReason('late_cancellation') }}</span>
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
    </div>
    <div v-else-if="!students.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <ChartBarIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">{{ t('p2p_policy_page.absence_empty') }}</p>
    </div>

    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[48rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2.5 font-medium">{{ t('p2p_policy_page.col_student') }}</th>
            <th v-for="d in weekDates" :key="d.date" class="px-3 py-2.5 text-center font-medium">{{ d.label }}</th>
            <th class="px-3 py-2.5 text-center font-medium">{{ t('p2p_policy_page.col_excused') }}</th>
            <th class="px-3 py-2.5 text-center font-medium">{{ t('p2p_policy_page.col_unexcused') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in students" :key="s.student_id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2.5">
              <div class="font-medium text-slate-800">{{ s.student_name }}</div>
              <div class="text-xs text-slate-400">{{ s.class_name }}</div>
            </td>
            <td v-for="d in weekDates" :key="d.date" class="px-3 py-2.5 text-center">
              <span :class="cellClass(s.days[d.date])">{{ cellSymbol(s.days[d.date]) }}</span>
            </td>
            <td class="px-3 py-2.5 text-center font-medium text-sky-700">{{ counts(s).excused }}</td>
            <td class="px-3 py-2.5 text-center font-medium text-rose-600">{{ counts(s).unexcused }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { getAbsenceReport, listPolicyRoutes } from '../../api/p2p'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { labelAbsenceReason } from '../../constants/policyTripStatus'

const { t } = useI18n()
const loading = ref(false)
const students = ref([])
const routes = ref([])
const weekStartActual = ref('')

const filters = reactive({
  weekStart: mondayOfThisWeek(),
  timeSlot: '',
  routeId: '',
  cls: '',
})

const weekDates = computed(() => {
  const base = weekStartActual.value || filters.weekStart
  const start = new Date(base)
  const labels = ['T2', 'T3', 'T4', 'T5', 'T6']
  return labels.map((label, i) => {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    return { date: d.toISOString().slice(0, 10), label }
  })
})

async function load() {
  loading.value = true
  try {
    const res = await getAbsenceReport({
      week_start: filters.weekStart,
      time_slot: filters.timeSlot || undefined,
      route_id: filters.routeId || undefined,
      class: filters.cls || undefined,
    })
    weekStartActual.value = res?.week_start ?? filters.weekStart
    students.value = res?.students ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function loadRoutes() {
  try {
    routes.value = (await listPolicyRoutes())?.items ?? []
  } catch {
    routes.value = []
  }
}

watch(() => [filters.weekStart, filters.timeSlot, filters.routeId, filters.cls], load)

function cellSymbol(day) {
  if (!day) return '✓'
  return { absent_reported: '✗V', absent_no_notice: '✗N', late_cancellation: '✗L' }[day.absence_reason] ?? '✗'
}
function cellClass(day) {
  if (!day) return 'font-semibold text-emerald-600'
  return {
    absent_reported: 'font-semibold text-sky-600',
    absent_no_notice: 'font-semibold text-rose-600',
    late_cancellation: 'font-semibold text-amber-600',
  }[day.absence_reason] ?? 'text-slate-500'
}
function counts(s) {
  let excused = 0
  let unexcused = 0
  for (const d of Object.values(s.days || {})) {
    if (d.absence_reason === 'absent_no_notice') unexcused++
    else excused++ // absent_reported + late_cancellation tính có phép
  }
  return { excused, unexcused }
}

function mondayOfThisWeek() {
  const d = new Date()
  const day = (d.getDay() + 6) % 7
  d.setDate(d.getDate() - day)
  return d.toISOString().slice(0, 10)
}

onMounted(() => {
  loadRoutes()
  load()
})
</script>
