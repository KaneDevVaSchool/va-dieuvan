<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Chi phí vận hành</h2>
        <p class="text-sm text-slate-500">Chi phí dự kiến và thực tế theo từng chuyến.</p>
      </div>
      <input
        v-model="month"
        type="month"
        class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-base outline-none ring-va-800/20 focus:ring"
      />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>

    <template v-else>
      <!-- KPIs -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Số chuyến</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ items.length }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Chi phí dự kiến</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ money(totalEstimated) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Chi phí thực tế</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ money(totalActual) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Chênh lệch</div>
          <div class="mt-1 text-2xl font-bold" :class="variance > 0 ? 'text-rose-600' : 'text-emerald-600'">
            {{ variance > 0 ? '+' : '' }}{{ money(variance) }}
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div v-if="items.length" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-800">Dự kiến vs Thực tế theo ngày</h3>
        <DashboardEChart :option="chartOption" height="280px" />
      </div>

      <!-- Table -->
      <div v-if="items.length" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[40rem] text-left text-base">
          <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">Ngày</th>
              <th class="px-4 py-3 font-medium">Tài xế</th>
              <th class="px-4 py-3 text-right font-medium">Dự kiến</th>
              <th class="px-4 py-3 text-right font-medium">Thực tế</th>
              <th class="px-4 py-3 font-medium">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="it in items" :key="it.execution_id" class="hover:bg-slate-50/60">
              <td class="px-4 py-3 text-slate-700">{{ it.date }}</td>
              <td class="px-4 py-3 text-slate-600">{{ it.driver_name || '—' }}</td>
              <td class="px-4 py-3 text-right text-slate-600">{{ money(it.estimated_cost) }}</td>
              <td class="px-4 py-3 text-right font-medium text-slate-900">{{ money(it.actual_cost) }}</td>
              <td class="px-4 py-3">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ it.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="rounded-2xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
        Chưa có dữ liệu chi phí trong tháng này.
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import DashboardEChart from '../../../components/dashboard/DashboardEChart.vue'
import { getCostReport } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const report = ref(null)
const month = ref(new Date().toISOString().slice(0, 7))

const items = computed(() => report.value?.items ?? [])
const totalEstimated = computed(() => Number(report.value?.total_estimated || 0))
const totalActual = computed(() => Number(report.value?.total_actual || 0))
const variance = computed(() => totalActual.value - totalEstimated.value)

const chartOption = computed(() => {
  const list = items.value
  const labels = list.map((i) => (i.date || '').slice(5))
  return {
    tooltip: {
      trigger: 'axis',
      confine: true,
      axisPointer: { type: 'shadow' },
      valueFormatter: (v) => money(v),
    },
    legend: { bottom: 0, textStyle: { color: '#64748b', fontSize: 11 } },
    grid: { left: 8, right: 12, top: 18, bottom: 28, containLabel: true },
    xAxis: {
      type: 'category',
      data: labels,
      axisLabel: { color: '#64748b', fontSize: 10 },
      axisLine: { lineStyle: { color: '#e2e8f0' } },
    },
    yAxis: {
      type: 'value',
      axisLabel: {
        color: '#64748b',
        fontSize: 10,
        formatter: (v) => (v >= 1e6 ? `${(v / 1e6).toFixed(1)}M` : v >= 1e3 ? `${(v / 1e3).toFixed(0)}k` : v),
      },
      splitLine: { lineStyle: { color: '#f1f5f9' } },
    },
    series: [
      { name: 'Dự kiến', type: 'bar', barMaxWidth: 18, itemStyle: { color: '#94a3b8', borderRadius: [3, 3, 0, 0] }, data: list.map((i) => Number(i.estimated_cost || 0)) },
      { name: 'Thực tế', type: 'bar', barMaxWidth: 18, itemStyle: { color: '#6366f1', borderRadius: [3, 3, 0, 0] }, data: list.map((i) => Number(i.actual_cost || 0)) },
    ],
  }
})

async function load() {
  loading.value = true
  try {
    report.value = await getCostReport(props.program.id, { month: month.value })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function money(v) {
  return new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + ' đ'
}

watch(month, load)
onMounted(load)
</script>
