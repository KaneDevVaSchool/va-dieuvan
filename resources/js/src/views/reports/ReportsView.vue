<template>
  <div class="space-y-4">
    <AppFilterBar>
      <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ t('reports_page.filter_title') }}
      </div>
      <div class="grid gap-3 md:grid-cols-2">
        <Input v-model="from" :label="t('reports_page.from')" type="date" />
        <Input v-model="to" :label="t('reports_page.to')" type="date" />
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <Button variant="secondary" :loading="loading" @click="load">{{ t('reports_page.load') }}</Button>
        <Button v-if="summary" variant="secondary" type="button" @click="downloadCsv">{{ t('reports_page.csv') }}</Button>
      </div>
      <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        {{ t('reports_page.csv_hint') }}
      </p>
    </AppFilterBar>

    <div v-if="summary" class="grid gap-4 md:grid-cols-3">
      <Card :title="t('reports_page.card_trips')">
        <div
          v-for="(v, k) in summary.trips_by_status ?? {}"
          :key="k"
          class="flex justify-between text-sm text-slate-800 dark:text-slate-200"
        >
          <span>{{ k }}</span><span class="font-semibold">{{ v }}</span>
        </div>
      </Card>
      <Card :title="t('reports_page.card_costs')">
        <div
          v-for="(v, k) in summary.confirmed_costs_by_type ?? {}"
          :key="k"
          class="flex justify-between text-sm text-slate-800 dark:text-slate-200"
        >
          <span>{{ k }}</span><span class="font-semibold">{{ formatMoney(v) }}</span>
        </div>
      </Card>
      <Card :title="t('reports_page.card_sla')">
        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ summary.cargo_sla_breaches ?? 0 }}</div>
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ t('reports_page.card_sla_sub') }}</div>
      </Card>
    </div>

    <Card v-if="summary" :title="t('reports_page.card_providers')">
      <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
        {{ t('reports_page.providers_hint') }}
      </p>
      <div v-if="!providerRows.length" class="text-sm text-slate-500 dark:text-slate-400">
        {{ t('reports_page.no_data') }}
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500 dark:border-slate-700 dark:text-slate-400">
              <th class="py-2 pr-4 font-medium">{{ t('reports_page.col_provider') }}</th>
              <th class="py-2 font-medium tabular-nums">{{ t('reports_page.col_total') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, i) in providerRows"
              :key="i"
              class="border-b border-slate-100 dark:border-slate-800"
            >
              <td class="py-2 pr-4 text-slate-800 dark:text-slate-200">{{ row.provider }}</td>
              <td class="py-2 tabular-nums font-medium text-slate-900 dark:text-slate-100">
                {{ formatMoney(row.total_amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import { getSummary } from '../../api/reports'

const { t } = useI18n()

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
