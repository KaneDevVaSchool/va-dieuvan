<template>
  <div class="space-y-5">
    <div v-if="chartLoading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('tp_program_detail.loading_charts') }}
    </div>

    <template v-else>
      <!-- Row 1: distributions -->
      <div class="grid gap-4 lg:grid-cols-3">
        <ChartCard title="Phân loại ngày" subtitle="Cơ cấu các ngày trong chương trình">
          <DashboardEChart :option="dayTypeOption" height="240px" @chart-click="onDayTypeClick" />
          <p v-if="selectedDayType" class="mt-1 text-center text-xs text-slate-500">{{ selectedDayType }}</p>
        </ChartCard>
        <ChartCard title="Trạng thái thực hiện" subtitle="Tiến độ chạy thực tế của các ngày vận hành">
          <DashboardEChart :option="executionOption" height="240px" />
        </ChartCard>
        <ChartCard title="Tỉ lệ ghế" subtitle="Số ghế đã đăng ký so với sức chứa">
          <DashboardEChart :option="seatOption" height="240px" />
        </ChartCard>
      </div>

      <!-- Row 2: trends -->
      <ChartCard title="Sĩ số dự kiến theo ngày" subtitle="Số học sinh dự kiến trên mỗi ngày vận hành">
        <DashboardEChart :option="expectedOption" height="280px" />
      </ChartCard>

      <!-- Row 3: weekday breakdown -->
      <ChartCard title="Phân bố theo thứ trong tuần" subtitle="Tần suất hoạt động theo ngày trong tuần">
        <DashboardEChart :option="weekdayOption" height="260px" />
      </ChartCard>
    </template>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import DashboardEChart from '../../../components/dashboard/DashboardEChart.vue'
import { emptyDashboardChartOption } from '../../../util/transportDashboardCharts'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({
  program: { type: Object, required: true },
  days: { type: Array, default: null },
  daysLoading: { type: Boolean, default: false },
})

const { t } = useI18n()

const localDays = ref([])
const localLoading = ref(false)
const selectedDayType = ref('')

const daysList = computed(() => (props.days != null ? props.days : localDays.value))
const chartLoading = computed(() => (props.days != null ? props.daysLoading : localLoading.value))

const AXIS = { label: '#64748b', line: '#e2e8f0', split: '#f1f5f9' }

const WEEKDAY_LABELS = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']

// Lightweight chart card
const ChartCard = (props, { slots }) =>
  h('div', { class: 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm' }, [
    h('div', { class: 'mb-2' }, [
      h('h3', { class: 'text-sm font-semibold text-slate-800' }, props.title),
      props.subtitle ? h('p', { class: 'text-xs text-slate-400' }, props.subtitle) : null,
    ]),
    slots.default?.(),
  ])
ChartCard.props = ['title', 'subtitle']

const capacity = computed(() => Number(props.program?.settings?.vehicle?.max_capacity || 0))
const fillPct = computed(() => {
  const cap = capacity.value
  if (!cap) return 0
  return Math.round(((props.program.enrolled_count ?? 0) / cap) * 100)
})

function donut(data, emptyText) {
  if (!data.length) return emptyDashboardChartOption(emptyText)
  return {
    tooltip: { trigger: 'item', confine: true, formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 11 } },
    series: [
      {
        type: 'pie',
        radius: ['46%', '72%'],
        center: ['50%', '44%'],
        avoidLabelOverlap: true,
        selectedMode: 'single',
        itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
        label: { color: '#334155', fontSize: 11, formatter: '{b}\n{c}' },
        emphasis: { scale: true, scaleSize: 8, itemStyle: { shadowBlur: 14, shadowColor: 'rgba(15,23,42,0.18)' } },
        data,
      },
    ],
  }
}

const dayTypeOption = computed(() => {
  const map = { operating: 'Vận hành', makeup: 'Học bù', cancelled: 'Đã hủy' }
  const colors = { operating: '#10b981', makeup: '#f59e0b', cancelled: '#ef4444' }
  const counts = {}
  for (const d of daysList.value) counts[d.day_type] = (counts[d.day_type] || 0) + 1
  const data = Object.entries(counts)
    .filter(([, v]) => v > 0)
    .map(([k, v]) => ({ value: v, name: map[k] || k, itemStyle: { color: colors[k] || '#6366f1' } }))
  return donut(data, t('tp_program_detail.chart_empty_days'))
})

