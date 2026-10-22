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
                  <div class="text-[11px] text-emerald-700 dark:text-emerald-300">{{ t('expense.ledger_confirmed') }}: {{ formatVndCurrency(v.confirmed_total, '') }}</div>
                  <div v-if="v.total - v.confirmed_total > 0" class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('expense.ledger_pending') }}: {{ formatVndCurrency(v.total - v.confirmed_total, '') }}</div>
                  <div v-if="v.no_invoice_total" class="text-[11px] text-amber-700 dark:text-amber-300">{{ t('expense.no_invoice_badge') }}: {{ formatVndCurrency(v.no_invoice_total, '') }}</div>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="summary.items.length">
              <tr class="font-semibold">
                <td class="px-3 py-2">{{ t('expense.total') }}</td>
                <td />
                <td class="px-3 py-2 text-right tabular-nums">
                  <div>{{ formatVndCurrency(summary.grand_total, '') }}</div>
                  <div class="text-[11px] font-normal text-emerald-700 dark:text-emerald-300">{{ t('expense.ledger_confirmed') }}: {{ formatVndCurrency(grandConfirmed, '') }}</div>
                </td>
              </tr>
            </tfoot>
          </table>
          <p v-if="loading" class="mt-3 text-sm text-slate-500">{{ t('expense.loading') }}</p>
          <p v-else-if="!summary.items.length" class="mt-3 text-sm text-slate-500 w-full text-center py-4">{{ t('expense.ledger_empty') }}</p>
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
            <div class="mt-3 grid grid-cols-2 gap-3">
              <div class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-200/70 dark:bg-emerald-950/30 dark:ring-emerald-900/50">
                <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ t('expense.ledger_confirmed') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-800 dark:text-emerald-200">{{ formatVndCurrency(detail.confirmed_total, '') }}</p>
                <p class="text-[11px] text-emerald-700/80 dark:text-emerald-300/80">{{ t('expense.ledger_lines_count', { n: statusCounts.confirmed }) }}</p>
              </div>
              <div class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200/70 dark:bg-amber-950/30 dark:ring-amber-900/50">
                <p class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">{{ t('expense.ledger_pending') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-amber-800 dark:text-amber-200">{{ formatVndCurrency(detail.pending_total, '') }}</p>
                <p class="text-[11px] text-amber-700/80 dark:text-amber-300/80">{{ t('expense.ledger_lines_count', { n: statusCounts.pending }) }}</p>
              </div>
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
            <div class="mt-3 flex flex-wrap gap-1.5" role="tablist">
              <button
                v-for="chip in statusChips"
                :key="chip.value"
                type="button"
                role="tab"
                :aria-selected="statusFilter === chip.value"
                class="rounded-full px-3 py-1 text-xs font-semibold ring-1 transition"
                :class="statusFilter === chip.value ? 'bg-teal-600 text-white ring-teal-600' : 'bg-white text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600'"
                @click="statusFilter = chip.value"
              >
                {{ chip.label }}
              </button>
            </div>
            <div class="mt-2 max-h-[480px] overflow-auto">
              <table class="w-full border-separate border-spacing-0 text-left text-sm">
                <thead class="sticky top-0">
                  <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_date') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_cost_type') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.line_description') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 text-right dark:border-slate-700">{{ t('expense.line_amount') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.ledger_status') }}</th>
                    <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('expense.ledger_doc') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="r in visibleRows" :key="r.id">
                    <td class="whitespace-nowrap px-2 py-1.5 text-xs">{{ formatDate(r.date) }}</td>
                    <td class="px-2 py-1.5 text-xs">{{ t(`expense.cost_type_${r.type}`, r.type) }}<span v-if="!r.has_invoice" class="ml-1 text-amber-700 dark:text-amber-300">*</span></td>
                    <td class="px-2 py-1.5 text-xs">{{ r.description }} <span class="text-slate-400">· {{ r.created_by }}</span></td>
                    <td class="whitespace-nowrap px-2 py-1.5 text-right tabular-nums">{{ formatVndCurrency(r.amount, '') }}</td>
                    <td class="whitespace-nowrap px-2 py-1.5">
                      <span
                        class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1"
                        :class="isConfirmed(r)
                          ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900'
                          : 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900'"
                      >
                        {{ isConfirmed(r) ? t('expense.ledger_confirmed') : t('expense.ledger_pending') }}
                      </span>
                    </td>
                    <td class="px-2 py-1.5 text-xs">
                      <RouterLink v-if="r.expense_document" :to="{ name: 'expenseDetail', params: { id: r.expense_document.id } }" class="text-teal-700 hover:underline dark:text-teal-400">
                        {{ r.expense_document.code }}
                      </RouterLink>
                      <span v-else class="text-slate-400">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <p v-if="!visibleRows.length" class="py-4 text-center text-sm text-slate-500">{{ t('expense.ledger_empty') }}</p>
              <p class="mt-2 text-[11px] text-slate-500">* {{ t('expense.no_invoice_badge') }}</p>
            </div>
          </template>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { downloadVehicleCostLedger, getVehicleCostLedger, getVehicleCostLedgerSummary } from '../../api/expenses'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { formatVndCurrency } from '../../util/money'
import { toLocalDateKey } from '../../util/dates'
import { debounceTrailing } from '../../composables/useDebounce'

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
const statusFilter = ref('')

const isConfirmed = (r) => r.status === 'confirmed'
const grandConfirmed = computed(() => summary.value.items.reduce((sum, v) => sum + (v.confirmed_total || 0), 0))
const statusCounts = computed(() => {
  const rows = detail.value?.rows ?? []
  const confirmed = rows.filter(isConfirmed).length
  return { confirmed, pending: rows.length - confirmed }
})
const statusChips = computed(() => [
  { value: '', label: `${t('expense.tab_all')} (${detail.value?.rows.length ?? 0})` },
  { value: 'confirmed', label: `${t('expense.ledger_confirmed')} (${statusCounts.value.confirmed})` },
  { value: 'pending', label: `${t('expense.ledger_pending')} (${statusCounts.value.pending})` },
])
const visibleRows = computed(() => {
  const rows = detail.value?.rows ?? []
  if (statusFilter.value === 'confirmed') return rows.filter(isConfirmed)
  if (statusFilter.value === 'pending') return rows.filter((r) => !isConfirmed(r))
  return rows
})

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

/** Đổi khoảng ngày → tự tải lại (không cần nút "Xem"); chỉ khi cả 2 ngày hợp lệ. */
const reloadSummary = debounceTrailing(() => void loadSummary(), 250)
watch(
  () => [range.date_from, range.date_to],
  ([from, to]) => {
    if (from && to) reloadSummary()
  },
)

onMounted(loadSummary)
</script>
