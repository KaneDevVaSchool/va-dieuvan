<template>
  <div class="space-y-3 sm:space-y-4">
    <RouterLink :to="{ name: 'expenses' }" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400">
      <ArrowLeftIcon class="h-4 w-4" /> {{ t('expense.back_to_list') }}
    </RouterLink>

    <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('expense.ledger_title') }}</h2>
          <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('expense.ledger_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <input v-model="range.date_from" type="date" :aria-label="t('expense.filter_from')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <input v-model="range.date_to" type="date" :aria-label="t('expense.filter_to')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <button type="button" class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700" @click="loadSummary">{{ t('checklist.apply') }}</button>
        </div>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-5">
        <div class="overflow-x-auto lg:col-span-2">
          <table class="w-full border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.line_vehicle') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.ledger_count') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.total') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="v in summary.items"
                :key="v.vehicle_id"
                class="cursor-pointer"
                :class="selectedId === v.vehicle_id ? 'bg-teal-50 dark:bg-teal-950/30' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40'"
                @click="select(v.vehicle_id)"
              >
                <td class="px-3 py-2"><span class="font-mono font-medium">{{ v.license_plate }}</span><div class="text-xs text-slate-500">{{ v.type }}</div></td>
                <td class="px-3 py-2 text-right tabular-nums">{{ v.count }}</td>
                <td class="px-3 py-2 text-right tabular-nums">
                  <div class="font-semibold">{{ formatVndCurrency(v.total, '') }}</div>
                  <div v-if="v.no_invoice_total" class="text-[11px] text-amber-700 dark:text-amber-300">{{ t('expense.no_invoice_badge') }}: {{ formatVndCurrency(v.no_invoice_total, '') }}</div>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="summary.items.length">
              <tr class="font-semibold"><td class="px-3 py-2">{{ t('expense.total') }}</td><td /><td class="px-3 py-2 text-right tabular-nums">{{ formatVndCurrency(summary.grand_total, '') }}</td></tr>
            </tfoot>
          </table>
          <p v-if="loading" class="mt-3 text-sm text-slate-500">{{ t('expense.loading') }}</p>
          <p v-else-if="!summary.items.length" class="mt-3 text-sm text-slate-500">{{ t('expense.ledger_empty') }}</p>
        </div>

        <div class="lg:col-span-3">
          <p v-if="!detail" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-800/50">{{ t('expense.ledger_pick') }}</p>
          <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h3 class="text-base font-semibold"><span class="font-mono">{{ detail.vehicle.license_plate }}</span> · {{ formatVndCurrency(detail.total) }}</h3>
              <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200" @click="exportDetail">
                <ArrowDownTrayIcon class="h-4 w-4" /> Excel
              </button>
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ t('expense.ledger_by_type') }}</p>
                <p v-for="g in detail.by_type" :key="g.key" class="mt-1 flex justify-between text-sm"><span>{{ t(`expense.cost_type_${g.key}`, g.key) }}</span><span class="tabular-nums">{{ formatVndCurrency(g.total, '') }}</span></p>
              </div>
              <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ t('expense.ledger_by_month') }}</p>
                <p v-for="g in detail.by_month" :key="g.key" class="mt-1 flex justify-between text-sm"><span>{{ g.key }}</span><span class="tabular-nums">{{ formatVndCurrency(g.total, '') }}</span></p>
              </div>
            </div>
            <div class="mt-3 max-h-[480px] overflow-auto">
              <table class="w-full border-separate border-spacing-0 text-left text-sm">
                <thead class="sticky top-0">
                  <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_date') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_cost_type') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_description') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 text-right dark:border-slate-700">{{ t('expense.line_amount') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.ledger_doc') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="r in detail.rows" :key="r.id">
                    <td class="whitespace-nowrap px-2 py-1.5 text-xs">{{ formatDate(r.date) }}</td>
                    <td class="px-2 py-1.5 text-xs">{{ t(`expense.cost_type_${r.type}`, r.type) }}<span v-if="!r.has_invoice" class="ml-1 text-amber-700 dark:text-amber-300">*</span></td>
                    <td class="px-2 py-1.5 text-xs">{{ r.description }} <span class="text-slate-400">· {{ r.created_by }}</span></td>
                    <td class="whitespace-nowrap px-2 py-1.5 text-right tabular-nums">{{ formatVndCurrency(r.amount, '') }}</td>
                    <td class="px-2 py-1.5 text-xs">
                      <RouterLink v-if="r.expense_document" :to="{ name: 'expenseDetail', params: { id: r.expense_document.id } }" class="text-teal-700 hover:underline dark:text-teal-400">
                        {{ r.expense_document.code }}
                      </RouterLink>
                      <span v-else class="text-slate-400">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <p class="mt-2 text-[11px] text-slate-500">* {{ t('expense.no_invoice_badge') }}</p>
            </div>
          </template>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { downloadVehicleCostLedger, getVehicleCostLedger, getVehicleCostLedgerSummary } from '../../api/expenses'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { formatVndCurrency } from '../../util/money'
import { toLocalDateKey } from '../../util/dates'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const now = new Date()
const range = reactive({
  date_from: toLocalDateKey(new Date(now.getFullYear(), now.getMonth(), 1)),
  date_to: toLocalDateKey(now),
})
const summary = ref({ items: [], grand_total: 0 })
const detail = ref(null)
const selectedId = ref(route.query.vehicle ? Number(route.query.vehicle) : null)
const loading = ref(false)

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}

async function loadSummary() {
  loading.value = true
  try {
    summary.value = await getVehicleCostLedgerSummary({ ...range })
    if (selectedId.value) await select(selectedId.value)
  } catch (err) {
    showAppErrorFromApi(err, t('expense.load_error'))
  } finally {
    loading.value = false
  }
}

async function select(vehicleId) {
  selectedId.value = vehicleId
  void router.replace({ query: { ...route.query, vehicle: vehicleId } })
  try {
    detail.value = await getVehicleCostLedger(vehicleId, { ...range })
  } catch (err) {
    showAppErrorFromApi(err, t('expense.load_error'))
  }
}

async function exportDetail() {
  try {
    await downloadVehicleCostLedger(detail.value.vehicle.id, detail.value.vehicle.license_plate, { ...range })
  } catch (err) {
    showAppErrorFromApi(err, t('expense.download_error'))
  }
}

onMounted(loadSummary)
</script>