const executionOption = computed(() => {
  const op = daysList.value.filter((d) => d.day_type === 'operating')
  let done = 0
  let running = 0
  let pending = 0
  for (const d of op) {
    if (!d.has_execution) pending++
    else if (d.execution_status === 'completed') done++
    else running++
  }
  const data = [
    { value: done, name: 'Hoàn thành', itemStyle: { color: '#10b981' } },
    { value: running, name: 'Đang chạy', itemStyle: { color: '#f59e0b' } },
    { value: pending, name: 'Chưa thực hiện', itemStyle: { color: '#cbd5e1' } },
  ].filter((d) => d.value > 0)
  return donut(data, t('tp_program_detail.chart_empty_days'))
})

const seatOption = computed(() => {
  const cap = capacity.value
  const enrolled = props.program.enrolled_count ?? 0
  if (!cap) return emptyDashboardChartOption(t('tp_program_detail.chart_empty_capacity'))
  const free = Math.max(0, cap - enrolled)
  const data = [
    { value: enrolled, name: 'Đã đăng ký', itemStyle: { color: '#0ea5e9' } },
    { value: free, name: 'Còn trống', itemStyle: { color: '#e2e8f0' } },
  ]
  return {
    tooltip: { trigger: 'item', confine: true, formatter: '{b}: {c} ({d}%)' },
    legend: { bottom: 0, textStyle: { color: AXIS.label, fontSize: 11 } },
    series: [
      {
        type: 'pie',
        radius: ['58%', '78%'],
        center: ['50%', '44%'],
        itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
        label: {
          show: true,
          position: 'center',
          formatter: `${fillPct.value}%`,
          fontSize: 22,
          fontWeight: 700,
          color: '#0f172a',
        },
        emphasis: { label: { show: true } },
        data,
      },
    ],
  }
})

const expectedOption = computed(() => {
  const op = daysList.value
    .filter((d) => d.day_type === 'operating')
    .slice()
    .sort((a, b) => a.scheduled_date.localeCompare(b.scheduled_date))
  if (!op.length) return emptyDashboardChartOption(t('tp_program_detail.chart_empty_days'))
  const labels = op.map((d) => d.scheduled_date.slice(5))
  const vals = op.map((d) => Number(d.expected_count || 0))
  return {
    tooltip: { trigger: 'axis', confine: true, axisPointer: { type: 'line', lineStyle: { color: '#818cf8', width: 1 } } },
    grid: { left: 8, right: 14, top: 18, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: labels,
      axisLabel: { color: AXIS.label, fontSize: 10, interval: Math.max(0, Math.floor(labels.length / 12)) },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'line',
        smooth: 0.25,
        symbol: 'circle',
        symbolSize: labels.length > 40 ? 0 : 5,
        areaStyle: { color: 'rgba(99,102,241,0.14)' },
        lineStyle: { width: 2, color: '#6366f1' },
        itemStyle: { color: '#6366f1' },
        data: vals,
      },
    ],
  }
})

const weekdayOption = computed(() => {
  const op = daysList.value.filter((d) => d.day_type === 'operating')
  if (!op.length) return emptyDashboardChartOption(t('tp_program_detail.chart_empty_days'))
  const counts = [0, 0, 0, 0, 0, 0, 0]
  for (const d of op) {
    const wd = new Date(d.scheduled_date + 'T00:00:00').getDay()
    counts[wd]++
  }
  // Reorder Mon..Sun
  const order = [1, 2, 3, 4, 5, 6, 0]
  const labels = order.map((i) => WEEKDAY_LABELS[i])
  const vals = order.map((i) => counts[i])
  return {
    tooltip: { trigger: 'axis', confine: true, axisPointer: { type: 'shadow' } },
    grid: { left: 8, right: 10, top: 18, bottom: 24, containLabel: true },
    xAxis: {
      type: 'category',
      data: labels,
      axisLabel: { color: AXIS.label, fontSize: 11 },
      axisLine: { lineStyle: { color: AXIS.line } },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      axisLabel: { color: AXIS.label, fontSize: 10 },
      splitLine: { lineStyle: { color: AXIS.split } },
    },
    series: [
      {
        type: 'bar',
        barMaxWidth: 34,
        data: vals.map((v) => ({ value: v, itemStyle: { color: '#6366f1', borderRadius: [4, 4, 0, 0] } })),
      },
    ],
  }
})

function onDayTypeClick(p) {
  if (p?.name) selectedDayType.value = `${p.name}: ${p.value} ngày (${p.percent}%)`
}

async function loadLocalDays() {
  localLoading.value = true
  try {
    localDays.value = (await listProgramDays(props.program.id))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    localLoading.value = false
  }
}

onMounted(() => {
  if (props.days == null) loadLocalDays()
})
</script>
