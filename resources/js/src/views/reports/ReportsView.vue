<template>
  <div class="space-y-4">
    <Card>
      <div class="grid gap-3 md:grid-cols-2">
        <Input v-model="from" label="Từ" type="date" />
        <Input v-model="to" label="Đến" type="date" />
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <Button variant="secondary" :loading="loading" @click="load">Tải báo cáo</Button>
        <Button v-if="summary" variant="secondary" type="button" @click="downloadCsv">Tải CSV (tóm tắt)</Button>
      </div>
      <p class="mt-2 text-xs text-slate-500">
        CSV gồm khoảng thời gian, trips theo trạng thái, chi phí theo loại, chi phí theo NCC/nội bộ, và số vi phạm SLA cargo — phục vụ biên bản nội bộ tạm thời.
      </p>
    </Card>

    <div v-if="summary" class="grid gap-4 md:grid-cols-3">
      <Card title="Trips theo trạng thái">
        <div v-for="(v, k) in summary.trips_by_status ?? {}" :key="k" class="flex justify-between text-sm">
          <span>{{ k }}</span><span class="font-semibold">{{ v }}</span>
        </div>
      </Card>
      <Card title="Chi phí confirmed">
        <div v-for="(v, k) in summary.confirmed_costs_by_type ?? {}" :key="k" class="flex justify-between text-sm">
          <span>{{ k }}</span><span class="font-semibold">{{ formatMoney(v) }}</span>
        </div>
      </Card>
      <Card title="Cargo SLA">
        <div class="text-2xl font-bold">{{ summary.cargo_sla_breaches ?? 0 }}</div>
        <div class="text-xs text-slate-500">Shipment trễ SLA</div>
      </Card>
    </div>

    <Card v-if="summary" title="Chi phí confirmed theo NCC / nội bộ">
      <p class="mb-3 text-xs text-slate-500">
        Nguồn: chi phí đã xác nhận trong khoảng thời gian đã chọn; nhóm theo nhà cung cấp vận tải gắn chuyến (INTERNAL = nội bộ).
      </p>
      <div v-if="!providerRows.length" class="text-sm text-slate-500">Chưa có dữ liệu.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b text-left text-slate-500">
              <th class="py-2 pr-4 font-medium">Đơn vị / NCC</th>
              <th class="py-2 font-medium tabular-nums">Tổng (VND)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in providerRows" :key="i" class="border-b border-slate-100">
              <td class="py-2 pr-4">{{ row.provider }}</td>
              <td class="py-2 tabular-nums font-medium">{{ formatMoney(row.total_amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import { getSummary } from '../../api/reports'

const loading = ref(false)
const summary = ref(null)
const from = ref('')
const to = ref('')

const providerRows = computed(() => {
  const raw = summary.value?.confirmed_costs_by_provider
  if (!raw) return []
  return Array.isArray(raw) ? raw : []
})

function formatMoney(v) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat('vi-VN').format(n)
}

function csvEscape(cell) {
  const s = String(cell ?? '')
  if (/[",\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

function downloadCsv() {
  if (!summary.value) return
  const lines = []
  const r = summary.value.range ?? {}
  lines.push(['section', 'key', 'value'].map(csvEscape).join(','))
  lines.push(['meta', 'from', r.from ?? ''].map(csvEscape).join(','))
  lines.push(['meta', 'to', r.to ?? ''].map(csvEscape).join(','))

  const trips = summary.value.trips_by_status ?? {}
  Object.entries(trips).forEach(([k, v]) => {
    lines.push(['trips_by_status', k, v].map(csvEscape).join(','))
  })

  const types = summary.value.confirmed_costs_by_type ?? {}
  Object.entries(types).forEach(([k, v]) => {
    lines.push(['confirmed_costs_by_type', k, v].map(csvEscape).join(','))
  })

  providerRows.value.forEach((row) => {
    lines.push(['confirmed_costs_by_provider', row.provider, row.total_amount].map(csvEscape).join(','))
  })

  lines.push(['cargo_sla_breaches', 'count', summary.value.cargo_sla_breaches ?? 0].map(csvEscape).join(','))

  const blob = new Blob(['\ufeff', lines.join('\n')], { type: 'text/csv;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `bao-cao-dieu-van-${from.value || 'tu'}_${to.value || 'den'}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

async function load() {
  loading.value = true
  try {
    const params = {}
    if (from.value) params.from = from.value
    if (to.value) params.to = to.value
    summary.value = await getSummary(params)
  } finally {
    loading.value = false
  }
}
</script>
